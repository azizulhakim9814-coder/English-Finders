<?php
/**
 * CEFR level importer.
 *
 * Reads the bundled dataset and applies levels to words already present in the
 * dictionary. It never inserts words: a dataset entry with no matching row is
 * skipped, so the import cannot quietly grow the word list.
 *
 * Supports a dry run, which is the intended first use. The question that
 * decides whether the feature is worth surfacing is not how many levels the
 * dataset contains, but how many of *this site's* words receive one — and that
 * is only knowable by intersecting the two.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Cefr;

use EnglishFindersCore\Database\Schema;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CefrImporter {
	/** Rows per UPDATE batch. Small enough not to hold a long transaction. */
	private const BATCH = 500;

	public const LEVELS = array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' );

	private string $dataset;

	public function __construct( ?string $dataset = null ) {
		$this->dataset = $dataset ?? EFC_PATH . 'data/cefr-levels.csv.gz';
	}

	public function dataset_exists(): bool {
		return is_readable( $this->dataset );
	}

	/**
	 * Run the import.
	 *
	 * @param bool $dry_run When true, counts what would change and writes nothing.
	 * @return array<string,mixed>|WP_Error Coverage report.
	 */
	public function run( bool $dry_run = true ): array|WP_Error {
		global $wpdb;

		if ( ! $this->dataset_exists() ) {
			return new WP_Error( 'efc_cefr_missing', __( 'The CEFR dataset file is missing.', 'english-finders-core' ) );
		}

		$table = Schema::shared_table( 'words' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prefix-derived table name.
		$total_words = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( 0 === $total_words ) {
			return new WP_Error( 'efc_cefr_no_words', __( 'The dictionary is empty, so there is nothing to match against.', 'english-finders-core' ) );
		}

		$handle = gzopen( $this->dataset, 'rb' );
		if ( false === $handle ) {
			return new WP_Error( 'efc_cefr_unreadable', __( 'The CEFR dataset could not be opened.', 'english-finders-core' ) );
		}

		$report = array(
			'dry_run'          => $dry_run,
			'dictionary_words' => $total_words,
			'dataset_rows'     => 0,
			'matched'          => 0,
			'unmatched'        => 0,
			'updated'          => 0,
			'by_level'         => array_fill_keys( self::LEVELS, 0 ),
			'by_source'        => array( 'verified' => 0, 'inferred' => 0 ),
		);

		$batch = array();
		gzgets( $handle ); // Discard the header row.

		while ( false !== ( $line = gzgets( $handle ) ) ) {
			$row = str_getcsv( rtrim( $line, "\r\n" ) );
			if ( count( $row ) < 3 ) {
				continue;
			}

			list( $word, $level, $source ) = $row;
			$word   = strtolower( trim( (string) $word ) );
			$level  = strtoupper( trim( (string) $level ) );
			$source = strtolower( trim( (string) $source ) );

			if ( '' === $word || ! in_array( $level, self::LEVELS, true ) ) {
				continue;
			}
			if ( ! in_array( $source, array( 'verified', 'inferred' ), true ) ) {
				continue;
			}

			++$report['dataset_rows'];
			$batch[] = array( $word, $level, $source );

			if ( count( $batch ) >= self::BATCH ) {
				$this->process_batch( $table, $batch, $dry_run, $report );
				$batch = array();
			}
		}

		if ( ! empty( $batch ) ) {
			$this->process_batch( $table, $batch, $dry_run, $report );
		}

		gzclose( $handle );

		$report['coverage_percent'] = $total_words > 0
			? round( $report['matched'] / $total_words * 100, 1 )
			: 0.0;

		if ( ! $dry_run ) {
			update_option( 'efc_cefr_last_import', $report, false );

			/**
			 * Fires after a real (non-dry-run) CEFR import writes to the
			 * dictionary — never for a dry run, which changes nothing.
			 *
			 * Anything caching a derived view of `cefr_level` coverage
			 * (which levels exist at all, per-level word counts, and so on)
			 * needs to know an import just changed the ground truth
			 * underneath it. Core has no such cache of its own to clear —
			 * this is the hook for the plugins that do.
			 *
			 * @param array<string,mixed> $report Coverage report from this run.
			 */
			do_action( 'efc_cefr_import_applied', $report );
		}

		return $report;
	}

	/**
	 * Match one batch against the dictionary and optionally write it.
	 *
	 * Matching is done with a single IN() query per batch rather than a query
	 * per word: 51,000 individual lookups would be slow enough to matter on
	 * shared hosting, which is the environment this runs in.
	 *
	 * @param string                       $table   Words table.
	 * @param list<array{0:string,1:string,2:string}> $batch Rows.
	 * @param bool                         $dry_run Whether to write.
	 * @param array<string,mixed>          $report  Report, by reference.
	 */
	private function process_batch( string $table, array $batch, bool $dry_run, array &$report ): void {
		global $wpdb;

		$words = array_map( static fn ( array $r ): string => $r[0], $batch );

		$placeholders = implode( ',', array_fill( 0, count( $words ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders built from a counted array; values are prepared.
		$sql   = $wpdb->prepare( "SELECT id, normalized FROM {$table} WHERE normalized IN ({$placeholders})", $words );
		$found = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared above.

		$ids = array();
		foreach ( (array) $found as $row ) {
			$ids[ strtolower( (string) $row['normalized'] ) ] = (int) $row['id'];
		}

		foreach ( $batch as list( $word, $level, $source ) ) {
			if ( ! isset( $ids[ $word ] ) ) {
				++$report['unmatched'];
				continue;
			}

			++$report['matched'];
			++$report['by_level'][ $level ];
			++$report['by_source'][ $source ];

			if ( $dry_run ) {
				continue;
			}

			$updated = $wpdb->update(
				$table,
				array(
					'cefr_level'  => $level,
					'cefr_source' => $source,
				),
				array( 'id' => $ids[ $word ] ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				++$report['updated'];
			}
		}
	}

	/**
	 * Levels currently stored, read back from the database.
	 *
	 * Deliberately reads live state rather than the last import report: a
	 * report describes what one run intended, while this describes what is
	 * actually there now.
	 *
	 * @return array<string,mixed>
	 */
	public function current_coverage(): array {
		global $wpdb;

		$table = Schema::shared_table( 'words' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prefix-derived table name.
		$rows = $wpdb->get_results( "SELECT cefr_level, cefr_source, COUNT(*) AS total FROM {$table} WHERE cefr_level <> '' GROUP BY cefr_level, cefr_source", ARRAY_A );

		$by_level  = array_fill_keys( self::LEVELS, 0 );
		$by_source = array( 'verified' => 0, 'inferred' => 0 );
		$total     = 0;

		foreach ( (array) $rows as $row ) {
			$level  = (string) $row['cefr_level'];
			$source = (string) $row['cefr_source'];
			$count  = (int) $row['total'];
			$total += $count;

			if ( isset( $by_level[ $level ] ) ) {
				$by_level[ $level ] += $count;
			}
			if ( isset( $by_source[ $source ] ) ) {
				$by_source[ $source ] += $count;
			}
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prefix-derived table name.
		$dictionary = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		return array(
			'with_level'       => $total,
			'dictionary_words' => $dictionary,
			'coverage_percent' => $dictionary > 0 ? round( $total / $dictionary * 100, 1 ) : 0.0,
			'by_level'         => $by_level,
			'by_source'        => $by_source,
		);
	}
}
