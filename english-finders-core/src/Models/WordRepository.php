<?php
/**
 * Optimized word query repository.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Models;

use EnglishFindersCore\Core\Cache;
use EnglishFindersCore\Support\WordTools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Not final: Word Games Pro subclasses this to keep its original class name and
 * namespace while the implementation lives here, so its existing call sites did
 * not all have to change in one release. Marking it final would make that
 * subclass a fatal error on load.
 */
class WordRepository {
	public function __construct( private readonly Cache $cache ) {}

	/**
	 * Search the dictionary.
	 *
	 * @param array<string,mixed> $args Search arguments.
	 * @return array{items:list<array<string,mixed>>,total:int,page:int,pages:int,cache_hit:bool,duration_ms:int}
	 */
	public function search( array $args ): array {
		$started = microtime( true );
		$args    = $this->normalize_args( $args );
		$key     = 'search_' . hash( 'sha256', wp_json_encode( $args ) ?: '' );
		$hit     = false;
		$cached  = $this->cache->get( $key, $hit, 'search' );

		if ( $hit && is_array( $cached ) ) {
			$cached['cache_hit']   = true;
			$cached['duration_ms'] = (int) round( ( microtime( true ) - $started ) * 1000 );
			return $cached;
		}

		$result                = $this->run_search( $args );
		$result['cache_hit']   = false;
		$result['duration_ms'] = (int) round( ( microtime( true ) - $started ) * 1000 );
		$this->cache->set( $key, $result, null, 'search' );

		return $result;
	}

	/** @return array<string,mixed>|null */
	public function find( string $word ): ?array {
		return $this->find_local( $word );
	}

	/**
	 * Return a local dictionary entry, including every stored lexical sense.
	 *
	 * @return array<string,mixed>|null
	 */
	public function find_local( string $word ): ?array {
		global $wpdb;

		$word = WordTools::normalize( $word );
		if ( '' === $word ) {
			return null;
		}

		$key    = 'word_' . hash( 'sha256', $word );
		$hit    = false;
		$cached = $this->cache->get( $key, $hit, 'dictionary-local' );
		if ( $hit ) {
			return is_array( $cached ) ? $cached : null;
		}

		$table   = $wpdb->prefix . 'wuc_words';
		$columns = 'id, word, definition, part_of_speech, frequency, length, scrabble_score, popularity, phonetics, synonyms, antonyms, example_sentence';

		/*
		 * The CEFR columns are added by English Finders Core 1.3.0. Selecting
		 * them unconditionally would make every word lookup fail with "unknown
		 * column" on an older Core — taking the whole dictionary down rather
		 * than merely hiding a badge. The version check costs nothing (no
		 * query) and fails closed: uncertain means omit the columns.
		 */
		if ( self::supports_cefr() ) {
			$columns .= ', cefr_level, cefr_source';
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$columns} FROM {$table} WHERE normalized = %s AND language = 'en' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$word
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			$this->cache->set( $key, false, 5 * MINUTE_IN_SECONDS, 'dictionary-local' );
			return null;
		}

