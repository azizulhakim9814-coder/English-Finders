<?php
/**
 * Vocabulary quiz.
 *
 * Shows a word and four definitions; the learner picks the right one. Words and
 * distractors come from Core's dictionary, filtered by CEFR level, so this tool
 * needs no authored content of its own.
 *
 * The correct answer is never sent to the browser. A round is stored server-side
 * behind a token and scored on the server, following the same pattern as the
 * Wordle round store — otherwise the answer is visible in the page source and
 * the activity is pointless.
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

final class VocabularyQuiz implements ToolInterface {
	private const ROUND_TTL   = HOUR_IN_SECONDS;
	private const OPTIONS     = 4;
	/** Questions per session. A session that never ends has no sense of completion. */
	private const SESSION_LENGTH = 20;
	private const RATE_WINDOW = MINUTE_IN_SECONDS;
	private const RATE_MAX    = 40;

	public function id(): string {
		return 'vocabulary-quiz';
	}

	public function title(): string {
		return __( 'Vocabulary Quiz', 'english-finders-study' );
	}

	public function shortcode_tag(): string {
		return 'efs_vocabulary_quiz';
	}

	public function category(): string {
		return 'vocabulary';
	}

	/** @return list<string> */
	public function attributes(): array {
		return array( 'free' );
	}

	public function register(): void {
		add_shortcode( 'efs_vocabulary_quiz', array( $this, 'shortcode' ) );
		add_action( 'wp_ajax_efs_quiz_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_nopriv_efs_quiz_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_efs_quiz_answer', array( $this, 'ajax_answer' ) );
		add_action( 'wp_ajax_nopriv_efs_quiz_answer', array( $this, 'ajax_answer' ) );
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
			'efs_vocabulary_quiz'
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

		wp_enqueue_style( 'efs-quiz', EFS_URL . 'assets/css/quiz.css', array(), EFS_VERSION );
		wp_enqueue_script( 'efs-quiz', EFS_URL . 'assets/js/quiz.js', array(), EFS_VERSION, true );
		wp_localize_script(
			'efs-quiz',
			'efsQuizConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'efs_quiz' ),
				'level'   => $level,
				'session' => self::SESSION_LENGTH,
				'i18n'    => array(
					'loading'  => __( 'Loading a word…', 'english-finders-study' ),
					'correct'  => __( 'Correct', 'english-finders-study' ),
					'wrong'    => __( 'Not quite', 'english-finders-study' ),
					'next'     => __( 'Next word', 'english-finders-study' ),
					'error'    => __( 'The quiz could not load. Please try again.', 'english-finders-study' ),
					'score'    => __( 'Score', 'english-finders-study' ),
					'question' => __( 'Question', 'english-finders-study' ),
					'done'     => __( 'Session complete', 'english-finders-study' ),
					'doneBody' => __( 'You answered %1$s of %2$s correctly.', 'english-finders-study' ),
					'restart'  => __( 'Start a new session', 'english-finders-study' ),
				),
			)
		);

		$levels = $this->available_levels();

		$show_crumb  = ! isset( $atts['breadcrumb'] ) || false !== $atts['breadcrumb'];
		$show_schema = ! isset( $atts['schema'] ) || false !== $atts['schema'];
		$crumbs      = $show_crumb ? Breadcrumb::trail( __( 'Vocabulary Quiz', 'english-finders-study' ) ) : array();

		ob_start();
		?>
		<section class="efs-quiz" data-efs-quiz>
			<header class="efs-quiz__header">
				<?php echo Breadcrumb::markup( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Breadcrumb. ?>

				<p class="efs-quiz__kicker"><?php esc_html_e( 'Vocabulary practice', 'english-finders-study' ); ?></p>
				<h2 class="efs-quiz__title"><?php esc_html_e( 'Vocabulary Quiz', 'english-finders-study' ); ?></h2>
				<p class="efs-quiz__intro"><?php esc_html_e( 'Choose the meaning that matches the word. Filter by level to practise vocabulary at your own stage.', 'english-finders-study' ); ?></p>
			</header>

			<div class="efs-quiz__main">
				<?php if ( ! empty( $levels ) ) : ?>
					<label class="efs-quiz__level">
						<span><?php esc_html_e( 'Level', 'english-finders-study' ); ?></span>
						<select data-efs-quiz-level>
							<option value=""><?php esc_html_e( 'Any level', 'english-finders-study' ); ?></option>
							<?php foreach ( $levels as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $level, $option ); ?>>
									<?php echo esc_html( $option ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<div class="efs-quiz__body" data-efs-quiz-body aria-live="polite"></div>
				<p class="efs-quiz__score" data-efs-quiz-score></p>
			</div>
		</section>
		<?php
		$html = (string) ob_get_clean();

		if ( $show_schema && ! empty( $crumbs ) ) {
			$html .= Breadcrumb::schema( $crumbs );
		}

		return $html;
	}

	/** Serve one round. The answer stays on the server. */
	public function ajax_round(): void {
		check_ajax_referer( 'efs_quiz', 'nonce' );

		if ( ! $this->within_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'english-finders-study' ) ), 429 );
		}

		$level = $this->clean_level( isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '' );
		$repo  = Plugin::instance()->core( 'words' );

		if ( ! is_object( $repo ) || ! method_exists( $repo, 'random_by_level' ) ) {
			wp_send_json_error( array( 'message' => __( 'The dictionary is unavailable.', 'english-finders-study' ) ), 503 );
		}

		/*
		 * Over-fetch, then filter. Some rows share a definition or a word stem,
		 * which would produce a question with two defensible answers.
		 */
		$rows = $repo->random_by_level( $level, self::OPTIONS * 3 );
		$rows = $this->distinct_rows( $rows );

		if ( count( $rows ) < self::OPTIONS ) {
			wp_send_json_error( array( 'message' => __( 'Not enough words at that level yet.', 'english-finders-study' ) ), 404 );
		}

		$rows    = array_slice( $rows, 0, self::OPTIONS );
		$correct = wp_rand( 0, self::OPTIONS - 1 );

		$options = array();
		foreach ( $rows as $index => $row ) {
			$options[] = array(
				'id'   => $index,
				'text' => (string) $row['definition'],
			);
		}

		$token = wp_generate_password( 20, false );
		set_transient(
			'efs_quiz_' . $token,
			array(
				'correct' => $correct,
				'word'    => (string) $rows[ $correct ]['word'],
				// Mistake-notebook snapshot: the definitions exactly as shown, in display order.
				'level'   => (string) ( $rows[ $correct ]['cefr_level'] ?? '' ),
				'options' => array_column( $options, 'text' ),
			),
			self::ROUND_TTL
		);

		wp_send_json_success(
			array(
				'token'   => $token,
				'word'    => (string) $rows[ $correct ]['word'],
				'level'   => (string) ( $rows[ $correct ]['cefr_level'] ?? '' ),
				'pos'     => (string) ( $rows[ $correct ]['part_of_speech'] ?? '' ),
				'options' => $options,
			)
		);
	}

	/** Score an answer. */
	public function ajax_answer(): void {
		check_ajax_referer( 'efs_quiz', 'nonce' );

		$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$choice = isset( $_POST['choice'] ) ? absint( wp_unslash( $_POST['choice'] ) ) : -1;

		$round = '' !== $token ? get_transient( 'efs_quiz_' . $token ) : false;

		if ( ! is_array( $round ) ) {
			wp_send_json_error( array( 'message' => __( 'That round has expired. Try the next word.', 'english-finders-study' ) ), 410 );
		}

		/*
		 * One answer per round. Without this a client could retry until it hit
		 * the right option and still be told "correct".
		 */
		delete_transient( 'efs_quiz_' . $token );

		$is_correct = (int) $round['correct'] === $choice;
		if ( $is_correct ) {
			Activity::record_correct_answer( $this->id() );
		}

		// Keyed by word, so the same word missed in a later round updates one entry. Pre-1.13.0 rounds carry no options and are skipped.
		if ( isset( $round['options'] ) ) {
			$key = 'word:' . strtolower( (string) $round['word'] );
			if ( $is_correct ) {
				Mistakes::record_correct( $this->id(), $key );
			} else {
				Mistakes::record_miss(
					$this->id(),
					$key,
					array(
						'skill'   => $this->category(),
						'level'   => (string) $round['level'],
						'prompt'  => (string) $round['word'],
						'given'   => (string) ( $round['options'][ $choice ] ?? '' ),
						'correct' => (string) ( $round['options'][ (int) $round['correct'] ] ?? '' ),
					)
				);
			}
		}

		wp_send_json_success(
			array(
				'correct' => $is_correct,
				'answer'  => (int) $round['correct'],
			)
		);
	}

	/**
	 * Drop rows that would make a question ambiguous.
	 *
	 * @param list<array<string,mixed>> $rows Candidate rows.
	 * @return list<array<string,mixed>>
	 */
	private function distinct_rows( array $rows ): array {
		$seen_words       = array();
		$seen_definitions = array();
		$out              = array();

		foreach ( $rows as $row ) {
			$word = strtolower( trim( (string) ( $row['word'] ?? '' ) ) );
			$def  = strtolower( trim( (string) ( $row['definition'] ?? '' ) ) );

			if ( '' === $word || '' === $def ) {
				continue;
			}
			if ( isset( $seen_words[ $word ] ) || isset( $seen_definitions[ $def ] ) ) {
				continue;
			}

			$seen_words[ $word ]       = true;
			$seen_definitions[ $def ]  = true;
			$out[]                     = $row;
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

	/**
	 * Simple per-visitor throttle.
	 *
	 * Each round is a database query, so an unthrottled endpoint is a cheap way
	 * to load the server. Keyed on the session rather than the user, since the
	 * quiz is playable logged out.
	 */
	private function within_rate_limit(): bool {
		$key   = 'efs_quiz_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_get_session_token() );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_MAX ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
