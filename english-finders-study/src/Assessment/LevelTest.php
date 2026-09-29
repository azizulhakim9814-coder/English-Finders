<?php
/**
 * English Level Test (Phase A4).
 *
 * A free placement test that estimates a CEFR level for grammar,
 * vocabulary and reading, plus an overall level. Shortcode:
 * [efs_level_test].
 *
 * Deliberately not a ToolInterface tool registered in ToolCatalog: the
 * catalog's rule is exactly one skill category per tool, and this spans
 * three. Forcing it into one category would put a placement test in, say,
 * Grammar navigation, which is wrong in a way the catalog's own docblock
 * warns against. It has its own page instead.
 *
 * Grammar and reading questions come from the same hand-curated banks the
 * practice tools use (GrammarBank, ReadingBank). Vocabulary comes from its
 * own curated VocabularyBank rather than the dictionary: the dictionary's
 * stored definition is often a rare sense of the word, which would mark
 * down learners who know it -- see VocabularyBank. See LevelTestEngine for
 * how answers become a level.
 *
 * No WordPress nonce: this site's .htaccess sends HTML with a 90-day
 * browser cache, so a nonce baked into the page would routinely be days
 * past its 24-hour lifetime and fail every request. The 32-character
 * session token returned by the start request plays that role instead --
 * every later request must present it, and a cross-site page can trigger a
 * start but can never read the token out of the response to continue.
 *
 * Correct answers never reach the browser, and there is no per-question
 * feedback: this is a test, not a drill. The explanations belong to the
 * practice tools.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Assessment;

use EnglishFindersCore\Assessment\LevelScale;
use EnglishFindersStudy\Plugin;
use EnglishFindersStudy\Support\Breadcrumb;
use EnglishFindersStudy\Tools\GrammarBank;
use EnglishFindersStudy\Tools\ReadingBank;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LevelTest {
	/** Core 1.8.0 is the first with the `level_results` service and LevelScale. */
	public const MIN_CORE_VERSION = '1.8.0';

	/** Stored with every result, so a later change to the rules or content can be told apart. */
	public const TEST_VERSION = '1.0';

	public const SHORTCODE = 'efs_level_test';

	/** Anonymous result claim cookie. HttpOnly; its value is a Core claim token. */
	public const CLAIM_COOKIE = 'efs_level_claim';

	private const SESSION_TTL = 2 * HOUR_IN_SECONDS;

	/** Generous on purpose: a whole class behind one school IP can start the test in the same hour. */
	private const START_RATE_MAX    = 60;
	private const START_RATE_WINDOW = HOUR_IN_SECONDS;
	private const ANSWER_RATE_MAX   = 60;
	private const ANSWER_RATE_WINDOW = MINUTE_IN_SECONDS;

	private const OPTIONS = 4;

	public function register(): void {
		add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );
		add_action( 'wp_ajax_efs_level_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_nopriv_efs_level_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_efs_level_answer', array( $this, 'ajax_answer' ) );
		add_action( 'wp_ajax_nopriv_efs_level_answer', array( $this, 'ajax_answer' ) );

		/*
		 * Anonymous -> account linking (my-account-design.md): a result taken
		 * logged out is attached when the same browser registers or logs in.
		 * English Finders Account's own login/registration both go through
		 * wp_signon()/wp_insert_user(), which fire these same core hooks.
		 */
		add_action( 'wp_login', array( $this, 'claim_on_login' ), 10, 2 );
		add_action( 'user_register', array( $this, 'claim_on_register' ) );
	}

	/** Whether Core is new enough to store results (and provide LevelScale). */
	public function is_available(): bool {
		return class_exists( '\\EnglishFindersCore\\Support\\Api' )
			&& \EnglishFindersCore\Support\Api::is_at_least( self::MIN_CORE_VERSION );
	}

	/**
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public function shortcode( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'breadcrumb' => '1',
				'schema'     => '1',
			),
			is_array( $atts ) ? $atts : array(),
			self::SHORTCODE
		);

		return $this->render(
			array(
				'breadcrumb' => '0' !== (string) $atts['breadcrumb'],
				'schema'     => '0' !== (string) $atts['schema'],
			)
		);
	}

	/**
	 * @param array<string,mixed> $atts Attributes.
	 */
	public function render( array $atts = array() ): string {
		$show_crumb  = ! isset( $atts['breadcrumb'] ) || false !== $atts['breadcrumb'];
		$show_schema = ! isset( $atts['schema'] ) || false !== $atts['schema'];
		$crumbs      = $show_crumb ? Breadcrumb::trail( __( 'English Level Test', 'english-finders-study' ) ) : array();

		wp_enqueue_style( 'efs-quiz', EFS_URL . 'assets/css/quiz.css', array(), EFS_VERSION );

		if ( $this->is_available() ) {
			wp_enqueue_script( 'efs-level-test', EFS_URL . 'assets/js/level-test.js', array(), EFS_VERSION, true );
			wp_localize_script(
				'efs-level-test',
				'efsLevelTestConfig',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'i18n'    => array(
						'start'        => __( 'Start the test', 'english-finders-study' ),
						'loading'      => __( 'Loading…', 'english-finders-study' ),
						'error'        => __( 'The test could not load. Please try again.', 'english-finders-study' ),
						'question'     => __( 'Question %s', 'english-finders-study' ),
						'progress'     => __( 'Test progress', 'english-finders-study' ),
						'vocabPrompt'  => __( 'What does this word mean?', 'english-finders-study' ),
						'resultKicker' => __( 'Your estimated level', 'english-finders-study' ),
						'skills'       => __( 'By skill', 'english-finders-study' ),
						'answered'     => __( 'You answered %1$s of %2$s questions correctly.', 'english-finders-study' ),
						'saved'        => __( 'This result is saved to your account.', 'english-finders-study' ),
						'viewAccount'  => __( 'See it in My Level', 'english-finders-study' ),
						'anonSave'     => __( 'Create a free account or log in on this device within 30 days to keep this result.', 'english-finders-study' ),
						'signUp'       => __( 'Create a free account', 'english-finders-study' ),
						'notSaved'     => __( 'Your result could not be saved this time.', 'english-finders-study' ),
						'course'       => __( 'Start the %s course', 'english-finders-study' ),
						'retake'       => __( 'Take the test again', 'english-finders-study' ),
						'disclaimer'   => __( 'This is a free estimate from a short test, not an official qualification or certificate.', 'english-finders-study' ),
					),
				)
			);
		}

		ob_start();
		?>
		<?php
		/*
		 * `google-anno-skip`: AdSense auto ads turns keywords in page text into
		 * clickable ad chips (seen live on this page's header, 2026-09-23: a
		 * "History" chip after the kicker). Inside a test, an ad link in a
		 * question or option could take a learner off the page mid-test, so
		 * the whole test is marked as an area for AdSense to skip. Ads
		 * elsewhere on the page are unaffected.
		 */
		?>
		<section class="efs-quiz efs-level google-anno-skip" data-efs-level-test>
			<header class="efs-quiz__header">
				<?php echo Breadcrumb::markup( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Breadcrumb. ?>

				<p class="efs-quiz__kicker"><?php esc_html_e( 'Free placement test', 'english-finders-study' ); ?></p>
				<h2 class="efs-quiz__title"><?php esc_html_e( 'English Level Test', 'english-finders-study' ); ?></h2>
				<p class="efs-quiz__intro"><?php esc_html_e( 'Find your CEFR level (A1–C2) in grammar, vocabulary and reading. Questions get harder as you go, and the test ends when it finds your level.', 'english-finders-study' ); ?></p>
			</header>

			<div class="efs-quiz__main">
				<div class="efs-level__progress" data-efs-level-progress hidden>
					<div class="efs-level__progress-bar"><div class="efs-level__progress-fill" data-efs-level-progress-fill></div></div>
					<p class="efs-level__progress-text" data-efs-level-progress-text></p>
				</div>

				<div class="efs-quiz__body" data-efs-level-body aria-live="polite">
					<?php if ( $this->is_available() ) : ?>
						<ul class="efs-level__facts">
							<li><?php esc_html_e( 'Usually 15–35 questions, no time limit', 'english-finders-study' ); ?></li>
							<li><?php esc_html_e( 'Grammar, vocabulary and reading', 'english-finders-study' ); ?></li>
							<li><?php esc_html_e( 'Free — no account needed to take it', 'english-finders-study' ); ?></li>
						</ul>
						<p class="efs-level__hint"><?php esc_html_e( 'Answer without a dictionary. If you don\'t know an answer, choose the one you think is most likely — a guess tells the test more than stopping.', 'english-finders-study' ); ?></p>
						<button type="button" class="efs-quiz__next" data-efs-level-start><?php esc_html_e( 'Start the test', 'english-finders-study' ); ?></button>
					<?php else : ?>
						<p class="efs-quiz__message"><?php esc_html_e( 'The level test is not available right now. Please check back soon.', 'english-finders-study' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		$html = (string) ob_get_clean();

		if ( $show_schema && ! empty( $crumbs ) ) {
			$html .= Breadcrumb::schema( $crumbs );
		}

		return $html;
	}

	/** Begin a test: create the server-side session and serve the first question. */
	public function ajax_start(): void {
		if ( ! $this->is_available() ) {
			wp_send_json_error( array( 'message' => __( 'The level test is not available right now.', 'english-finders-study' ) ), 503 );
		}

		if ( ! $this->within_rate_limit( 'start', (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_get_session_token(), self::START_RATE_MAX, self::START_RATE_WINDOW ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many tests started. Please try again later.', 'english-finders-study' ) ), 429 );
		}

		$session = array(
			'state' => LevelTestEngine::start(),
			'asked' => array(
				'grammar'    => array(),
				'vocabulary' => array(),
				'reading'    => array(),
			),
			'seq'     => 0,
			'pending' => null,
		);

		$question = $this->next_question( $session );
		if ( null === $question ) {
			wp_send_json_error( array( 'message' => __( 'The test could not load. Please try again.', 'english-finders-study' ) ), 503 );
		}

		$token = wp_generate_password( 32, false );
		set_transient( $this->session_key( $token ), $session, self::SESSION_TTL );

		wp_send_json_success(
			array(
				'token'    => $token,
				'question' => $question,
				'progress' => LevelTestEngine::progress_percent( $session['state'] ),
			)
		);
	}

	/** Score one answer, then serve the next question or finish. */
	public function ajax_answer(): void {
		if ( ! $this->is_available() ) {
			wp_send_json_error( array( 'message' => __( 'The level test is not available right now.', 'english-finders-study' ) ), 503 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- The session token is the request credential; see the class docblock.
		$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$choice = isset( $_POST['choice'] ) ? absint( wp_unslash( $_POST['choice'] ) ) : -1;
		$seq    = isset( $_POST['seq'] ) ? absint( wp_unslash( $_POST['seq'] ) ) : -1;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$valid_token = 1 === preg_match( '/^[A-Za-z0-9]{32}$/', $token );

		/*
		 * Throttled per test session, not per IP: logged-out visitors have no
		 * WordPress session, so an IP key would make a whole classroom behind
		 * one school IP share a single budget and cut students off mid-test
		 * (found by running the test repeatedly in a real browser). Starting
		 * sessions is what's throttled per IP, so this can't be sidestepped by
		 * opening unlimited sessions.
		 */
		if ( $valid_token && ! $this->within_rate_limit( 'answer', $token, self::ANSWER_RATE_MAX, self::ANSWER_RATE_WINDOW ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'english-finders-study' ) ), 429 );
		}

		$session = $valid_token ? get_transient( $this->session_key( $token ) ) : false;

		if ( ! is_array( $session ) || ! is_array( $session['pending'] ?? null ) ) {
			wp_send_json_error( array( 'message' => __( 'This test has expired. Please start again.', 'english-finders-study' ) ), 410 );
		}

		/*
		 * The sequence number must match the question actually pending: a
		 * double-click or a replayed request answers nothing twice.
		 */
		if ( (int) $session['pending']['seq'] !== $seq ) {
			wp_send_json_error( array( 'message' => __( 'That question was already answered.', 'english-finders-study' ) ), 409 );
		}

		$pending          = $session['pending'];
		$session['state'] = LevelTestEngine::answer( $session['state'], (string) $pending['skill'], (int) $pending['correct'] === $choice );
		$session['pending'] = null;

		if ( LevelTestEngine::is_finished( $session['state'] ) ) {
			delete_transient( $this->session_key( $token ) );
			wp_send_json_success(
				array(
					'done'   => true,
					'result' => $this->finish( $session['state'] ),
				)
			);
		}

		$question = $this->next_question( $session );
		if ( null === $question ) {
			wp_send_json_error( array( 'message' => __( 'The test could not load the next question. Please try again.', 'english-finders-study' ) ), 503 );
		}

		set_transient( $this->session_key( $token ), $session, self::SESSION_TTL );

		wp_send_json_success(
			array(
				'done'     => false,
				'question' => $question,
				'progress' => LevelTestEngine::progress_percent( $session['state'] ),
			)
		);
	}

	/** @param \WP_User|mixed $user */
	public function claim_on_login( string $user_login, mixed $user = null ): void {
		if ( is_object( $user ) && isset( $user->ID ) ) {
			$this->claim_from_cookie( (int) $user->ID );
		}
	}

	public function claim_on_register( int $user_id ): void {
		$this->claim_from_cookie( $user_id );
	}

	/**
	 * Attach this browser's anonymous result, if any, to an account.
	 *
	 * @return int Rows claimed.
	 */
	public function claim_from_cookie( int $user_id ): int {
		$token = isset( $_COOKIE[ self::CLAIM_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::CLAIM_COOKIE ] ) ) : '';
		if ( '' === $token || $user_id <= 0 ) {
			return 0;
		}

		$results = Plugin::instance()->core( 'level_results' );
		if ( ! $results instanceof \EnglishFindersCore\Assessment\LevelResultRepository ) {
			return 0;
		}

		$claimed = $results->claim( $token, $user_id );

		// Cleared whether or not anything matched: a spent or expired token has no further use.
		$this->set_claim_cookie( '', time() - YEAR_IN_SECONDS );

		return $claimed;
	}

	/**
	 * Build the next question from the engine's next step, recording what
	 * is pending in the session. Returns the browser-safe payload.
	 *
	 * @param array<string,mixed> $session Passed by reference; updated in place.
	 * @return array<string,mixed>|null
	 */
	private function next_question( array &$session ): ?array {
		$step = LevelTestEngine::next( $session['state'] );
		if ( null === $step ) {
			return null;
		}

		$built = match ( $step['skill'] ) {
			'grammar'    => $this->grammar_question( $step['level'], $session['asked']['grammar'] ),
			'reading'    => $this->reading_question( $step['level'], $session['asked']['reading'] ),
			'vocabulary' => $this->vocabulary_question( $step['level'], $session['asked']['vocabulary'] ),
			default      => null,
		};

		if ( null === $built ) {
			return null;
		}

		$session['asked'][ $step['skill'] ][] = $built['id'];
		++$session['seq'];
		$session['pending'] = array(
			'seq'     => $session['seq'],
			'skill'   => $step['skill'],
			'correct' => $built['correct'],
		);

		return array_merge(
			$built['payload'],
			array(
				'seq'        => $session['seq'],
				'number'     => (int) $session['state']['answered'] + 1,
				'skill'      => $step['skill'],
				'skillLabel' => LevelScale::skill_label( $step['skill'] ),
			)
		);
	}

	/**
	 * @param list<string> $asked
	 * @return array{id:string,correct:int,payload:array<string,mixed>}|null
	 */
	private function grammar_question( string $level, array $asked ): ?array {
		$item = $this->pick( GrammarBank::by_level( $level ), $asked );
		if ( null === $item ) {
			return null;
		}

		list( $options, $correct ) = $this->shuffle_options( $item['options'], (int) $item['correct'] );

		return array(
			'id'      => (string) $item['id'],
			'correct' => $correct,
			'payload' => array(
				'type'    => 'grammar',
				'prompt'  => (string) $item['prompt'],
				'options' => $options,
			),
		);
	}

	/**
	 * @param list<string> $asked
	 * @return array{id:string,correct:int,payload:array<string,mixed>}|null
	 */
	private function reading_question( string $level, array $asked ): ?array {
		$item = $this->pick( ReadingBank::by_level( $level ), $asked );
		if ( null === $item ) {
			return null;
		}

		list( $options, $correct ) = $this->shuffle_options( $item['options'], (int) $item['correct'] );

		return array(
			'id'      => (string) $item['id'],
			'correct' => $correct,
			'payload' => array(
				'type'     => 'reading',
				'passage'  => (string) $item['passage'],
				'prompt'   => (string) $item['question'],
				'options'  => $options,
			),
		);
	}

	/**
	 * A word and four definitions from VocabularyBank: the word's own
	 * definition plus three other same-level words' definitions. Every option
	 * is a real definition of something; only one fits. Not the dictionary --
	 * see VocabularyBank's docblock for why.
	 *
	 * @param list<string> $asked Bank ids already used in this test.
	 * @return array{id:string,correct:int,payload:array<string,mixed>}|null
	 */
	private function vocabulary_question( string $level, array $asked ): ?array {
		$pool = VocabularyBank::by_level( $level );
		$item = $this->pick( $pool, $asked );
		if ( null === $item || count( $pool ) < self::OPTIONS ) {
			return null;
		}

		$others = array_values( array_filter( $pool, static fn ( array $other ): bool => $other['id'] !== $item['id'] ) );
		shuffle( $others );

		$definitions = array( (string) $item['definition'] );
		foreach ( array_slice( $others, 0, self::OPTIONS - 1 ) as $other ) {
			$definitions[] = (string) $other['definition'];
		}

		list( $options, $correct ) = $this->shuffle_options( $definitions, 0 );

		return array(
			'id'      => (string) $item['id'],
			'correct' => $correct,
			'payload' => array(
				'type'    => 'vocabulary',
				'word'    => (string) $item['word'],
				'pos'     => (string) $item['pos'],
				'options' => $options,
			),
		);
	}

	/**
	 * A random unused item. Falls back to the whole pool rather than failing
	 * if every item at the level was somehow used -- the engine asks at most
	 * three per skill per level, well under every bank's size.
	 *
	 * @param list<array<string,mixed>> $pool
	 * @param list<string>              $asked
	 * @return array<string,mixed>|null
	 */
	private function pick( array $pool, array $asked ): ?array {
		if ( array() === $pool ) {
			return null;
		}

		$available = array_values(
			array_filter(
				$pool,
				static fn ( array $item ): bool => ! in_array( (string) $item['id'], $asked, true )
			)
		);

		if ( array() === $available ) {
			$available = $pool;
		}

		return $available[ array_rand( $available ) ];
	}

	/**
	 * @param list<string> $options
	 * @return array{0:list<array{id:int,text:string}>,1:int}
	 */
	private function shuffle_options( array $options, int $correct_index ): array {
		$order = range( 0, count( $options ) - 1 );
		shuffle( $order );

		$out     = array();
		$correct = 0;
		foreach ( $order as $new_position => $old_position ) {
			$out[] = array(
				'id'   => $new_position,
				'text' => (string) $options[ $old_position ],
			);
			if ( $old_position === $correct_index ) {
				$correct = $new_position;
			}
		}

		return array( $out, $correct );
	}

	/**
	 * Store the result and shape it for the results screen.
	 *
	 * @param array<string,mixed> $state Finished engine state.
	 * @return array<string,mixed>
	 */
	private function finish( array $state ): array {
		$result  = LevelTestEngine::result( $state );
		$user_id = get_current_user_id();
		$saved   = false;

		$results = Plugin::instance()->core( 'level_results' );
		if ( $results instanceof \EnglishFindersCore\Assessment\LevelResultRepository ) {
			if ( $user_id > 0 ) {
				$saved = $results->record( $user_id, null, $result['skills'], $result['answered'], $result['correct'], $result['details'], self::TEST_VERSION ) > 0;
			} else {
				$claim = bin2hex( random_bytes( 16 ) );
				$saved = $results->record( null, $claim, $result['skills'], $result['answered'], $result['correct'], $result['details'], self::TEST_VERSION ) > 0;
				if ( $saved ) {
					$this->set_claim_cookie( $claim, time() + \EnglishFindersCore\Assessment\LevelResultRepository::CLAIM_WINDOW_DAYS * DAY_IN_SECONDS );
				}
			}
		}

		$skills = array();
		foreach ( $result['skills'] as $skill => $level ) {
			$skills[] = array(
				'skill' => $skill,
				'label' => LevelScale::skill_label( $skill ),
				'level' => $level,
				'short' => LevelScale::short_label( $level ),
				'index' => LevelScale::index( $level ),
			);
		}

		$course_level = 'PRE-A1' === $result['overall'] ? 'A1' : $result['overall'];

		return array(
			'overall'     => array(
				'level'       => $result['overall'],
				'short'       => LevelScale::short_label( $result['overall'] ),
				'label'       => LevelScale::label( $result['overall'] ),
				'description' => LevelScale::description( $result['overall'] ),
			),
			'skills'      => $skills,
			'answered'    => $result['answered'],
			'correct'     => $result['correct'],
			'saved'       => $saved,
			'loggedIn'    => $user_id > 0,
			'accountUrl'  => (string) apply_filters( 'efs_level_test_account_url', home_url( '/my-account/' ) ),
			'course'      => $this->course_for_level( $course_level ),
		);
	}

	/**
	 * The published Tutor LMS course for a CEFR level, matched by the level
	 * code in its title ("English Finders B1 — Intermediate"). Null when
	 * Tutor isn't active or no course matches, and the result screen simply
	 * omits the link.
	 *
	 * @return array{level:string,title:string,url:string}|null
	 */
	private function course_for_level( string $level ): ?array {
		if ( ! function_exists( 'get_posts' ) || ! post_type_exists( 'courses' ) ) {
			return null;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'courses',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( (array) $ids as $id ) {
			$title = (string) get_the_title( (int) $id );
			if ( 1 === preg_match( '/\b' . preg_quote( $level, '/' ) . '\b/', $title ) ) {
				return array(
					'level' => $level,
					'title' => $title,
					'url'   => (string) get_permalink( (int) $id ),
				);
			}
		}

		return null;
	}

	private function set_claim_cookie( string $value, int $expires ): void {
		if ( headers_sent() ) {
			return;
		}

		setcookie(
			self::CLAIM_COOKIE,
			$value,
			array(
				'expires'  => $expires,
				'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	private function session_key( string $token ): string {
		return 'efs_lt_' . $token;
	}

	/**
	 * Simple fixed-window throttle.
	 *
	 * @param string $subject What is being throttled: an IP + WordPress session for starts, a test session token for answers.
	 */
	private function within_rate_limit( string $bucket, string $subject, int $max, int $window ): bool {
		$key   = 'efs_lt_rate_' . $bucket . '_' . md5( $subject );
		$count = (int) get_transient( $key );

		if ( $count >= $max ) {
			return false;
		}

		set_transient( $key, $count + 1, $window );

		return true;
	}
}