		$entry           = $this->cast_row( $row );
		$entry['senses'] = $this->senses_for_word_id( (int) $entry['id'] );
		$entry           = $this->apply_first_sense( $entry );
		$this->cache->set( $key, $entry, DAY_IN_SECONDS, 'dictionary-local' );
		return $entry;
	}

	public function exists( string $word ): bool {
		return null !== $this->find_local( $word );
	}

	public function wordle_exists( string $word ): bool {
		global $wpdb;
		$word = WordTools::normalize( $word );
		if ( 5 !== strlen( $word ) ) {
			return false;
		}
		$table = $wpdb->prefix . 'wuc_words';
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$table} WHERE normalized=%s AND language='en' AND is_wordle_allowed=1 LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$word
			)
		);
	}

	/** @return list<string> */
	public function suggestions( string $term, int $limit = 8 ): array {
		global $wpdb;

		$term  = WordTools::normalize( $term );
		$limit = min( 20, max( 1, $limit ) );
		if ( strlen( $term ) < 2 ) {
			return array();
		}

		$key    = 'suggestions_' . hash( 'sha256', $term . '|' . $limit );
		$hit    = false;
		$cached = $this->cache->get( $key, $hit, 'suggestions' );
		if ( $hit && is_array( $cached ) ) {
			return array_values( array_map( 'strval', $cached ) );
		}

		$table = $wpdb->prefix . 'wuc_words';
		$sql   = $wpdb->prepare(
			"SELECT word FROM {$table} WHERE normalized LIKE %s ORDER BY frequency DESC, popularity DESC, word ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->esc_like( $term ) . '%',
			$limit
		);
		$items = array_map( 'strval', $wpdb->get_col( $sql ) );
		$this->cache->set( $key, $items, 5 * MINUTE_IN_SECONDS, 'suggestions' );
		return $items;
	}

	/** @return list<string> */
	public function popular_words( int $limit = 10 ): array {
		global $wpdb;

		$limit  = min( 20, max( 1, $limit ) );
		$key    = 'popular_words_' . $limit;
		$hit    = false;
		$cached = $this->cache->get( $key, $hit, 'suggestions' );
		if ( $hit && is_array( $cached ) ) {
			return array_values( array_map( 'strval', $cached ) );
		}

		$table = $wpdb->prefix . 'wuc_words';
		$sql   = $wpdb->prepare(
			"SELECT word FROM {$table} WHERE language = 'en' AND definition <> '' ORDER BY popularity DESC, frequency DESC, word ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$limit
		);
		$items = array_values( array_map( 'strval', $wpdb->get_col( $sql ) ) );
		$this->cache->set( $key, $items, 15 * MINUTE_IN_SECONDS, 'suggestions' );
		return $items;
	}

	/**
	 * Find words that can be built from a rack.
	 *
	 * @param bool|null $cache_hit  Receives cache state.
	 * @param int|null  $duration_ms Receives total duration.
	 * @return list<array<string,mixed>>
	 */
	public function rack_words( string $letters, int $blanks = 0, int $limit = 250, int $min_length = 2, ?bool &$cache_hit = null, ?int &$duration_ms = null ): array {
		global $wpdb;

		$started    = microtime( true );
		$letters    = WordTools::normalize( $letters );
		$blanks     = min( 2, max( 0, $blanks ) );
		$max_length = min( 15, strlen( $letters ) + $blanks );
		$limit      = min( 500, max( 1, $limit ) );
		$min_length = min( max( 2, $max_length ), max( 2, $min_length ) );
		$key        = 'rack_' . hash( 'sha256', implode( '|', array( $letters, $blanks, $limit, $min_length ) ) );
		$hit        = false;
		$cached     = $this->cache->get( $key, $hit, 'word-finder' );

		if ( $hit && is_array( $cached ) ) {
			$cache_hit  = true;
			$duration_ms = (int) round( ( microtime( true ) - $started ) * 1000 );
			return $cached;
		}

		if ( '' === $letters || $max_length < 2 ) {
			$cache_hit   = false;
			$duration_ms = (int) round( ( microtime( true ) - $started ) * 1000 );
			return array();
		}

		$table      = $wpdb->prefix . 'wuc_words';
		$mask       = WordTools::letter_mask( $letters );
		$where_mask = $blanks > 0 ? '1=1' : $wpdb->prepare( '(letter_mask & ~%d) = 0', $mask );
		$candidates = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, word, definition, part_of_speech, frequency, length, scrabble_score, popularity, phonetics, synonyms, antonyms, example_sentence
				FROM {$table}
				WHERE language = 'en' AND length BETWEEN %d AND %d AND {$where_mask}
				ORDER BY frequency DESC, scrabble_score DESC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$min_length,
				$max_length,
				max( 1000, $limit * 8 )
			),
			ARRAY_A
		);

		$results = array();
		foreach ( $candidates as $row ) {
			if ( WordTools::can_build( (string) $row['word'], $letters, $blanks ) ) {
				$results[] = $this->cast_row( $row );
				if ( count( $results ) >= $limit ) {
					break;
				}
			}
		}
		$results = $this->hydrate_first_senses( $results );
		$this->cache->set( $key, $results, null, 'word-finder' );
		$cache_hit   = false;
		$duration_ms = (int) round( ( microtime( true ) - $started ) * 1000 );
		return $results;
	}

	/** Return a stable site-specific Wordle solution for a supplied seed. */
	/**
	 * Random words at a CEFR level, each with a usable definition.
	 *
	 * Exists here rather than in a consumer so practice tools never query the
	 * dictionary tables directly — that is the duplication the Core plugin was
	 * created to prevent.
	 *
	 * Definitions shorter than a few characters are excluded: a quiz option
	 * reading "a unit" is not a usable distractor, and filtering in SQL is
	 * cheaper than fetching and discarding.
	 *
	 * @param string $level CEFR band, e.g. "B1". Empty means any level.
	 * @param int    $limit Maximum rows.
	 * @return list<array<string,mixed>>
	 */
	public function random_by_level( string $level = '', int $limit = 20 ): array {
		global $wpdb;

		$level = strtoupper( trim( $level ) );
		$limit = max( 1, min( 100, $limit ) );
		$table = $wpdb->prefix . 'wuc_words';

		$where  = array( "language = 'en'", "definition <> ''", 'CHAR_LENGTH(definition) >= 12' );
		$params = array();

		if ( '' !== $level && in_array( $level, array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' ), true ) ) {
			$level_clause = $this->cefr_level_sql( $level );
			$where[]      = $level_clause['sql'];
			$params       = array_merge( $params, $level_clause['params'] );
		}

		$where_sql = implode( ' AND ', $where );
		$params[]  = $limit;

		/*
		 * ORDER BY RAND() is acceptable here only because the filtered set is
		 * small — a single CEFR band is a few thousand rows, not the whole
		 * dictionary. It is deliberately not cached: a quiz that served the
		 * same words to everyone for an hour would stop being practice.
		 */
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Fragments are fixed; values are bound.
		$sql = "SELECT id, word, definition, part_of_speech, cefr_level, length, scrabble_score
			FROM {$table}
			WHERE {$where_sql}
			ORDER BY RAND()
			LIMIT %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $rows ) ? array_values( $rows ) : array();
	}

	public function deterministic_five_letter_word( string $seed ): string {
		global $wpdb;

		$table = $wpdb->prefix . 'wuc_words';
		$count = $this->wordle_solution_count();
		if ( $count < 1 ) {
			return 'crane';
		}

		$digest = hash_hmac( 'sha256', $seed, wp_salt( 'auth' ) );
		$offset = (int) ( hexdec( substr( $digest, 0, 8 ) ) % $count );
		$word   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT word FROM {$table} WHERE length=5 AND language='en' AND is_wordle_solution=1 ORDER BY normalized ASC LIMIT 1 OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$offset
			)
		);
		return is_string( $word ) && '' !== $word ? $word : 'crane';
	}

	public function random_five_letter_word(): string {
		global $wpdb;

		$table = $wpdb->prefix . 'wuc_words';
		$count = $this->wordle_solution_count();
		if ( $count < 1 ) {
			return 'crane';
		}
		$offset = wp_rand( 0, $count - 1 );
		$word   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT word FROM {$table} WHERE length=5 AND language='en' AND is_wordle_solution=1 ORDER BY id ASC LIMIT 1 OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$offset
			)
		);
		return is_string( $word ) && '' !== $word ? $word : 'crane';
	}

	private function wordle_solution_count(): int {
		global $wpdb;

		$hit   = false;
		$count = $this->cache->get( 'wordle_solution_count', $hit, 'wordle' );
		if ( $hit ) {
			return max( 0, (int) $count );
		}

		$table = $wpdb->prefix . 'wuc_words';
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE length=5 AND language='en' AND is_wordle_solution=1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->cache->set( 'wordle_solution_count', $count, HOUR_IN_SECONDS, 'wordle' );
		return max( 0, $count );
	}

	/** @param array<string,mixed> $args @return array<string,mixed> */
	private function run_search( array $args ): array {
		global $wpdb;

		$table       = $wpdb->prefix . 'wuc_words';
		$grams       = $wpdb->prefix . 'wuc_word_grams';
		$where       = array( "w.language = 'en'" );
		$params      = array();
		$joins       = array();
		$group_by    = '';
		$query       = (string) $args['query'];
		$search_mode = (string) $args['mode'];

		if ( 'anagram' === $search_mode && '' !== $query ) {
			$where[]  = 'w.letter_signature = %s';
			$params[] = WordTools::signature( $query );
		} elseif ( 'pattern' === $search_mode && '' !== $query ) {
			$where[]  = 'w.normalized LIKE %s';
			$params[] = WordTools::wildcard_to_like( $query );
		} elseif ( 'regex' === $search_mode ) {
			$where[]  = 'w.normalized REGEXP %s';
			$params[] = '' !== $query ? $query : '^$';
		} elseif ( '' !== $query ) {
			$where[]  = 'w.normalized LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $query ) . '%';
		}

		if ( '' !== $args['starts_with'] ) {
			$where[]  = 'w.normalized LIKE %s';
			$params[] = $wpdb->esc_like( (string) $args['starts_with'] ) . '%';
		}
		if ( '' !== $args['ends_with'] ) {
			$where[]  = 'w.reversed_word LIKE %s';
			$params[] = $wpdb->esc_like( strrev( (string) $args['ends_with'] ) ) . '%';
		}
		if ( '' !== $args['contains'] ) {
			$contains = (string) $args['contains'];
			if ( strlen( $contains ) >= 3 ) {
				$joins[]    = "INNER JOIN {$grams} g ON g.word_id = w.id";
				$where[]    = 'g.gram = %s';
				$params[]   = substr( $contains, 0, 3 );
				$group_by   = ' GROUP BY w.id';
			}
			$where[]  = 'w.normalized LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $contains ) . '%';
		}
		if ( '' !== $args['exclude'] ) {
			foreach ( array_unique( str_split( (string) $args['exclude'] ) ) as $letter ) {
				$where[]  = 'w.normalized NOT LIKE %s';
				$params[] = '%' . $wpdb->esc_like( $letter ) . '%';
			}
		}
		if ( $args['length'] > 0 ) {
			$where[]  = 'w.length = %d';
			$params[] = $args['length'];
		} else {
			$where[]  = 'w.length BETWEEN %d AND %d';
			$params[] = $args['min_length'];
			$params[] = $args['max_length'];
		}

		$order_map = array(
			'length_desc' => 'w.length DESC, w.frequency DESC, w.word ASC',
			'length_asc'  => 'w.length ASC, w.frequency DESC, w.word ASC',
			'score_desc'  => 'w.scrabble_score DESC, w.length DESC, w.word ASC',
			'alpha_desc'  => 'w.word DESC',
			'popular'     => 'w.popularity DESC, w.frequency DESC, w.word ASC',
			'alpha_asc'   => 'w.word ASC',
		);
		$order      = $order_map[ $args['sort'] ] ?? $order_map['popular'];
		$offset     = ( $args['page'] - 1 ) * $args['per_page'];
		$from       = "FROM {$table} w " . implode( ' ', $joins );
		$where_sql  = ' WHERE ' . implode( ' AND ', $where );
		$count_sql  = "SELECT COUNT(DISTINCT w.id) {$from}{$where_sql}";
		$select_sql = "SELECT w.id, w.word, w.definition, w.part_of_speech, w.frequency, w.length, w.scrabble_score, w.popularity, w.phonetics, w.synonyms, w.antonyms, w.example_sentence {$from}{$where_sql}{$group_by} ORDER BY {$order} LIMIT %d OFFSET %d";

		$total = (int) $wpdb->get_var( $this->prepare( $count_sql, $params ) );
		$rows  = $wpdb->get_results( $this->prepare( $select_sql, array_merge( $params, array( $args['per_page'], $offset ) ) ), ARRAY_A );
		$items = $this->hydrate_first_senses( array_map( array( $this, 'cast_row' ), is_array( $rows ) ? $rows : array() ) );

		return array(
			'items' => $items,
			'total' => $total,
			'page'  => $args['page'],
			'pages' => max( 1, (int) ceil( $total / $args['per_page'] ) ),
		);
	}

	/** @param array<string,mixed> $args @return array<string,mixed> */
	private function normalize_args( array $args ): array {
		$settings   = get_option( 'wuc_settings', array() );
		$per_page   = min( 100, max( 5, absint( $args['per_page'] ?? $settings['results_per_page'] ?? 30 ) ) );
		$mode       = sanitize_key( (string) ( $args['mode'] ?? 'contains' ) );
		$min_length = min( 32, max( 1, absint( $args['min_length'] ?? 2 ) ) );
		$max_length = min( 32, max( 2, absint( $args['max_length'] ?? 15 ) ) );
		if ( $min_length > $max_length ) {
			[ $min_length, $max_length ] = array( $max_length, $min_length );
		}
		if ( ! in_array( $mode, array( 'contains', 'anagram', 'pattern', 'regex' ), true ) ) {
			$mode = 'contains';
		}

		return array(
			'query'       => in_array( $mode, array( 'pattern', 'regex' ), true ) ? $this->sanitize_pattern( (string) ( $args['query'] ?? '' ), 'regex' === $mode ) : WordTools::normalize( (string) ( $args['query'] ?? '' ) ),
			'mode'        => $mode,
			'starts_with' => WordTools::normalize( (string) ( $args['starts_with'] ?? '' ) ),
			'ends_with'   => WordTools::normalize( (string) ( $args['ends_with'] ?? '' ) ),
			'contains'    => WordTools::normalize( (string) ( $args['contains'] ?? '' ) ),
			'exclude'     => WordTools::normalize( (string) ( $args['exclude'] ?? '' ) ),
			'length'      => min( 32, absint( $args['length'] ?? 0 ) ),
			'min_length'  => $min_length,
			'max_length'  => $max_length,
			'sort'        => sanitize_key( (string) ( $args['sort'] ?? 'popular' ) ),
			'page'        => max( 1, absint( $args['page'] ?? 1 ) ),
			'per_page'    => $per_page,
		);
	}

	/**
	 * Regex mode intentionally supports only anchors, literal letters, a single
	 * character dot, and simple character classes. Repetition and alternation
	 * are excluded to prevent expensive public database expressions.
	 */
	private function sanitize_pattern( string $pattern, bool $regex ): string {
		$pattern = substr( sanitize_text_field( wp_unslash( $pattern ) ), 0, $regex ? 32 : 64 );
		if ( ! $regex ) {
			return preg_replace( '/[^a-zA-Z*?_\-]/', '', $pattern ) ?? '';
		}
		$pattern = preg_replace( '/[^a-zA-Z^$.\[\]\-]/', '', $pattern ) ?? '';
		if ( '' === $pattern || false === @preg_match( '~' . $pattern . '~', 'validation' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return '^$';
		}
		return $pattern;
	}

	/** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
	private function hydrate_first_senses( array $items ): array {
		global $wpdb;

		$missing_ids = array();
		foreach ( $items as $item ) {
			if ( '' === trim( (string) ( $item['definition'] ?? '' ) ) && ! empty( $item['id'] ) ) {
				$missing_ids[] = (int) $item['id'];
			}
		}
		$missing_ids = array_values( array_unique( array_filter( $missing_ids ) ) );
		if ( empty( $missing_ids ) ) {
			return array_map( array( $this, 'mark_definition_status' ), $items );
		}

		$table        = $wpdb->prefix . 'wuc_word_senses';
		$placeholders = implode( ',', array_fill( 0, count( $missing_ids ), '%d' ) );
		$sql          = $wpdb->prepare(
			"SELECT word_id, part_of_speech, definition, examples FROM {$table} WHERE word_id IN ({$placeholders}) AND definition IS NOT NULL AND definition <> '' ORDER BY word_id ASC, sense_order ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			...$missing_ids
		);
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$first = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$id = (int) $row['word_id'];
			if ( ! isset( $first[ $id ] ) ) {
				$first[ $id ] = $row;
			}
		}

		foreach ( $items as &$item ) {
			$id = (int) ( $item['id'] ?? 0 );
			if ( '' === trim( (string) ( $item['definition'] ?? '' ) ) && isset( $first[ $id ] ) ) {
				$item['definition']     = (string) $first[ $id ]['definition'];
				$item['part_of_speech'] = (string) ( $first[ $id ]['part_of_speech'] ?? $item['part_of_speech'] ?? '' );
				$examples               = $this->decode_list( $first[ $id ]['examples'] ?? '' );
				if ( '' === trim( (string) ( $item['example_sentence'] ?? '' ) ) && ! empty( $examples ) ) {
					$item['example_sentence'] = (string) $examples[0];
				}
			}
			$item = $this->mark_definition_status( $item );
		}
		unset( $item );
		return $items;
	}

	/** @return list<array<string,mixed>> */
	private function senses_for_word_id( int $word_id ): array {
		global $wpdb;
		if ( $word_id < 1 ) {
			return array();
		}
		$table = $wpdb->prefix . 'wuc_word_senses';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT source_pack, source_id, sense_order, part_of_speech, definition, examples, synonyms, antonyms, cefr_level, cefr_source FROM {$table} WHERE word_id = %d ORDER BY sense_order ASC, id ASC LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$word_id
			),
			ARRAY_A
		);
		$senses = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$row['sense_order'] = (int) $row['sense_order'];
			$row['examples']    = $this->decode_list( $row['examples'] ?? '' );
			$row['synonyms']    = $this->decode_list( $row['synonyms'] ?? '' );
			$row['antonyms']    = $this->decode_list( $row['antonyms'] ?? '' );
			/*
			 * Sense-level CEFR is narrower than the word-level column: it
			 * marks one part of speech, not the whole headword, so a reader
			 * must never mistake it for the badge shown elsewhere on the
			 * page. Empty for the large majority of senses -- most words
			 * have no per-sense override and fall back to the word-level tag.
			 */
			$row['cefr_level']  = strtoupper( trim( (string) ( $row['cefr_level'] ?? '' ) ) );
			$row['cefr_source'] = strtolower( trim( (string) ( $row['cefr_source'] ?? '' ) ) );
			$senses[]           = $row;
		}
		return $senses;
	}

	/** @param array<string,mixed> $entry @return array<string,mixed> */
	private function apply_first_sense( array $entry ): array {
		if ( '' !== trim( (string) ( $entry['definition'] ?? '' ) ) ) {
			return $this->mark_definition_status( $entry );
		}
		foreach ( (array) ( $entry['senses'] ?? array() ) as $sense ) {
			if ( ! is_array( $sense ) || '' === trim( (string) ( $sense['definition'] ?? '' ) ) ) {
				continue;
			}
			$entry['definition']     = (string) $sense['definition'];
			$entry['part_of_speech'] = (string) ( $sense['part_of_speech'] ?? $entry['part_of_speech'] ?? '' );
			if ( '' === trim( (string) ( $entry['example_sentence'] ?? '' ) ) && ! empty( $sense['examples'][0] ) ) {
				$entry['example_sentence'] = (string) $sense['examples'][0];
			}
			break;
		}
		return $this->mark_definition_status( $entry );
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private function mark_definition_status( array $row ): array {
		$row['definition_status'] = '' !== trim( (string) ( $row['definition'] ?? '' ) ) ? 'local' : 'missing';
		return $row;
	}

	/** @param array<string,mixed> $row @return array<string,mixed> */
	private function cast_row( array $row ): array {
		foreach ( array( 'id', 'frequency', 'length', 'scrabble_score', 'popularity' ) as $key ) {
			if ( isset( $row[ $key ] ) ) {
				$row[ $key ] = (int) $row[ $key ];
			}
		}
		foreach ( array( 'synonyms', 'antonyms' ) as $key ) {
			$row[ $key ] = $this->decode_list( $row[ $key ] ?? '' );
		}
		return $this->mark_definition_status( $row );
	}

	/** @return list<string> */
	private function decode_list( mixed $value ): array {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'strval', $value ) ) );
		}
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return array();
		}
		$decoded = json_decode( $value, true );
		return is_array( $decoded ) ? array_values( array_filter( array_map( 'strval', $decoded ) ) ) : array();
	}

	/**
	 * Length ceiling applied only to `cefr_source = 'inferred'` rows at A1/A2.
	 *
	 * The imported dataset's verified rows track length sensibly by level
	 * (A1 averages ~5 letters, A2 ~6.6). Its inferred rows -- the ML-classified
	 * remainder for words the reference list doesn't cover -- do not: 40% of
	 * inferred A1 words and 49% of inferred A2 words are 9+ letters, including
	 * entries like "unanswerable", "disputation" and "insignificance". B1 and
	 * above show no comparable gap, so no cap is applied there.
	 */
	private const INFERRED_LENGTH_CAP = array(
		'A1' => 7,
		'A2' => 9,
	);

	/**
	 * Build a WHERE fragment matching one CEFR level, capping low-confidence
	 * "inferred" rows by length at A1/A2. "verified" rows and every other
	 * level pass through unrestricted.
	 *
	 * @return array{sql:string,params:list<mixed>}
	 */
	private function cefr_level_sql( string $level ): array {
		$cap = self::INFERRED_LENGTH_CAP[ $level ] ?? null;
		if ( null === $cap ) {
			return array( 'sql' => 'cefr_level = %s', 'params' => array( $level ) );
		}

		return array(
			'sql'    => "(cefr_level = %s AND (cefr_source = 'verified' OR length <= %d))",
			'params' => array( $level, $cap ),
		);
	}

	/** @param list<mixed> $params */
	private function prepare( string $sql, array $params ): string {
		global $wpdb;
		return empty( $params ) ? $sql : $wpdb->prepare( $sql, ...$params );
	}

	/**
	 * Whether the CEFR columns exist.
	 *
	 * Determined from the Core plugin version rather than a SHOW COLUMNS query,
	 * so it adds no database round trip to a hot path. Resolved once per
	 * request.
	 */
	private static function supports_cefr(): bool {
		static $supported = null;

		if ( null !== $supported ) {
			return $supported;
		}

		/*
		 * Inside Core the columns always exist: Core owns the schema and its
		 * own migration created them. The version check this replaced was a
		 * consumer-side guard, and asking Core whether Core is new enough would
		 * be circular.
		 */
		$supported = true;

		return $supported;
	}
}
