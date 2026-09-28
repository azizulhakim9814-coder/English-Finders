<?php
/**
 * Sentence Builder.
 *
 * The Writing category's first tool, filling the last of the two categories
 * empty since 1.0.0 (Pronunciation remains). The learner is given a
 * sentence's words in shuffled order and taps them into the correct sequence
 * — a word-order drill rather than a multiple-choice one, and deliberately
 * so: writing is a construction skill, and every other tool in this plugin
 * already asks the learner to recognise or select, never to build something
 * themselves.
 *
 * Correctness is exact by construction rather than by string comparison, but
 * the word ids sent to the browser deliberately do *not* encode the correct
 * order themselves — unlike, say, a naive scheme where the answer is just
 * "sort by id". Each word gets an opaque id from an independent random
 * permutation, decoupled from both its position in the correct sentence and
 * its position in the shuffled display order; only the server holds the
 * mapping (`expected`, in the round transient) from that permutation back to
 * the correct sequence. A client reading the raw AJAX response therefore sees
 * ids that carry no information about ordering at all — sorting by id
 * reconstructs nothing — matching the same server-held-answer principle every
 * other tool here uses (the correct option index, the spelled word, the
 * grammar explanation: never sent until after the round is scored).
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

final class SentenceBuilder implements ToolInterface {
	private const ROUND_TTL      = HOUR_IN_SECONDS;
	/** Questions per session. A session that never ends has no sense of completion. */
	private const SESSION_LENGTH = 10;
	private const RATE_WINDOW    = MINUTE_IN_SECONDS;
	private const RATE_MAX       = 40;
	/** Bounds how many previously-seen ids a client can send back. */
	private const MAX_EXCLUDE = 400;
	/** Bounds the submitted word order string, well above the longest item's word count. */
	private const MAX_ORDER = 40;

	public function id(): string {
		return 'sentence-builder';
	}

	public function title(): string {
		return __( 'Sentence Builder', 'english-finders-study' );
	}

	public function shortcode_tag(): string {
		return 'efs_sentence_builder';
	}

	public function category(): string {
		return 'writing';
	}

	/** @return list<string> */
	public function attributes(): array {
		return array( 'free' );
	}

	public function register(): void {
		add_shortcode( 'efs_sentence_builder', array( $this, 'shortcode' ) );
		add_action( 'wp_ajax_efs_sentence_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_nopriv_efs_sentence_round', array( $this, 'ajax_round' ) );
		add_action( 'wp_ajax_efs_sentence_answer', array( $this, 'ajax_answer' ) );
		add_action( 'wp_ajax_nopriv_efs_sentence_answer', array( $this, 'ajax_answer' ) );
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
			'efs_sentence_builder'
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
		wp_enqueue_script( 'efs-sentence', EFS_URL . 'assets/js/sentence.js', array(), EFS_VERSION, true );
		wp_localize_script(
			'efs-sentence',
			'efsSentenceConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'efs_sentence' ),
				'level'   => $level,
				'session' => self::SESSION_LENGTH,
				'i18n'    => array(
					'loading'  => __( 'Loading a sentence…', 'english-finders-study' ),
					'prompt'   => __( 'Tap the words in the correct order.', 'english-finders-study' ),
					'submit'   => __( 'Check', 'english-finders-study' ),
					'reset'    => __( 'Reset', 'english-finders-study' ),
					'correct'  => __( 'Correct', 'english-finders-study' ),
					'wrong'    => __( 'Not quite', 'english-finders-study' ),
					'wasLabel' => __( 'The correct sentence:', 'english-finders-study' ),
					'next'     => __( 'Next sentence', 'english-finders-study' ),
					'error'    => __( 'The quiz could not load. Please try again.', 'english-finders-study' ),
					'score'    => __( 'Score', 'english-finders-study' ),
					'question' => __( 'Question', 'english-finders-study' ),
					'done'     => __( 'Session complete', 'english-finders-study' ),
					'doneBody' => __( 'You built %1$s of %2$s sentences correctly.', 'english-finders-study' ),
					'restart'  => __( 'Start a new session', 'english-finders-study' ),
				),
			)
		);

		$levels = SentenceBank::available_levels();

		$show_crumb  = ! isset( $atts['breadcrumb'] ) || false !== $atts['breadcrumb'];
		$show_schema = ! isset( $atts['schema'] ) || false !== $atts['schema'];
		$crumbs      = $show_crumb ? Breadcrumb::trail( __( 'Sentence Builder', 'english-finders-study' ) ) : array();

		ob_start();
		?>
		<section class="efs-quiz" data-efs-sentence>
			<header class="efs-quiz__header">
				<?php echo Breadcrumb::markup( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Breadcrumb. ?>

				<p class="efs-quiz__kicker"><?php esc_html_e( 'Writing practice', 'english-finders-study' ); ?></p>
				<h2 class="efs-quiz__title"><?php esc_html_e( 'Sentence Builder', 'english-finders-study' ); ?></h2>
				<p class="efs-quiz__intro"><?php esc_html_e( 'Put the shuffled words back into the correct order. Filter by level to practise sentence structure at your own stage.', 'english-finders-study' ); ?></p>
			</header>

			<div class="efs-quiz__main">
				<?php if ( ! empty( $levels ) ) : ?>
					<label class="efs-quiz__level">
						<span><?php esc_html_e( 'Level', 'english-finders-study' ); ?></span>
						<select data-efs-sentence-level>
							<option value=""><?php esc_html_e( 'Any level', 'english-finders-study' ); ?></option>
							<?php foreach ( $levels as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $level, $option ); ?>>
									<?php echo esc_html( $option ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<div class="efs-quiz__body" data-efs-sentence-body aria-live="polite"></div>
				<p class="efs-quiz__score" data-efs-sentence-score></p>
			</div>
		</section>
		<?php
		$html = (string) ob_get_clean();

		if ( $show_schema && ! empty( $crumbs ) ) {
			$html .= Breadcrumb::schema( $crumbs );
		}

		return $html;
	}

	/** Serve one round. The words are sent shuffled; the correct order is never sent at all. */
	public function ajax_round(): void {
		check_ajax_referer( 'efs_sentence', 'nonce' );

		if ( ! $this->within_rate_limit() ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'english-finders-study' ) ), 429 );
		}

		$level   = $this->clean_level( isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '' );
		$exclude = $this->clean_exclude( isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '' );

		$pool = SentenceBank::by_level( $level );
		if ( empty( $pool ) ) {
			wp_send_json_error( array( 'message' => __( 'Not enough sentences at that level yet.', 'english-finders-study' ) ), 404 );
		}

		$available = array_values(
			array_filter(
				$pool,
				static fn ( array $item ): bool => ! in_array( $item['id'], $exclude, true )
			)
		);

		// The bank is small and curated; once every item at this level has
		// been seen, repeats are allowed rather than ending the session early.
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

		$item  = $available[ array_rand( $available ) ];
		$words = $item['words'];
		$count = count( $words );

		/*
		 * Two independent shuffles, deliberately kept separate:
		 * - `$ids` assigns each original word position an opaque id from a
		 *   random permutation. `$ids[$i]` is the id standing in for the word
		 *   at correct position `$i` — the correct submission is exactly the
		 *   sequence `$ids[0], $ids[1], …`, which only the server keeps.
		 * - `$display` is purely presentation: the order tokens are listed in
		 *   the response, unrelated to `$ids`, so the bank doesn't render in
		 *   the sentence's own original order either.
		 */
		$ids = range( 0, $count - 1 );
		shuffle( $ids );

		$display = range( 0, $count - 1 );
		shuffle( $display );

		$tokens = array();
		foreach ( $display as $original_index ) {
			$tokens[] = array( 'id' => $ids[ $original_index ], 'text' => $words[ $original_index ] );
		}

		$token = wp_generate_password( 20, false );
		set_transient(
			'efs_sentence_' . $token,
			array(
				'expected'    => $ids,
				'sentence'    => implode( ' ', $words ),
				'explanation' => (string) $item['explanation'],
				// Mistake-notebook snapshot: token id => word, so the learner's own order can be written back out.
				'id'          => (string) $item['id'],
				'level'       => (string) $item['level'],
				'words'       => array_combine( $ids, $words ),
				'shown'       => implode( ' / ', array_column( $tokens, 'text' ) ),
			),
			self::ROUND_TTL
		);

		wp_send_json_success(
			array(
				'token'  => $token,
				'id'     => $item['id'],
				'cycled' => $cycled,
				'topic'  => SentenceBank::topic_label( (string) $item['topic'] ),
				'level'  => $item['level'],
				'tokens' => $tokens,
			)
		);
	}

	/** Score an answer. */
	public function ajax_answer(): void {
		check_ajax_referer( 'efs_sentence', 'nonce' );

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$order = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';

		$round = '' !== $token ? get_transient( 'efs_sentence_' . $token ) : false;

		if ( ! is_array( $round ) ) {
			wp_send_json_error( array( 'message' => __( 'That round has expired. Try the next sentence.', 'english-finders-study' ) ), 410 );
		}

		// One answer per round, as with the other tools: otherwise a client
		// could retry combinations until it landed on the right one.
		delete_transient( 'efs_sentence_' . $token );

		$submitted = $this->clean_order( $order );
		$expected  = array_map( 'intval', (array) $round['expected'] );

		$is_correct = $submitted === $expected;
		if ( $is_correct ) {
			Activity::record_correct_answer( $this->id() );
		}

		// Rounds served before 1.13.0 carry no snapshot; skip the notebook for those rather than record a blank entry.
		if ( isset( $round['id'] ) ) {
			if ( $is_correct ) {
				Mistakes::record_correct( $this->id(), (string) $round['id'] );
			} else {
				$by_id = (array) $round['words'];
				$given = implode( ' ', array_map( static fn ( int $id ): string => (string) ( $by_id[ $id ] ?? '' ), $submitted ) );
				Mistakes::record_miss(
					$this->id(),
					(string) $round['id'],
					array(
						'skill'       => $this->category(),
						'level'       => (string) $round['level'],
						'prompt'      => (string) $round['shown'],
						'given'       => trim( $given ),
						'correct'     => (string) $round['sentence'],
						'explanation' => (string) $round['explanation'],
					)
				);
			}
		}

		wp_send_json_success(
			array(
				'correct'     => $is_correct,
				'sentence'    => (string) $round['sentence'],
				'explanation' => (string) $round['explanation'],
			)
		);
	}

	/**
	 * Parse the client's submitted word order into a list of ints.
	 *
	 * Bounded the same way `clean_exclude()` is: a malformed or oversized
	 * string simply fails the equality check below rather than needing its
	 * own error path, since nothing here reveals an answer either way.
	 *
	 * @return list<int>
	 */
	private function clean_order( string $raw ): array {
		if ( '' === $raw ) {
			return array();
		}

		$parts = array_slice( array_filter( array_map( 'trim', explode( ',', $raw ) ), 'is_numeric' ), 0, self::MAX_ORDER );

		return array_map( 'intval', array_values( $parts ) );
	}

	/**
	 * Item ids the client has already been asked this session.
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
		$key   = 'efs_sentence_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_get_session_token() );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_MAX ) {
			return false;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}
