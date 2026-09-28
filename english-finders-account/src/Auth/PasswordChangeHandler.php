<?php
/**
 * Change password from My Account (0.13.0).
 *
 * Current password + new password (8+ characters) + confirmation. Accounts
 * created with "Continue with Google" have no password the learner knows,
 * so for them (`efa_password_set` = '0') the current-password field is
 * skipped the first time -- they are already signed in through Google.
 *
 * wp_update_user() does the change, which (a) sends WordPress's standard
 * "your password was changed" email, and (b) re-issues this browser's login
 * cookie. Every other device's cookie embeds the old password hash, so
 * those sessions end -- the notice says so.
 *
 * wp_check_password() compares the hash directly; it doesn't go through
 * the `authenticate` filters, so Wordfence's login CAPTCHA isn't involved.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Auth;

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PasswordChangeHandler {
	public const ACTION     = 'efa_change_password';
	public const MIN_LENGTH = 8;

	public function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/** True when the learner has never chosen a password (Google-created account). */
	public static function needs_current_password( int $user_id ): bool {
		return '0' !== (string) get_user_meta( $user_id, GoogleLogin::PASSWORD_SET_META, true );
	}

	public function handle(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to do that.', 'english-finders-account' ) );
		}

		check_admin_referer( self::ACTION );

		$user    = wp_get_current_user();
		$current = isset( $_POST['current_password'] ) ? (string) wp_unslash( $_POST['current_password'] ) : '';
		$new     = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
		$confirm = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

		if ( self::needs_current_password( (int) $user->ID ) && ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
			$this->back( 'password_wrong' );
		}
		if ( strlen( $new ) < self::MIN_LENGTH ) {
			$this->back( 'password_short' );
		}
		if ( $new !== $confirm ) {
			$this->back( 'password_mismatch' );
		}

		$result = wp_update_user(
			array(
				'ID'        => (int) $user->ID,
				'user_pass' => $new,
			)
		);
		if ( is_wp_error( $result ) ) {
			$this->back( 'password_failed' );
		}

		update_user_meta( (int) $user->ID, GoogleLogin::PASSWORD_SET_META, '1' );
		$this->back( 'password_changed' );
	}

	private function back( string $notice ): void {
		wp_safe_redirect( add_query_arg( 'efa_notice', $notice, Urls::my_account() ) . '#efa-section-password' );
		exit;
	}
}
