<?php
/**
 * Add an `occurred_at` index to activity_events (1.11.0).
 *
 * The weekly leaderboard sums XP across *all* learners within a date range.
 * The existing (user_id, occurred_at) index serves per-user queries but not
 * a cross-user range, and this table gains a row per correct practice
 * answer, so without it the leaderboard would become a full table scan.
 *
 * dbDelta() on the updated definition adds the missing key and leaves the
 * data alone -- the same tool the table was created with.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

use EnglishFindersCore\Contracts\MigrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivityOccurredAtIndexMigration implements MigrationInterface {
	public function version(): string {
		return '1.11.0';
	}

	public function description(): string {
		return 'Add an occurred_at index to activity_events for the weekly leaderboard.';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( Schema::definitions()['activity_events'] );
	}

	/** Dropping an index loses no data, so this one is safely reversible. */
	public function down(): void {
		global $wpdb;

		$name = Schema::table( 'activity_events' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange -- table name is prefix-derived.
		$wpdb->query( "ALTER TABLE {$name} DROP INDEX occurred_at" );
	}
}
