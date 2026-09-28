<?php
/**
 * Standalone login and sign-up pages, and header account links (0.14.0).
 *
 *  [efa_login]          The login form on its own page (recommended slug: /login/).
 *  [efa_signup]         The sign-up form on its own page (/sign-up/). Alias: [efa_register].
 *                       Attribute panel="no" hides the brand panel beside the form.
 *  [efa_account_links]  For the site header (Astra's "HTML" header element):
 *                       guests get "Log in" + a "Sign up" button, members get
 *                       their photo + "My account". Attribute show="both|login|signup".
 *
 * Both pages reuse the /my-account/ forms (templates/public/partials/), so
 * everything behind them -- Turnstile, Google, full name, learner/teacher,
 * photo, kept values after a mistake, return to the page the learner came
 * from -- is the same code. A mistake comes back to the same page (the
 * forms carry `efa_origin`).
 *
 * Pages holding either form are:
 *  - never cached (they carry form nonces, see Support\CachePolicy);
 *  - skipped by logged-in visitors, who go straight to where they were
 *    heading or to My Account -- except inside the Elementor editor /
 *    preview, where the page must stay editable (a short notice shows).
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Pages;

use EnglishFindersAccount\Profile\Avatar;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuthPages {
	public const LOGIN_TAG   = 'efa_login';
	public const SIGNUP_TAG  = 'efa_signup';
	public const SIGNUP_ALIAS = 'efa_register';
	public const LINKS_TAG   = 'efa_account_links';

	/** Per-request cache of is_auth_page(). */
	private static ?bool $is_auth_page = null;

	private static bool $links_style_printed = false;

	public function register_hooks(): void {
		add_shortcode( self::LOGIN_TAG, array( $this, 'render_login' ) );
		add_shortcode( self::SIGNUP_TAG, array( $this, 'render_signup' ) );
		add_shortcode( self::SIGNUP_ALIAS, array( $this, 'render_signup' ) );
		add_shortcode( self::LINKS_TAG, array( $this, 'render_account_links' ) );
		add_action( 'template_redirect', array( $this, 'maybe_skip_for_members' ), 5 );
	}

	/**
	 * Is the current request a page holding [efa_login] / [efa_signup]?
	 * Checks the page content and, for Elementor-built pages, Elementor's data.
	 */
	public static function is_auth_page(): bool {
		if ( null !== self::$is_auth_page ) {
			return self::$is_auth_page;
		}

		$post = self::queried_post();
		if ( ! $post instanceof \WP_Post ) {
			return false; // Not cached: the query may not be ready yet.
		}

		$haystack = (string) $post->post_content . ' ' . (string) get_post_meta( $post->ID, '_elementor_data', true );

		self::$is_auth_page = (bool) preg_match( '/\[(' . self::LOGIN_TAG . '|' . self::SIGNUP_TAG . '|' . self::SIGNUP_ALIAS . ')[\s\]]/', $haystack );

		return self::$is_auth_page;
	}

	/** Pages that need the account stylesheet, scripts and Turnstile: My Account and the standalone pages. */
	public static function needs_account_assets(): bool {
		return is_page( 'my-account' ) || self::is_auth_page();
	}

	/** Tests reset the per-request cache between simulated requests. */
	public static function reset_cache(): void {
		self::$is_auth_page = null;
	}

	/**
	 * A logged-in learner has nothing to do on a login or sign-up page:
	 * send them on to where they were heading, or to My Account.
	 */
	public function maybe_skip_for_members(): void {
		if ( ! is_user_logged_in() || ! self::is_auth_page() || self::is_editor_request() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only; validated by Urls::return_target().
		$return = Urls::return_target( isset( $_GET[ Urls::RETURN_PARAM ] ) ? (string) wp_unslash( $_GET[ Urls::RETURN_PARAM ] ) : '' );
		wp_safe_redirect( '' !== $return ? $return : Urls::my_account() );
		exit;
	}

	/** @param array<string,string>|string $atts */
	public function render_login( $atts = array() ): string {
		return $this->render_page( 'login', shortcode_atts( array( 'panel' => 'yes' ), (array) $atts, self::LOGIN_TAG ) );
	}

	/** @param array<string,string>|string $atts */
	public function render_signup( $atts = array() ): string {
		return $this->render_page( 'signup', shortcode_atts( array( 'panel' => 'yes' ), (array) $atts, self::SIGNUP_TAG ) );
	}

	/**
	 * @param array<string,string> $atts
	 */
	private function render_page( string $mode, array $atts ): string {
		// Belt and braces: CachePolicy already marks these pages no-cache at `wp`.
		do_action( 'litespeed_control_set_nocache', 'english finders login/sign-up page: form nonces' );

		$view         = AuthView::from_request();
		$error        = $view['error'];
		$form         = $view['form'];
		$turnstile_site_key = $view['turnstile_site_key'];
		$return       = $view['return'];
		$draft        = $view['draft'];
		$origin       = self::current_page_url();
		$show_panel   = 'no' !== strtolower( (string) $atts['panel'] );
		$logged_in    = is_user_logged_in();
		$display_name = $logged_in ? (string) wp_get_current_user()->display_name : '';

		ob_start();
		include EFA_PATH . 'templates/public/auth-page.php';

		return (string) ob_get_clean();
	}

	/** @param array<string,string>|string $atts */
	public function render_account_links( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'show'        => 'both',
				'login_text'  => __( 'Log in', 'english-finders-account' ),
				'signup_text' => __( 'Sign up', 'english-finders-account' ),
				'account_text' => __( 'My account', 'english-finders-account' ),
			),
			(array) $atts,
			self::LINKS_TAG
		);

		$print_style = ! self::$links_style_printed;
		self::$links_style_printed = true;

		$user_id   = get_current_user_id();
		$avatar    = $user_id > 0 ? ( new Avatar() )->url( $user_id ) : '';
		$name      = $user_id > 0 ? (string) wp_get_current_user()->display_name : '';
		// Guests come back to this page after logging in -- unless they're already on an account page.
		$here      = ( self::is_auth_page() || is_page( 'my-account' ) ) ? '' : self::current_page_url();
		$login_url  = Urls::login_page( $here );
		$signup_url = Urls::signup_page( $here );
		$show       = in_array( $atts['show'], array( 'both', 'login', 'signup' ), true ) ? $atts['show'] : 'both';
		$menu_vars  = $print_style ? self::menu_vars() : '';
		// 0.19.0: a PRO badge for Pro members; "Go Pro" for free members once switched on.
		$is_pro     = $user_id > 0 && \EnglishFindersAccount\Membership\ProOffer::is_pro( $user_id );
		$header_break = max( 320, min( 2000, (int) apply_filters( 'efa_header_break_point', function_exists( 'astra_header_break_point' ) ? (int) astra_header_break_point() : 921 ) ) );
		$go_pro_url = ( $user_id > 0 && ! $is_pro && \EnglishFindersAccount\Membership\ProOffer::promo_on() && ! \EnglishFindersAccount\Membership\PricingPage::is_pricing_page() ) ? Urls::pricing_page() : '';

		ob_start();
		include EFA_PATH . 'templates/public/account-links.php';

		return (string) ob_get_clean();
	}

	/**
	 * This page's own URL (no query string), used as the forms' origin and the
	 * header links' return address. Built from the address actually requested:
	 * virtual pages (Word Games Pro's /dictionary/ routes) mark the request
	 * singular while the queried object is whatever post the default query
	 * loaded, so its permalink would send learners to an unrelated article.
	 */
	public static function current_page_url(): string {
		$path = self::request_path();
		if ( '' !== $path ) {
			return home_url( '/' . user_trailingslashit( $path ) );
		}

		// Plain permalinks (?page_id=...) or the front page: nothing in the path to go on.
		$post = self::queried_post();

		return $post instanceof \WP_Post ? (string) get_permalink( $post ) : home_url( '/' );
	}

	/**
	 * The queried post -- but only when it really is the page being viewed,
	 * i.e. its permalink matches the requested path (see current_page_url()).
	 * Public since 0.18.1 (Membership\AdFree uses it too).
	 */
	public static function queried_post(): ?\WP_Post {
		$post = is_singular() ? get_queried_object() : null;
		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		$path = self::request_path();
		if ( '' !== $path && untrailingslashit( (string) get_permalink( $post ) ) !== untrailingslashit( home_url( '/' . $path ) ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * The header menu's font as CSS custom properties, so "Log in" matches the
	 * menu items beside it (0.14.2). Read from Astra's primary-menu settings;
	 * filter `efa_account_links_menu_typography` to set it for other themes.
	 * Values are whitelisted because they are printed inside a <style> block.
	 */
	public static function menu_vars(): string {
		$typo = array( 'family' => '', 'size' => '', 'weight' => '' );
		if ( function_exists( 'astra_get_option' ) ) {
			$size           = astra_get_option( 'header-menu1-font-size' );
			$typo['family'] = (string) astra_get_option( 'header-menu1-font-family' );
			$typo['weight'] = (string) astra_get_option( 'header-menu1-font-weight' );
			if ( is_array( $size ) && is_numeric( $size['desktop'] ?? '' ) ) {
				$typo['size'] = $size['desktop'] . ( $size['desktop-unit'] ?? 'px' );
			}
		}
		$typo = (array) apply_filters( 'efa_account_links_menu_typography', $typo );

		$vars   = array();
		$family = trim( (string) ( $typo['family'] ?? '' ) );
		if ( '' !== $family && ! in_array( strtolower( $family ), array( 'inherit', 'default' ), true ) && preg_match( '/^[A-Za-z0-9 ,\'"_-]+$/', $family ) ) {
			$vars[] = '--efa-menu-font:' . $family;
		}
		if ( preg_match( '/^\d{1,2}(\.\d+)?(px|rem|em)$/', (string) ( $typo['size'] ?? '' ) ) ) {
			$vars[] = '--efa-menu-size:' . $typo['size'];
		}
		if ( preg_match( '/^[1-9]00$/', (string) ( $typo['weight'] ?? '' ) ) ) {
			$vars[] = '--efa-menu-weight:' . $typo['weight'];
		}

		return implode( ';', $vars );
	}

	private static function request_path(): string {
		global $wp;

		return isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	}

	private static function is_editor_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- presence checks only.
		if ( is_preview() || isset( $_GET['elementor-preview'] ) || isset( $_GET['preview'] ) ) {
			return true;
		}

		return class_exists( '\\Elementor\\Plugin' )
			&& isset( \Elementor\Plugin::$instance->preview )
			&& method_exists( \Elementor\Plugin::$instance->preview, 'is_preview_mode' )
			&& \Elementor\Plugin::$instance->preview->is_preview_mode();
	}
}
