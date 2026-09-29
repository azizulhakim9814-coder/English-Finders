<?php
/**
 * Builds (or rebuilds) the /writing-feedback/ page for the AI Writing
 * Feedback tool (English Finders Study 1.18.0). Send the body (without the
 * opening tag) through Novamira execute-php.
 *
 * Same Elementor layout as the other tool pages (e.g. /sentence-builder/,
 * 34620): one section with the tool's shortcode, then one section of
 * heading + text-editor widgets, ending with [efs_related_tools].
 * Idempotent: an existing page with the slug is updated in place.
 *
 * @package EnglishFinders
 */

$efw_id = function () {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
};
$efw_heading = function ( $title, $size = 'h2' ) use ( $efw_id ) {
	$settings = array( 'title' => $title );
	if ( 'h2' !== $size ) {
		$settings['header_size'] = $size;
	}
	return array( 'id' => $efw_id(), 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => $settings, 'elements' => array() );
};
$efw_text = function ( $html ) use ( $efw_id ) {
	return array( 'id' => $efw_id(), 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => array( 'editor' => $html ), 'elements' => array() );
};
$efw_sc = function ( $code ) use ( $efw_id ) {
	return array( 'id' => $efw_id(), 'elType' => 'widget', 'widgetType' => 'shortcode', 'settings' => array( 'shortcode' => $code ), 'elements' => array() );
};
$efw_section = function ( $widgets ) use ( $efw_id ) {
	return array(
		'id'       => $efw_id(),
		'elType'   => 'section',
		'settings' => array(),
		'elements' => array(
			array( 'id' => $efw_id(), 'elType' => 'column', 'settings' => array( '_column_size' => 100, '_inline_size' => null ), 'elements' => $widgets ),
		),
	);
};

$efw_q = get_option( 'efc_settings', array() );
$efw_free = (int) ( $efw_q['ai_free_daily'] ?? 3 );
$efw_pro  = (int) ( $efw_q['ai_pro_daily'] ?? 30 );

$efw_widgets = array(
	$efw_text( '<p>Write a short text in English and get feedback like a teacher would give: corrections with a short reason for each, a corrected version of your text, and one thing to work on next.</p>' ),
	$efw_heading( 'What Is AI Writing Feedback?' ),
	$efw_text( '<p>AI Writing Feedback is English Finders&#8217; writing-practice tool. You choose your CEFR level (A1 to C2) and a writing task, such as an email, a story, a review or an essay, and write your answer. An AI model then reads it the way an English teacher would. It estimates the CEFR level your text shows, points out what you did well, and corrects up to eight real mistakes in grammar, vocabulary, spelling, punctuation and word order, each with a one-line explanation. You also get a corrected version that keeps your own ideas and stays close to your level, so you can see exactly what changed.</p>' ),
	$efw_heading( 'How to Use AI Writing Feedback' ),
	$efw_text( '<ul><li>Choose your level. If you have taken the English Level Test, the tool starts at your level.</li><li>Choose a writing task, or pick &#8220;My own topic&#8221; to write about anything.</li><li>Write your text in the box. The counter shows how many words to aim for at your level.</li><li>Press &#8220;Check my writing&#8221;. Feedback usually arrives in 10 to 30 seconds.</li><li>Read the corrections, then press &#8220;Edit and check again&#8221; to improve your text.</li></ul>' ),
	$efw_heading( 'Daily Checks' ),
	$efw_text( '<p>You need a free English Finders account to get feedback. Every account gets <strong>' . $efw_free . ' checks a day</strong>, and Pro members get <strong>' . $efw_pro . ' a day</strong>. A check that fails is not counted. You can read the tasks and start writing without an account: your draft is kept in your browser, so it is still there after you sign in.</p>' ),
	$efw_heading( 'Your Text and Your Privacy' ),
	$efw_text( '<p>To create the feedback, your text is sent to our AI provider. It is not published, and English Finders does not keep a copy of it: only the three main corrections are saved to the Mistakes notebook in your account, so you can review them later. Please don&#8217;t include private details such as your address or phone number.</p>' ),
	$efw_heading( 'Tips for Better Feedback' ),
	$efw_text( '<ul><li>Choose the level you are working at, not the level you hope to reach. The feedback is written in English you can understand at that level.</li><li>Write the whole text before you check it. The feedback also looks at how well your text answers the task.</li><li>Look for patterns: if the same kind of mistake appears twice, practise it with the Grammar Quiz or Error Correction.</li><li>Rewrite your text using the corrections, then compare it with the corrected version.</li><li>AI feedback is usually helpful, but it can make mistakes. If a correction looks wrong, check it in a grammar lesson or ask a teacher.</li></ul>' ),
	$efw_heading( 'Who Is AI Writing Feedback For?' ),
	$efw_text( '<p>Any English learner who wants to practise writing, from A1 beginners writing a few sentences about themselves to C2 learners working on a persuasive essay. It is useful practice for the writing parts of exams such as IELTS and Cambridge English, and teachers can use the tasks for homework and let learners check a draft before handing it in.</p>' ),
	$efw_heading( 'Frequently Asked Questions' ),
	$efw_heading( 'Is AI Writing Feedback free?', 'h3' ),
	$efw_text( '<p>Yes. Every free account gets ' . $efw_free . ' checks a day. Pro members get ' . $efw_pro . ' a day.</p>' ),
	$efw_heading( 'How long can my text be?', 'h3' ),
	$efw_text( '<p>It depends on the level: about 25 to 60 words at A1, up to about 180 to 320 words at C2. The word counter under the box shows the target for the task you chose.</p>' ),
	$efw_heading( 'Can the AI make mistakes?', 'h3' ),
	$efw_text( '<p>Yes, sometimes. It is instructed to correct only real errors, but no checker is perfect. Use the explanations to understand each change, and don&#8217;t accept a correction you are sure is wrong.</p>' ),
	$efw_heading( 'Where can I see my corrections later?', 'h3' ),
	$efw_text( '<p>The main corrections from each check go into the Mistakes notebook in My Account, next to the questions you missed in the other practice tools.</p>' ),
	$efw_heading( 'Does it count towards my streak?', 'h3' ),
	$efw_text( '<p>Yes. Each check counts as practice for your daily streak and XP.</p>' ),
	$efw_heading( 'Related Practice Tools' ),
	$efw_text( '<p>Want to practise a different skill? Here are more free tools from English Finders&#8217; Practice hub.</p>' ),
	$efw_sc( '[efs_related_tools]' ),
);

