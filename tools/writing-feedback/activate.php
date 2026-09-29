<?php
/**
 * Switches AI Writing Feedback on, once the OpenRouter account has credits.
 * Send the body (without the opening tag) through Novamira execute-php.
 *
 * 1. Makes one real test request (about half a US cent). If it fails, it
 *    changes nothing and returns the provider's error.
 * 2. Turns on `ai_enabled` in English Finders Core's settings.
 * 3. Publishes /writing-feedback/ (page 35949, kept as a draft until then).
 * 4. Adds the tool's card to the Practice hub (34543), right after Sentence
 *    Builder. Skipped if the card is already there, so re-running is safe.
 * 5. Purges those pages and /pricing/ by post ID (never purge-all).
 *
 * Afterwards, rebuild the Writing hub so it gets the card too: send
 * tools/learn-hubs/lib.php + levels.php + skill_pages.php with
 * `$efl_only = array( 'writing' );` prepended.
 *
 * @package EnglishFinders
 */

$efa_page_id     = 35949;
$efa_practice_id = 34543;
$efa_pricing     = get_page_by_path( 'pricing' );

// 1. Test request, with AI switched on only for its duration.
$efa_settings = get_option( 'efc_settings', array() );
$efa_was_on   = ! empty( $efa_settings['ai_enabled'] );
$efa_settings['ai_enabled'] = true;
update_option( 'efc_settings', $efa_settings, false );

$efa_ai     = \EnglishFindersCore\Support\Api::service( 'ai' );
$efa_answer = $efa_ai->complete_json(
	\EnglishFindersStudy\Tools\WritingFeedback::system_prompt( 'A2', 'Write about what you did last weekend.', 40, 90 ),
	"<learner_text>\nLast weekend I go to the beach with my friends. We was very happy because the weather were sunny. We eat pizza and play volleyball, and in the evening we watched a funny film.\n</learner_text>",
	1800
);
$efa_shaped = is_wp_error( $efa_answer ) ? null : \EnglishFindersStudy\Tools\WritingFeedback::shape( $efa_answer );

if ( null === $efa_shaped ) {
	$efa_settings['ai_enabled'] = $efa_was_on;
	update_option( 'efc_settings', $efa_settings, false );

	return array(
		'activated' => false,
		'error'     => is_wp_error( $efa_answer ) ? $efa_answer->get_error_code() : 'unusable answer',
		'detail'    => is_wp_error( $efa_answer ) ? $efa_answer->get_error_data() : $efa_answer,
	);
}

// 2 + 3. Leave AI on, publish the page.
wp_update_post(
	array(
		'ID'          => $efa_page_id,
		'post_status' => 'publish',
	)
);

// 4. Practice hub card.
$efa_url  = home_url( '/writing-feedback/' );
$efa_pill = '<span style="background:#f3f4f6;color:#374151;font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;border:1px solid #e5e7eb;">%s</span>';
$efa_card = '<div class="ef-hub-item"><div class="ef-game-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;box-shadow:0 1px 2px rgba(16,24,40,.04);display:flex;flex-direction:column;height:350px;font-family:Open Sans,sans-serif;">'
	. '<div style="height:96px;flex:0 0 96px;background:linear-gradient(135deg,#14B8A6,#0E7490);display:flex;align-items:center;justify-content:center;">'
	. '<div style="width:56px;height:56px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,.15);"><i class="fas fa-pen-fancy" style="font-size:22px;color:#1f2937;"></i></div></div>'
	. '<div style="padding:20px 20px 22px;display:flex;flex-direction:column;gap:10px;flex:1 1 auto;min-height:0;">'
	. '<div style="display:flex;gap:8px;flex-wrap:wrap;flex:0 0 auto;">' . sprintf( $efa_pill, 'Writing' ) . sprintf( $efa_pill, 'AI feedback' ) . '</div>'
	. '<div style="font-size:18px;font-weight:800;color:#1d4ed8;line-height:1.3;flex:0 0 auto;"><a href="' . esc_url( $efa_url ) . '" style="color:inherit;text-decoration:none;">AI Writing Feedback</a></div>'
	. '<div style="font-size:13.5px;color:#4b5563;line-height:1.55;flex:1 1 auto;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">Write a short text at your level and get corrections with explanations, a corrected version and one thing to work on next.</div>'
	. '<a href="' . esc_url( $efa_url ) . '" style="font-size:14px;font-weight:700;color:#1d4ed8;text-decoration:none;margin-top:2px;display:flex;align-items:center;gap:5px;flex:0 0 auto;width:100%;"><span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0;flex:1 1 auto;">Open AI Writing Feedback</span><span aria-hidden="true" style="flex:0 0 auto;">&#8594;</span></a>'
	. '</div></div></div>';

$efa_card_added = false;
$efa_doc        = \Elementor\Plugin::$instance->documents->get( $efa_practice_id, false );
$efa_data       = $efa_doc ? $efa_doc->get_elements_data() : array();
$efa_insert     = function ( array &$elements ) use ( &$efa_insert, $efa_card, $efa_url, &$efa_card_added ) {
	foreach ( $elements as &$el ) {
		if ( 'html' === ( $el['widgetType'] ?? '' ) && str_contains( $el['settings']['html'] ?? '', 'ef-hub-item' ) ) {
			$html = $el['settings']['html'];
			if ( str_contains( $html, $efa_url ) ) {
				return;
			}
			$anchor = strpos( $html, home_url( '/sentence-builder/' ) );
			$next   = false !== $anchor ? strpos( $html, '<div class="ef-hub-item">', $anchor ) : false;
			if ( false === $next ) {
				$next = strpos( $html, '</div><nav class="ef-hub-pagination"' );
			}
			if ( false !== $next ) {
				$el['settings']['html'] = substr( $html, 0, $next ) . $efa_card . substr( $html, $next );
				$efa_card_added         = true;
			}
			return;
		}
		if ( ! empty( $el['elements'] ) ) {
			$efa_insert( $el['elements'] );
		}
	}
};
$efa_insert( $efa_data );
if ( $efa_card_added ) {
	$efa_doc->save( array( 'elements' => $efa_data ) );
	wp_update_post( array( 'ID' => $efa_practice_id ) );
	clean_post_cache( $efa_practice_id );
	$efa_css = new \Elementor\Core\Files\CSS\Post( $efa_practice_id );
	$efa_css->delete();
	$efa_css->update();
}
$efa_saved = (string) get_post_meta( $efa_practice_id, '_elementor_data', true );

// 5. Purge by post ID.
foreach ( array( $efa_page_id, $efa_practice_id, $efa_pricing ? $efa_pricing->ID : 0 ) as $efa_pid ) {
	if ( $efa_pid ) {
		do_action( 'litespeed_purge_post', $efa_pid );
	}
}

return array(
	'activated'      => true,
	'test_estimate'  => $efa_shaped['cefr_estimate'],
	'test_fixes'     => count( $efa_shaped['corrections'] ),
	'ai_enabled'     => ! empty( get_option( 'efc_settings' )['ai_enabled'] ),
	'page_status'    => get_post_status( $efa_page_id ),
	'card_added'     => $efa_card_added,
	'practice_cards' => substr_count( $efa_saved, 'ef-hub-item\\"' ) ?: substr_count( $efa_saved, 'ef-hub-item' ),
	'usage_today'    => $efa_ai->usage(),
);
