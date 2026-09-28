$test = get_page_by_path( 'english-level-test' );
$cards = array( efl_card( '#64748B', '#334155', 'fa-clipboard-check', array( 'Free', 'A1 to C2' ), 'English Level Test', 'Not sure where to start? Take the free level test to find your CEFR level and the course that fits you.', get_permalink( $test ), 'Take the' ) );
foreach ( $efl_levels as $L => $v ) {
	$c       = efl_course( $L );
	$cards[] = efl_card( $v[2], $v[3], $v[4], array( $v[0], $c ? $c['units'] . ' units · ' . $c['lessons'] . ' lessons' : 'Course' ), $L . ' ' . $v[0], $v[5], home_url( '/learn/' . strtolower( $L ) . '/' ), 'Explore' );
}
$skills = array();
$skill_style = array( 5573 => array( '#4F46E5', '#7C3AED', 'fa-spell-check' ), 11654 => array( '#10B981', '#059669', 'fa-book-open' ), 34387 => array( '#F59E0B', '#EA580C', 'fa-book-reader' ), 34386 => array( '#059669', '#0D9488', 'fa-pen-nib' ), 34388 => array( '#DB2777', '#9D174D', 'fa-comments' ), 34389 => array( '#0EA5E9', '#2563EB', 'fa-headphones' ) );
foreach ( $skill_style as $pid => $s ) {
	$term     = get_term_by( 'slug', get_post_field( 'post_name', $pid ), 'category' );
	$skills[] = efl_card( $s[0], $s[1], $s[2], array( 'Skill', ( $term ? $term->count : 0 ) . ' articles' ), get_the_title( $pid ), (string) get_post_meta( $pid, 'rank_math_description', true ), get_permalink( $pid ), 'Explore' );
}
$sections = array(
	efl_hero( 'Learn English, Level by Level', 'Pick your CEFR level, from complete beginner (A1) to proficiency (C2), and find its course, words, lessons and practice in one place.' ),
	efl_section( array( efl_w_html( efl_grid( $cards, 12 ) ) ), 40, 24 ),
	efl_section( array_merge( efl_w_heading( 'Learn by skill', 'Prefer to focus on one skill? Each hub gathers lessons and practice for it across all levels.' ), array( efl_w_html( efl_grid( $skills, 12 ) ) ) ), 32, 48 ),
);
return efl_save_page( 'learn', 'Learn English', 0, $sections, 'Learn English level by level: free CEFR courses from A1 to C2, graded word lists, lessons and practice, plus a free level test to find where to start.', 'learn English' );
