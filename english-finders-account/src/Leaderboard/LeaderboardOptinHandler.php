<?php
/**
 * Join / leave the weekly leaderboard from the Leaderboard section.
 *
 * Writes the same `leaderboard_optin` profile flag the Profile form's
 * checkbox does -- one setting, two places to change it. admin-post
 * handler with a nonce, same shape as ProfileSaveHandler.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Leaderboard;

use EnglishFindersAccount\Profile\ProfileRepository;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LeaderboardOptinHandler {
	public const ACTION = 'efa_leaderboard_optin';

	public function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	public function handle(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to do that.', 'english-finders-account' ) );
		}

		check_admin_referer( self::ACTION );

		$join = ! empty( $_POST['join'] );
		( new ProfileRepository() )->set( get_current_user_id(), 'leaderboard_optin', $join );
		// 0.15.0: the public [efa_weekly_top] numbers change straight away (no two-minute wait).
		PublicBoardFile::refresh();

		wp_safe_redirect( add_query_arg( 'efa_notice', $join ? 'leaderboard_joined' : 'leaderboard_left', Urls::my_account() ) . '#efa-section-leaderboard' );
		exit;
	}
}
