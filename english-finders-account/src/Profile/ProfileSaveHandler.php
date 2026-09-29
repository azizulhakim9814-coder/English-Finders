<?php
/**
 * Saves the profile/privacy form on the logged-in My Account page.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Profile;

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProfileSaveHandler {
	private const ACTION = 'efa_save_profile';

	public function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	public function handle(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to do that.', 'english-finders-account' ) );
		}

		check_admin_referer( self::ACTION );

		$user_id = get_current_user_id();
		$repo    = new ProfileRepository();

		// 0.13.0: the full name is required; an empty or invalid one is refused (the old name stays).
		// Only fields the form actually sent are touched.
		if ( isset( $_POST['display_name'] ) ) {
			$full_name = FullName::clean( (string) wp_unslash( $_POST['display_name'] ) );
			if ( ! FullName::is_valid( $full_name ) ) {
				$this->back( 'name_invalid' );
			}
			FullName::save( $user_id, $full_name );
		}

		if ( isset( $_POST['account_type'] ) ) {
			$repo->set( $user_id, 'account_type', AccountType::normalize( sanitize_key( wp_unslash( $_POST['account_type'] ) ) ) );
		}

		$avatar = new Avatar();
		$photo  = isset( $_FILES['avatar'] ) && is_array( $_FILES['avatar'] ) ? $_FILES['avatar'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated by Avatar.
		if ( ! empty( $_POST['remove_avatar'] ) ) {
			$avatar->delete( $user_id );
		} elseif ( Avatar::was_submitted( $photo ) ) {
			$photo_error = $avatar->store_upload( $user_id, $photo );
			if ( null !== $photo_error ) {
				$this->back( $photo_error );
			}
		}

		$repo->set( $user_id, 'native_language', isset( $_POST['native_language'] ) ? sanitize_text_field( wp_unslash( $_POST['native_language'] ) ) : '' );
		/*
		 * Daily goal (0.9.0): only a real Core DailyGoal tier is stored;
		 * anything else -- or a missing field -- leaves the saved goal as it
		 * was rather than resetting it.
		 */
		$goal = isset( $_POST['daily_goal'] ) ? sanitize_key( wp_unslash( $_POST['daily_goal'] ) ) : '';
		if ( class_exists( '\\EnglishFindersCore\\Activity\\DailyGoal' ) && \EnglishFindersCore\Activity\DailyGoal::is_valid( $goal ) ) {
			$repo->set( $user_id, 'daily_goal', $goal );
		}
		$repo->set( $user_id, 'public_profile', ! empty( $_POST['public_profile'] ) );
		$repo->set( $user_id, 'leaderboard_optin', ! empty( $_POST['leaderboard_optin'] ) );
		$repo->set( $user_id, 'notifications_enabled', ! empty( $_POST['notifications_enabled'] ) );

		$this->back( 'saved' );
	}

	private function back( string $notice ): void {
		wp_safe_redirect( add_query_arg( 'efa_notice', $notice, Urls::my_account() ) . ( 'saved' === $notice ? '' : '#efa-section-profile' ) );
		exit;
	}
}
