<?php
/**
 * Add paddle_customer_id to billing_events, indexed (Phase A5).
 *
 * Uses dbDelta() against the updated Schema definition rather than a
 * hand-written ALTER, unlike CefrColumnsMigration/SenseCefrColumnsMigration.
 * Those two target the large, *adopted* `words`/`word_senses` tables, where
 * restating the full CREATE TABLE from memory for dbDelta risks drift
 * against live dictionary data. `billing_events` is a small table Core
 * fully defines and owns outright -- dbDelta is exactly how this table was
 * created in the first place (BillingTablesMigration), so using it again
 * to add one column is consistent, not a shortcut.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

use EnglishFindersCore\Contracts\MigrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BillingEventsCustomerColumnMigration implements MigrationInterface {
	public function version(): string {
		return '1.6.0';
	}

	public function description(): string {
		return 'Add an indexed paddle_customer_id column to billing_events for per-customer transaction history lookups (Phase A5), and backfill it for existing rows from their stored payload.';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( Schema::definitions()['billing_events'] );

		$this->backfill();
	}

	/**
	 * Reversible: the column holds nothing that is not already re-derivable
	 * from `payload_json`, so dropping it destroys no information. Contrast
	 * BillingTablesMigration::down(), which refuses to drop whole tables
	 * holding genuine, non-derivable billing records.
	 */
	public function down(): void {
		global $wpdb;

		$table = Schema::table( 'billing_events' );

		if ( $this->column_exists( $table, 'paddle_customer_id' ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "ALTER TABLE {$table} DROP COLUMN paddle_customer_id" );
		}
	}

	/**
	 * Populate the new column for rows written before it existed.
	 *
	 * Every Paddle event payload this project handles (subscription.* and
	 * transaction.completed alike) carries `data.customer_id` directly, so
	 * one extraction path covers every event type already logged.
	 */
	private function backfill(): void {
		global $wpdb;

		$table = Schema::table( 'billing_events' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT id, payload_json FROM {$table} WHERE paddle_customer_id = ''", ARRAY_A );

		foreach ( (array) $rows as $row ) {
			$payload = json_decode( (string) $row['payload_json'], true );
			$data    = is_array( $payload['data'] ?? null ) ? $payload['data'] : array();
			$customer_id = (string) ( $data['customer_id'] ?? '' );

			if ( '' === $customer_id ) {
				continue;
			}

			$wpdb->update(
				$table,
				array( 'paddle_customer_id' => $customer_id ),
				array( 'id' => (int) $row['id'] )
			);
		}
	}

	private function column_exists( string $table, string $column ): bool {
		global $wpdb;

		$found = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived.
				"SHOW COLUMNS FROM {$table} LIKE %s",
				$column
			)
		);

		return is_string( $found ) && '' !== $found;
	}
}
