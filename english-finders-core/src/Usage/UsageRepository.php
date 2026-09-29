<?php
/**
 * Daily usage counts per practice tool and game (1.15.0).
 *
 * Every English Finders Study tool and Word Games Pro game already fires the
 * shared `ef:progress` browser event (a correct answer, a solved puzzle, a
 * finished round), but until now nothing counted it for visitors who are not
 * signed in -- which is nearly everyone. ActivityRecorder only sees members,
 * and Word Games Pro's own analytics table only logs searches. So there was
 * no way to tell which tools are actually used.
 *
 * This keeps aggregate counts only: one row per site day, source and kind,
 * with a running total. No user id, IP address, cookie or page URL is
 * stored, so there is nothing personal to export or erase.
 *
 * Kinds:
 *  - `visit`    one per source per report from a browser -- a page view (or a
 *               return to the tab) in which that tool registered any progress.
 *               Recorded by the server, never sent by the browser.
 *  - `correct`  a correct answer.
 *  - `solved`   a solved puzzle.
 *  - `finished` a finished round or quiz.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Usage;

use EnglishFindersCore\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UsageRepository {
	public const KIND_VISIT = 'visit';

	/** Kinds the browser may report, as fired in `ef:progress` detail.kind. */
	public const REPORTED_KINDS = array( 'correct', 'solved', 'finished' );

	/** `efs-grammar-quiz`, `wgp-daily-unscramble`, `wgp-wordle-daily`... */
	public const SOURCE_PATTERN = '/^(efs|wgp)-[a-z0-9-]{1,40}$/';

	/** Upper bound for one count in one report; a real visit stays far below it. */
	public const MAX_COUNT = 500;

	/** Upper bound for distinct source/kind pairs in one report. */
	public const MAX_PAIRS = 30;

	public static function is_valid_source( string $source ): bool {
		return 1 === preg_match( self::SOURCE_PATTERN, $source );
	}

	/**
	 * Turn a browser report into clean counts, dropping anything malformed.
	 *
	 * @param mixed $events Decoded `e` map: "source:kind" => count.
	 * @return array<string,array<string,int>> source => kind => count, including a `visit` per source.
	 */
	public static function normalize( mixed $events ): array {
		if ( ! is_array( $events ) ) {
			return array();
		}

		$out = array();
		foreach ( array_slice( $events, 0, self::MAX_PAIRS, true ) as $key => $count ) {
			$parts = explode( ':', (string) $key );
			if ( 2 !== count( $parts ) ) {
				continue;
			}

			list( $source, $kind ) = $parts;
			$count                 = is_numeric( $count ) ? (int) $count : 0;
			if ( ! self::is_valid_source( $source ) || ! in_array( $kind, self::REPORTED_KINDS, true ) || $count < 1 ) {
				continue;
			}

			$out[ $source ][ $kind ]            = min( self::MAX_COUNT, $count );
			$out[ $source ][ self::KIND_VISIT ] = 1;
		}

		return $out;
	}

	/**
	 * Add counts to today's rows.
	 *
	 * One statement for the whole report: each row is created at its count, or
	 * incremented by it when it already exists.
	 *
	 * @param array<string,array<string,int>> $counts Output of normalize().
	 */
	public function add( array $counts, ?string $day = null ): bool {
		global $wpdb;

		$day    = $day ?? current_time( 'Y-m-d' );
		$rows   = array();
		$values = array();
		foreach ( $counts as $source => $kinds ) {
			foreach ( $kinds as $kind => $count ) {
				$rows[]   = '(%s,%s,%s,%d)';
				$values[] = $day;
				$values[] = $source;
				$values[] = $kind;
				$values[] = $count;
			}
		}

		if ( array() === $rows ) {
			return false;
		}

		$table = Schema::table( 'usage_daily' );
		$sql   = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table name is prefix-derived; $rows holds only literal placeholder groups built above.
			"INSERT INTO {$table} (day, source, kind, count) VALUES " . implode( ',', $rows ) . ' ON DUPLICATE KEY UPDATE count = count + VALUES(count)',
			$values
		);

		return false !== $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above.
	}

	/**
	 * Totals per source over the last $days site days, busiest first.
	 *
	 * @return list<array{source:string,visit:int,correct:int,solved:int,finished:int}>
	 */
	public function totals( int $days ): array {
		global $wpdb;

		$table = Schema::table( 'usage_daily' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived.
				"SELECT source, kind, SUM(count) AS total FROM {$table} WHERE day >= %s GROUP BY source, kind",
				self::first_day( $days )
			),
			ARRAY_A
		);

		$out = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$source = (string) $row['source'];
			$kind   = (string) $row['kind'];
			if ( ! isset( $out[ $source ] ) ) {
				$out[ $source ] = array(
					'source'   => $source,
					'visit'    => 0,
					'correct'  => 0,
					'solved'   => 0,
					'finished' => 0,
				);
			}
			if ( array_key_exists( $kind, $out[ $source ] ) && 'source' !== $kind ) {
				$out[ $source ][ $kind ] = (int) $row['total'];
			}
		}

		$out = array_values( $out );
		usort(
			$out,
			static function ( array $a, array $b ): int {
				$by_visits = $b['visit'] <=> $a['visit'];
				return 0 !== $by_visits ? $by_visits : strcmp( $a['source'], $b['source'] );
			}
		);

		return $out;
	}

	/**
	 * Visits per site day over the last $days days, all sources together.
	 * Days without any visit are included as 0 so a quiet day is visible.
	 *
	 * @return array<string,int> Y-m-d => visits, oldest first.
	 */
	public function daily_visits( int $days ): array {
		global $wpdb;

		$table = Schema::table( 'usage_daily' );
		$first = self::first_day( $days );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived.
				"SELECT day, SUM(count) AS total FROM {$table} WHERE kind = %s AND day >= %s GROUP BY day",
				self::KIND_VISIT,
				$first
			),
			ARRAY_A
		);

		$found = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$found[ (string) $row['day'] ] = (int) $row['total'];
		}

		$out   = array();
		$date  = new \DateTimeImmutable( $first, wp_timezone() );
		$count = max( 1, $days );
		for ( $i = 0; $i < $count; $i++ ) {
			$key         = $date->modify( "+{$i} days" )->format( 'Y-m-d' );
			$out[ $key ] = $found[ $key ] ?? 0;
		}

		return $out;
	}

	/** First site day of a window of $days days ending today. */
	private static function first_day( int $days ): string {
		$days = max( 1, $days );

		return ( new \DateTimeImmutable( current_time( 'Y-m-d' ), wp_timezone() ) )->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d' );
	}
}
