<?php
/**
 * Create the usage_daily table (1.15.0).
 *
 * A brand-new table, so dbDelta is the right tool -- same as the other
 * table migrations.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

use EnglishFindersCore\Contracts\MigrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UsageTableMigration implements MigrationInterface {
	public function version(): string {
		return '1.15.0';
	}

	public function description(): string {
		return 'Create the usage_daily table for per-tool usage counts.';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( Schema::definitions()['usage_daily'] );
	}

	/**
	 * Dropping loses the usage history, but it is aggregate counts only --
	 * nothing a learner owns -- so, unlike the notebook or certificates,
	 * rolling back is allowed.
	 */
	public function down(): void {
		global $wpdb;

		$name = Schema::table( 'usage_daily' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$name}" );
	}
}
