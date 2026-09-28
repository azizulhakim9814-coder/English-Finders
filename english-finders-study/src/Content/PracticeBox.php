<?php
/**
 * "Practice what you just read" box at the end of blog posts (1.14.0).
 *
 * Learn -> Practice: most visitors arrive on articles (grammar and language
 * posts get 50-115 visitors a month each, the practice tools 10-29), and
 * only 7 of 335 posts linked a practice tool. This adds a short box after
 * every post, pointing at the tool that fits the post's category, plus the
 * free level test.
 *
 * Rules, first match wins (category slugs as used on englishfinders.com):
 *  - reading / literature / drama       -> Reading Quiz
 *  - writing / essays                   -> Sentence Builder
 *  - speaking / listening               -> Pronunciation Practice
 *  - vocabulary / idioms / phrasal verbs / positive words -> Vocabulary Quiz
 *  - grammar topics                     -> Grammar Quiz (A1-B1), or Error
 *    Correction (all levels) when the post is tagged B2, C1 or C2
 *  - anything else                      -> Vocabulary Quiz
 * No box on reviews / gear posts, on posts that already link a practice
 * tool or the level test, in feeds, excerpts, or anywhere but the post
 * itself. Same for every visitor, so cached pages are fine.
 *
 * Filters: efs_practice_box_enabled (bool, WP_Post), efs_practice_box_tool
 * (tool page slug, WP_Post; '' for no box).
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PracticeBox {
	/** Practice tool page slugs (as published) this box can point at. */
	public const TOOL_PAGES = array( 'grammar-quiz', 'error-correction', 'vocabulary-quiz', 'reading-quiz', 'sentence-builder', 'pronunciation-practice', 'spelling-quiz', 'match-the-definition' );

	public const LEVEL_TEST_PAGE = 'english-level-test';

	/** Category slug => tool page slug, checked in this order. */
	private const RULES = array(
		'reading'            => 'reading-quiz',
		'literature'         => 'reading-quiz',
		'drama'              => 'reading-quiz',
		'writing'            => 'sentence-builder',
		'essays'             => 'sentence-builder',
		'speaking'           => 'pronunciation-practice',
		'listening'          => 'pronunciation-practice',
		'vocabulary'         => 'vocabulary-quiz',
		'idioms-and-phrases' => 'vocabulary-quiz',
		'phrasal-verbs'      => 'vocabulary-quiz',
		'positive-words'     => 'vocabulary-quiz',
	);

	private const GRAMMAR = array( 'grammar', 'tenses', 'present-tenses', 'past-tenses', 'future-tenses', 'sentences', 'sentence-examples', 'parts-of-speech', 'nouns', 'verbs', 'pronouns', 'adjectives', 'adverbs', 'prepositions', 'conjunctions', 'articles', 'clauses', 'phrases', 'modal-verbs' );

	private const UPPER_LEVELS = array( 'b2-english-upper-intermediate', 'c1-english-advanced', 'c2-english-proficiency' );

	private const NO_BOX = array( 'reviews', 'gear' );

	private const FALLBACK = 'vocabulary-quiz';

	private static bool $style_printed = false;

	public function register(): void {
		add_filter( 'the_content', array( $this, 'append' ), 20 );
	}

	/** @param string $content */
	public function append( $content ) {
		$content = (string) $content;
		if ( ! self::is_post_body() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post instanceof \WP_Post || ! (bool) apply_filters( 'efs_practice_box_enabled', true, $post ) ) {
			return $content;
		}
		if ( self::links_practice( $content ) ) {
			return $content;
		}

		$slug = (string) apply_filters( 'efs_practice_box_tool', self::tool_for( self::category_slugs( $post ) ), $post );
		$tool = '' !== $slug ? self::page_url( $slug ) : '';
		if ( '' === $tool ) {
			return $content;
		}

		return $content . self::render( $slug, $tool, self::page_url( self::LEVEL_TEST_PAGE ) );
	}

	/**
	 * The tool page slug for a post's categories ('' = no box).
	 *
	 * @param list<string> $cats
	 */
	public static function tool_for( array $cats ): string {
		if ( array() !== array_intersect( $cats, self::NO_BOX ) ) {
			return '';
		}
		foreach ( self::RULES as $cat => $tool ) {
			if ( in_array( $cat, $cats, true ) ) {
				return $tool;
			}
		}
		if ( array() !== array_intersect( $cats, self::GRAMMAR ) ) {
			// The Grammar Quiz covers A1-B1; Error Correction covers every level.
			return array() !== array_intersect( $cats, self::UPPER_LEVELS ) ? 'error-correction' : 'grammar-quiz';
		}

		return self::FALLBACK;
	}

	/** Whether the post already links a practice tool or the level test. */
	public static function links_practice( string $content ): bool {
		$pages = implode( '|', array_map( 'preg_quote', array_merge( self::TOOL_PAGES, array( self::LEVEL_TEST_PAGE ) ) ) );

		return 1 === preg_match( '#/(' . $pages . ')/?["\'?\#]#', $content );
	}

	/** @return array{title:string,blurb:string,button:string} */
	public static function copy( string $slug ): array {
		$all = array(
			'grammar-quiz'           => array( __( 'Grammar Quiz', 'english-finders-study' ), __( 'Fill-in-the-blank questions with an instant explanation for each answer.', 'english-finders-study' ), __( 'Start the Grammar Quiz', 'english-finders-study' ) ),
			'error-correction'       => array( __( 'Error Correction', 'english-finders-study' ), __( 'Spot the mistake in real sentences, at every level from A1 to C2.', 'english-finders-study' ), __( 'Try Error Correction', 'english-finders-study' ) ),
			'vocabulary-quiz'        => array( __( 'Vocabulary Quiz', 'english-finders-study' ), __( 'Multiple-choice word meanings at every level from A1 to C2.', 'english-finders-study' ), __( 'Start the Vocabulary Quiz', 'english-finders-study' ) ),
			'reading-quiz'           => array( __( 'Reading Quiz', 'english-finders-study' ), __( 'Short passages with comprehension questions, from A1 to C2.', 'english-finders-study' ), __( 'Start the Reading Quiz', 'english-finders-study' ) ),
			'sentence-builder'       => array( __( 'Sentence Builder', 'english-finders-study' ), __( 'Put the words in the right order to build correct sentences.', 'english-finders-study' ), __( 'Try Sentence Builder', 'english-finders-study' ) ),
			'pronunciation-practice' => array( __( 'Pronunciation Practice', 'english-finders-study' ), __( 'Say a word out loud and check your pronunciation.', 'english-finders-study' ), __( 'Try Pronunciation Practice', 'english-finders-study' ) ),
			'spelling-quiz'          => array( __( 'Spelling Quiz', 'english-finders-study' ), __( 'Listen to a word and type it correctly.', 'english-finders-study' ), __( 'Start the Spelling Quiz', 'english-finders-study' ) ),
			'match-the-definition'   => array( __( 'Match the Definition', 'english-finders-study' ), __( 'Pair each word with its meaning.', 'english-finders-study' ), __( 'Try Match the Definition', 'english-finders-study' ) ),
		);
		$row = $all[ $slug ] ?? array( ucwords( str_replace( '-', ' ', $slug ) ), '', __( 'Start practising', 'english-finders-study' ) );

		return array(
			'title'  => $row[0],
			'blurb'  => $row[1],
			'button' => $row[2],
		);
	}

	private static function render( string $slug, string $tool_url, string $level_url ): string {
		$c   = self::copy( $slug );
		$out = '';
		if ( ! self::$style_printed ) {
			self::$style_printed = true;
			// Inline and data-no-optimize, like EFA's header styles: LiteSpeed's combined / "unused CSS" files are built ahead of time.
			$out .= '<style id="efs-practice-box-css" data-no-optimize="1">'
				. '.efs-practice-box{box-sizing:border-box;margin:36px 0 12px;padding:20px 22px;border:1px solid #d6e6f7;border-left:4px solid #075aae;border-radius:12px;background:#f0f6fc;font-family:Lexend,sans-serif;color:#0e2a4a}'
				. '.efs-practice-box p{margin:0}'
				. '.efs-practice-box .efs-practice-box__eyebrow{margin:0 0 6px;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#075aae}'
				. '.efs-practice-box .efs-practice-box__title{margin:0 0 4px;font-size:20px;font-weight:600;line-height:1.3;color:#0e2a4a}'
				. '.efs-practice-box .efs-practice-box__blurb{margin:0 0 16px;font-size:15px;line-height:1.55;color:#4d5c72}'
				. '.efs-practice-box a.efs-practice-box__btn{display:inline-flex;align-items:center;min-height:42px;padding:0 22px;border-radius:25px;background:#075aae;color:#fff;font-size:15px;font-weight:500;line-height:1.2;text-decoration:none}'
				. '.efs-practice-box a.efs-practice-box__btn:hover{background:#067ae0;color:#fff}'
				. '.efs-practice-box .efs-practice-box__level{margin:14px 0 0;font-size:14px;line-height:1.5;color:#4d5c72}'
				. '.efs-practice-box .efs-practice-box__level a{color:#075aae;font-weight:500;text-decoration:underline;text-underline-offset:3px}'
				. '.efs-practice-box a:focus-visible{outline:2px solid #075aae;outline-offset:2px}'
				. '</style>';
		}

		$out .= '<aside class="efs-practice-box" aria-label="' . esc_attr__( 'Practice what you just read', 'english-finders-study' ) . '">';
		$out .= '<p class="efs-practice-box__eyebrow">' . esc_html__( 'Practice what you just read', 'english-finders-study' ) . '</p>';
		$out .= '<p class="efs-practice-box__title">' . esc_html( $c['title'] ) . '</p>';
		if ( '' !== $c['blurb'] ) {
			$out .= '<p class="efs-practice-box__blurb">' . esc_html( $c['blurb'] ) . '</p>';
		}
		$out .= '<a class="efs-practice-box__btn" href="' . esc_url( $tool_url ) . '">' . esc_html( $c['button'] ) . ' &rarr;</a>';
		if ( '' !== $level_url ) {
			$out .= '<p class="efs-practice-box__level">' . esc_html__( 'Not sure of your level?', 'english-finders-study' ) . ' <a href="' . esc_url( $level_url ) . '">' . esc_html__( 'Take the free level test (about 10 minutes)', 'english-finders-study' ) . ' &rarr;</a></p>';
		}
		$out .= '</aside>';

		return $out;
	}

	/**
	 * The main post body on a single post -- not feeds, excerpts, admin, or
	 * other posts shown on the page (related posts etc.). "The post being
	 * filtered is the post this page is about" rather than in_the_loop():
	 * block themes render the post content outside the classic loop.
	 */
	private static function is_post_body(): bool {
		return ! is_admin()
			&& ! is_feed()
			&& is_singular( 'post' )
			&& is_main_query()
			&& (int) get_the_ID() > 0
			&& (int) get_the_ID() === (int) get_queried_object_id()
			&& ! doing_filter( 'get_the_excerpt' )
			&& ! doing_filter( 'wp_trim_excerpt' );
	}

	/** @return list<string> */
	private static function category_slugs( \WP_Post $post ): array {
		$out = array();
		foreach ( (array) get_the_category( $post->ID ) as $cat ) {
			if ( is_object( $cat ) && isset( $cat->slug ) ) {
				$out[] = (string) $cat->slug;
			}
		}

		return $out;
	}

	private static function page_url( string $slug ): string {
		$page = get_page_by_path( $slug );

		return ( $page instanceof \WP_Post && 'publish' === $page->post_status ) ? (string) get_permalink( $page ) : '';
	}
}
