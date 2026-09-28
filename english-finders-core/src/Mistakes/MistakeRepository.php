<?php
/**
 * Storage for the mistake notebook.
 *
 * Registered as Core service `mistakes`. English Finders Study writes
 * (record_miss() when a practice answer is wrong, record_correct() when a
 * learner later gets the same question right); English Finders Account
 * reads (the notebook in My Account's Library) and lets the learner mark an
 * entry learned (resolve()). Neither plugin touches the table directly --
 * the same one-owner rule as `activity` and `level_results`.
 *
 * One row per user per tool per question (`item_key`): a repeat miss bumps
 * `times_missed` and reopens the entry rather than adding a row, so the
 * table grows with distinct questions missed, not with attempts.
 *
 * Logged-in learners only -- callers pass a real user id; 0 is ignored.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Mistakes;

use EnglishFindersCore\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MistakeRepository {
	/** Upper bound on any list query, whatever a caller asks for. */
	private const MAX_LIST = 200;

	/** Column length caps, so an oversized snapshot is trimmed rather than rejected by MySQL. */
	private const CAPS = array(
		'tool'           => 40,
		'skill'          => 20,
		'item_key'       => 120,
		'level'          => 8,
		'prompt'         => 1000,
		'context'        => 2000,
		'given_answer'   => 255,
		'correct_answer' => 255,
		'explanation'    => 1000,
	);

	/**
	 * Record a wrong answer: insert the entry, or bump and reopen it.
	 *
	 * @param array{skill:string,level?:string,prompt:string,context?:string,given:string,correct:string,explanation?:string} $snapshot
	 */
	public function record_miss( int $user_id, string $tool, string $item_key, array $snapshot ): bool {
		global $wpdb;

		if ( $user_id <= 0 || ! self::valid_slug( $tool ) || '' === trim( $item_key ) || ! self::valid_slug( (string) ( $snapshot['skill'] ?? '' ) ) ) {
			return false;
		}

		$row = array(
			'tool'           => $tool,
			'skill'          => (string) $snapshot['skill'],
			'item_key'       => $item_key,
			'level'          => (string) ( $snapshot['level'] ?? '' ),
			'prompt'         => (string) ( $snapshot['prompt'] ?? '' ),
			'context'        => (string) ( $snapshot['context'] ?? '' ),
			'given_answer'   => (string) ( $snapshot['given'] ?? '' ),
			'correct_answer' => (string) ( $snapshot['correct'] ?? '' ),
			'explanation'    => (string) ( $snapshot['explanation'] ?? '' ),
		);
		foreach ( self::CAPS as $column => $cap ) {
			$row[ $column ] = mb_substr( trim( $row[ $column ] ), 0, $cap );
		}

		if ( '' === $row['prompt'] || '' === $row['correct_answer'] ) {
			return false;
		}

		$table = Schema::table( 'mistakes' );
		$now   = current_time( 'mysql', true );

		/*
		 * One statement, so two quick answers can't race into a duplicate
		 * row: the UNIQUE (user_id, tool, item_key) key turns the second into
		 * an update. The snapshot is refreshed on every miss so the entry
		 * always shows the learner's latest wrong answer.
		 */
		$result = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"INSERT INTO {$table} (user_id, tool, skill, item_key, level, prompt, context, given_answer, correct_answer, explanation, times_missed, first_missed_at, last_missed_at, resolved_at)
				VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, 1, %s, %s, NULL)
				ON DUPLICATE KEY UPDATE times_missed = times_missed + 1, last_missed_at = VALUES(last_missed_at), resolved_at = NULL,
					skill = VALUES(skill), level = VALUES(level), prompt = VALUES(prompt), context = VALUES(context),
					given_answer = VALUES(given_answer), correct_answer = VALUES(correct_answer), explanation = VALUES(explanation)",
				$user_id,
				$row['tool'],
				$row['skill'],
				$row['item_key'],
				$row['level'],
				$row['prompt'],
				$row['context'],
				$row['given_answer'],
				$row['correct_answer'],
				$row['explanation'],
				$now,
				$now
			)
		);

		return false !== $result;
	}

	/**
	 * The learner answered this question correctly: close the entry if it is
	 * open. A no-op for a question they never missed.
	 *
	 * @return int Entries closed (0 or 1).
	 */
	public function record_correct( int $user_id, string $tool, string $item_key ): int {
		global $wpdb;

		if ( $user_id <= 0 || ! self::valid_slug( $tool ) || '' === trim( $item_key ) ) {
			return 0;
		}

		$table   = Schema::table( 'mistakes' );
		$updated = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"UPDATE {$table} SET resolved_at = %s WHERE user_id = %d AND tool = %s AND item_key = %s AND resolved_at IS NULL",
				current_time( 'mysql', true ),
				$user_id,
				$tool,
				mb_substr( trim( $item_key ), 0, self::CAPS['item_key'] )
			)
		);

		return is_int( $updated ) ? $updated : 0;
	}

	/**
	 * The learner marked one of their own entries as learned. Scoped to the
	 * user id, so an id belonging to someone else changes nothing.
	 *
	 * @return int Entries closed (0 or 1).
	 */
	public function resolve( int $user_id, int $mistake_id ): int {
		global $wpdb;

		if ( $user_id <= 0 || $mistake_id <= 0 ) {
			return 0;
		}

		$table   = Schema::table( 'mistakes' );
		$updated = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"UPDATE {$table} SET resolved_at = %s WHERE id = %d AND user_id = %d AND resolved_at IS NULL",
				current_time( 'mysql', true ),
				$mistake_id,
				$user_id
			)
		);

		return is_int( $updated ) ? $updated : 0;
	}

	/**
	 * Open entries, most recently missed first.
	 *
	 * @return list<Mistake>
	 */
	public function open_for_user( int $user_id, int $limit = 20, string $skill = '' ): array {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return array();
		}

		$table  = Schema::table( 'mistakes' );
		$limit  = max( 1, min( self::MAX_LIST, $limit ) );
		$where  = 'user_id = %d AND resolved_at IS NULL';
		$params = array( $user_id );

		if ( '' !== $skill ) {
			if ( ! self::valid_slug( $skill ) ) {
				return array();
			}
			$where   .= ' AND skill = %s';
			$params[] = $skill;
		}
		$params[] = $limit;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived; the WHERE fragment is fixed text.
				"SELECT * FROM {$table} WHERE {$where} ORDER BY last_missed_at DESC, id DESC LIMIT %d",
				...$params
			),
			ARRAY_A
		);

		return is_array( $rows ) ? array_map( static fn ( array $row ): Mistake => Mistake::from_row( $row ), $rows ) : array();
	}

	/**
	 * Open and fixed counts, overall and per skill.
	 *
	 * @return array{open:int,fixed:int,by_skill:array<string,array{open:int,fixed:int}>}
	 */
	public function counts_for_user( int $user_id ): array {
		global $wpdb;

		$out = array(
			'open'     => 0,
			'fixed'    => 0,
			'by_skill' => array(),
		);

		if ( $user_id <= 0 ) {
			return $out;
		}

		$table = Schema::table( 'mistakes' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT skill, SUM(resolved_at IS NULL) AS open_count, SUM(resolved_at IS NOT NULL) AS fixed_count FROM {$table} WHERE user_id = %d GROUP BY skill",
				$user_id
			),
			ARRAY_A
		);

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$open  = (int) $row['open_count'];
			$fixed = (int) $row['fixed_count'];

			$out['by_skill'][ (string) $row['skill'] ] = array(
				'open'  => $open,
				'fixed' => $fixed,
			);
			$out['open']  += $open;
			$out['fixed'] += $fixed;
		}

		return $out;
	}

	/**
	 * Every entry, open and fixed, for WordPress Privacy Tools export.
	 *
	 * @return list<Mistake>
	 */
	public function all_for_user( int $user_id ): array {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return array();
		}

		$table = Schema::table( 'mistakes' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY last_missed_at DESC, id DESC",
				$user_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? array_map( static fn ( array $row ): Mistake => Mistake::from_row( $row ), $rows ) : array();
	}

	/** For WordPress Privacy Tools erasure. @return int Rows deleted. */
	public function delete_for_user( int $user_id ): int {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return 0;
		}

		$deleted = $wpdb->delete( Schema::table( 'mistakes' ), array( 'user_id' => $user_id ), array( '%d' ) );

		return is_int( $deleted ) ? $deleted : 0;
	}

	/** Tool and skill ids are short lowercase slugs, e.g. "grammar-quiz", "vocabulary". */
	private static function valid_slug( string $value ): bool {
		return 1 === preg_match( '/^[a-z0-9][a-z0-9_-]{0,39}$/', $value );
	}
}
