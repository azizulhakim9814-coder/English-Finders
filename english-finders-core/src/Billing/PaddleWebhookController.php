<?php
/**
 * Receives Paddle Billing webhooks and turns them into entitlement writes.
 *
 * Registers one REST route. `permission_callback` is intentionally
 * `__return_true` -- Paddle cannot authenticate as a WordPress user, so the
 * signature check inside handle() is the real security boundary for this
 * route, not WordPress's normal auth layer.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Billing;

use EnglishFindersCore\Database\Installer;
use EnglishFindersCore\Database\Schema;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PaddleWebhookController {
	/*
	 * Named REST_NAMESPACE, not NAMESPACE: `namespace` is a PHP keyword, and
	 * with no local `php -l` available to confirm whether it is genuinely
	 * safe as a class constant name in PHP 8.1, avoiding the question
	 * entirely costs nothing.
	 */
	private const REST_NAMESPACE = 'efc/v1';
	private const ROUTE          = '/paddle-webhook';

	/** Events that move an entitlement to a terminal/degraded status by id alone. */
	private const STATUS_ONLY_EVENTS = array(
		'subscription.canceled' => 'canceled',
		'subscription.past_due' => 'past_due',
		'subscription.paused'   => 'paused',
	);

	public function __construct( private readonly EntitlementRepository $entitlements ) {}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	public function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$raw_body  = $request->get_body();
		$signature = (string) $request->get_header( 'paddle-signature' );
		$secret    = self::webhook_secret();

		if ( '' === $secret ) {
			// Unconfigured, not unauthorized: this is a site setup problem, and
			// a 401 here would look identical to "the secret is wrong" in logs.
			return new WP_REST_Response( array( 'error' => 'webhook_not_configured' ), 500 );
		}

		if ( ! PaddleSignatureVerifier::verify( $raw_body, $signature, $secret ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_signature' ), 401 );
		}

		$payload = json_decode( $raw_body, true );

		if ( ! is_array( $payload ) || empty( $payload['event_id'] ) || empty( $payload['event_type'] ) ) {
			return new WP_REST_Response( array( 'error' => 'malformed_payload' ), 400 );
		}

		$event_id   = (string) $payload['event_id'];
		$event_type = (string) $payload['event_type'];
		$data       = is_array( $payload['data'] ?? null ) ? $payload['data'] : array();

		$customer_id = (string) ( $data['customer_id'] ?? '' );

		if ( ! $this->record_event( $event_id, $event_type, $customer_id, $raw_body ) ) {
			// The paddle_event_id unique key rejected this insert: a
			// redelivery of an event already on record. Acknowledge without
			// reprocessing -- entitlement writes are not re-run for it.
			return new WP_REST_Response( array( 'ok' => true, 'duplicate' => true ), 200 );
		}

		$this->dispatch( $event_type, $data );
		$this->mark_processed( $event_id );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Insert the event row.
	 *
	 * Relies on the `paddle_event_id` unique key rather than a preceding
	 * SELECT: a check-then-insert has a race window under concurrent
	 * redelivery that a single unique-key insert does not.
	 *
	 * @return bool True if this is a newly recorded event; false if the
	 *              unique key rejected it as a duplicate.
	 */
	private function record_event( string $event_id, string $event_type, string $paddle_customer_id, string $raw_body ): bool {
		global $wpdb;

		$table = Schema::table( 'billing_events' );

		// Suppress $wpdb's own error output for the expected-duplicate case;
		// the return value, not a printed warning, is how the caller learns
		// this happened.
		$suppress = $wpdb->suppress_errors( true );

		$inserted = $wpdb->insert(
			$table,
			array(
				'paddle_event_id'    => $event_id,
				'event_type'         => $event_type,
				'paddle_customer_id' => $paddle_customer_id,
				'payload_json'       => $raw_body,
				'received_at'        => current_time( 'mysql', true ),
			)
		);

		$wpdb->suppress_errors( $suppress );

		return false !== $inserted && $inserted > 0;
	}

	private function mark_processed( string $event_id ): void {
		global $wpdb;

		$table = Schema::table( 'billing_events' );

		$wpdb->update(
			$table,
			array( 'processed_at' => current_time( 'mysql', true ) ),
			array( 'paddle_event_id' => $event_id )
		);
	}

	/** @param array<string,mixed> $data */
	private function dispatch( string $event_type, array $data ): void {
		if ( in_array( $event_type, array( 'subscription.created', 'subscription.updated' ), true ) ) {
			$this->entitlements->upsert_from_subscription( self::map_subscription( $data ) );
			return;
		}

		if ( isset( self::STATUS_ONLY_EVENTS[ $event_type ] ) ) {
			$this->entitlements->mark_status( (string) ( $data['id'] ?? '' ), self::STATUS_ONLY_EVENTS[ $event_type ] );
			return;
		}

		/*
		 * transaction.completed and any event type this controller does not
		 * yet act on: already on record via record_event() above (the
		 * invoices/billing-history view in Phase A5 reads that log directly),
		 * and acknowledged with 200 rather than left for Paddle to retry
		 * indefinitely against an event we have no handler for.
		 */
	}

	/**
	 * Shape a Paddle subscription payload into what EntitlementRepository expects.
	 *
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	private static function map_subscription( array $data ): array {
		$custom        = is_array( $data['custom_data'] ?? null ) ? $data['custom_data'] : array();
		$items         = is_array( $data['items'] ?? null ) ? $data['items'] : array();
		$billing_period = is_array( $data['current_billing_period'] ?? null ) ? $data['current_billing_period'] : array();
		$scheduled     = is_array( $data['scheduled_change'] ?? null ) ? $data['scheduled_change'] : array();

		return array(
			'user_id'                => isset( $custom['user_id'] ) ? (int) $custom['user_id'] : null,
			'paddle_customer_id'     => (string) ( $data['customer_id'] ?? '' ),
			'paddle_subscription_id' => (string) ( $data['id'] ?? '' ),
			'plan_code'              => self::plan_code_from_items( $items ),
			'status'                 => (string) ( $data['status'] ?? 'active' ),
			'current_period_end'     => $billing_period['ends_at'] ?? null,
			'cancel_at_period_end'   => 'cancel' === ( $scheduled['action'] ?? '' ),
			'seats'                  => isset( $custom['seats'] ) ? (int) $custom['seats'] : null,
		);
	}

	/**
	 * Map a Paddle price ID to our internal plan code.
	 *
	 * The mapping is a stored setting, not a constant: Paddle price IDs are
	 * environment-specific (sandbox vs production catalogues), so this must
	 * be configurable without a code release. Unmapped price IDs default to
	 * 'free' rather than fatally erroring -- a misconfigured mapping should
	 * fail safe (no paid access granted) rather than break checkout entirely.
	 *
	 * @param array<int,mixed> $items
	 */
	private static function plan_code_from_items( array $items ): string {
		$first    = is_array( $items[0] ?? null ) ? $items[0] : array();
		$price    = is_array( $first['price'] ?? null ) ? $first['price'] : array();
		$price_id = (string) ( $price['id'] ?? '' );

		$map = self::price_plan_map();

		return isset( $map[ $price_id ] ) ? (string) $map[ $price_id ] : 'free';
	}

	/** @return array<string,string> */
	private static function price_plan_map(): array {
		$settings = get_option( Installer::SETTINGS_OPTION, array() );
		$map      = is_array( $settings ) && is_array( $settings['paddle_price_plan_map'] ?? null )
			? $settings['paddle_price_plan_map']
			: array();

		return $map;
	}

	/**
	 * Read the webhook signing secret.
	 *
	 * A wp-config.php constant takes precedence over the stored option,
	 * matching the same pattern already used for the OpenRouter API key --
	 * defining it in wp-config.php keeps it out of the database entirely,
	 * which matters more here than for a TTS key.
	 */
	private static function webhook_secret(): string {
		if ( defined( 'EFC_PADDLE_WEBHOOK_SECRET' ) && is_string( EFC_PADDLE_WEBHOOK_SECRET ) && '' !== EFC_PADDLE_WEBHOOK_SECRET ) {
			return EFC_PADDLE_WEBHOOK_SECRET;
		}

		$settings = get_option( Installer::SETTINGS_OPTION, array() );

		return is_array( $settings ) ? (string) ( $settings['paddle_webhook_secret'] ?? '' ) : '';
	}
}
