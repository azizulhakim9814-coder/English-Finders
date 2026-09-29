<?php
/**
 * "Go Pro" under articles (0.19.0).
 *
 * A one-line note after the article (after English Finders Study's
 * "Practice this" box, which runs at the_content priority 20), for
 * signed-in free members only, and only while ProOffer::promo_on(). Pages
 * for signed-in members are never page-cached on this site, so the note
 * never reaches a cached guest copy. Filter: efa_pro_note_enabled.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Membership;

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProPromo {
	public function register_hooks(): void {
		add_filter( 'the_content', array( $this, 'append' ), 30 );
	}

	/** @param string $content */
	public function append( $content ) {
		$content = (string) $content;
		if ( ! self::is_post_body() || ! is_user_logged_in() ) {
			return $content;
		}

		$user_id = get_current_user_id();
		if ( ProOffer::is_pro( $user_id ) || ! ProOffer::promo_on() || ! (bool) apply_filters( 'efa_pro_note_enabled', true, $user_id ) ) {
			return $content;
		}

		return $content . self::note();
	}

	public static function note(): string {
		$labels = ProOffer::labels();

		return '<p class="efa-pro-note" style="margin:24px 0 0;padding:12px 16px;border-radius:10px;background:#f4f8fd;color:#1d2b3a;font-size:15px;line-height:1.5">'
			. esc_html__( 'Tired of ads?', 'english-finders-account' ) . ' '
			. '<a href="' . esc_url( Urls::pricing_page() ) . '" style="color:#075aae;font-weight:600">' . esc_html__( 'Go Pro', 'english-finders-account' ) . '</a> '
			/* translators: %s: monthly price, e.g. $2.99 */
			. esc_html( sprintf( __( 'for no ads anywhere and 5 streak freezes every month, from %s a month.', 'english-finders-account' ), $labels['month'] ) )
			. '</p>';
	}

	/** The main post's own body (not excerpts, feeds, widgets or other loops). */
	private static function is_post_body(): bool {
		return ! is_admin()
			&& ! is_feed()
			&& is_singular( 'post' )
			&& is_main_query()
			&& (int) get_the_ID() > 0
			&& (int) get_the_ID() === (int) get_queried_object_id();
	}
}
