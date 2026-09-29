<?php
/**
 * [efa_pricing] -- Free vs Pro (0.18.0), for a /pricing/ page.
 *
 *  - Visitors: "Sign up free" / "Sign up to get Pro" (back to this page).
 *  - Free members: "Get Pro" buttons for $2.99/month and $24.99/year, which
 *    open Paddle's checkout (assets/js/checkout.js).
 *  - Pro members: "You're Pro" and Manage billing.
 *  - Until Pro is on sale (ProOffer::on_sale()): "Coming soon", nothing to buy.
 *
 * The visitor version is the same for everyone (cache-safe); member
 * versions are never page-cached.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Membership;

use EnglishFindersAccount\Pages\AuthPages;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PricingPage {
	public const TAG = 'efa_pricing';

	private static bool $style_printed = false;

	public function register_hooks(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
	}

	/**
	 * Is the page being viewed the one holding [efa_pricing]? Checks the
	 * content and Elementor's data, and only when the URL really is that
	 * page (AuthPages::queried_post()). 0.18.2: shared by AdFree and
	 * CachePolicy.
	 */
	public static function is_pricing_page(): bool {
		$post = AuthPages::queried_post();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return str_contains( (string) $post->post_content . ' ' . (string) get_post_meta( $post->ID, '_elementor_data', true ), '[' . self::TAG );
	}

	/** Paddle's checkout script, only where a free member can press "Get Pro". */
	public function maybe_enqueue(): void {
		$post = get_queried_object();
		if ( $post instanceof \WP_Post && has_shortcode( (string) $post->post_content, self::TAG ) && is_user_logged_in() && ! ProOffer::is_pro( get_current_user_id() ) ) {
			ProOffer::enqueue_checkout();
		}
	}

	/** @param array<string,string>|string $atts */
	public function render( $atts = array() ): string {
		$user_id   = get_current_user_id();
		$member    = $user_id > 0;
		$pro       = $member && ProOffer::is_pro( $user_id );
		$on_sale   = ProOffer::on_sale();
		$prices    = ProOffer::prices();
		$labels    = ProOffer::labels();
		$here      = AuthPages::current_page_url();
		$signup    = Urls::signup_page( $here );
		$print_css = ! self::$style_printed;
		self::$style_printed = true;

		ob_start();
		include EFA_PATH . 'templates/public/pricing.php';

		return (string) ob_get_clean();
	}
}
