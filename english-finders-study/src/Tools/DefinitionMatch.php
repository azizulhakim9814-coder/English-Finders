<?php
/**
 * Definition match.
 *
 * Four words, four shuffled definitions, pair them up. The quiz tests whether a
 * learner recognises one meaning; this tests whether they can tell four apart,
 * which is a harder and different skill using the same data.
 *
 * As with the quiz, the pairing is held server-side and scored there. Sending
 * the mapping to the browser would put the answers in the page source.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Tools;

use EnglishFindersStudy\Contracts\ToolInterface;
use EnglishFindersStudy\Plugin;
use EnglishFindersStudy\Support\Activity;
use EnglishFindersStudy\Support\Breadcrumb;
use EnglishFindersStudy\Support\Mistakes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DefinitionMatch implements ToolInterface {
	private const ROUND_TTL   = HOUR_IN_SECONDS;
	private const PAIRS       = 4;
	private const SESSION     = 5;
	private const RATE_WINDOW = MINUTE_IN_SECONDS;
	private const RATE_MAX    = 30;

	public function id(): string {
		return 'definition-match';
	}

	public function title(): string {
		return __( 'Definition Match', 'english-finders-study' );
	}

	public function shortcode_tag(): string {
		return 'efs_definition_match';
	}

	public function category(): string {
		return 'vocabulary';
	}

	/** @return list<string> */
	public function attributes(): array {
		return array( 'free' );
	}

	public function register(): void {
		add_shortcode( 'efs_definition_match', array( $this, 'shortcode' ) );
		add_action( 'wp_ajax_efs_match_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_nopriv_efs_match_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_efs_match_check', array( $this, 'ajax_check' ) );
		add_action( 'wp_ajax_nopriv_efs_match_check', array( $this, 'ajax_check' ) );
	}

	/**
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public function shortcode( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'level'      => '',
				'breadcrumb' => '1',
				'schema'     => '1',
			),
			is_array( $atts ) ? $atts : array(),
			'efs_definition_match'
		);

		return $this->render(
			array(
				'level'      => (string) $atts['level'],
				'breadcrumb' => '0' !== (string) $atts['breadcrumb'],
				'schema'     => '0' !== (string) $atts['schema'],
			)
		);
	}

	/**
	 * @param array<string,mixed> $atts Attributes.
	 */
	public function render( array $atts = array() ): string {
		$level = $this->clean_level( (string) ( $atts['level'] ?? '' ) );

		/*
		 * Shares the quiz stylesheet. The two tools use the same card, header,
		 * option and button treatment; a second stylesheet would be a copy that
		 * drifts the first time either is adjusted.
		 */
		wp_enqueue_style( 'efs-quiz', EFS_URL . 'assets/css/quiz.css', array(), EFS_VERSION );
		wp_enqueue_script( 'efs-match', EFS_URL . 'assets/js/match.js', array(), EFS_VERSION, true );
		wp_localize_script(
			'efs-match',
			'efsMatchConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'efs_match' ),
				'level'   => $level,
				'session' => self::SESSION,
				'i18n'    => array(
					'loading'  => __( 'Loading words…', 'english-finders-study' ),
					'prompt'   => __( 'Select a word, then its meaning.', 'english-finders-study' ),
					'check'    => __( 'Check answers', 'english-finders-study' ),
					'next'     => __( 'Next round', 'english-finders-study' ),
					'round'    => __( 'Round', 'english-finders-study' ),
					'score'    => __( 'Score', 'english-finders-study' ),
					'done'     => __( 'Session complete', 'english-finders-study' ),
					'doneBody' => __( 'You matched %1$s of %2$s correctly.', 'english-finders-study' ),
					'restart'  => __( 'Start a new session', 'english-finders-study' ),
					'error'    => __( 'This activity could not load. Please try again.', 'english-finders-study' ),
				),
			)
		);

		$levels = $this->available_levels();
		$crumbs = ( ! isset( $atts['breadcrumb'] ) || false !== $atts['breadcrumb'] )
			? Breadcrumb::trail( __( 'Definition Match', 'english-finders-study' ) )
			: array();

		ob_start();
		?>
		<section class="efs-quiz" data-efs-match>
			<header class="efs-quiz__header">
				<?php echo Breadcrumb::markup( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Breadcrumb. ?>
				<p class="efs-quiz__kicker"><?php esc_html_e( 'Vocabulary practice', 'english-finders-study' ); ?></p>
				<h2 class="efs-quiz__title"><?php esc_html_e( 'Definition Match', 'english-finders-study' ); ?></h2>
				<p class="efs-quiz__intro"><?php esc_html_e( 'Pair each word with its meaning. Harder than a quiz: the meanings are all plausible.', 'english-finders-study' ); ?></p>
			</header>

			<div class="efs-quiz__main">
				<?php if ( ! empty( $levels ) ) : ?>
					<label class="efs-quiz__level">
						<span><?php esc_html_e( 'Level', 'english-finders-study' ); ?></span>
						<select data-efs-match-level>
							<option value=""><?php esc_html_e( 'Any level', 'english-finders-study' ); ?></option>
							<?php foreach ( $levels as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $level, $option ); ?>>
									<?php echo esc_html( $option ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<div class="efs-quiz__body" data-efs-match-body aria-live="polite"></div>
				<p class="efs-quiz__score" data-efs-match-score></p>
			</div>
		</section>
		<?php
		$html = (string) ob_get_clean();

		if ( ( ! isset( $atts['schema'] ) || false !== $atts['schema'] ) && ! empty( $crumbs ) ) {
			$html .= Breadcrumb::schema( $crumbs );
		}

		return $html;
	}

	/** Serve one round of pairs. The mapping stays on the server. */
	public function ajax_round(): void {
		check_ajax_referer( 'efs_match', 'nonce' );

		if ( ! $this->within_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'english-finders-study' ) ), 429 );
		}

		$level = $this->clean_level( isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '' );
		$repo  = Plugin::instance()->core( 'words' );

		if ( ! is_object( $repo ) || ! method_exists( $repo, 'random_by_level' ) ) {
			wp_send_json_error( array( 'message' => __( 'The dictionary is unavailable.', 'english-finders-study' ) ), 503 );
		}

		$rows = $this->distinct_rows( $repo->random_by_level( $level, self::PAIRS * 3 ) );

		if ( count( $rows ) < self::PAIRS ) {
			wp_send_json_error( array( 'message' => __( 'Not enough words at that level yet.', 'english-finders-study' ) ), 404 );
		}

		$rows = array_slice( $rows, 0, self::PAIRS );

		$words       = array();
		$definitions = array();
		$mapping     = array();

		foreach ( $rows as $index => $row ) {
			$words[]       = array( 'id' => $index, 'text' => (string) $row['word'] );
			$definitions[] = array( 'id' => $index, 'text' => (string) $row['definition'] );
			$mapping[]     = $index;
		}

		/*
		 * Shuffle the definitions only. Shuffling both would leave the pairing
		 * visible whenever the two lists happened to align.
		 */
		shuffle( $definitions );

		$token = wp_generate_password( 20, false );
		/*
		 * Since 1.13.0 the round also keeps each word and its definition, so a
		 * wrong pair can go into the mistake notebook. ajax_check() still
		 * accepts the older bare-mapping shape for rounds served before an
		 * update (a round lives up to ROUND_TTL).
		 */
		set_transient(
			'efs_match_' . $token,
			array(
				'mapping'     => $mapping,
				'words'       => array_column( $words, 'text', 'id' ),
				'definitions' => array_column( $definitions, 'text', 'id' ),
				'levels'      => array_map( static fn ( array $row ): string => (string) ( $row['cefr_level'] ?? '' ), $rows ),
			),
			self::ROUND_TTL
		);

		wp_send_json_success(
			array(
				'token'       => $token,
				'words'       => $words,
				'definitions' => $definitions,
			)
		);
	}

	/** Score a completed round. */
	public function ajax_check(): void {
		check_ajax_referer( 'efs_match', 'nonce' );

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$raw   = isset( $_POST['pairs'] ) ? sanitize_text_field( wp_unslash( $_POST['pairs'] ) ) : '';

		$round = '' !== $token ? get_transient( 'efs_match_' . $token ) : false;

		if ( ! is_array( $round ) ) {
			wp_send_json_error( array( 'message' => __( 'That round has expired. Start the next one.', 'english-finders-study' ) ), 410 );
		}

		// 1.13.0+ rounds carry words/definitions; an older round is the bare mapping list.
		$has_snapshot = isset( $round['mapping'] );
		$mapping      = $has_snapshot ? (array) $round['mapping'] : $round;

		// One submission per round, as with the quiz: otherwise a client can
		// resubmit until every pair is right.
		delete_transient( 'efs_match_' . $token );

		$submitted = array();
		foreach ( explode( ',', $raw ) as $pair ) {
			$parts = explode( ':', $pair );
			if ( 2 !== count( $parts ) ) {
				continue;
			}
			$submitted[ absint( $parts[0] ) ] = absint( $parts[1] );
		}

		$results = array();
		$correct = 0;

		foreach ( $mapping as $word_id ) {
			$chosen = $submitted[ $word_id ] ?? -1;
			$is_ok  = $chosen === $word_id;

			if ( $is_ok ) {
				++$correct;
			}

			$results[] = array(
				'word'    => $word_id,
				'correct' => $is_ok,
			);
		}

		/*
		 * One activity event per correct pair, not one per round -- keeps
		 * the same "one event = one correct answer" granularity every other
		 * quiz tool uses, rather than inventing a round-level unit just for
		 * this tool.
		 */
		for ( $i = 0; $i < $correct; $i++ ) {
			Activity::record_correct_answer( $this->id() );
		}

		/*
		 * Notebook: one entry per wrongly matched word (keyed by the word, so
		 * the same word in a later round updates it), and a correct pair
		 * closes that word's entry.
		 */
		if ( $has_snapshot ) {
			$words       = (array) $round['words'];
			$definitions = (array) $round['definitions'];
			foreach ( $mapping as $word_id ) {
				$word = (string) ( $words[ $word_id ] ?? '' );
				if ( '' === $word ) {
					continue;
				}
				$key    = 'word:' . strtolower( $word );
				$chosen = $submitted[ $word_id ] ?? -1;

				if ( $chosen === $word_id ) {
					Mistakes::record_correct( $this->id(), $key );
				} else {
					Mistakes::record_miss(
						$this->id(),
						$key,
						array(
							'skill'   => $this->category(),
							'level'   => (string) ( $round['levels'][ $word_id ] ?? '' ),
							'prompt'  => $word,
							'given'   => (string) ( $definitions[ $chosen ] ?? '' ),
							'correct' => (string) ( $definitions[ $word_id ] ?? '' ),
						)
					);
				}
			}
		}

		wp_send_json_success(
			array(
				'results' => $results,
				'correct' => $correct,
				'total'   => count( $mapping ),
			)
		);
	}

	/**
	 * @param list<array<string,mixed>> $rows Candidate rows.
	 * @return list<array<string,mixed>>
	 */
	private function distinct_rows( array $rows ): array {
		$seen_words = array();
		$seen_defs  = array();
		$out        = array();

		foreach ( $rows as $row ) {
			$word = strtolower( trim( (string) ( $row['word'] ?? '' ) ) );
			$def  = strtolower( trim( (string) ( $row['definition'] ?? '' ) ) );

			if ( '' === $word || '' === $def || isset( $seen_words[ $word ], $seen_defs[ $def ] ) ) {
				continue;
			}
			if ( isset( $seen_words[ $word ] ) || isset( $seen_defs[ $def ] ) ) {
				continue;
			}

			$seen_words[ $word ] = true;
			$seen_defs[ $def ]   = true;
			$out[]               = $row;
		}

		return $out;
	}

	private function clean_level( string $level ): string {
		$level = strtoupper( trim( $level ) );

		return in_array( $level, array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' ), true ) ? $level : '';
	}

	/** @return list<string> */
	private function available_levels(): array {
		return function_exists( 'wuc_available_cefr_levels' ) ? wuc_available_cefr_levels() : array();
	}

	private function within_rate_limit(): bool {
		$key   = 'efs_match_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_get_session_token() );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_MAX ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
