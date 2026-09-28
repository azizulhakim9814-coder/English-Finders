<?php
/**
 * "Got it" on a mistake-notebook entry: the learner marks it learned.
 *
 * admin-post handler with a nonce, same shape as ProfileSaveHandler. The
 * repository scopes the update to the current user's own rows, so posting
 * someone else's entry id changes nothing.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Mistakes;

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ResolveMistakeHandler {
	public const ACTION = 'efa_resolve_mistake';

	public function register_hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	public function handle(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to do that.', 'english-finders-account' ) );
		}

		check_admin_referer( self::ACTION );

		$id   = isset( $_POST['mistake_id'] ) ? absint( wp_unslash( $_POST['mistake_id'] ) ) : 0;
		$repo = MistakesController::repository();
		$done = null !== $repo && $id > 0 && $repo->resolve( get_current_user_id(), $id ) > 0;

		// Keep the learner's skill filter, and land them back on the notebook.
		$skill = isset( $_POST['skill'] ) ? sanitize_key( wp_unslash( $_POST['skill'] ) ) : '';
		$args  = array( 'efa_notice' => $done ? 'mistake_resolved' : 'request_failed' );
		if ( '' !== $skill ) {
			$args[ MistakesController::SKILL_PARAM ] = $skill;
		}

		wp_safe_redirect( add_query_arg( $args, Urls::my_account() ) . '#efa-section-mistakes' );
		exit;
	}
}
