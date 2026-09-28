<?php
/**
 * Requests a Paddle Customer Portal session.
 *
 * The portal is Paddle's own hosted page for changing/canceling a plan,
 * updating payment method, and viewing invoices -- see a5-billing-ui.md
 * for why this phase links out to it rather than building a parallel
 * change/cancel flow and re-deciding proration ourselves. This class does
 * nothing but ask Paddle's API for a session URL.
 *
 * HTTP pattern (wp_remote_post, is_wp_error, response-code check, upstream
 * error text logged but never shown to the visitor) mirrors
 * OpenRouterTtsProvider -- not a new convention invented for Paddle.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Billing;

use EnglishFindersCore\Database\Installer;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PaddlePortalClient {
	/*
	 * Sandbox by default: this project has no live Paddle account yet (see
	 * a5-billing-ui.md). Switching to production is a settings change, not
	 * a code change -- see base_url().
	 */
	private const SANDBOX_BASE = 'https://sandbox-api.paddle.com';
	private const LIVE_BASE    = 'https://api.paddle.com';

	private const TIMEOUT = 15;

	public function is_configured(): bool {
		return '' !== $this->api_key();
	}

	/**
	 * @return string|WP_Error The portal URL, or an error.
	 */
	public function create_session( string $paddle_customer_id ): string|WP_Error {
		if ( '' === $paddle_customer_id ) {
			return new WP_Error( 'efc_paddle_no_customer', __( 'No Paddle customer on record for this account.', 'english-finders-core' ) );
		}

		$key = $this->api_key();
		if ( '' === $key ) {
			return new WP_Error( 'efc_paddle_unconfigured', __( 'Billing is not configured yet.', 'english-finders-core' ) );
		}

		$response = wp_remote_post(
			$this->base_url() . '/customers/' . rawurlencode( $paddle_customer_id ) . '/portal-sessions',
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => '{}',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );

		if ( 200 !== $code && 201 !== $code ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'EFC Paddle portal HTTP ' . $code . ': ' . substr( $body, 0, 300 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return new WP_Error(
				'efc_paddle_portal_http_error',
				__( 'Could not open the billing portal right now.', 'english-finders-core' ),
				array(
					'status' => $code,
					'detail' => substr( wp_strip_all_tags( $body ), 0, 400 ),
				)
			);
		}

		$decoded = json_decode( $body, true );
		$url     = is_array( $decoded ) ? (string) ( $decoded['data']['urls']['general']['overview'] ?? '' ) : '';

		if ( '' === $url ) {
			return new WP_Error( 'efc_paddle_portal_bad_payload', __( 'The billing portal returned an unexpected response.', 'english-finders-core' ) );
		}

		return $url;
	}

	/** @return array<string,mixed> */
	private function settings(): array {
		$settings = get_option( Installer::SETTINGS_OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Resolve the Paddle API key.
	 *
	 * Same precedence as EFC_PADDLE_WEBHOOK_SECRET / EFC_OPENROUTER_KEY: a
	 * wp-config.php constant, if set, wins over the stored setting.
	 */
	private function api_key(): string {
		if ( defined( 'EFC_PADDLE_API_KEY' ) && is_string( EFC_PADDLE_API_KEY ) && '' !== EFC_PADDLE_API_KEY ) {
			return trim( EFC_PADDLE_API_KEY );
		}

		return trim( (string) ( $this->settings()['paddle_api_key'] ?? '' ) );
	}

	/**
	 * Sandbox vs production base URL.
	 *
	 * Driven by the same settings, not a separate flag: a sandbox API key
	 * only ever works against the sandbox base, so which environment this
	 * points at is really a property of *which key is configured*, not an
	 * independent choice. `paddle_environment` lets that be stated
	 * explicitly rather than guessed from the key's shape.
	 */
	private function base_url(): string {
		$environment = (string) ( $this->settings()['paddle_environment'] ?? 'sandbox' );
		return 'production' === $environment ? self::LIVE_BASE : self::SANDBOX_BASE;
	}
}
