$efl_levels = array(
	'A1' => array( 'Beginner', 1669, '#34D399', '#10B981', 'fa-seedling', 'Start from zero: greetings, numbers, everyday words and simple sentences about yourself.' ),
	'A2' => array( 'Elementary', 1670, '#2DD4BF', '#0EA5E9', 'fa-leaf', 'Handle routine situations: past events, plans, shopping, directions and short messages.' ),
	'B1' => array( 'Intermediate', 1671, '#3B82F6', '#2563EB', 'fa-compass', 'Talk about experiences, plans and opinions, and cope with most travel and work situations.' ),
	'B2' => array( 'Upper Intermediate', 1672, '#6366F1', '#4F46E5', 'fa-chart-line', 'Discuss complex topics fluently and write clear, detailed texts that argue a point of view.' ),
	'C1' => array( 'Advanced', 1673, '#8B5CF6', '#7C3AED', 'fa-award', 'Use English flexibly and precisely for study, work and social life, with a wide range of expression.' ),
	'C2' => array( 'Proficiency', 1674, '#F59E0B', '#D97706', 'fa-crown', 'Understand virtually everything you hear or read and express yourself with nuance and precision.' ),
);
if ( ! function_exists( 'efl_course' ) ) {
	function efl_course( $level ) {
		global $wpdb;
		foreach ( get_posts( array( 'post_type' => 'courses', 'post_status' => 'publish', 'posts_per_page' => 20 ) ) as $c ) {
			if ( preg_match( '/\b' . $level . '\b/', $c->post_title ) ) {
				$topics  = get_posts( array( 'post_type' => 'topics', 'post_parent' => $c->ID, 'fields' => 'ids', 'posts_per_page' => -1 ) );
				$lessons = $topics ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='lesson' AND post_status='publish' AND post_parent IN (" . implode( ',', array_map( 'intval', $topics ) ) . ')' ) : 0;
				return array( 'url' => get_permalink( $c ), 'units' => count( $topics ), 'lessons' => $lessons, 'desc' => (string) get_post_meta( $c->ID, 'rank_math_description', true ) );
			}
		}
		return null;
	}
	function efl_words( $level ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wuc_words WHERE cefr_level = %s", $level ) );
	}
}
