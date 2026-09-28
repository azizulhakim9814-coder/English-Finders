<?php
/**
 * Create the level_results table (Phase A4, English Level Test).
 *
 * A brand-new table, not an ALTER of existing data, so dbDelta is the right
 * tool here -- same reasoning as ActivityTablesMigration.
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

final class LevelResultsTableMigration implements MigrationInterface {
	public function version(): string {
		return '1.8.0';
	}

	public function description(): string {
		return 'Create the level_results table for English Level Test results (Phase A4).';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( Schema::definitions()['level_results'] );
	}

	/**
	 * Reverse the migration.
	 *
	 * Same rule as ActivityTablesMigration::down(): a learner's test history
	 * can't be regenerated once dropped, so this refuses while any row exists.
	 */
	public function down(): void {
		global $wpdb;

		$name = Schema::table( 'level_results' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$name}" );

		if ( $count > 0 ) {
			throw new RuntimeException(
				"Refusing to drop {$name}: it holds {$count} level test result(s) that cannot be regenerated. Roll back manually if this is genuinely intended."
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$name}" );
	}
}
