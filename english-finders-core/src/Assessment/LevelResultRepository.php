<?php
/**
 * Storage for English Level Test results (Phase A4).
 *
 * Registered as Core service `level_results`. English Finders Study (which
 * runs the test) writes through record() and claim(); English Finders
 * Account (which shows My Level) reads through latest_for_user() and
 * history_for_user(). Neither plugin touches the table directly -- the same
 * one-owner rule as `entitlements` and `activity`.
 *
 * Anonymous results: the test is playable logged out (my-account-design.md
 * requires that), so a result can be stored with no user and a random claim
 * token instead. The token lives only in the visitor's own HttpOnly cookie;
 * claim() attaches the row to an account when that visitor later signs up
 * or logs in. No IP, user agent, or other identifying detail is stored with
 * an anonymous row -- it's a level and a timestamp, nothing more.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Assessment;

use EnglishFindersCore\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LevelResultRepository {
	/** An unclaimed anonymous result stops being claimable after this many days. */
	public const CLAIM_WINDOW_DAYS = 30;

	/** Upper bound on history_for_user(), whatever a caller asks for. */
	private const MAX_HISTORY = 50;

	/**
	 * Store one finished attempt.
	 *
	 * Exactly one of $user_id / $claim_token is expected: a logged-in taker
	 * gets their user id, an anonymous one gets a claim token. Invalid level
	 * codes are rejected rather than coerced, since a result that silently
	 * became a different level would be worse than no result.
	 *
	 * @param array<string,string> $skill_levels skill => level code.
	 * @param array<string,mixed>  $details      Per-skill/per-level answer counts, for later review.
	 * @return int Inserted row id, or 0 on failure.
	 */
	public function record(
		?int $user_id,
		?string $claim_token,
		array $skill_levels,
		int $questions_answered,
		int $correct_answers,
		array $details,
		string $test_version
	): int {
		global $wpdb;

		$clean = array();
		foreach ( LevelScale::SKILLS as $skill ) {
			$level = (string) ( $skill_levels[ $skill ] ?? '' );
			if ( ! LevelScale::is_valid( $level ) ) {
				return 0;
			}
			$clean[ $skill ] = $level;
		}

		if ( null !== $user_id && $user_id <= 0 ) {
			$user_id = null;
		}

		if ( null === $user_id && ( null === $claim_token || ! self::is_valid_token( $claim_token ) ) ) {
			return 0;
		}

		$data    = array(
			'overall_level'      => LevelScale::overall( $clean ),
			'skill_levels_json'  => wp_json_encode( $clean ),
			'questions_answered' => max( 0, $questions_answered ),
			'correct_answers'    => max( 0, $correct_answers ),
			'details_json'       => wp_json_encode( $details ),
			'test_version'       => substr( $test_version, 0, 16 ),
			'taken_at'           => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s', '%d', '%d', '%s', '%s', '%s' );

		/*
		 * NULL columns are left out of the insert entirely rather than passed
		 * with a format: $wpdb->insert() would bind a PHP null through '%d' or
		 * '%s' as 0 or '', not SQL NULL.
		 */
		if ( null !== $user_id ) {
			$data['user_id'] = $user_id;
			$formats[]       = '%d';
		} else {
			$data['claim_token'] = (string) $claim_token;
			$formats[]           = '%s';
		}

		$ok = $wpdb->insert( Schema::table( 'level_results' ), $data, $formats );

		return false === $ok ? 0 : (int) $wpdb->insert_id;
	}

	public function latest_for_user( int $user_id ): ?LevelResult {
		$rows = $this->history_for_user( $user_id, 1 );

		return $rows[0] ?? null;
	}

	/**
	 * Newest first.
	 *
	 * @return list<LevelResult>
	 */
	public function history_for_user( int $user_id, int $limit = 10 ): array {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return array();
		}

		$table = Schema::table( 'level_results' );
		$limit = max( 1, min( self::MAX_HISTORY, $limit ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY taken_at DESC, id DESC LIMIT %d",
				$user_id,
				$limit
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( static fn ( array $row ): LevelResult => LevelResult::from_row( $row ), $rows );
	}

	/**
	 * Attach an anonymous result to an account.
	 *
	 * Only rows that are still unclaimed and inside the claim window match,
	 * so a token can't be replayed to move a result that already belongs to
	 * someone else, and a months-old cookie doesn't resurface a stale level.
	 * The token is cleared on claim for the same reason.
	 *
	 * @return int Rows claimed (0 or 1 in practice).
	 */
	public function claim( string $claim_token, int $user_id ): int {
		global $wpdb;

		if ( $user_id <= 0 || ! self::is_valid_token( $claim_token ) ) {
			return 0;
		}

		$table  = Schema::table( 'level_results' );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql', true ) ) - self::CLAIM_WINDOW_DAYS * DAY_IN_SECONDS );

		$updated = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"UPDATE {$table} SET user_id = %d, claim_token = NULL, claimed_at = %s WHERE claim_token = %s AND user_id IS NULL AND taken_at >= %s",
				$user_id,
				current_time( 'mysql', true ),
				$claim_token,
				$cutoff
			)
		);

		return is_int( $updated ) ? $updated : 0;
	}

	/**
	 * Remove every result belonging to a user -- for WordPress's own
	 * Privacy Tools erasure, which English Finders Account wires up.
	 *
	 * @return int Rows deleted.
	 */
	public function delete_for_user( int $user_id ): int {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return 0;
		}

		$deleted = $wpdb->delete( Schema::table( 'level_results' ), array( 'user_id' => $user_id ), array( '%d' ) );

		return is_int( $deleted ) ? $deleted : 0;
	}

	/** A claim token is exactly 32 lowercase hex characters -- anything else never reaches SQL. */
	public static function is_valid_token( string $token ): bool {
		return 1 === preg_match( '/^[a-f0-9]{32}$/', $token );
	}
}
