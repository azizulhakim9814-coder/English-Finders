<?php
/**
 * One way in: every account is created through /sign-up/ (0.16.0).
 *
 * Found live 2026-09-26: 82 of 84 accounts made in a week came through side
 * doors that skip the sign-up page's protections (Turnstile, rate limit,
 * honeypot, full name) -- throwaway email domains, generated usernames, and
 * Tutor profiles filled with outside links. The doors, all closed here:
 *
 *  - Tutor's student registration form (page `student_register_page`, and
 *    its POST handler `tutor_action=tutor_register_student`);
 *  - Tutor's "become an instructor" sign-up for new users
 *    (`instructor_register_page`, `tutor_action=tutor_register_instructor`).
 *    Signed-in members can still apply from their Tutor dashboard
 *    (`tutor_apply_instructor`, untouched) and an admin approves them;
 *  - WordPress's own registration (wp-login.php?action=register, served at
 *    the hidden login address), whose "Register" links also leaked that
 *    address -- register_url now points at /sign-up/.
 *
 * The POST handlers are refused as well as the pages, because bots post
 * straight to them. Tutor runs its handlers on template_redirect (priority
 * 10); these run first. Admins adding users in wp-admin are unaffected.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Security;

use EnglishFindersAccount\Profile\AccountType;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RegistrationGate {
	/** Query arg that pre-selects "Teacher" on the sign-up form. */
	public const TYPE_PARAM = 'efa_type';

	private const TUTOR_STUDENT    = 'tutor_register_student';
	private const TUTOR_INSTRUCTOR = 'tutor_register_instructor';

	public function register_hooks(): void {
		add_action( 'template_redirect', array( $this, 'refuse_tutor_registration' ), 1 );
		add_action( 'template_redirect', array( $this, 'redirect_tutor_pages' ), 2 );
		add_action( 'login_form_register', array( $this, 'redirect_core_registration' ) );
		add_filter( 'registration_errors', array( $this, 'refuse_core_registration' ), 99 );
		add_filter( 'register_url', array( $this, 'register_url' ) );
	}

	/** A POST to Tutor's student / new-instructor registration is never processed. */
	public function refuse_tutor_registration(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- we refuse the request; nothing is read or stored.
		$action = isset( $_POST['tutor_action'] ) ? sanitize_key( wp_unslash( $_POST['tutor_action'] ) ) : '';
		if ( self::TUTOR_STUDENT !== $action && self::TUTOR_INSTRUCTOR !== $action ) {
			return;
		}

		wp_safe_redirect( self::signup_url( self::TUTOR_INSTRUCTOR === $action ), 303 );
		exit;
	}

	/** Tutor's registration pages lead to /sign-up/ ("Teacher" pre-selected for the instructor one). */
	public function redirect_tutor_pages(): void {
		$pages = self::tutor_pages();
		foreach ( $pages as $key => $page_id ) {
			if ( $page_id > 0 && is_page( $page_id ) ) {
				wp_safe_redirect( self::signup_url( 'instructor_register_page' === $key ), 302 );
				exit;
			}
		}
	}

	/** wp-login.php?action=register (GET or POST) goes to /sign-up/. */
	public function redirect_core_registration(): void {
		wp_safe_redirect( self::signup_url( false ), 302 );
		exit;
	}

	/**
	 * Belt and braces: anything that still reaches register_new_user() is
	 * refused.
	 *
	 * @param \WP_Error $errors
	 * @return \WP_Error
	 */
	public function refuse_core_registration( $errors ) {
		if ( $errors instanceof \WP_Error ) {
			$errors->add(
				'efa_registration_closed',
				sprintf(
					/* translators: %s: sign-up page URL */
					__( 'Please create your account at %s.', 'english-finders-account' ),
					esc_url( self::signup_url( false ) )
				)
			);
		}

		return $errors;
	}

	/** Every "Register" link points at /sign-up/ (and no longer at the hidden login address). */
	public function register_url( $url ): string {
		return self::signup_url( false );
	}

	public static function signup_url( bool $teacher ): string {
		$url = Urls::signup_page();

		return $teacher ? add_query_arg( self::TYPE_PARAM, AccountType::TEACHER, $url ) : $url;
	}

	/** @return array{student_register_page:int,instructor_register_page:int} */
	private static function tutor_pages(): array {
		$opt = get_option( 'tutor_option' );
		$opt = is_array( $opt ) ? $opt : array();

		return array(
			'student_register_page'    => (int) ( $opt['student_register_page'] ?? 0 ),
			'instructor_register_page' => (int) ( $opt['instructor_register_page'] ?? 0 ),
		);
	}
}
