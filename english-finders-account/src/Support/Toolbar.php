<?php
/**
 * Hides WordPress's admin toolbar from learners (0.13.1).
 *
 * WordPress shows its toolbar on the front end to every logged-in user by
 * default (checked live: "show toolbar" is on for all 14,617 accounts). For
 * a learner it's just a way into the WordPress dashboard and its profile
 * screen -- the My Account page replaces both.
 *
 *  - On /my-account/: hidden for everyone, staff included.
 *  - Elsewhere: hidden for learners; kept for staff (anyone who can edit
 *    posts, and Tutor instructors), who use it to reach the dashboard.
 *
 * Filter `efa_show_admin_bar` ($show, $user_id, $is_account_page) to adjust.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Toolbar {
	public function register_hooks(): void {
		add_filter( 'show_admin_bar', array( $this, 'filter_show' ), 20 );
	}

	/** @param mixed $show */
	public function filter_show( $show ) {
		if ( ! $show || ! is_user_logged_in() ) {
			return $show;
		}

		$user_id         = get_current_user_id();
		$is_account_page = is_page( 'my-account' );
		$visible         = ! $is_account_page && self::is_staff( $user_id );

		return (bool) apply_filters( 'efa_show_admin_bar', $visible, $user_id, $is_account_page );
	}

	/** Staff = can edit posts (contributors and up), or is a Tutor instructor. */
	public static function is_staff( int $user_id ): bool {
		if ( user_can( $user_id, 'edit_posts' ) ) {
			return true;
		}

		$user = get_userdata( $user_id );

		return $user && in_array( 'tutor_instructor', (array) $user->roles, true );
	}
}
