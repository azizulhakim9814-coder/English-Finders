$learn = get_page_by_path( 'learn' );
if ( ! $learn ) {
	return 'no /learn/ page';
}
$test  = get_permalink( get_page_by_path( 'english-level-test' ) );
$tools = array(
	array( 'grammar-quiz', 'Grammar Quiz', '#4F46E5', '#7C3AED', 'fa-spell-check', 'Grammar', 'Test your grammar with simple questions and instant answers, then review what you got wrong.' ),
	array( 'vocabulary-quiz', 'Vocabulary Quiz', '#10B981', '#059669', 'fa-book-open', 'Vocabulary', 'Learn new words and see how much vocabulary you really know.' ),
	array( 'reading-quiz', 'Reading Quiz', '#F59E0B', '#EA580C', 'fa-book-reader', 'Reading', 'Read short passages and answer questions to test your understanding.' ),
	array( 'spelling-quiz', 'Spelling Quiz', '#0EA5E9', '#2563EB', 'fa-keyboard', 'Spelling', 'Listen to a word and type it to test your spelling.' ),
	array( 'match-the-definition', 'Match The Definition', '#EC4899', '#DB2777', 'fa-link', 'Vocabulary', 'Connect words with their correct meanings to build your vocabulary.' ),
	array( 'error-correction', 'Error Correction', '#7C3AED', '#4338CA', 'fa-check-double', 'Grammar', 'Spot and fix the mistake in each sentence.' ),
	array( 'sentence-builder', 'Sentence Builder', '#059669', '#0D9488', 'fa-arrows-turn-to-dots', 'Writing', 'Put the words in the right order to build correct sentences.' ),
	array( 'pronunciation-practice', 'Pronunciation Practice', '#DB2777', '#9D174D', 'fa-microphone', 'Speaking', 'Listen to native audio and practise saying words correctly.' ),
);
$only = isset( $efl_only ) ? $efl_only : array_keys( $efl_levels );
$out  = array();
foreach ( $only as $L ) {
	$v     = $efl_levels[ $L ];
	$c     = efl_course( $L );
	$words = efl_words( $L );
	$term  = get_term( $v[1], 'category' );
	$lc    = strtolower( $L );
	$name  = $L . ' ' . $v[0];
	$cards = array();
	if ( $c ) {
		$cards[] = efl_card( $v[2], $v[3], 'fa-graduation-cap', array( $c['units'] . ' units', $c['lessons'] . ' lessons', 'Free' ), $L . ' Course', $c['desc'], $c['url'], 'Open the' );
	}
	$cards[] = efl_card( $v[2], $v[3], $v[4], array( $v[0], number_format_i18n( $words ) . ' words' ), $L . ' Words', 'Every word in our dictionary graded ' . $L . ', with meanings, examples and pronunciation.', home_url( '/word-finder/' . $lc . '-words/' ), 'Explore' );
	if ( $term && ! is_wp_error( $term ) && $term->count > 0 ) {
		$cards[] = efl_card( $v[2], $v[3], 'fa-newspaper', array( $term->count . ' articles' ), $L . ' Articles', 'Grammar and vocabulary articles written for ' . $name . ' learners.', get_term_link( $term ), 'Read' );
	}
	$cards[] = efl_card( '#64748B', '#334155', 'fa-clipboard-check', array( 'Free', 'A1 to C2' ), 'English Level Test', 'Not sure ' . $L . ' is your level? Take the free test to find your CEFR level and the course to start with.', $test, 'Take the' );
	foreach ( $tools as $t ) {
		if ( count( $cards ) >= 12 ) {
			break;
		}
		$cards[] = efl_card( $t[2], $t[3], $t[4], array( $t[5], 'Choose ' . $L ), $t[1], $t[6], home_url( '/' . $t[0] . '/' ), 'Open' );
	}
	$sections = array(
		efl_hero( $L . ' English: ' . $v[0], $v[5] ),
		efl_section( array( efl_w_html( efl_grid( $cards, 12 ) ) ), 40, 24 ),
		efl_section(
			array_merge(
				efl_w_heading( 'Practise ' . $L . ' now', 'Two quick practice rounds, already set to ' . $L . '. Answer on the page and see instant feedback.' ),
				array(
					efl_w_shortcode( '[efs_grammar_quiz level="' . $L . '" breadcrumb="0" schema="0"]' ),
					efl_w_shortcode( '[efs_vocabulary_quiz level="' . $L . '" breadcrumb="0" schema="0"]' ),
				)
			),
			32,
			48
		),
	);
	$out[ $L ] = efl_save_page( $lc, $name . ' English', $learn->ID, $sections, 'Free ' . $name . ' English (CEFR ' . $L . '): the ' . $L . ' course, ' . number_format_i18n( $words ) . ' graded words, articles and practice set to your level, all in one place.', $L . ' English' );
}
return $out;
