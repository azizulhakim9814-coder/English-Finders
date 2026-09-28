<?php
/**
 * A lightweight, same-site transaction history for the billing UI.
 *
 * Reads `billing_events`, the log PaddleWebhookController already writes --
 * no new table, no new Paddle API call needed for this. Deliberately not a
 * replacement for the Paddle Customer Portal's own invoice list (which is
 * the authoritative one); this is a quick same-page view.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Billing;

use EnglishFindersCore\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TransactionLog {
	/**
	 * @return list<array{id: string, amount: string, currency: string, status: string, occurred_at: string}>
	 */
	public function for_customer( string $paddle_customer_id, int $limit = 20 ): array {
		if ( '' === $paddle_customer_id ) {
			return array();
		}

		global $wpdb;

		$table = Schema::table( 'billing_events' );
		$limit = max( 1, min( 100, $limit ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT payload_json, received_at FROM {$table} WHERE paddle_customer_id = %s AND event_type = 'transaction.completed' ORDER BY received_at DESC LIMIT %d",
				$paddle_customer_id,
				$limit
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$parsed = $this->parse( (string) $row['payload_json'], (string) $row['received_at'] );
			if ( null !== $parsed ) {
				$out[] = $parsed;
			}
		}

		return $out;
	}

	/**
	 * Extract the fields the UI needs from one stored transaction payload.
	 *
	 * The exact field path for a transaction's total is NOT confirmed
	 * against a real Paddle transaction.completed payload -- A0's live
	 * verification test used subscription.created, not a transaction event
	 * (see a5-billing-ui.md's "genuinely uncertain" section). Two candidate
	 * paths are tried; if neither matches, the amount is left blank rather
	 * than shown wrong, and the row still displays with its date and
	 * status. Confirm this against a real transaction.completed event and
	 * simplify to one path once it is.
	 *
	 * @return array{id: string, amount: string, currency: string, status: string, occurred_at: string}|null
	 */
	private function parse( string $payload_json, string $received_at ): ?array {
		$payload = json_decode( $payload_json, true );
		$data    = is_array( $payload['data'] ?? null ) ? $payload['data'] : array();

		if ( array() === $data ) {
			return null;
		}

		$totals = is_array( $data['details']['totals'] ?? null ) ? $data['details']['totals'] : array();

		$amount = (string) ( $totals['grand_total'] ?? $totals['total'] ?? '' );

		return array(
			'id'          => (string) ( $data['id'] ?? '' ),
			'amount'      => $amount,
			'currency'    => (string) ( $data['currency_code'] ?? '' ),
			'status'      => (string) ( $data['status'] ?? '' ),
			'occurred_at' => $received_at,
		);
	}
}
