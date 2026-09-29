<?php
/**
 * Shared URL helpers.
 *
 * One place for the /my-account/ URL so every handler that redirects there
 * (registration, login, profile save, privacy requests) agrees on it,
 * rather than each hardcoding home_url('/my-account/') separately.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Urls {
	public static function my_account(): string {
		return home_url( '/my-account/' );
	}

	/** The Free vs Pro page (0.19.0). Filter: efa_pricing_page_url. */
	public static function pricing_page(): string {
		return (string) apply_filters( 'efa_pricing_page_url', home_url( '/pricing/' ) );
	}

	/** Where the empty Library states (0.6.0) send a visitor to go find something to save. */
	public static function games(): string {
		return home_url( '/games/' );
	}

	/**
	 * 0.13.0: My Library's empty states point at word tools, not games --
	 * saved words (the heart on each result) and searches come from them.
	 */
	public static function word_unscrambler(): string {
		return home_url( '/word-unscramble-cheat/' );
	}

	public static function word_tools(): string {
		return home_url( '/word-tools/' );
	}

	/** A dictionary word page, or '' when $word isn't a plain word (letters, apostrophes, hyphens, spaces). */
	public static function word_page( string $word ): string {
		$word = strtolower( trim( $word ) );

		return 1 === preg_match( "/^[a-z][a-z' -]{0,40}$/", $word ) ? home_url( '/dictionary/' . rawurlencode( $word ) . '/' ) : '';
	}

	/**
	 * 0.14.0: the page a login/sign-up form was on, so a mistake (wrong
	 * password, missing name...) goes back to that same page rather than to
	 * /my-account/. Validated like the return address.
	 */
	public const ORIGIN_PARAM = 'efa_origin';

	/** Slugs of the standalone pages that hold [efa_login] / [efa_signup] (0.14.0). */
	public const LOGIN_PAGE_SLUG  = 'login';
	public const SIGNUP_PAGE_SLUG = 'sign-up';

	/**
	 * The standalone login page, or My Account when that page doesn't exist
	 * (yet) -- so a link can never point at a missing page.
	 */
	public static function login_page( string $return = '' ): string {
		return self::with_return( self::page_or_account( self::LOGIN_PAGE_SLUG, 'efa_login_page_url' ), $return );
	}

	/** The standalone sign-up page, or My Account when it doesn't exist. */
	public static function signup_page( string $return = '' ): string {
		return self::with_return( self::page_or_account( self::SIGNUP_PAGE_SLUG, 'efa_signup_page_url' ), $return );
	}

	private static function page_or_account( string $slug, string $filter ): string {
		$page = get_page_by_path( $slug );
		$url  = ( $page instanceof \WP_Post && 'publish' === $page->post_status ) ? (string) get_permalink( $page ) : self::my_account();

		/**
		 * Filter the standalone login / sign-up page URL (e.g. if the pages use other slugs).
		 *
		 * @param string $url
		 */
		return (string) apply_filters( $filter, $url );
	}

	private static function with_return( string $url, string $return ): string {
		$return = self::return_target( $return );

		return '' !== $return ? add_query_arg( self::RETURN_PARAM, rawurlencode( $return ), $url ) : $url;
	}

	/**
	 * Query parameter carrying "where to go after logging in" (0.12.0).
	 * Deliberately not WordPress's own `redirect_to`, which the site's
	 * hidden-login-URL setup and other plugins also read.
	 */
	public const RETURN_PARAM = 'efa_return';

	/** The My Account login page, returning to $return afterwards when it is a safe same-site URL. */
	public static function login( string $return = '' ): string {
		$return = self::return_target( $return );

		return '' !== $return ? add_query_arg( self::RETURN_PARAM, rawurlencode( $return ), self::my_account() ) : self::my_account();
	}

	/**
	 * $raw if it is safe to send a learner to after login, else ''.
	 *
	 * Only absolute http(s) URLs on this site's own host, never wp-admin
	 * or a logout link -- so the parameter can't be turned into an open
	 * redirect, or into a loop that logs the learner straight back out.
	 */
	public static function return_target( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}

		$url  = wp_parse_url( $raw );
		$home = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $url ) || ! is_array( $home ) || empty( $url['host'] ) || empty( $home['host'] ) ) {
			return '';
		}

		$scheme = strtolower( (string) ( $url['scheme'] ?? '' ) );
		$path   = (string) ( $url['path'] ?? '/' );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true )
			|| strtolower( $url['host'] ) !== strtolower( $home['host'] )
			|| str_starts_with( $path, '/wp-admin' )
			|| str_contains( $path, 'wp-login.php' )
			|| str_contains( (string) ( $url['query'] ?? '' ), 'action=logout' ) ) {
			return '';
		}

		return esc_url_raw( $raw );
	}
}
