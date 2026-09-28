<?php
/**
 * What the login / sign-up forms need to render (0.14.0).
 *
 * Shared by the logged-out /my-account/ view and the standalone [efa_login]
 * / [efa_signup] pages, so both read errors, kept sign-up values and the
 * return address the same way, and show the same messages.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Pages;

use EnglishFindersAccount\Auth\RegistrationHandler;
use EnglishFindersAccount\Profile\AccountType;
use EnglishFindersAccount\Security\RegistrationGate;
use EnglishFindersAccount\Security\TurnstileVerifier;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuthView {
	/**
	 * @return array{error: string, form: string, turnstile_site_key: string, return: string, draft: array{name: string, email: string, account_type: string}}
	 */
	public static function from_request(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display values; each is validated below.
		$error  = isset( $_GET['efa_error'] ) ? sanitize_key( wp_unslash( $_GET['efa_error'] ) ) : '';
		$form   = isset( $_GET['efa_form'] ) ? sanitize_key( wp_unslash( $_GET['efa_form'] ) ) : '';
		$return = Urls::return_target( isset( $_GET[ Urls::RETURN_PARAM ] ) ? (string) wp_unslash( $_GET[ Urls::RETURN_PARAM ] ) : '' );
		$token  = isset( $_GET[ RegistrationHandler::DRAFT_PARAM ] ) ? sanitize_key( wp_unslash( $_GET[ RegistrationHandler::DRAFT_PARAM ] ) ) : '';
		$draft  = RegistrationHandler::draft( $token );
		// 0.16.0: arriving from Tutor's "become an instructor" page pre-selects Teacher (a kept draft wins).
		if ( '' === $token && isset( $_GET[ RegistrationGate::TYPE_PARAM ] ) ) {
			$draft['account_type'] = AccountType::normalize( sanitize_key( wp_unslash( $_GET[ RegistrationGate::TYPE_PARAM ] ) ) );
		}
		// phpcs:enable

		return array(
			'error'              => $error,
			'form'               => $form,
			'turnstile_site_key' => ( new TurnstileVerifier() )->site_key(),
			'return'             => $return,
			'draft'              => $draft,
		);
	}

	/** @return array<string,string> error code => message */
	public static function messages(): array {
		return array(
			'invalid_email'       => __( 'Enter a valid email address.', 'english-finders-account' ),
			'email_exists'        => __( 'An account with that email already exists. Try logging in instead.', 'english-finders-account' ),
			'weak_password'       => __( 'Password must be at least 8 characters.', 'english-finders-account' ),
			'password_mismatch'   => __( "The two passwords don't match. Please type them again.", 'english-finders-account' ),
			'name_required'       => __( 'Please enter your full name (at least 2 letters).', 'english-finders-account' ),
			'avatar_invalid'      => __( 'The profile photo must be a JPG or PNG image.', 'english-finders-account' ),
			'avatar_too_big'      => __( 'The profile photo must be 2 MB or smaller.', 'english-finders-account' ),
			'avatar_too_small'    => __( 'The profile photo is too small. Please use one at least 64 pixels wide and tall.', 'english-finders-account' ),
			'registration_failed' => __( 'Something went wrong creating your account. Please try again.', 'english-finders-account' ),
			'invalid_login'       => __( 'That email/username or password was not right.', 'english-finders-account' ),
			'too_many_attempts'   => __( 'Too many attempts from this connection. Please try again in a few minutes.', 'english-finders-account' ),
			'captcha_failed'      => __( "We couldn't verify you're human. Please try again.", 'english-finders-account' ),
			'google_failed'       => __( "Google sign-in didn't finish. Please try again, or use your email instead.", 'english-finders-account' ),
			'google_unverified'   => __( 'Your Google account email is not verified, so it cannot be used to sign in here.', 'english-finders-account' ),
			'google_staff'        => __( 'This is a staff account. Please log in with your password.', 'english-finders-account' ),
		);
	}

	public static function message( string $code ): string {
		return self::messages()[ $code ] ?? '';
	}

	/** Google's "G" mark, as Google's sign-in branding guidelines require on this button. */
	public static function google_mark(): string {
		return '<svg class="efa-google-btn__mark" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>';
	}

	/**
	 * Real numbers for the brand panel, counted from the site (cached 12 hours):
	 * published courses and lessons, and dictionary words when Word Games Pro's
	 * table exists. No invented audience figures.
	 *
	 * @return array{courses: int, lessons: int, words: int}
	 */
	public static function site_facts(): array {
		$cached = get_transient( 'efa_auth_site_facts' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$count = static function ( string $type ): int {
			$counts = wp_count_posts( $type );

			return is_object( $counts ) && isset( $counts->publish ) ? (int) $counts->publish : 0;
		};
		$table = $wpdb->prefix . 'wuc_words';
		$words = $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefix-derived table name.

		$facts = array(
			'courses' => $count( 'courses' ),
			'lessons' => $count( 'lesson' ),
			'words'   => $words,
		);
		set_transient( 'efa_auth_site_facts', $facts, 12 * HOUR_IN_SECONDS );

		return $facts;
	}

	/** "64,000+" for 64,041; "400" for exactly 400; "450+" for 468. */
	public static function round_down( int $n ): string {
		if ( $n >= 10000 ) {
			$r = (int) ( floor( $n / 1000 ) * 1000 );
		} elseif ( $n >= 100 ) {
			$r = (int) ( floor( $n / 50 ) * 50 );
		} else {
			$r = $n;
		}

		return number_format_i18n( $r ) . ( $r < $n ? '+' : '' );
	}
}
