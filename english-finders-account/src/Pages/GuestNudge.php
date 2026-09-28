<?php
/**
 * Turning visitors into learners at the moment they get value (0.17.0).
 *
 * Found live 2026-09-26: of 14,629 accounts only 7 had ever earned XP, and
 * the practice tools and games -- played by 10-33 visitors a month each --
 * never mentioned that an account keeps progress.
 *
 *  - The "keep your progress" bar (assets/js/guest-nudge.js): loaded for
 *    visitors who aren't signed in, shown the first time in a visit that a
 *    practice tool or game sends the shared `ef:progress` event (English
 *    Finders Study 1.13.1, Word Games Pro 2.12.13). Not on the account pages
 *    themselves, where it would only get in the way.
 *  - The level test's "Create a free account" link goes to /sign-up/ (it
 *    went to My Account). Study attaches the visitor's result to the new
 *    account on `user_register`, so it isn't lost.
 *
 * Pages seen by visitors are cached, so everything here is the same for
 * every visitor: the return address is added in the browser.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Pages;

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GuestNudge {
	public const HANDLE = 'efa-guest-nudge';

	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'efs_level_test_account_url', array( $this, 'level_test_account_url' ) );
	}

	public function enqueue(): void {
		if ( ! self::wanted() ) {
			return;
		}

		wp_enqueue_script( self::HANDLE, EFA_URL . 'assets/js/guest-nudge.js', array(), EFA_VERSION, true );
		wp_script_add_data( self::HANDLE, 'strategy', 'defer' );
		wp_localize_script(
			self::HANDLE,
			'efaGuestNudge',
			array(
				'signup' => Urls::signup_page(),
				'login'  => Urls::login_page(),
				'param'  => Urls::RETURN_PARAM,
				'text'   => array(
					'title'  => __( 'Nice work!', 'english-finders-account' ),
					'body'   => __( 'Create a free account to save your XP, keep a daily streak and review what you get wrong.', 'english-finders-account' ),
					'signup' => __( 'Sign up free', 'english-finders-account' ),
					'login'  => __( 'Log in', 'english-finders-account' ),
					'close'  => __( 'Dismiss', 'english-finders-account' ),
				),
			)
		);
	}

	/** Visitors who aren't signed in, on ordinary front-end pages. Filter: efa_guest_nudge_enabled. */
	public static function wanted(): bool {
		$wanted = ! is_user_logged_in() && ! is_admin() && ! AuthPages::needs_account_assets();

		return (bool) apply_filters( 'efa_guest_nudge_enabled', $wanted );
	}

	/**
	 * The level test's account link: /sign-up/ for visitors (members keep
	 * My Account, where their result is).
	 *
	 * @param string $url
	 */
	public function level_test_account_url( $url ): string {
		return is_user_logged_in() ? (string) $url : Urls::signup_page();
	}
}
