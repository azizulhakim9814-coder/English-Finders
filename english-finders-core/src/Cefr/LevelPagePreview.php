<?php
/**
 * Level page coverage preview.
 *
 * Reports how many words exist for each CEFR level and length combination, so
 * the decision about which pages to generate is made against real counts rather
 * than an assumption. Writes nothing and creates no routes.
 *
 * The question it answers: a page with three words is thin content that ranks
 * for nothing and drags on quality signals across the section, so the useful
 * number is not "how many combinations exist" but "how many clear a threshold
 * worth publishing".
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Cefr;

use EnglishFindersCore\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LevelPagePreview {
	/** Lengths a page could plausibly be generated for. */
	private const MIN_LENGTH = 2;
	private const MAX_LENGTH = 15;

	/**
	 * Word counts per level and length.
	 *
	 * A single grouped query rather than one per combination: 70 separate
	 * COUNT queries on a 64,000-row table would be slow enough to matter on
	 * shared hosting, and this runs from an admin screen.
	 *
	 * @return array<string,mixed>
	 */
	public function report( int $threshold = 15 ): array {
		global $wpdb;

		$threshold = max( 1, $threshold );
		$table     = Schema::shared_table( 'words' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prefix-derived table name.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT cefr_level, length, COUNT(*) AS total
				 FROM {$table}
				 WHERE language = 'en' AND cefr_level <> '' AND length BETWEEN %d AND %d
				 GROUP BY cefr_level, length
				 ORDER BY cefr_level ASC, length ASC",
				self::MIN_LENGTH,
				self::MAX_LENGTH
			),
			ARRAY_A
		);

		$grid        = array();
		$level_total = array();

		foreach ( (array) $rows as $row ) {
			$level  = (string) $row['cefr_level'];
			$length = (int) $row['length'];
			$count  = (int) $row['total'];

			if ( ! in_array( $level, CefrImporter::LEVELS, true ) ) {
				continue;
			}

			$grid[ $level ][ $length ] = $count;
			$level_total[ $level ]     = ( $level_total[ $level ] ?? 0 ) + $count;
		}

		ksort( $grid );
		ksort( $level_total );

		$viable = 0;
		$thin   = 0;
		$words_on_viable = 0;

		foreach ( $grid as $lengths ) {
			foreach ( $lengths as $count ) {
				if ( $count >= $threshold ) {
					++$viable;
					$words_on_viable += $count;
				} else {
					++$thin;
				}
			}
		}

		return array(
			'threshold'          => $threshold,
			'grid'               => $grid,
			'level_totals'       => $level_total,
			'combinations_total' => $viable + $thin,
			'viable_pages'       => $viable,
			'thin_pages'         => $thin,
			'words_on_viable'    => $words_on_viable,
			// Level-only pages are worth comparing against: five broad pages may
			// outperform thirty narrow ones, and they cost nothing extra to add.
			'level_only_pages'   => count( array_filter( $level_total, static fn ( int $n ): bool => $n >= $threshold ) ),
		);
	}
}
