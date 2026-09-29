// Skill hubs: /reading/, /writing/, /speaking/, /listening/ (send after lib.php + levels.php).
// Each page: hero, practice tools, the skill's lessons from the A1–C2 courses, the skill's
// articles, then one embedded practice tool.
$efl_skills = array(
	'reading'   => array(
		'Reading',
		'Reading regularly is one of the fastest ways to build vocabulary and understanding. Practise with graded passages, course lessons and tips.',
		array( '#F59E0B', '#EA580C', 'fa-book-reader' ),
		array( 'reading-quiz', 'vocabulary-quiz', 'match-the-definition' ),
		array( 35016, 35107, 35313, 35408, 35500, 35591 ),
		array( 'reading' ),
		'[efs_reading_quiz breadcrumb="0" schema="0"]',
	),
	'writing'   => array(
		'Writing',
		'Good writing turns what you know into something a reader can follow. Build sentences, fix mistakes and learn to write messages, emails and essays.',
		array( '#059669', '#0D9488', 'fa-pen-nib' ),
		array( 'writing-feedback', 'sentence-builder', 'error-correction', 'grammar-quiz', 'spelling-quiz' ),
		array( 35009, 35095, 35102, 35307, 35308, 35388, 35402, 35495, 35586 ),
		array( 'writing', 'essays' ),
		'[efs_sentence_builder breadcrumb="0" schema="0"]',
	),
	'speaking'  => array(
		'Speaking',
		'Speaking confidently takes practice, not just knowledge. Practise pronunciation, conversation and real-life speaking tasks from A1 to C2.',
		array( '#DB2777', '#9D174D', 'fa-comments' ),
		array( 'pronunciation-practice', 'english-dictionary-search' ),
		array( 34922, 35002, 35089, 35301, 35542, 35572 ),
		array( 'speaking' ),
		'[efs_pronunciation_practice breadcrumb="0" schema="0"]',
	),
	'listening' => array(
		'Listening',
		'Understanding spoken English is a skill of its own. Train your ear with audio practice, listening lessons and practical tips.',
		array( '#0EA5E9', '#2563EB', 'fa-headphones' ),
		array( 'spelling-quiz', 'pronunciation-practice', 'english-dictionary-search' ),
		array( 35016, 35523, 35534 ),
		array( 'listening' ),
		'[efs_spelling_quiz breadcrumb="0" schema="0"]',
	),
);
$efl_tool_cards = array(
	'grammar-quiz'              => array( 'Grammar Quiz', '#4F46E5', '#7C3AED', 'fa-spell-check', 'Grammar', 'Test your grammar with simple questions and instant answers, then review what you got wrong.' ),
	'vocabulary-quiz'           => array( 'Vocabulary Quiz', '#10B981', '#059669', 'fa-book-open', 'Vocabulary', 'Learn new words and see how much vocabulary you really know.' ),
	'reading-quiz'              => array( 'Reading Quiz', '#F59E0B', '#EA580C', 'fa-book-reader', 'Reading', 'Read short passages and answer questions to test your understanding, at every level from A1 to C2.' ),
	'spelling-quiz'             => array( 'Spelling Quiz', '#0EA5E9', '#2563EB', 'fa-keyboard', 'Listening', 'Listen to a word and type it: it trains your ear and your spelling at the same time.' ),
	'match-the-definition'      => array( 'Match The Definition', '#EC4899', '#DB2777', 'fa-link', 'Vocabulary', 'Connect words with their correct meanings to build the vocabulary you need for reading.' ),
	'error-correction'          => array( 'Error Correction', '#7C3AED', '#4338CA', 'fa-check-double', 'Grammar', 'Spot and fix the mistake in each sentence, the same skill you use to check your own writing.' ),
	'sentence-builder'          => array( 'Sentence Builder', '#059669', '#0D9488', 'fa-arrows-turn-to-dots', 'Writing', 'Put the words in the right order to build correct sentences.' ),
	'writing-feedback'          => array( 'AI Writing Feedback', '#14B8A6', '#0E7490', 'fa-pen-fancy', 'Writing', 'Write a short text at your level and get corrections with explanations, a corrected version and one thing to work on next.' ),
	'pronunciation-practice'    => array( 'Pronunciation Practice', '#DB2777', '#9D174D', 'fa-microphone', 'Speaking', 'Listen to native audio, say the word out loud and check your pronunciation.' ),
	'english-dictionary-search' => array( 'English Dictionary', '#64748B', '#334155', 'fa-volume-up', 'Audio', 'Look up any word to hear its pronunciation, with meanings, examples and its CEFR level.' ),
);
if ( ! function_exists( 'efl_lesson_desc' ) ) {
	/** Card copy for a lesson: its meta description minus the "Title (A1 Beginner English): " style prefix. */
	function efl_lesson_desc( $id ) {
		$d = (string) get_post_meta( $id, 'rank_math_description', true );
		$d = preg_replace( '/^.{0,120}?\((A1|A2|B1|B2|C1|C2) [^)]*English\):\s*/u', '', $d );
		$d = preg_replace( '/^.{0,120}?\| Free [^.]{0,60} course\.\s*/u', '', $d );
		$d = preg_replace( '/^(A1|A2|B1|B2|C1|C2) Can-Do task: [^.]{0,120}\.\s*/u', '', $d );
		$d = preg_replace( '/^Reading Passage\s*/u', '', $d );
		return '' !== trim( $d ) ? $d : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 28 );
	}
	function efl_article_desc( $post ) {
		$d = (string) get_post_meta( $post->ID, 'rank_math_description', true );
		if ( '' === trim( $d ) ) {
			$d = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 28 );
		}
		return html_entity_decode( $d, ENT_QUOTES, 'UTF-8' );
	}
}
$test = get_permalink( get_page_by_path( 'english-level-test' ) );
$only = isset( $efl_only ) ? $efl_only : array_keys( $efl_skills );
$out  = array();
foreach ( $only as $slug ) {
	$s    = $efl_skills[ $slug ];
	$name = $s[0];
	$lc   = strtolower( $name );

	$tools = array();
	foreach ( $s[3] as $t ) {
		$c       = $efl_tool_cards[ $t ];
		$tools[] = efl_card( $c[1], $c[2], $c[3], array( $c[4], 'Free' ), $c[0], $c[5], home_url( '/' . $t . '/' ), 'Open' );
	}
	$tools[] = efl_card( '#64748B', '#334155', 'fa-clipboard-check', array( 'Free', 'A1 to C2' ), 'English Level Test', 'Find your CEFR level in about 10 minutes, then practise ' . $lc . ' at the right level.', $test, 'Take the' );
	$tools[] = efl_card( '#035DA2', '#003bb1', 'fa-layer-group', array( 'A1 to C2' ), 'Learn by Level', 'Everything for your level in one place: the course, graded words, articles and practice.', home_url( '/learn/' ), 'Explore' );

	$lessons = array();
	$l_meta  = array();
	foreach ( $s[4] as $lid ) {
		$lp = get_post( $lid );
		if ( ! $lp || 'publish' !== $lp->post_status ) {
			continue;
		}
		$course = get_post( (int) get_post_field( 'post_parent', $lp->post_parent ) );
		$L      = ( $course && preg_match( '/\b(A1|A2|B1|B2|C1|C2)\b/', $course->post_title, $m ) ) ? $m[1] : '';
		$lv     = isset( $efl_levels[ $L ] ) ? $efl_levels[ $L ] : array( '', 0, '#64748B', '#334155', 'fa-book' );
		$kind   = preg_match( '/^Can-Do Task/i', $lp->post_title ) ? 'Can-Do task' : ( 'reading' === $slug && preg_match( '/Reading (Passage|&)/i', $lp->post_title ) ? 'Reading passage' : 'Lesson' );
		$title     = preg_replace( '/^Can-Do Task:\s*/i', '', html_entity_decode( $lp->post_title, ENT_QUOTES, 'UTF-8' ) );
		$lessons[] = efl_card( $lv[2], $lv[3], $s[2][2], array_filter( array( $L . ( $lv[0] ? ' ' . $lv[0] : '' ), $kind ) ), $title, efl_lesson_desc( $lid ), get_permalink( $lid ), 'Open' );
		$l_meta[]  = $L . ' ' . $kind . ' | ' . $title . ' | ' . mb_substr( efl_lesson_desc( $lid ), 0, 90 );
	}

	$articles = array();
	$a_meta   = array();
	$posts    = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 60, 'category_name' => implode( ',', $s[5] ), 'orderby' => 'modified', 'order' => 'DESC' ) );
	foreach ( $posts as $p ) {
		$cats       = wp_get_post_categories( $p->ID, array( 'fields' => 'slugs' ) );
		$pills      = in_array( 'ielts', $cats, true ) ? array( 'Article', 'IELTS' ) : array( 'Article', $name );
		$articles[] = efl_card( $s[2][0], $s[2][1], 'fa-newspaper', $pills, html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ), efl_article_desc( $p ), get_permalink( $p ), 'Read' );
		$a_meta[]   = $pills[1] . ' | ' . html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ) . ' | ' . mb_substr( efl_article_desc( $p ), 0, 60 );
	}

	$sections   = array( efl_hero( 'English ' . $name, $s[1] ) );
	$sections[] = efl_section( array_merge( efl_w_heading( 'Practise ' . $lc, 'Free tools with instant feedback. Choose your level inside each tool.' ), array( efl_w_html( efl_grid( $tools, 12 ) ) ) ), 40, 16 );
	if ( $lessons ) {
		$sections[] = efl_section( array_merge( efl_w_heading( $name . ' lessons, A1 to C2', 'Free lessons from our CEFR courses, with examples and a task to try.' ), array( efl_w_html( efl_grid( $lessons, 12 ) ) ) ), 32, 16 );
	}
	if ( $articles ) {
		$sections[] = efl_section( array_merge( efl_w_heading( $name . ' tips and articles' ), array( efl_w_html( efl_grid( $articles, 12 ) ) ) ), 32, 16 );
	}
	$sections[] = efl_section( array_merge( efl_w_heading( 'Try it now', 'A quick practice round right here on the page.' ), array( efl_w_shortcode( $s[6] ) ) ), 32, 48 );

	if ( ! empty( $efl_dry ) ) {
		$out[ $slug ] = array( 'tools' => count( $tools ), 'lessons' => $l_meta, 'articles' => $a_meta );
		continue;
	}
	$existing = get_page_by_path( $slug );
	$desc     = $existing ? (string) get_post_meta( $existing->ID, 'rank_math_description', true ) : '';
	$res      = efl_save_page( $slug, $name, 0, $sections, $desc, 'English ' . $lc );
	if ( is_array( $res ) ) {
		$res['tools']    = count( $tools );
		$res['lessons']  = count( $lessons );
		$res['articles'] = count( $articles );
	}
	$out[ $slug ] = $res;
}
if ( empty( $efl_dry ) ) {
	// Add the skill pages to the hover-lift CSS block shared with the Learn hubs.
	$css = wp_get_custom_css();
	$ids = preg_match( '/EF-LEARN-HUBS-START \*\/(.*?)\/\* EF-LEARN-HUBS-END/s', $css, $m ) && preg_match_all( '/page-id-(\d+)/', $m[1], $mm ) ? array_map( 'intval', $mm[1] ) : array();
	foreach ( $out as $r ) {
		if ( is_array( $r ) ) {
			$ids[] = (int) $r['id'];
		}
	}
	$ids   = array_values( array_unique( $ids ) );
	$sel   = function ( $suffix ) use ( $ids ) {
		return implode( ', ', array_map( fn( $i ) => '.page-id-' . $i . ' .ef-game-card' . $suffix, $ids ) );
	};
	$block = "\n/* EF-LEARN-HUBS-START */\n"
		. $sel( '' ) . " { transition: transform .25s ease, box-shadow .25s ease; }\n"
		. $sel( ':hover' ) . " { transform: translateY(-6px); box-shadow: 0 12px 24px rgba(16,24,40,.12); }\n"
		. $sel( ' a:hover span' ) . " { text-decoration: underline; }\n"
		. "/* EF-LEARN-HUBS-END */\n";
	$css   = preg_replace( '/\n?\/\* EF-LEARN-HUBS-START \*\/.*?\/\* EF-LEARN-HUBS-END \*\/\n?/s', '', $css );
	wp_update_custom_css_post( $css . $block );
	$out['css_ids'] = count( $ids );
}
return $out;
