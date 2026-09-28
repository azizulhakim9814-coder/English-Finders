<?php
/**
 * Create the mistakes table (mistake notebook).
 *
 * A brand-new table, not an ALTER of existing data, so dbDelta is the right
 * tool here -- same reasoning as LevelResultsTableMigration.
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

final class MistakesTableMigration implements MigrationInterface {
	public function version(): string {
		return '1.9.0';
	}

	public function description(): string {
		return 'Create the mistakes table for the mistake notebook.';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( Schema::definitions()['mistakes'] );
	}

	/**
	 * Reverse the migration. Refuses while any row exists -- a learner's
	 * notebook can't be regenerated once dropped.
	 */
	public function down(): void {
		global $wpdb;

		$name = Schema::table( 'mistakes' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$name}" );

		if ( $count > 0 ) {
			throw new RuntimeException(
				"Refusing to drop {$name}: it holds {$count} notebook entr(ies) that cannot be regenerated. Roll back manually if this is genuinely intended."
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$name}" );
	}
}
