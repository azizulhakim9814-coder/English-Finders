<?php
/**
 * Create the activity log and derived-state tables (Phase A2).
 *
 * All three tables are brand new, not an ALTER of existing data, so dbDelta
 * is the right tool here -- same reasoning as BillingTablesMigration.
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

final class ActivityTablesMigration implements MigrationInterface {
	private const TABLES = array( 'activity_events', 'user_stats', 'user_badges' );

	public function version(): string {
		return '1.7.0';
	}

	public function description(): string {
		return 'Create the activity_events, user_stats, and user_badges tables for the shared activity log (Phase A2).';
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
	 * Same rule as BillingTablesMigration::down(): once real activity has
	 * been recorded, dropping these tables would destroy history nothing
	 * else can regenerate. Refuses if any of the three tables holds a row.
	 */
	public function down(): void {
		global $wpdb;

		foreach ( self::TABLES as $table ) {
			$name = Schema::table( $table );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$name}" );

			if ( $count > 0 ) {
				throw new RuntimeException(
					"Refusing to drop {$name}: it holds {$count} row(s) of activity data that cannot be regenerated. Roll back manually if this is genuinely intended."
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
