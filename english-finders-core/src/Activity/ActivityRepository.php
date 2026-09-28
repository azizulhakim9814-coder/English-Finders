<?php
/**
 * Read and write access to the activity log and its derived tables.
 *
 * ActivityRecorder is the only writer this repository should have --
 * everything else that needs a user's XP/streak/badges should call
 * ActivityRecorder::stats_for_user() / badges_for_user(), not this class
 * directly, the same "one recorder owns writes" rule EntitlementRepository
 * documents for the webhook controller.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Activity;

use EnglishFindersCore\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivityRepository {
	/** @param array<string,mixed> $metadata */
	public function insert_event( int $user_id, string $source, string $event_type, int $xp_earned, array $metadata, string $occurred_at ): void {
		global $wpdb;

		$wpdb->insert(
			Schema::table( 'activity_events' ),
			array(
				'user_id'       => $user_id,
				'source'        => $source,
				'event_type'    => $event_type,
				'xp_earned'     => $xp_earned,
				'metadata_json' => wp_json_encode( $metadata ),
				'occurred_at'   => $occurred_at,
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/** @return array<string,mixed>|null Raw row, or null if the user has no stats yet. */
	public function get_stats( int $user_id ): ?array {
		global $wpdb;

		$table = Schema::table( 'user_stats' );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT user_id, total_xp, current_streak_days, longest_streak_days, last_active_date, streak_freezes_available FROM {$table} WHERE user_id = %d",
				$user_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Create or update a user's derived stats row in one atomic statement.
	 *
	 * ON DUPLICATE KEY UPDATE rather than a check-then-insert-or-update: two
	 * concurrent record_event() calls for the same user (unlikely, but
	 * possible under real traffic) should never race into a duplicate-key
	 * insert failure the way a separate SELECT-then-INSERT would risk.
	 */
	public function upsert_stats(
		int $user_id,
		int $total_xp,
		int $current_streak_days,
		int $longest_streak_days,
		string $last_active_date,
		int $streak_freezes_available
	): void {
		global $wpdb;

		$table = Schema::table( 'user_stats' );
		$now   = current_time( 'mysql', true );

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"INSERT INTO {$table} (user_id, total_xp, current_streak_days, longest_streak_days, last_active_date, streak_freezes_available, updated_at)
				 VALUES (%d, %d, %d, %d, %s, %d, %s)
				 ON DUPLICATE KEY UPDATE total_xp = VALUES(total_xp), current_streak_days = VALUES(current_streak_days), longest_streak_days = VALUES(longest_streak_days), last_active_date = VALUES(last_active_date), streak_freezes_available = VALUES(streak_freezes_available), updated_at = VALUES(updated_at)",
				$user_id,
				$total_xp,
				$current_streak_days,
				$longest_streak_days,
				$last_active_date,
				$streak_freezes_available,
				$now
			)
		);
	}

	/** Awards a badge, idempotently. Returns whether it was newly awarded (false if already held). */
	public function award_badge( int $user_id, string $badge_code, string $earned_at ): bool {
		global $wpdb;

		$table = Schema::table( 'user_badges' );

		$affected = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"INSERT IGNORE INTO {$table} (user_id, badge_code, earned_at) VALUES (%d, %s, %s)",
				$user_id,
				$badge_code,
				$earned_at
			)
		);

		return false !== $affected && $affected > 0;
	}

	/** @return list<string> Badge codes the user has earned, oldest first. */
	/**
	 * XP and event count per calendar day, for one user, from $from_gmt on.
	 *
	 * `occurred_at` is stored in UTC; $offset_seconds shifts it into the
	 * site's timezone before taking the date, so a "day" here is the same
	 * calendar day StreakCalculator uses (current_time('Y-m-d')). One
	 * grouped query -- at most one row per day, however many events.
	 *
	 * @return array<string,array{xp:int,events:int}> keyed by Y-m-d, days with no activity absent.
	 */
	public function daily_xp( int $user_id, string $from_gmt, int $offset_seconds ): array {
		global $wpdb;

		$table = Schema::table( 'activity_events' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT DATE(DATE_ADD(occurred_at, INTERVAL %d SECOND)) AS day, SUM(xp_earned) AS xp, COUNT(*) AS events FROM {$table} WHERE user_id = %d AND occurred_at >= %s GROUP BY day",
				$offset_seconds,
				$user_id,
				$from_gmt
			),
			ARRAY_A
		);

		$out = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$out[ (string) $row['day'] ] = array(
				'xp'     => (int) $row['xp'],
				'events' => (int) $row['events'],
			);
		}

		return $out;
	}

	/**
	 * Weekly leaderboard rows (1.11.0): XP earned in [$from_gmt, $to_gmt) per
	 * user, only for users whose usermeta $optin_meta_key is '1', highest
	 * first, users with no XP in the range left out. Ties are ordered by
	 * user id only so the order is stable; rank itself is computed by the
	 * caller.
	 *
	 * Core doesn't own the opt-in setting (English Finders Account does), so
	 * the caller names the meta key; only rows explicitly set to '1' count,
	 * which keeps the board opt-in by construction.
	 *
	 * @return list<array{user_id:int,xp:int}>
	 */
	public function xp_ranking( string $from_gmt, string $to_gmt, string $optin_meta_key, int $limit ): array {
		global $wpdb;

		$table = Schema::table( 'activity_events' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are prefix-derived, not user input.
				"SELECT e.user_id AS user_id, SUM(e.xp_earned) AS xp FROM {$table} e INNER JOIN {$wpdb->usermeta} m ON m.user_id = e.user_id AND m.meta_key = %s AND m.meta_value = '1' WHERE e.occurred_at >= %s AND e.occurred_at < %s GROUP BY e.user_id HAVING xp > 0 ORDER BY xp DESC, e.user_id ASC LIMIT %d",
				$optin_meta_key,
				$from_gmt,
				$to_gmt,
				max( 1, min( 100, $limit ) )
			),
			ARRAY_A
		);

		return array_map(
			static fn ( array $row ): array => array(
				'user_id' => (int) $row['user_id'],
				'xp'      => (int) $row['xp'],
			),
			is_array( $rows ) ? $rows : array()
		);
	}

	/** XP one user earned in [$from_gmt, $to_gmt). */
	/**
	 * XP one user earned from one source in a time window (1.14.0), for the
	 * per-source daily cap in ActivityRecorder::record_event().
	 */
	public function xp_for_source_between( int $user_id, string $source, string $from_gmt, string $to_gmt ): int {
		global $wpdb;

		$table = Schema::table( 'activity_events' );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT COALESCE(SUM(xp_earned), 0) FROM {$table} WHERE user_id = %d AND source = %s AND occurred_at >= %s AND occurred_at < %s",
				$user_id,
				$source,
				$from_gmt,
				$to_gmt
			)
		);
	}

	public function xp_between( int $user_id, string $from_gmt, string $to_gmt ): int {
		global $wpdb;

		$table = Schema::table( 'activity_events' );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT COALESCE(SUM(xp_earned), 0) FROM {$table} WHERE user_id = %d AND occurred_at >= %s AND occurred_at < %s",
				$user_id,
				$from_gmt,
				$to_gmt
			)
		);
	}

	/**
	 * How many opted-in users earned more than $xp in the range ($xp = 0:
	 * how many are on the board at all). Rank = this + 1.
	 */
	public function count_ranked_above( string $from_gmt, string $to_gmt, string $optin_meta_key, int $xp ): int {
		global $wpdb;

		$table = Schema::table( 'activity_events' );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are prefix-derived, not user input.
				"SELECT COUNT(*) FROM (SELECT e.user_id, SUM(e.xp_earned) AS xp FROM {$table} e INNER JOIN {$wpdb->usermeta} m ON m.user_id = e.user_id AND m.meta_key = %s AND m.meta_value = '1' WHERE e.occurred_at >= %s AND e.occurred_at < %s GROUP BY e.user_id HAVING xp > %d) ranked",
				$optin_meta_key,
				$from_gmt,
				$to_gmt,
				max( 0, $xp )
			)
		);
	}

	public function badges_for_user( int $user_id ): array {
		global $wpdb;

		$table = Schema::table( 'user_badges' );

		$codes = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT badge_code FROM {$table} WHERE user_id = %d ORDER BY earned_at ASC",
				$user_id
			)
		);

		return array_map( 'strval', is_array( $codes ) ? $codes : array() );
	}
}
