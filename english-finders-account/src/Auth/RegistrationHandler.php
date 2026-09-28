<?php
/**
 * Handles the registration form on the (logged-out) My Account page.
 *
 * Auth mechanics stay native WordPress throughout -- wp_insert_user()
 * handles password hashing, wp_set_auth_cookie() handles the session.
 * Nothing here reimplements either. See a1-account-foundation.md point 1.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Auth;

use EnglishFindersAccount\Profile\AccountType;
use EnglishFindersAccount\Profile\Avatar;
use EnglishFindersAccount\Profile\FullName;
use EnglishFindersAccount\Profile\ProfileRepository;
use EnglishFindersAccount\Security\RegistrationThrottle;
use EnglishFindersAccount\Security\TurnstileVerifier;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RegistrationHandler {
	private const ACTION       = 'efa_register';
	private const HONEYPOT_KEY = 'efa_hp_website';
	public const DRAFT_PARAM   = 'efa_draft';
	private const DRAFT_PREFIX = 'efa_reg_draft_';

	public function register_hooks(): void {
		// nopriv only: a logged-in user has no business hitting this route.
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_turnstile' ) );
	}

	/**
	 * Loads Cloudflare's widget script on the logged-out My Account page,
	 * but only once real Turnstile keys are configured -- see
	 * TurnstileVerifier::is_configured().
	 */
	public function maybe_enqueue_turnstile(): void {
		// 0.14.0: also the standalone [efa_login] / [efa_signup] pages.
		if ( is_user_logged_in() || ! \EnglishFindersAccount\Pages\AuthPages::needs_account_assets() ) {
			return;
		}

		if ( ! ( new TurnstileVerifier() )->is_configured() ) {
			return;
		}

		wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
	}

	public function handle(): void {
		check_admin_referer( self::ACTION );

		/*
		 * Honeypot: a field real users never see (CSS-hidden, aria-hidden,
		 * tabindex="-1" in login-register.php) but a naive bot filling
		 * every field will populate. Reuses the generic invalid_email code
		 * rather than a distinct one, so a bot's response reveals nothing
		 * about why it failed.
		 */
		if ( ! empty( $_POST[ self::HONEYPOT_KEY ] ) ) {
			$this->redirect_with_error( 'invalid_email' );
		}

		$ip       = RegistrationThrottle::resolve_ip();
		$throttle = new RegistrationThrottle();

		if ( $throttle->is_rate_limited( $ip ) ) {
			$this->redirect_with_error( 'too_many_attempts' );
		}

		// Counts this attempt regardless of outcome -- a bot that succeeds
		// every time is exactly the case this is meant to stop.
		$throttle->record_attempt( $ip );

		$turnstile = new TurnstileVerifier();
		if ( $turnstile->is_configured() ) {
			$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
			if ( ! $turnstile->verify( $token, $ip ) ) {
				$this->redirect_with_error( 'captcha_failed' );
			}
		}

		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password     = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		// 0.14.3: "Confirm password" on the sign-up form.
		$confirm      = isset( $_POST['password_confirm'] ) ? (string) wp_unslash( $_POST['password_confirm'] ) : '';
		// 0.13.0: full name is required; learner/teacher; optional photo.
		$full_name    = FullName::clean( isset( $_POST['display_name'] ) ? (string) wp_unslash( $_POST['display_name'] ) : '' );
		$account_type = AccountType::normalize( isset( $_POST['account_type'] ) ? sanitize_key( wp_unslash( $_POST['account_type'] ) ) : '' );
		$photo        = isset( $_FILES['avatar'] ) && is_array( $_FILES['avatar'] ) ? $_FILES['avatar'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated by Avatar.

		if ( ! FullName::is_valid( $full_name ) ) {
			$this->redirect_with_error( 'name_required' );
		}

		$error_code = $this->validate( $email, $password, $confirm );
		if ( null !== $error_code ) {
			$this->redirect_with_error( $error_code );
		}

		// Checked before the account exists, so a bad photo doesn't leave a half-made account.
		if ( Avatar::was_submitted( $photo ) ) {
			$photo_error = Avatar::validate_upload( $photo );
			if ( null !== $photo_error ) {
				$this->redirect_with_error( $photo_error );
			}
		}

		list( $first_name, $last_name ) = FullName::split( $full_name );

		$user_id = wp_insert_user(
			array(
				'user_login'   => Usernames::from_email( $email ),
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $full_name,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			// WordPress's own message is often more specific than our
			// pre-checks (e.g. a race where the email was taken between
			// our email_exists() check and this call); pass it through
			// via a generic code rather than the raw message, since the
			// error code carries through a redirect but arbitrary message
			// text should not be trusted from a query string on the way
			// back into the page.
			$this->redirect_with_error( 'registration_failed' );
		}

		( new ProfileRepository() )->set( (int) $user_id, 'account_type', $account_type );
		update_user_meta( (int) $user_id, GoogleLogin::PASSWORD_SET_META, '1' );
		if ( Avatar::was_submitted( $photo ) ) {
			// Already validated; a processing failure here just leaves the default avatar.
			( new Avatar() )->store_upload( (int) $user_id, $photo );
		}

		/*
		 * Log the new user in immediately -- registering and then being
		 * asked to log in again is unnecessary friction for something
		 * this low-stakes (a free account, not a banking signup).
		 */
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		/*
		 * Phase A5 will look here for an unclaimed entitlement row to
		 * attach (EntitlementRepository::find_unclaimed_by_customer() /
		 * claim(), see billing-spec-a0.md) -- not called in A1, since this
		 * plugin has no dependency on Core yet.
		 */

		// 0.12.0: back to the lesson/quiz the learner came from, if any.
		$return = LoginHandler::posted_return();
		wp_safe_redirect( '' !== $return ? $return : Urls::my_account() );
		exit;
	}

	private function validate( string $email, string $password, string $confirm ): ?string {
		if ( '' === $email || ! is_email( $email ) ) {
			return 'invalid_email';
		}

		// WordPress only enforces a unique *username* by default, not
		// email -- since login-by-email assumes one account per address,
		// this check is what actually makes that assumption true.
		if ( email_exists( $email ) ) {
			return 'email_exists';
		}

		if ( strlen( $password ) < 8 ) {
			return 'weak_password';
		}

		if ( ! hash_equals( $password, $confirm ) ) {
			return 'password_mismatch';
		}

		return null;
	}

	private function redirect_with_error( string $code ): void {
		$args = array(
			'efa_error' => $code,
			'efa_form'  => 'register',
		);
		if ( '' !== LoginHandler::posted_return() ) {
			$args[ Urls::RETURN_PARAM ] = rawurlencode( LoginHandler::posted_return() );
		}

		/*
		 * 0.13.0: keep what the learner typed (never the password or the
		 * photo) so a mistake doesn't mean starting again. Held server-side
		 * for 10 minutes under a random token -- the email address never
		 * goes into the URL.
		 */
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- only reached after check_admin_referer() in handle().
		$draft = array(
			'name'         => FullName::clean( isset( $_POST['display_name'] ) ? (string) wp_unslash( $_POST['display_name'] ) : '' ),
			'email'        => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'account_type' => AccountType::normalize( isset( $_POST['account_type'] ) ? sanitize_key( wp_unslash( $_POST['account_type'] ) ) : '' ),
		);
		// phpcs:enable
		if ( empty( $_POST[ self::HONEYPOT_KEY ] ) && ( '' !== $draft['name'] || '' !== $draft['email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above; bots get nothing stored.
			$token = strtolower( wp_generate_password( 20, false ) );
			set_transient( self::DRAFT_PREFIX . $token, $draft, 10 * MINUTE_IN_SECONDS );
			$args[ self::DRAFT_PARAM ] = $token;
		}

		wp_safe_redirect( add_query_arg( $args, LoginHandler::error_base() ) ); // 0.14.0: back to the page the form was on.
		exit;
	}

	/**
	 * The sign-up values kept after a failed attempt (see redirect_with_error()).
	 *
	 * @return array{name: string, email: string, account_type: string}
	 */
	public static function draft( string $token ): array {
		$empty = array(
			'name'         => '',
			'email'        => '',
			'account_type' => AccountType::LEARNER,
		);
		if ( 1 !== preg_match( '/^[a-z0-9]{20}$/', $token ) ) {
			return $empty;
		}

		$draft = get_transient( self::DRAFT_PREFIX . $token );

		return is_array( $draft ) ? array_merge( $empty, array_intersect_key( $draft, $empty ) ) : $empty;
	}
}
