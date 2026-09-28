<?php
/**
 * First steps after sign-up (0.20.0): account -> level test -> course.
 *
 * Found live 2026-09-28: sign-ups had nowhere in particular to go. Most
 * came back to the page they signed up from (the header links and the
 * "keep your progress" bar carry a return address) and were never told
 * about the level test, which is what picks their course -- only 3 real
 * learners had ever taken it.
 *
 * Where a new account goes is decided by first_url():
 *
 *  - a return address (the lesson, quiz or page they signed up from)
 *    always wins: the learner is sent back to it, with the welcome banner;
 *  - otherwise a learner without a level result goes straight to the level
 *    test, with the welcome banner;
 *  - teachers, and learners whose anonymous result was just attached to
 *    the new account (English Finders Study claims it on user_register),
 *    go to My Account, whose Home card already names the course to start.
 *
 * The banner is shown on that one page view only -- it is driven by a
 * query parameter, not stored state -- and only to a signed-in learner who
 * still has no level result. That response is never cached.
 *
 * Filters: efa_after_signup_url (string $url, int $user_id, string $return),
 * efa_welcome_note_enabled (bool, int $user_id).
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Pages;

use EnglishFindersAccount\Level\LevelController;
use EnglishFindersAccount\Profile\AccountType;
use EnglishFindersAccount\Profile\ProfileRepository;
use EnglishFindersAccount\Support\Urls;
use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WelcomeNote {
	/** Query parameter that asks for the banner on the first page after sign-up. */
	public const PARAM = 'efa_welcome';

	public function register_hooks(): void {
		add_action( 'wp', array( $this, 'prevent_caching' ) );
		add_action( 'wp_body_open', array( $this, 'render' ) );
	}

	/**
	 * The first page a brand-new account is sent to. See the class docblock.
	 *
	 * @param int    $user_id The new account.
	 * @param string $return  The posted return address, if any (validated here).
	 */
	public static function first_url( int $user_id, string $return ): string {
		$return    = Urls::return_target( $return );
		$test_page = LevelController::test_page_url();

		if ( '' !== $return ) {
			$url = self::is_learner_without_result( $user_id ) ? add_query_arg( self::PARAM, '1', $return ) : $return;
		} elseif ( '' !== $test_page && self::is_learner_without_result( $user_id ) ) {
			$url = add_query_arg( self::PARAM, '1', $test_page );
		} else {
			$url = Urls::my_account();
		}

		/**
		 * Filter where a new account goes straight after sign-up.
		 *
		 * @param string $url     Chosen address.
		 * @param int    $user_id The new account.
		 * @param string $return  The validated return address, or ''.
		 */
		return (string) apply_filters( 'efa_after_signup_url', $url, $user_id, $return );
	}

	/** A learner account (not a teacher) with no English Level Test result yet. */
	public static function is_learner_without_result( int $user_id ): bool {
		if ( $user_id <= 0 || AccountType::TEACHER === ( new ProfileRepository() )->get( $user_id, 'account_type' ) ) {
			return false;
		}

		if ( ! class_exists( '\\EnglishFindersCore\\Support\\Api' ) ) {
			return false;
		}

		$results = Api::service( 'level_results' );

		return $results instanceof \EnglishFindersCore\Assessment\LevelResultRepository && null === $results->latest_for_user( $user_id );
	}

	/** Whether this request should carry the banner. */
	private static function wanted(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag only; nothing is changed.
		if ( is_admin() || ! isset( $_GET[ self::PARAM ] ) || ! is_user_logged_in() ) {
			return false;
		}

		$user_id = get_current_user_id();

		return (bool) apply_filters( 'efa_welcome_note_enabled', self::is_learner_without_result( $user_id ), $user_id );
	}

	/** The banner carries the learner's name, so that one response is never stored. */
	public function prevent_caching(): void {
		if ( ! self::wanted() ) {
			return;
		}

		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'english finders welcome note: personalised' );
	}

	public function render(): void {
		if ( ! self::wanted() ) {
			return;
		}

		$user     = wp_get_current_user();
		$first    = trim( (string) $user->first_name );
		$name     = '' !== $first ? $first : (string) $user->display_name;
		$test_url = LevelController::test_page_url();
		$on_test  = '' !== $test_url && is_page( LevelController::TEST_PAGE_SLUG );
		?>
		<div class="efa-welcome-note" role="status" style="background:#eef6ee;border-bottom:1px solid #b7d8b9;color:#1d3b20;padding:12px 16px;text-align:center;font-size:15px;line-height:1.5">
			<strong>
				<?php
				/* translators: %s: the learner's first name */
				echo esc_html( sprintf( __( 'Welcome, %s! Your free account is ready.', 'english-finders-account' ), $name ) );
				?>
			</strong>
			<?php if ( $on_test ) : ?>
				<?php esc_html_e( 'Start with the short level test below: it finds your CEFR level (A1 to C2) and shows which course to begin with.', 'english-finders-account' ); ?>
			<?php elseif ( '' !== $test_url ) : ?>
				<?php esc_html_e( 'Next step: take the short level test to find your CEFR level (A1 to C2) and the course to begin with.', 'english-finders-account' ); ?>
				<a href="<?php echo esc_url( $test_url ); ?>" style="color:inherit;font-weight:600;text-decoration:underline"><?php esc_html_e( 'Take the level test', 'english-finders-account' ); ?> &rarr;</a>
			<?php endif; ?>
		</div>
		<?php
	}
}