$efw_sections = array(
	$efw_section( array( $efw_sc( '[efs_writing_feedback schema="0"]' ) ) ),
	$efw_section( $efw_widgets ),
);

$efw_page = get_page_by_path( 'writing-feedback' );
$efw_new  = ! $efw_page;
$efw_pid  = $efw_new ? wp_insert_post(
	array(
		'post_title'   => 'AI Writing Feedback',
		'post_name'    => 'writing-feedback',
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_content' => '',
	)
) : $efw_page->ID;
if ( is_wp_error( $efw_pid ) || ! $efw_pid ) {
	return array( 'error' => 'insert failed' );
}

$efw_meta = array(
	'_elementor_edit_mode'       => 'builder',
	'_elementor_template_type'   => 'wp-page',
	'_elementor_version'         => '4.2.4',
	'_wp_page_template'          => 'default',
	'_elementor_page_settings'   => array( 'hide_title' => 'yes' ),
	'site-post-title'            => 'disabled',
	'_ef_breadcrumb_hub'         => 'practice',
	'rank_math_title'            => 'AI Writing Feedback | Free English Writing Checker %page% %sep% %sitename%',
	'rank_math_description'      => 'Get free AI feedback on your English writing: corrections with explanations, a corrected version and your CEFR level, for A1 to C2 learners.',
	'rank_math_focus_keyword'    => 'AI writing feedback',
);
foreach ( $efw_meta as $efw_k => $efw_v ) {
	update_post_meta( $efw_pid, $efw_k, $efw_v );
}

$efw_doc = \Elementor\Plugin::$instance->documents->get( $efw_pid, false );
$efw_doc->save( array( 'elements' => $efw_sections ) );
wp_update_post( array( 'ID' => $efw_pid ) );
clean_post_cache( $efw_pid );
$efw_css = new \Elementor\Core\Files\CSS\Post( $efw_pid );
$efw_css->delete();
$efw_css->update();
do_action( 'litespeed_purge_post', $efw_pid );

$efw_saved = json_decode( (string) get_post_meta( $efw_pid, '_elementor_data', true ), true );

return array(
	'id'       => $efw_pid,
	'created'  => $efw_new,
	'url'      => get_permalink( $efw_pid ),
	'sections' => is_array( $efw_saved ) ? count( $efw_saved ) : 0,
	'widgets'  => is_array( $efw_saved ) ? count( $efw_saved[1]['elements'][0]['elements'] ?? array() ) : 0,
);
