<?php
/**
 * Spelling quiz.
 *
 * The learner hears a word and types what they heard. Unlike the other
 * tools, the correct answer cannot be shown as text anywhere in the round —
 * revealing it, even as a hidden attribute, would let a determined user read
 * it straight out of the page instead of listening. So the round never
 * carries the word itself, only a URL to audio of it, generated through
 * Core's existing text-to-speech pipeline (`Api::service('audio')`) — the
 * same one the Word Details page's pronunciation button already uses.
 *
 * Recorded-audio lookup (Level 1 of Core's resolution chain) needs a live,
 * per-word call to an external dictionary API, which this tool has no reason
 * to pay for: generated speech (Level 2) is enough to spell from and is
 * cached after the first request, so `resolve()` is always called with an
 * empty candidate list to go straight there.
 *
 * A word actually chosen at random can have no audio yet and fail to
 * generate (provider hiccup, rate limit). Rather than surface that mid-round,
 * `ajax_round()` tries a small pool of candidates and returns the first one
 * that resolves, so an occasional generation failure is invisible to the
 * learner instead of a dead end.
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

final class SpellingQuiz implements ToolInterface {
	private const ROUND_TTL   = HOUR_IN_SECONDS;
	/** Questions per session. A session that never ends has no sense of completion. */
	private const SESSION_LENGTH = 15;
	private const RATE_WINDOW    = MINUTE_IN_SECONDS;
	private const RATE_MAX       = 40;
	/**
	 * Candidate words tried per round before giving up.
	 *
	 * Generating speech for an uncached word is one live API call; trying
	 * several sequentially in one request bounds that cost while still
	 * covering the case where the first candidate's generation fails.
	 */
	private const CANDIDATE_POOL = 4;

	public function id(): string {
		return 'spelling-quiz';
	}

	public function title(): string {
		return __( 'Spelling Quiz', 'english-finders-study' );
	}

	public function shortcode_tag(): string {
		return 'efs_spelling_quiz';
	}

	public function category(): string {
		return 'spelling';
	}

	/** @return list<string> */
	public function attributes(): array {
		return array( 'free' );
	}

	public function register(): void {
		add_shortcode( 'efs_spelling_quiz', array( $this, 'shortcode' ) );
		add_action( 'wp_ajax_efs_spelling_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_nopriv_efs_spelling_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_efs_spelling_answer', array( $this, 'ajax_answer' ) );
		add_action( 'wp_ajax_nopriv_efs_spelling_answer', array( $this, 'ajax_answer' ) );
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
			'efs_spelling_quiz'
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
		wp_enqueue_script( 'efs-spelling', EFS_URL . 'assets/js/spelling.js', array(), EFS_VERSION, true );
		wp_localize_script(
			'efs-spelling',
			'efsSpellingConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'efs_spelling' ),
				'level'   => $level,
				'session' => self::SESSION_LENGTH,
				'i18n'    => array(
					'loading'   => __( 'Finding a word…', 'english-finders-study' ),
					'listen'    => __( 'Listen', 'english-finders-study' ),
					'replay'    => __( 'Play again', 'english-finders-study' ),
					'placeholder' => __( 'Type what you hear', 'english-finders-study' ),
					'submit'    => __( 'Check', 'english-finders-study' ),
					'correct'   => __( 'Correct', 'english-finders-study' ),
					'wrong'     => __( 'Not quite', 'english-finders-study' ),
					'wasLabel'  => __( 'The word was:', 'english-finders-study' ),
					'next'      => __( 'Next word', 'english-finders-study' ),
					'error'     => __( 'The quiz could not load. Please try again.', 'english-finders-study' ),
					'score'     => __( 'Score', 'english-finders-study' ),
					'question'  => __( 'Question', 'english-finders-study' ),
					'done'      => __( 'Session complete', 'english-finders-study' ),
					'doneBody'  => __( 'You spelled %1$s of %2$s correctly.', 'english-finders-study' ),
					'restart'   => __( 'Start a new session', 'english-finders-study' ),
					/* translators: %d: number of letters in the word. */
					'letters'   => __( '%d letters', 'english-finders-study' ),
				),
			)
		);

		$levels = $this->available_levels();

		$show_crumb  = ! isset( $atts['breadcrumb'] ) || false !== $atts['breadcrumb'];
		$show_schema = ! isset( $atts['schema'] ) || false !== $atts['schema'];
		$crumbs      = $show_crumb ? Breadcrumb::trail( __( 'Spelling Quiz', 'english-finders-study' ) ) : array();

		ob_start();
		?>
		<section class="efs-quiz" data-efs-spelling>
			<header class="efs-quiz__header">
				<?php echo Breadcrumb::markup( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Breadcrumb. ?>

				<p class="efs-quiz__kicker"><?php esc_html_e( 'Spelling practice', 'english-finders-study' ); ?></p>
				<h2 class="efs-quiz__title"><?php esc_html_e( 'Spelling Quiz', 'english-finders-study' ); ?></h2>
				<p class="efs-quiz__intro"><?php esc_html_e( 'Listen to the word and type what you hear. Filter by level to practise spelling at your own stage.', 'english-finders-study' ); ?></p>
			</header>

			<div class="efs-quiz__main">
				<?php if ( ! empty( $levels ) ) : ?>
					<label class="efs-quiz__level">
						<span><?php esc_html_e( 'Level', 'english-finders-study' ); ?></span>
						<select data-efs-spelling-level>
							<option value=""><?php esc_html_e( 'Any level', 'english-finders-study' ); ?></option>
							<?php foreach ( $levels as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $level, $option ); ?>>
									<?php echo esc_html( $option ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<div class="efs-quiz__body" data-efs-spelling-body aria-live="polite"></div>
				<p class="efs-quiz__score" data-efs-spelling-score></p>
			</div>
		</section>
		<?php
		$html = (string) ob_get_clean();

		if ( $show_schema && ! empty( $crumbs ) ) {
			$html .= Breadcrumb::schema( $crumbs );
		}

		return $html;
	}

	/** Serve one round. The word itself never leaves the server — only audio of it. */
	public function ajax_round(): void {
		check_ajax_referer( 'efs_spelling', 'nonce' );

		if ( ! $this->within_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'english-finders-study' ) ), 429 );
		}

		$level = $this->clean_level( isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '' );

		$repo  = Plugin::instance()->core( 'words' );
		$audio = Plugin::instance()->core( 'audio' );

		if ( ! is_object( $repo ) || ! method_exists( $repo, 'random_by_level' ) || ! is_object( $audio ) || ! method_exists( $audio, 'resolve' ) ) {
			wp_send_json_error( array( 'message' => __( 'Spelling practice is unavailable right now.', 'english-finders-study' ) ), 503 );
		}

		$rows = $repo->random_by_level( $level, self::CANDIDATE_POOL );

		foreach ( $rows as $row ) {
			$word = strtolower( trim( (string) ( $row['word'] ?? '' ) ) );
			if ( '' === $word ) {
				continue;
			}

			// Generated speech only (Level 2) — see the class docblock for why
			// recorded-audio lookup (Level 1) is skipped here.
			$resolved = $audio->resolve( $word, array() );
			if ( null === $resolved ) {
				continue;
			}

			$token = wp_generate_password( 20, false );
			set_transient(
				'efs_spelling_' . $token,
				array(
					'correct' => $word,
					// Mistake-notebook snapshot.
					'level'   => (string) ( $row['cefr_level'] ?? '' ),
					'pos'     => (string) ( $row['part_of_speech'] ?? '' ),
				),
				self::ROUND_TTL
			);

			wp_send_json_success(
				array(
					'token'    => $token,
					'audioUrl' => $resolved['url'],
					'length'   => strlen( $word ),
					'level'    => (string) ( $row['cefr_level'] ?? '' ),
					'pos'      => (string) ( $row['part_of_speech'] ?? '' ),
				)
			);
		}

		wp_send_json_error( array( 'message' => __( 'Audio is not available right now. Please try again.', 'english-finders-study' ) ), 503 );
	}

	/** Score an answer. */
	public function ajax_answer(): void {
		check_ajax_referer( 'efs_spelling', 'nonce' );

		$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$answer = isset( $_POST['answer'] ) ? sanitize_text_field( wp_unslash( $_POST['answer'] ) ) : '';

		$round = '' !== $token ? get_transient( 'efs_spelling_' . $token ) : false;

		if ( ! is_array( $round ) ) {
			wp_send_json_error( array( 'message' => __( 'That round has expired. Try the next word.', 'english-finders-study' ) ), 410 );
		}

		// One answer per round, as with the other tools: otherwise a client
		// could retry with a corrected spelling until it matched.
		delete_transient( 'efs_spelling_' . $token );

		$correct = (string) $round['correct'];
		$given   = strtolower( trim( $answer ) );

		$is_correct = $given === $correct;
		if ( $is_correct ) {
			Activity::record_correct_answer( $this->id() );
		}

		// Keyed by word; pre-1.13.0 rounds (no 'level' key) are skipped.
		if ( array_key_exists( 'level', $round ) ) {
			$key = 'word:' . $correct;
			if ( $is_correct ) {
				Mistakes::record_correct( $this->id(), $key );
			} else {
				$pos = (string) $round['pos'];
				Mistakes::record_miss(
					$this->id(),
					$key,
					array(
						'skill'   => $this->category(),
						'level'   => (string) $round['level'],
						/* translators: %s: part of speech, e.g. "noun" */
						'prompt'  => '' !== $pos ? sprintf( __( 'Spell the word you heard (%s).', 'english-finders-study' ), $pos ) : __( 'Spell the word you heard.', 'english-finders-study' ),
						'given'   => '' !== $given ? $given : __( '(no answer)', 'english-finders-study' ),
						'correct' => $correct,
					)
				);
			}
		}

		wp_send_json_success(
			array(
				'correct' => $is_correct,
				'answer'  => $correct,
			)
		);
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
	 * A tighter concern here than on the other tools: a round that resolves
	 * can trigger a real text-to-speech API call, so an unthrottled endpoint
	 * is a way to run up that bill, not just load the database. Keyed on the
	 * session rather than the user, since the quiz is playable logged out.
	 */
	private function within_rate_limit(): bool {
		$key   = 'efs_spelling_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_get_session_token() );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_MAX ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
