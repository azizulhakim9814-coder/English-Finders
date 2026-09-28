<?php
/**
 * Grammar quiz.
 *
 * A sentence with a blank and four options; the learner picks the word or
 * phrase that completes it correctly. Unlike the vocabulary tools, the
 * content is not drawn from the dictionary — there is no sentence-grammar
 * dataset anywhere in this codebase — so questions come from the small,
 * hand-curated bank in `GrammarBank` instead of a repository query.
 *
 * The correct answer is never sent to the browser, following the same
 * server-held-token pattern as the other tools. An explanation is revealed
 * alongside the answer, which the vocabulary tools do not have: a grammar
 * drill without a reason is a scored guess, not a lesson.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Tools;

use EnglishFindersStudy\Contracts\ToolInterface;
use EnglishFindersStudy\Support\Activity;
use EnglishFindersStudy\Support\Breadcrumb;
use EnglishFindersStudy\Support\Mistakes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GrammarQuiz implements ToolInterface {
	private const ROUND_TTL   = HOUR_IN_SECONDS;
	/** Questions per session. A session that never ends has no sense of completion. */
	private const SESSION_LENGTH = 12;
	private const RATE_WINDOW    = MINUTE_IN_SECONDS;
	private const RATE_MAX       = 40;
	/** Bounds how many previously-seen ids a client can send back. */
	private const MAX_EXCLUDE = 400;

	public function id(): string {
		return 'grammar-quiz';
	}

	public function title(): string {
		return __( 'Grammar Quiz', 'english-finders-study' );
	}

	public function shortcode_tag(): string {
		return 'efs_grammar_quiz';
	}

	public function category(): string {
		return 'grammar';
	}

	/** @return list<string> */
	public function attributes(): array {
		return array( 'free' );
	}

	public function register(): void {
		add_shortcode( 'efs_grammar_quiz', array( $this, 'shortcode' ) );
		add_action( 'wp_ajax_efs_grammar_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_nopriv_efs_grammar_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_efs_grammar_answer', array( $this, 'ajax_answer' ) );
		add_action( 'wp_ajax_nopriv_efs_grammar_answer', array( $this, 'ajax_answer' ) );
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
			'efs_grammar_quiz'
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
		 * Shares the quiz stylesheet, adding only the handful of rules a
		 * sentence-shaped prompt needs that a single word never did.
		 */
		wp_enqueue_style( 'efs-quiz', EFS_URL . 'assets/css/quiz.css', array(), EFS_VERSION );
		wp_enqueue_script( 'efs-grammar', EFS_URL . 'assets/js/grammar.js', array(), EFS_VERSION, true );
		wp_localize_script(
			'efs-grammar',
			'efsGrammarConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'efs_grammar' ),
				'level'   => $level,
				'session' => self::SESSION_LENGTH,
				'i18n'    => array(
					'loading'  => __( 'Loading a sentence…', 'english-finders-study' ),
					'correct'  => __( 'Correct', 'english-finders-study' ),
					'wrong'    => __( 'Not quite', 'english-finders-study' ),
					'next'     => __( 'Next sentence', 'english-finders-study' ),
					'error'    => __( 'The quiz could not load. Please try again.', 'english-finders-study' ),
					'score'    => __( 'Score', 'english-finders-study' ),
					'question' => __( 'Question', 'english-finders-study' ),
					'done'     => __( 'Session complete', 'english-finders-study' ),
					'doneBody' => __( 'You answered %1$s of %2$s correctly.', 'english-finders-study' ),
					'restart'  => __( 'Start a new session', 'english-finders-study' ),
				),
			)
		);

		$levels = GrammarBank::available_levels();

		$show_crumb  = ! isset( $atts['breadcrumb'] ) || false !== $atts['breadcrumb'];
		$show_schema = ! isset( $atts['schema'] ) || false !== $atts['schema'];
		$crumbs      = $show_crumb ? Breadcrumb::trail( __( 'Grammar Quiz', 'english-finders-study' ) ) : array();

		ob_start();
		?>
		<section class="efs-quiz" data-efs-grammar>
			<header class="efs-quiz__header">
				<?php echo Breadcrumb::markup( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Breadcrumb. ?>

				<p class="efs-quiz__kicker"><?php esc_html_e( 'Grammar practice', 'english-finders-study' ); ?></p>
				<h2 class="efs-quiz__title"><?php esc_html_e( 'Grammar Quiz', 'english-finders-study' ); ?></h2>
				<p class="efs-quiz__intro"><?php esc_html_e( 'Choose the word that completes each sentence correctly. Filter by level to practise grammar at your own stage.', 'english-finders-study' ); ?></p>
			</header>

			<div class="efs-quiz__main">
				<?php if ( ! empty( $levels ) ) : ?>
					<label class="efs-quiz__level">
						<span><?php esc_html_e( 'Level', 'english-finders-study' ); ?></span>
						<select data-efs-grammar-level>
							<option value=""><?php esc_html_e( 'Any level', 'english-finders-study' ); ?></option>
							<?php foreach ( $levels as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $level, $option ); ?>>
									<?php echo esc_html( $option ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<div class="efs-quiz__body" data-efs-grammar-body aria-live="polite"></div>
				<p class="efs-quiz__score" data-efs-grammar-score></p>
			</div>
		</section>
		<?php
		$html = (string) ob_get_clean();

		if ( $show_schema && ! empty( $crumbs ) ) {
			$html .= Breadcrumb::schema( $crumbs );
		}

		return $html;
	}

	/** Serve one round. The answer and explanation stay on the server. */
	public function ajax_round(): void {
		check_ajax_referer( 'efs_grammar', 'nonce' );

		if ( ! $this->within_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'english-finders-study' ) ), 429 );
		}

		$level   = $this->clean_level( isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '' );
		$exclude = $this->clean_exclude( isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '' );

		$pool = GrammarBank::by_level( $level );
		if ( empty( $pool ) ) {
			wp_send_json_error( array( 'message' => __( 'Not enough sentences at that level yet.', 'english-finders-study' ) ), 404 );
		}

		$available = array_values(
			array_filter(
				$pool,
				static fn ( array $item ): bool => ! in_array( $item['id'], $exclude, true )
			)
		);

		/*
		 * The bank is small and curated, unlike the dictionary the other tools
		 * draw from — a session can legitimately outlast one level's items.
		 * Once every item at this level has been seen, repeats are allowed
		 * rather than ending the session early.
		 */
		/*
		 * 1.16.0: the browser remembers what it has seen at each level across
		 * sessions and visits, so an empty $available means this learner has now
		 * seen the whole pool. Start a new shuffled cycle over every item except
		 * the one just shown (so a cycle never opens with an immediate repeat),
		 * and say so via `cycled`, which tells the browser to reset its record.
		 */
		$cycled = false;
		if ( empty( $available ) ) {
			$cycled    = true;
			$last      = (string) end( $exclude );
			$available = array_values(
				array_filter(
					$pool,
					static fn ( array $item ): bool => $item['id'] !== $last
				)
			);
			if ( empty( $available ) ) {
				$available = $pool;
			}
		}

		$item = $available[ array_rand( $available ) ];

		list( $options, $correct ) = $this->shuffle_options( $item['options'], (int) $item['correct'] );

		$token = wp_generate_password( 20, false );
		set_transient(
			'efs_grammar_' . $token,
			array(
				'correct'     => $correct,
				'explanation' => (string) $item['explanation'],
				// Mistake-notebook snapshot: what the learner saw, so a wrong answer can be recorded meaningfully.
				'id'          => (string) $item['id'],
				'level'       => (string) $item['level'],
				'prompt'      => (string) $item['prompt'],
				'options'     => $options,
			),
			self::ROUND_TTL
		);

		$out = array();
		foreach ( $options as $index => $text ) {
			$out[] = array( 'id' => $index, 'text' => $text );
		}

		wp_send_json_success(
			array(
				'token'   => $token,
				'id'      => $item['id'],
				'cycled'  => $cycled,
				'prompt'  => $item['prompt'],
				'topic'   => GrammarBank::topic_label( (string) $item['topic'] ),
				'level'   => $item['level'],
				'options' => $out,
			)
		);
	}

	/** Score an answer. */
	public function ajax_answer(): void {
		check_ajax_referer( 'efs_grammar', 'nonce' );

		$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$choice = isset( $_POST['choice'] ) ? absint( wp_unslash( $_POST['choice'] ) ) : -1;

		$round = '' !== $token ? get_transient( 'efs_grammar_' . $token ) : false;

		if ( ! is_array( $round ) ) {
			wp_send_json_error( array( 'message' => __( 'That round has expired. Try the next sentence.', 'english-finders-study' ) ), 410 );
		}

		// One answer per round, as with the other tools: otherwise a client
		// could retry until it hit the right option.
		delete_transient( 'efs_grammar_' . $token );

		$is_correct = (int) $round['correct'] === $choice;
		if ( $is_correct ) {
			Activity::record_correct_answer( $this->id() );
		}

		// Rounds served before 1.13.0 carry no snapshot; skip the notebook for those rather than record a blank entry.
		if ( isset( $round['id'] ) ) {
			if ( $is_correct ) {
				Mistakes::record_correct( $this->id(), (string) $round['id'] );
			} else {
				Mistakes::record_miss(
					$this->id(),
					(string) $round['id'],
					array(
						'skill'       => $this->category(),
						'level'       => (string) $round['level'],
						'prompt'      => (string) $round['prompt'],
						'given'       => (string) ( $round['options'][ $choice ] ?? '' ),
						'correct'     => (string) ( $round['options'][ (int) $round['correct'] ] ?? '' ),
						'explanation' => (string) $round['explanation'],
					)
				);
			}
		}

		wp_send_json_success(
			array(
				'correct'     => $is_correct,
				'answer'      => (int) $round['correct'],
				'explanation' => (string) $round['explanation'],
			)
		);
	}

	/**
	 * Reorder options and track where the correct one lands.
	 *
	 * The bank stores a fixed order per item; shuffling at serve time is what
	 * stops the same sentence always putting the answer in the same slot.
	 *
	 * @param list<string> $options Options in the bank's stored order.
	 * @return array{0:list<string>,1:int}
	 */
	private function shuffle_options( array $options, int $correct_index ): array {
		$order = range( 0, count( $options ) - 1 );
		shuffle( $order );

		$shuffled = array();
		$correct  = 0;

		foreach ( $order as $new_position => $old_position ) {
			$shuffled[] = $options[ $old_position ];
			if ( $old_position === $correct_index ) {
				$correct = $new_position;
			}
		}

		return array( $shuffled, $correct );
	}

	/**
	 * Item ids the client has already been asked this session.
	 *
	 * Trusting a client-reported exclusion list has no security cost — at
	 * worst a client lies and sees more repeats, since nothing here reveals
	 * an answer. It only needs bounding so a hostile request cannot pass an
	 * arbitrarily large string through `explode()`.
	 *
	 * @return list<string>
	 */
	private function clean_exclude( string $raw ): array {
		if ( '' === $raw ) {
			return array();
		}

		$ids = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

		return array_slice( array_values( $ids ), 0, self::MAX_EXCLUDE );
	}

	private function clean_level( string $level ): string {
		$level = strtoupper( trim( $level ) );

		return in_array( $level, array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' ), true ) ? $level : '';
	}

	/**
	 * Simple per-visitor throttle.
	 *
	 * Keyed on the session rather than the user, since the quiz is playable
	 * logged out.
	 */
	private function within_rate_limit(): bool {
		$key   = 'efs_grammar_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_get_session_token() );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_MAX ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
