<?php
/**
 * "Continue with Google" (0.13.0).
 *
 * OAuth 2.0 authorization-code flow with PKCE, server side:
 *
 *  1. start    admin-post.php?action=efa_google_start -- stores a random
 *              `state` (10-minute transient + an HttpOnly cookie binding it
 *              to this browser) with the PKCE verifier, the chosen account
 *              type and the return address, then redirects to Google.
 *  2. callback admin-post.php?action=efa_google_callback -- checks state
 *              against the cookie, swaps the code for tokens at Google's
 *              token endpoint (with the PKCE verifier and client secret),
 *              and reads the ID token. It came straight from Google over
 *              TLS in that exchange, so per Google's guidance its claims are
 *              checked (issuer, audience, expiry, verified email) rather
 *              than its signature.
 *  3. account  existing Google link (`efa_google_sub`) -> that user; else an
 *              existing account with the same verified email -> linked;
 *              else a new account (full name, learner/teacher, photo from
 *              Google). Staff accounts (anyone who can edit others' posts)
 *              are refused: they keep password login, so Wordfence's
 *              protections on those accounts can't be bypassed this way.
 *
 * Keys come from wp-config.php (EFA_GOOGLE_CLIENT_ID / EFA_GOOGLE_CLIENT_SECRET),
 * like the Turnstile keys; the button only appears once both are set.
 * Logging in sets the auth cookie directly (no wp_signon()), so Wordfence's
 * CAPTCHA -- which Google's own checks replace here -- isn't involved.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Auth;

use EnglishFindersAccount\Pages\WelcomeNote;
use EnglishFindersAccount\Profile\AccountType;
use EnglishFindersAccount\Profile\Avatar;
use EnglishFindersAccount\Profile\FullName;
use EnglishFindersAccount\Profile\ProfileRepository;
use EnglishFindersAccount\Support\Settings;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GoogleLogin {
	public const START_ACTION    = 'efa_google_start';
	public const CALLBACK_ACTION = 'efa_google_callback';

	/** Links a WordPress user to their Google account ("sub" = Google's stable user id). */
	public const SUB_META = 'efa_google_sub';

	/** '0' for accounts created through Google (no password chosen yet), '1' otherwise. */
	public const PASSWORD_SET_META = 'efa_password_set';

	private const AUTH_ENDPOINT  = 'https://accounts.google.com/o/oauth2/v2/auth';
	private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
	private const ISSUERS        = array( 'https://accounts.google.com', 'accounts.google.com' );
	private const STATE_COOKIE   = 'efa_google_state';
	private const STATE_TTL      = 600;

	/** Set by create_user(): this sign-in made a new account (0.20.0, for the first-steps redirect). */
	private bool $created = false;

	public function register_hooks(): void {
		add_action( 'admin_post_nopriv_' . self::START_ACTION, array( $this, 'start' ) );
		add_action( 'admin_post_' . self::START_ACTION, array( $this, 'already_logged_in' ) );
		add_action( 'admin_post_nopriv_' . self::CALLBACK_ACTION, array( $this, 'callback' ) );
		add_action( 'admin_post_' . self::CALLBACK_ACTION, array( $this, 'already_logged_in' ) );
	}

	public static function is_configured(): bool {
		return '' !== self::client_id() && '' !== self::client_secret();
	}

	public static function client_id(): string {
		if ( defined( 'EFA_GOOGLE_CLIENT_ID' ) && is_string( EFA_GOOGLE_CLIENT_ID ) ) {
			return trim( EFA_GOOGLE_CLIENT_ID );
		}

		return trim( (string) Settings::get( 'google_client_id', '' ) );
	}

	private static function client_secret(): string {
		if ( defined( 'EFA_GOOGLE_CLIENT_SECRET' ) && is_string( EFA_GOOGLE_CLIENT_SECRET ) ) {
			return trim( EFA_GOOGLE_CLIENT_SECRET );
		}

		return trim( (string) Settings::get( 'google_client_secret', '' ) );
	}

	/** The exact redirect URI to register in Google Cloud Console. */
	public static function redirect_uri(): string {
		return admin_url( 'admin-post.php?action=' . self::CALLBACK_ACTION );
	}

	/** Link for the login card's button (sign-up posts its form to the same action instead). */
	public static function start_url( string $return = '', string $origin = '' ): string {
		$url    = admin_url( 'admin-post.php?action=' . self::START_ACTION );
		$return = Urls::return_target( $return );
		$origin = Urls::return_target( $origin );
		if ( '' !== $return ) {
			$url = add_query_arg( Urls::RETURN_PARAM, rawurlencode( $return ), $url );
		}

		// 0.14.0: the page the button was on, so a failed sign-in goes back there.
		return '' !== $origin ? add_query_arg( Urls::ORIGIN_PARAM, rawurlencode( $origin ), $url ) : $url;
	}

	public function already_logged_in(): void {
		wp_safe_redirect( Urls::my_account() );
		exit;
	}

	public function start(): void {
		if ( ! self::is_configured() ) {
			$this->fail( 'google_failed' );
		}

		// phpcs:disable WordPress.Security.NonceVerification -- the OAuth `state` below is this flow's CSRF protection.
		$type   = AccountType::normalize( isset( $_REQUEST['account_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['account_type'] ) ) : '' );
		$return = Urls::return_target( isset( $_REQUEST[ Urls::RETURN_PARAM ] ) ? (string) wp_unslash( $_REQUEST[ Urls::RETURN_PARAM ] ) : '' );
		$origin = Urls::return_target( isset( $_REQUEST[ Urls::ORIGIN_PARAM ] ) ? (string) wp_unslash( $_REQUEST[ Urls::ORIGIN_PARAM ] ) : '' );
		// phpcs:enable

		$state    = wp_generate_password( 32, false );
		$verifier = wp_generate_password( 64, false );

		set_transient(
			self::state_key( $state ),
			array(
				'verifier'     => $verifier,
				'account_type' => $type,
				'return'       => $return,
				'origin'       => $origin,
			),
			self::STATE_TTL
		);
		self::set_state_cookie( $state, time() + self::STATE_TTL );

		$url = add_query_arg(
			array(
				'client_id'             => rawurlencode( self::client_id() ),
				'redirect_uri'          => rawurlencode( self::redirect_uri() ),
				'response_type'         => 'code',
				'scope'                 => rawurlencode( 'openid email profile' ),
				'state'                 => $state,
				'code_challenge'        => self::code_challenge( $verifier ),
				'code_challenge_method' => 'S256',
				'prompt'                => 'select_account',
			),
			self::AUTH_ENDPOINT
		);

		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- external, fixed Google endpoint.
		exit;
	}

	public function callback(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- validated against the state cookie below.
		$state  = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code   = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$cookie = isset( $_COOKIE[ self::STATE_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::STATE_COOKIE ] ) ) : '';
		// phpcs:enable

		$saved = '' !== $state ? get_transient( self::state_key( $state ) ) : false;
		if ( '' !== $state ) {
			delete_transient( self::state_key( $state ) );
		}
		self::set_state_cookie( '', time() - 3600 );

		if ( '' === $state || '' === $code || ! hash_equals( $state, $cookie ) || ! is_array( $saved ) ) {
			$this->fail( 'google_failed' ); // Cancelled at Google, expired, or not started in this browser.
		}

		$claims = $this->exchange( $code, (string) $saved['verifier'] );
		if ( null === $claims ) {
			$this->fail( 'google_failed', (string) $saved['return'], (string) ( $saved['origin'] ?? '' ) );
		}
		if ( empty( $claims['email_verified'] ) || ! is_email( (string) ( $claims['email'] ?? '' ) ) ) {
			$this->fail( 'google_unverified', (string) $saved['return'], (string) ( $saved['origin'] ?? '' ) );
		}

		$user_id = $this->resolve_user( $claims, (string) $saved['account_type'] );
		if ( $user_id <= 0 ) {
			$this->fail( -1 === $user_id ? 'google_staff' : 'google_failed', (string) $saved['return'], (string) ( $saved['origin'] ?? '' ) );
		}

		$user = get_userdata( $user_id );
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
		do_action( 'wp_login', $user->user_login, $user ); // Same signal a password login gives other plugins.

		$return = (string) $saved['return'];
		// 0.20.0: a brand-new account gets the same first steps as the sign-up form (see WelcomeNote).
		$target = $this->created ? WelcomeNote::first_url( $user_id, $return ) : ( '' !== $return ? $return : Urls::my_account() );
		wp_safe_redirect( $target );
		exit;
	}

	/**
	 * @param array<string,mixed> $claims Verified ID-token claims.
	 * @return int User id; 0 on failure; -1 when a staff account matched.
	 */
	public function resolve_user( array $claims, string $account_type ): int {
		$sub   = (string) ( $claims['sub'] ?? '' );
		$email = sanitize_email( (string) $claims['email'] );
		if ( '' === $sub ) {
			return 0;
		}

		$linked = get_users(
			array(
				'meta_key'   => self::SUB_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $sub, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
				'fields'     => 'ID',
			)
		);
		$user_id = ! empty( $linked ) ? (int) $linked[0] : 0;

		if ( 0 === $user_id ) {
			$existing = get_user_by( 'email', $email );
			if ( $existing ) {
				$user_id = (int) $existing->ID;
			}
		}

		if ( $user_id > 0 ) {
			if ( user_can( $user_id, 'edit_others_posts' ) ) {
				return -1;
			}
			update_user_meta( $user_id, self::SUB_META, $sub );
			if ( ! empty( $claims['picture'] ) ) {
				Avatar::remember_remote( $user_id, (string) $claims['picture'] );
			}

			return $user_id;
		}

		return $this->create_user( $claims, $email, $sub, $account_type );
	}

	/** @param array<string,mixed> $claims */
	private function create_user( array $claims, string $email, string $sub, string $account_type ): int {
		$name = FullName::clean( (string) ( $claims['name'] ?? '' ) );
		if ( ! FullName::is_valid( $name ) ) {
			$name = FullName::clean( trim( (string) ( $claims['given_name'] ?? '' ) . ' ' . (string) ( $claims['family_name'] ?? '' ) ) );
		}
		if ( ! FullName::is_valid( $name ) ) {
			$name = current( explode( '@', $email ) );
		}
		list( $first, $last ) = FullName::split( $name );

		$user_id = wp_insert_user(
			array(
				'user_login'   => Usernames::from_email( $email ),
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => $name,
				'first_name'   => $first,
				'last_name'    => $last,
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return 0;
		}

		update_user_meta( $user_id, self::SUB_META, $sub );
		update_user_meta( $user_id, self::PASSWORD_SET_META, '0' );
		( new ProfileRepository() )->set( $user_id, 'account_type', AccountType::normalize( $account_type ) );
		if ( ! empty( $claims['picture'] ) ) {
			Avatar::remember_remote( $user_id, (string) $claims['picture'] );
		}

		$this->created = true;

		return (int) $user_id;
	}

	/** @return array<string,mixed>|null Checked ID-token claims. */
	private function exchange( string $code, string $verifier ): ?array {
		$response = wp_remote_post(
			self::TOKEN_ENDPOINT,
			array(
				'timeout' => 15,
				'body'    => array(
					'code'          => $code,
					'client_id'     => self::client_id(),
					'client_secret' => self::client_secret(),
					'redirect_uri'  => self::redirect_uri(),
					'grant_type'    => 'authorization_code',
					'code_verifier' => $verifier,
				),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) ? self::check_id_token( (string) ( $body['id_token'] ?? '' ), self::client_id(), time() ) : null;
	}

	/**
	 * Decodes an ID token received directly from Google's token endpoint and
	 * checks issuer, audience and expiry.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function check_id_token( string $jwt, string $client_id, int $now ): ?array {
		$parts = explode( '.', $jwt );
		if ( 3 !== count( $parts ) ) {
			return null;
		}

		$claims = json_decode( (string) base64_decode( strtr( $parts[1], '-_', '+/' ), true ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- JWT payload.
		if ( ! is_array( $claims ) ) {
			return null;
		}

		$aud = (array) ( $claims['aud'] ?? array() );
		if ( ! in_array( (string) ( $claims['iss'] ?? '' ), self::ISSUERS, true )
			|| ! in_array( $client_id, $aud, true )
			|| (int) ( $claims['exp'] ?? 0 ) < $now ) {
			return null;
		}

		// Google sends email_verified as a boolean (older tokens: the string "true").
		$claims['email_verified'] = true === ( $claims['email_verified'] ?? false ) || 'true' === ( $claims['email_verified'] ?? '' );

		return $claims;
	}

	public static function code_challenge( string $verifier ): string {
		return rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- PKCE S256.
	}

	private static function state_key( string $state ): string {
		return 'efa_google_' . md5( $state );
	}

	private static function set_state_cookie( string $value, int $expires ): void {
		if ( headers_sent() ) {
			return;
		}

		setcookie(
			self::STATE_COOKIE,
			$value,
			array(
				'expires'  => $expires,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	private function fail( string $code, string $return = '', string $origin = '' ): void {
		$args = array(
			'efa_error' => $code,
			'efa_form'  => 'register',
		);
		if ( '' !== $return ) {
			$args[ Urls::RETURN_PARAM ] = rawurlencode( $return );
		}

		$base = '' !== $origin ? (string) strtok( $origin, '?#' ) : Urls::my_account();
		wp_safe_redirect( add_query_arg( $args, $base ) );
		exit;
	}
}
