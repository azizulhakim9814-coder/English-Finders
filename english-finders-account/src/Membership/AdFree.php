<?php
/**
 * No ads for Pro members (0.18.0).
 *
 * Ads reach englishfinders.com three ways (checked live 2026-09-26), and each
 * is switched off through that plugin's own mechanism:
 *  - Site Kit's AdSense module (auto ads in <head>):
 *    filter `googlesitekit_adsense_tag_blocked`.
 *  - "Insert Headers and Footers" (WPCode): its header box holds the AdSense
 *    script (plus two affiliate-verification meta tags that only the
 *    verifier's logged-out crawler needs): filter `disable_ihaf_header`.
 *  - Ad Inserter 2.8.x (the in-article code blocks): its setup runs on `wp`
 *    and stops if AI_WP_HOOK is already defined (its own re-entry guard), so
 *    it is defined first; its head / footer / script hooks, which are
 *    registered at load, are detached -- only functions from Ad Inserter's
 *    own files, never anything else.
 *
 * Pages for signed-in members are never page-cached (LiteSpeed cache for
 * logged-in users is off), so this is decided per member and per request.
 * If a future Ad Inserter version changes, the worst case is that ads show
 * again -- nothing breaks.
 *
 * Admins can preview the Pro view with ?efa_pro_preview=1.
 * Filter: efa_ad_free (bool, user id).
 *
 * 0.18.1: the pricing page, /login/, /sign-up/ and My Account never show
 * ads, for anyone -- AdSense auto ads had put ad links inside the pricing
 * cards (one right under the price). This depends only on the URL, not the
 * visitor, so LiteSpeed's cached guest copy is correct too.
 * Filter: efa_ad_free_page (bool, WP_Post|null).
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Membership;

use EnglishFindersAccount\Pages\AuthPages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdFree {
	/** Pages matched by slug (0.19.0: + the Refund Policy, whose text AdSense could annotate). */
	private const AD_FREE_SLUGS = array( 'my-account', 'refund-policy' );

	/** Hooks Ad Inserter registers at load time. */
	private const AD_INSERTER_HOOKS = array( 'wp_head', 'wp_footer', 'wp_enqueue_scripts', 'the_content', 'the_excerpt', 'loop_start', 'loop_end', 'the_post', 'pre_do_shortcode_tag' );

	private bool $active = false;

	public function register_hooks(): void {
		// Priority 1: before Ad Inserter's own `wp` setup (priority 10).
		add_action( 'wp', array( $this, 'maybe_go_ad_free' ), 1 );
	}

	public function maybe_go_ad_free(): void {
		if ( is_admin() ) {
			return;
		}
		if ( ! self::is_ad_free_page() && ( ! is_user_logged_in() || ! self::applies( get_current_user_id() ) ) ) {
			return;
		}

		$this->active = true;
		add_filter( 'googlesitekit_adsense_tag_blocked', '__return_true' );
		add_filter( 'disable_ihaf_header', '__return_true' );
		self::stop_ad_inserter();
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	public function is_active(): bool {
		return $this->active;
	}

	/** @param list<string> $classes @return list<string> */
	public function body_class( $classes ): array {
		$classes   = (array) $classes;
		$classes[] = 'efa-ad-free';

		return $classes;
	}

	/** Pro members; admins previewing with ?efa_pro_preview=1. */
	public static function applies( int $user_id ): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview flag for admins.
		$preview = isset( $_GET['efa_pro_preview'] ) && user_can( $user_id, 'manage_options' );

		return (bool) apply_filters( 'efa_ad_free', $preview || ProOffer::is_pro( $user_id ), $user_id );
	}

	/**
	 * Pages that never show ads (0.18.1): the page holding [efa_pricing],
	 * the [efa_login] / [efa_signup] pages, My Account, and (0.19.0) the
	 * Refund Policy.
	 */
	public static function is_ad_free_page(): bool {
		$post = AuthPages::queried_post();
		$page = $post instanceof \WP_Post
			&& ( in_array( $post->post_name, self::AD_FREE_SLUGS, true ) || PricingPage::is_pricing_page() || AuthPages::is_auth_page() );

		return (bool) apply_filters( 'efa_ad_free_page', $page, $post );
	}

	/**
	 * Stops Ad Inserter for this request. Returns how many of its callbacks
	 * were detached.
	 */
	public static function stop_ad_inserter(): int {
		if ( ! defined( 'AI_WP_HOOK' ) ) {
			define( 'AI_WP_HOOK', true );
		}

		global $wp_filter;
		$removed = 0;
		foreach ( self::AD_INSERTER_HOOKS as $hook ) {
			if ( empty( $wp_filter[ $hook ] ) || ! is_object( $wp_filter[ $hook ] ) ) {
				continue;
			}
			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$fn = $callback['function'] ?? null;
					if ( is_string( $fn ) && str_starts_with( $fn, 'ai_' ) && self::is_ad_inserter_function( $fn ) ) {
						remove_action( $hook, $fn, $priority );
						++$removed;
					}
				}
			}
		}

		return $removed;
	}

	private static function is_ad_inserter_function( string $fn ): bool {
		if ( ! function_exists( $fn ) ) {
			return false;
		}
		$file = str_replace( '\\', '/', (string) ( new \ReflectionFunction( $fn ) )->getFileName() );

		return str_contains( $file, '/ad-inserter/' );
	}
}
