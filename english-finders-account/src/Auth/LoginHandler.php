<?php
/**
 * Handles the login form on the (logged-out) My Account page.
 *
 * Logout deliberately has no equivalent handler here -- wp_logout_url()
 * already generates a nonce-protected logout link to wp-login.php, which
 * is the native, already-secure path. Building a parallel one would be
 * pure duplication.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Auth;

use EnglishFindersAccount\Security\RegistrationThrottle;
use EnglishFindersAccount\Security\TurnstileVerifier;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LoginHandler {
	private const ACTION = 'efa_login';

	public function register_hooks(): void {
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	public function handle(): void {
		check_admin_referer( self::ACTION );

		$turnstile = new TurnstileVerifier();
		if ( $turnstile->is_configured() ) {
			/*
			 * Wordfence Login Security enforces its own CAPTCHA requirement
			 * inside wp_signon()'s authenticate filter chain -- confirmed
			 * live (2026-09-22/23) via Novamira: real users, correct
			 * password, rejected with a WP_Error (wfls_captcha_verify)
			 * because this form never rendered Wordfence's own CAPTCHA
			 * widget or sent the token it checks for. That's what "That
			 * email/username or password was not right" was actually
			 * masking. Turnstile above is now the equivalent protection on
			 * this route, so Wordfence's own layer is suppressed here --
			 * scoped to this request only via a filter added inline, never
			 * touching wp-login.php, XML-RPC, or any other login path this
			 * plugin doesn't own.
			 */
			add_filter( 'wordfence_ls_require_captcha', '__return_false' );

			$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
			if ( ! $turnstile->verify( $token, RegistrationThrottle::resolve_ip() ) ) {
				$this->redirect_with_error( 'captcha_failed' );
			}
		}

		$login    = isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '';
		$password = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
		$remember = ! empty( $_POST['remember'] );

		$user = wp_signon(
			array(
				'user_login'    => $login,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			/*
			 * Deliberately the same error code regardless of whether the
			 * account exists -- distinguishing "wrong password" from "no
			 * such account" in the response tells an attacker which
			 * emails are registered.
			 */
			$this->redirect_with_error( 'invalid_login' );
		}

		// 0.12.0: back to the lesson/quiz the learner came from, if any.
		$return = self::posted_return();
		wp_safe_redirect( '' !== $return ? $return : Urls::my_account() );
		exit;
	}

	/** The validated `efa_return` from the submitted form, or ''. Shared with RegistrationHandler. */
	public static function posted_return(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read after check_admin_referer() in handle(); validated by Urls::return_target().
		return Urls::return_target( isset( $_POST[ Urls::RETURN_PARAM ] ) ? (string) wp_unslash( $_POST[ Urls::RETURN_PARAM ] ) : '' );
	}

	/**
	 * 0.14.0: the page the form was on (the standalone /login/ or /sign-up/
	 * page, or My Account), validated like the return address. Mistakes go
	 * back there. Shared with RegistrationHandler.
	 */
	public static function error_base(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read after check_admin_referer(); validated by Urls::return_target().
		$origin = Urls::return_target( isset( $_POST[ Urls::ORIGIN_PARAM ] ) ? (string) wp_unslash( $_POST[ Urls::ORIGIN_PARAM ] ) : '' );

		return '' !== $origin ? strtok( $origin, '?#' ) : Urls::my_account();
	}

	private function redirect_with_error( string $code ): void {
		$args = array(
			'efa_error' => $code,
			'efa_form'  => 'login',
		);
		if ( '' !== self::posted_return() ) {
			$args[ Urls::RETURN_PARAM ] = rawurlencode( self::posted_return() );
		}

		wp_safe_redirect( add_query_arg( $args, self::error_base() ) );
		exit;
	}
}
