<?php
/**
 * Page-cache rules for the account pages (0.12.0).
 *
 * The problem (found live, 2026-09-24): /my-account/ was cached for guests
 * like any public page -- a LiteSpeed page-cache hit shared by every
 * visitor (7-day TTL), plus `Cache-Control: public, max-age=7776000` from
 * the site's .htaccess (90 days in the browser). The login and
 * registration forms carry WordPress nonces, which expire after 12-24
 * hours, so a cached copy goes on serving a dead nonce and the login fails
 * ("the link you followed has expired"). The certificate page already sent
 * no-cache headers and was verified to get `no-store, private` live, so the
 * same treatment is applied here: /my-account/ is never cached, for anyone.
 *
 * Deploying a new version doesn't purge LiteSpeed on this site (purge on
 * upgrade is off), so the first request after an update purges -- once --
 * exactly the cached pages this plugin's output changed on: /my-account/,
 * Tutor's /dashboard/, and the course and quiz pages (which carried Tutor's
 * login form/pop-up, replaced in 0.12.0). By post id, never purge-all: a
 * full purge cold-starts ~64k dictionary pages on this host.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CachePolicy {
	private const PAGE_SLUG = 'my-account';

	/** Stores the plugin version whose deploy purge already ran. */
	public const PURGED_OPTION = 'efa_cache_purged_for';

	public function register_hooks(): void {
		add_action( 'wp', array( $this, 'prevent_caching' ) );
		add_action( 'init', array( $this, 'purge_after_update' ), 20 );
	}

	public function prevent_caching(): void {
		// 0.18.2: the pricing page shows live prices and whether Pro is on
		// sale. A cached copy kept saying "Coming soon" after Pro went on sale
		// (LiteSpeed, Hostinger's CDN, and the browser for up to 90 days), so
		// it is never stored. It gets few visits, so the extra renders are cheap.
		if ( \EnglishFindersAccount\Membership\PricingPage::is_pricing_page() ) {
			nocache_headers();
			do_action( 'litespeed_control_set_nocache', 'english finders pricing page: live prices and on-sale state' );
			return;
		}

		// 0.14.0: the standalone login / sign-up pages carry the same form nonces.
		if ( ! is_page( self::PAGE_SLUG ) && ! \EnglishFindersAccount\Pages\AuthPages::is_auth_page() ) {
			return;
		}

		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'english finders my account: login forms carry nonces' );
		self::noindex();
	}

	/**
	 * 0.19.0: My Account, /login/ and /sign-up/ are kept out of search
	 * results. Every page links to them with its own ?efa_return= address, and
	 * crawlers (ClaudeBot, Googlebot) were rendering thousands of these
	 * uncacheable pages a day (access log 2026-09-27). robots.txt now blocks
	 * the ?efa_return= variants; the plain pages stay crawlable so search
	 * engines can read this noindex. Sent as a header (theme / SEO plugin
	 * independent) and as the page's robots meta (Rank Math and core agree).
	 */
	private static function noindex(): void {
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
		add_filter(
			'rank_math/frontend/robots',
			static function ( $robots ) {
				$robots           = is_array( $robots ) ? $robots : array();
				$robots['index']  = 'noindex';
				$robots['follow'] = 'nofollow';

				return $robots;
			}
		);
		add_filter(
			'wp_robots',
			static function ( $robots ) {
				$robots             = is_array( $robots ) ? $robots : array();
				$robots['noindex']  = true;
				$robots['nofollow'] = true;
				unset( $robots['index'], $robots['follow'] );

				return $robots;
			}
		);
	}

	public function purge_after_update(): void {
		if ( EFA_VERSION === get_option( self::PURGED_OPTION ) ) {
			return;
		}

		// Recorded first, so concurrent requests right after the upload don't all purge.
		update_option( self::PURGED_OPTION, EFA_VERSION, false );

		do_action( 'litespeed_purge_url', Urls::my_account() );
		// 0.18.2: the copy of the pricing page cached before it became uncacheable.
		foreach ( (array) apply_filters( 'efa_pricing_page_urls', array( home_url( '/pricing/' ) ) ) as $url ) {
			do_action( 'litespeed_purge_url', (string) $url );
		}
		// 0.19.0: the Refund Policy's copy cached with ads (it is ad-free now).
		do_action( 'litespeed_purge_url', home_url( '/refund-policy/' ) );
		if ( function_exists( 'tutor_utils' ) && method_exists( tutor_utils(), 'tutor_dashboard_url' ) ) {
			do_action( 'litespeed_purge_url', (string) tutor_utils()->tutor_dashboard_url() );
		}

		$ids = get_posts(
			array(
				'post_type'      => array( 'courses', 'tutor_quiz' ),
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$tags = array_map( static fn ( $id ): string => 'Po.' . (int) $id, (array) $ids );
		if ( array() !== $tags ) {
			do_action( 'litespeed_purge', $tags );
		}
	}
}
