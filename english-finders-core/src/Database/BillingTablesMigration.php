<?php
/**
 * Create the billing entitlement tables (Phase A0).
 *
 * `entitlements` and `billing_events` are brand-new tables, not an ALTER of
 * existing data, so dbDelta is the right tool here (unlike CefrColumnsMigration
 * and SenseCefrColumnsMigration, which target the large, already-populated
 * `words`/`word_senses` tables and use a targeted ALTER for that reason).
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

use RuntimeException;
use EnglishFindersCore\Contracts\MigrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BillingTablesMigration implements MigrationInterface {
	private const TABLES = array( 'entitlements', 'billing_events' );

	public function version(): string {
		return '1.5.0';
	}

	public function description(): string {
		return 'Create the entitlements and billing_events tables for Paddle billing (Phase A0).';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$definitions = Schema::definitions();

		foreach ( self::TABLES as $table ) {
			dbDelta( $definitions[ $table ] );
		}
	}

	/**
	 * Reverse the migration.
	 *
	 * Unlike the CEFR column migrations, these tables can hold real billing
	 * records once live -- dropping them silently would destroy data nothing
	 * else can regenerate. Rollback is only allowed while both tables are
	 * still empty (e.g. immediately after a failed deploy, before go-live);
	 * once either has a row, this throws instead of dropping anything, per
	 * MigrationInterface's contract that a migration which cannot genuinely
	 * be reversed should say so rather than appear to succeed.
	 */
	public function down(): void {
		global $wpdb;

		foreach ( self::TABLES as $table ) {
			$name = Schema::table( $table );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$name}" );

			if ( $count > 0 ) {
				throw new RuntimeException(
					"Refusing to drop {$name}: it holds {$count} row(s) of billing data that cannot be regenerated. Roll back manually if this is genuinely intended."
				);
			}
		}

		foreach ( self::TABLES as $table ) {
			$name = Schema::table( $table );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "DROP TABLE IF EXISTS {$name}" );
		}
	}
}
