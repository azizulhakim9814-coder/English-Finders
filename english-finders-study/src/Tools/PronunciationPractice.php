<?php
/**
 * Pronunciation Practice.
 *
 * The Pronunciation category's first tool — the last of the six skill
 * categories that has been empty since 1.0.0. The learner is shown a word,
 * taps Speak, and says it aloud; the browser's own speech recognition
 * (`SpeechRecognition`/`webkitSpeechRecognition`) transcribes what it heard,
 * and the transcript is compared to the target word entirely in the browser.
 *
 * This is the one tool in the plugin with no `ajax_answer` handler and no
 * server-held-token round, and that is deliberate, not an oversight. Every
 * other tool hides its answer from the client because the client is shown
 * something *other* than the answer (a blank, an audio clip, a shuffled word
 * order) and could otherwise read the answer straight out of the page. Here
 * there is nothing to hide: the word is the prompt itself, shown as plain
 * text, and scoring happens against a browser-generated transcript that
 * never reaches this server at all — there is no channel to leak an answer
 * through, and no server-side state a token could usefully protect.
 *
 * Unlike `SpellingQuiz`, a failed audio lookup does not block serving a
 * round: the correct-pronunciation "Listen" button is a nice-to-have here,
 * not the prompt itself, so a word is served with or without it rather than
 * retrying a candidate pool.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Tools;

use EnglishFindersStudy\Contracts\ToolInterface;
use EnglishFindersStudy\Plugin;
use EnglishFindersStudy\Support\Breadcrumb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PronunciationPractice implements ToolInterface {
	/** Questions per session. A session that never ends has no sense of completion. */
	private const SESSION_LENGTH = 15;
	private const RATE_WINDOW    = MINUTE_IN_SECONDS;
	private const RATE_MAX       = 40;

	public function id(): string {
		return 'pronunciation-practice';
	}

	public function title(): string {
		return __( 'Pronunciation Practice', 'english-finders-study' );
	}

	public function shortcode_tag(): string {
		return 'efs_pronunciation_practice';
	}

	public function category(): string {
		return 'pronunciation';
	}

	/** @return list<string> */
	public function attributes(): array {
		return array( 'free' );
	}

	public function register(): void {
		add_shortcode( 'efs_pronunciation_practice', array( $this, 'shortcode' ) );
		add_action( 'wp_ajax_efs_pronunciation_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_nopriv_efs_pronunciation_round', array( $this, 'ajax_round' ) );
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
			'efs_pronunciation_practice'
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
		wp_enqueue_script( 'efs-pronunciation', EFS_URL . 'assets/js/pronunciation.js', array(), EFS_VERSION, true );
		wp_localize_script(
			'efs-pronunciation',
			'efsPronunciationConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'efs_pronunciation' ),
				'level'   => $level,
				'session' => self::SESSION_LENGTH,
				'i18n'    => array(
					'loading'     => __( 'Finding a word…', 'english-finders-study' ),
					'speak'       => __( 'Speak', 'english-finders-study' ),
					'listening'   => __( 'Listening…', 'english-finders-study' ),
					'listen'      => __( 'Listen', 'english-finders-study' ),
					'heardLabel'  => __( 'You said:', 'english-finders-study' ),
					'correct'     => __( 'Correct', 'english-finders-study' ),
					'wrong'       => __( 'Not quite', 'english-finders-study' ),
					'retry'       => __( 'Try again', 'english-finders-study' ),
					'next'        => __( 'Next word', 'english-finders-study' ),
					'error'       => __( 'The quiz could not load. Please try again.', 'english-finders-study' ),
					'score'       => __( 'Score', 'english-finders-study' ),
					'question'    => __( 'Question', 'english-finders-study' ),
					'done'        => __( 'Session complete', 'english-finders-study' ),
					'doneBody'    => __( 'You pronounced %1$s of %2$s words correctly.', 'english-finders-study' ),
					'restart'     => __( 'Start a new session', 'english-finders-study' ),
					'unsupported' => __( 'Your browser doesn\'t support speech recognition. Try Chrome or Edge on a desktop or Android device.', 'english-finders-study' ),
					'micDenied'   => __( 'Microphone access was blocked. Allow microphone access for this site, then try again.', 'english-finders-study' ),
					'noSpeech'    => __( 'Didn\'t catch that — try again.', 'english-finders-study' ),
					'micError'    => __( 'Something went wrong with the microphone. Try again.', 'english-finders-study' ),
					/* translators: %d: number of letters in the word. */
					'letters'     => __( '%d letters', 'english-finders-study' ),
				),
			)
		);

		$levels = $this->available_levels();

		$show_crumb  = ! isset( $atts['breadcrumb'] ) || false !== $atts['breadcrumb'];
		$show_schema = ! isset( $atts['schema'] ) || false !== $atts['schema'];
		$crumbs      = $show_crumb ? Breadcrumb::trail( __( 'Pronunciation Practice', 'english-finders-study' ) ) : array();

		ob_start();
		?>
		<section class="efs-quiz" data-efs-pronunciation>
			<header class="efs-quiz__header">
				<?php echo Breadcrumb::markup( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Breadcrumb. ?>

				<p class="efs-quiz__kicker"><?php esc_html_e( 'Pronunciation practice', 'english-finders-study' ); ?></p>
				<h2 class="efs-quiz__title"><?php esc_html_e( 'Pronunciation Practice', 'english-finders-study' ); ?></h2>
				<p class="efs-quiz__intro"><?php esc_html_e( 'Say the word out loud and see if your browser recognises it. Filter by level to practise at your own stage.', 'english-finders-study' ); ?></p>
			</header>

			<div class="efs-quiz__main">
				<?php if ( ! empty( $levels ) ) : ?>
					<label class="efs-quiz__level">
						<span><?php esc_html_e( 'Level', 'english-finders-study' ); ?></span>
						<select data-efs-pronunciation-level>
							<option value=""><?php esc_html_e( 'Any level', 'english-finders-study' ); ?></option>
							<?php foreach ( $levels as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $level, $option ); ?>>
									<?php echo esc_html( $option ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<div class="efs-quiz__body" data-efs-pronunciation-body aria-live="polite"></div>
				<p class="efs-quiz__score" data-efs-pronunciation-score></p>
			</div>
		</section>
		<?php
		$html = (string) ob_get_clean();

		if ( $show_schema && ! empty( $crumbs ) ) {
			$html .= Breadcrumb::schema( $crumbs );
		}

		return $html;
	}

	/**
	 * Serve one round: a word, shown in plain text, plus optional audio of
	 * its correct pronunciation. Nothing here is secret — see the class
	 * docblock for why this tool has no answer-checking endpoint at all.
	 */
	public function ajax_round(): void {
		check_ajax_referer( 'efs_pronunciation', 'nonce' );

		if ( ! $this->within_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'english-finders-study' ) ), 429 );
		}

		$level = $this->clean_level( isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '' );

		$repo = Plugin::instance()->core( 'words' );

		if ( ! is_object( $repo ) || ! method_exists( $repo, 'random_by_level' ) ) {
			wp_send_json_error( array( 'message' => __( 'Pronunciation practice is unavailable right now.', 'english-finders-study' ) ), 503 );
		}

		$rows = $repo->random_by_level( $level, 1 );
		$row  = $rows[0] ?? null;

		$word = null !== $row ? strtolower( trim( (string) ( $row['word'] ?? '' ) ) ) : '';
		if ( '' === $word ) {
			wp_send_json_error( array( 'message' => __( 'No words are available at that level yet.', 'english-finders-study' ) ), 404 );
		}

		// Audio is a bonus "hear the correct pronunciation" button, not the
		// prompt itself, so a resolution failure degrades gracefully rather
		// than blocking the round the way it would in Spelling Quiz.
		$audio_url = null;
		$audio     = Plugin::instance()->core( 'audio' );
		if ( is_object( $audio ) && method_exists( $audio, 'resolve' ) ) {
			$resolved = $audio->resolve( $word, array() );
			if ( null !== $resolved ) {
				$audio_url = $resolved['url'];
			}
		}

		wp_send_json_success(
			array(
				'word'     => $word,
				'level'    => (string) ( $row['cefr_level'] ?? '' ),
				'pos'      => (string) ( $row['part_of_speech'] ?? '' ),
				'audioUrl' => $audio_url,
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
	 * A tighter concern here than a plain content lookup: a round that
	 * resolves audio can trigger a real text-to-speech API call, so an
	 * unthrottled endpoint is a way to run up that bill, not just load the
	 * database — the same reasoning `SpellingQuiz` uses.
	 */
	private function within_rate_limit(): bool {
		$key   = 'efs_pronunciation_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_get_session_token() );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_MAX ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
