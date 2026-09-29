<?php
/**
 * Cloudflare Turnstile verification.
 *
 * Dormant until both a site key and secret key are configured -- mirrors
 * how Paddle checkout stays inert until real credentials are added, so
 * this plugin keeps working before the user sets up their Cloudflare
 * account. This project has no settings admin screen yet (every other
 * credential -- Paddle's API key, webhook secret, OpenRouter key -- is
 * set via a wp-config.php constant, same precedence pattern used here),
 * so both EFA_TURNSTILE_SITE_KEY and EFA_TURNSTILE_SECRET_KEY are valid
 * constants; the site key is not secret, so Settings (efa_settings) is
 * also a valid place to put it if a future admin screen writes there.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Security;

use EnglishFindersAccount\Support\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TurnstileVerifier {
	private const VERIFY_ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	private const TIMEOUT_SECONDS = 10;

	public function is_configured(): bool {
		return '' !== $this->site_key() && '' !== $this->secret_key();
	}

	public function site_key(): string {
		if ( defined( 'EFA_TURNSTILE_SITE_KEY' ) && is_string( EFA_TURNSTILE_SITE_KEY ) && '' !== EFA_TURNSTILE_SITE_KEY ) {
			return trim( EFA_TURNSTILE_SITE_KEY );
		}

		return trim( (string) Settings::get( 'turnstile_site_key', '' ) );
	}

	/**
	 * Verifies a submitted token against Cloudflare's siteverify API.
	 *
	 * Fails OPEN on a network/API error: an outage in a third-party
	 * verification service should not itself block real users from
	 * registering, since Turnstile is one layer among several (honeypot
	 * and IP rate limiting run regardless and don't depend on Cloudflare).
	 */
	public function verify( string $token, string $remote_ip ): bool {
		$secret = $this->secret_key();

		if ( '' === $secret || '' === $token ) {
			return false;
		}

		$response = wp_remote_post(
			self::VERIFY_ENDPOINT,
			array(
				'timeout' => self::TIMEOUT_SECONDS,
				'body'    => array(
					'secret'   => $secret,
					'response' => $token,
					'remoteip' => $remote_ip,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return true;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) && ! empty( $body['success'] );
	}

	private function secret_key(): string {
		if ( defined( 'EFA_TURNSTILE_SECRET_KEY' ) && is_string( EFA_TURNSTILE_SECRET_KEY ) && '' !== EFA_TURNSTILE_SECRET_KEY ) {
			return trim( EFA_TURNSTILE_SECRET_KEY );
		}

		return trim( (string) Settings::get( 'turnstile_secret_key', '' ) );
	}
}
