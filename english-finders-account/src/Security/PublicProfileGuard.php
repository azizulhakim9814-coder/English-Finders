<?php
/**
 * Tutor public profiles (englishfinders.com/profile/<name>/) only for people
 * whose profile is part of the site: approved instructors and staff (0.16.0).
 *
 * Found live 2026-09-26: Tutor publishes a profile page for every account,
 * marked index/follow, and 211 spam accounts had filled their bio and social
 * fields with outside links -- user-generated spam published on this domain.
 * Learners have no use for a public profile (the leaderboard is members-only
 * and shows names from the account itself).
 *
 *  - Anyone else's profile is a real 404 -- whatever the address, including
 *    Tutor's ?view=instructor, which otherwise shows any account with the
 *    instructor layout.
 *  - On the profiles that stay, every outside link gets rel="nofollow ugc"
 *    (user-written content passes no search ranking).
 *  - 0.16.1: the 404 is shown straight away, so "404 -> similar post"
 *    redirect plugins can't turn it into a redirect (see guard()).
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PublicProfileGuard {
	private const QUERY_VAR = 'tutor_profile_username';

	private bool $hidden = false;

	public function register_hooks(): void {
		add_action( 'template_redirect', array( $this, 'guard' ), 1 );
		// Well after Tutor's own template_include (99), which returns its profile template.
		add_filter( 'template_include', array( $this, 'template' ), 999 );
		add_filter( 'pre_get_document_title', array( $this, 'title' ), 20 );
	}

	public function guard(): void {
		$name = (string) get_query_var( self::QUERY_VAR );
		if ( '' === $name ) {
			return;
		}

		$user = self::find_user( $name );
		if ( $user instanceof \WP_User && self::may_have_profile( $user ) ) {
			ob_start( array( self::class, 'nofollow_outside_links' ) );
			return;
		}

		$this->hidden = true;
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

		/*
		 * 0.16.1: show the 404 page now. Found live: the "WP 404 Auto Redirect
		 * to Similar Post" plugin (template_redirect 999) turns every 404 into
		 * a 301 to a look-alike page -- here the leftover PMPro
		 * /membership-account/your-profile/ -- and has no per-request opt-out.
		 * These addresses should simply be gone. The template goes through
		 * template_include as usual (theme / Elementor 404, then template()
		 * below), only the later template_redirect handlers are skipped.
		 */
		if ( (bool) apply_filters( 'efa_public_profile_render_404_now', true ) ) {
			$template = (string) apply_filters( 'template_include', get_404_template() );
			if ( '' !== $template && is_file( $template ) ) {
				include $template;
				exit;
			}
		}
	}

	/** @param string $template */
	public function template( $template ) {
		if ( ! $this->hidden ) {
			return $template;
		}

		$not_found = get_404_template();

		return '' !== $not_found ? $not_found : $template;
	}

	/** No account name in the title of a hidden profile. @param string $title */
	public function title( $title ) {
		return $this->hidden ? '' : $title;
	}

	/** Approved Tutor instructors and staff. Filter: efa_public_profile_allowed. */
	public static function may_have_profile( \WP_User $user ): bool {
		$allowed = user_can( $user, 'edit_posts' )
			|| ( 'approved' === get_user_meta( $user->ID, '_tutor_instructor_status', true ) && '' !== (string) get_user_meta( $user->ID, '_is_tutor_instructor', true ) );

		return (bool) apply_filters( 'efa_public_profile_allowed', $allowed, $user );
	}

	/** Adds rel="nofollow ugc" to links that leave this site. */
	public static function nofollow_outside_links( string $html ): string {
		$home = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

		return (string) preg_replace_callback(
			'/<a\s[^>]*href=(["\'])(https?:)?\/\/([^\/"\'?#]+)[^>]*>/i',
			static function ( array $m ) use ( $home ): string {
				$tag  = $m[0];
				$host = strtolower( preg_replace( '/:\d+$/', '', $m[3] ) );
				if ( $host === $home || str_ends_with( $host, '.' . $home ) ) {
					return $tag;
				}
				if ( preg_match( '/\srel=(["\'])(.*?)\1/i', $tag, $rel ) ) {
					$words = array_unique( array_merge( preg_split( '/\s+/', trim( strtolower( $rel[2] ) ) ) ?: array(), array( 'nofollow', 'ugc' ) ) );
					return str_replace( $rel[0], ' rel="' . implode( ' ', array_filter( $words ) ) . '"', $tag );
				}

				return substr( $tag, 0, -1 ) . ' rel="nofollow ugc">';
			},
			$html
		);
	}

	private static function find_user( string $name ): ?\WP_User {
		$name = rawurldecode( $name );
		$user = get_user_by( 'slug', sanitize_title( $name ) );
		if ( ! $user instanceof \WP_User ) {
			$user = get_user_by( 'login', $name );
		}

		return $user instanceof \WP_User ? $user : null;
	}
}
