<?php
/**
 * Sends Tutor LMS's sign-in prompts to the My Account login (0.12.0).
 *
 * The problem (found live, 2026-09-24): Tutor renders its own login form --
 * as a full page for guests on a quiz or the /dashboard/ page, and as a
 * pop-up behind "Sign in"/enrol buttons on course pages. It logs in through
 * wp_signon(), and Wordfence Login Security, whose CAPTCHA is required on
 * this site, then demands a CAPTCHA token that Tutor's form never sends
 * (Wordfence's script isn't loaded there). Result: correct passwords are
 * rejected and an email-verification link is sent instead -- the same
 * failure EFA 0.3.1 fixed for its own form. Wordfence's log shows 45
 * valid-username failures against 11 successes over 14 days.
 *
 * The fix, chosen by the site owner: one login for the whole site. Instead
 * of Tutor's form, learners get a "Log in or sign up" button to /my-account/
 * (Turnstile-protected, Wordfence handled), which returns them to the
 * lesson/quiz afterwards. No Tutor settings change -- turning off Tutor's
 * native login would send learners to the site's private admin login URL.
 *
 *  - Full page: Tutor loads it via tutor_get_template('login'), which is
 *    filterable (`tutor_get_template_path`); EFA supplies its own template.
 *  - Pop-up: loaded from a fixed path with no filter, so its markup is
 *    buffered and dropped (before/after actions), and a small click handler
 *    sends its triggers (`.tutor-open-login-modal`) to the My Account login.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Learning;

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TutorLoginBridge {
	/** Tutor's pop-up login markup, loaded via tutor_load_template_from_custom_path(). */
	private const MODAL_TEMPLATE = 'views/modal/login.php';

	private bool $buffering = false;

	public function register_hooks(): void {
		/**
		 * Lets a site switch back to Tutor's own login form (e.g. if Tutor
		 * adds Wordfence support later) without a code change.
		 */
		if ( ! apply_filters( 'efa_replace_tutor_login', true ) ) {
			return;
		}

		add_filter( 'tutor_get_template_path', array( $this, 'filter_template_path' ), 10, 2 );
		add_action( 'tutor_load_template_from_custom_path_before', array( $this, 'start_modal_buffer' ) );
		add_action( 'tutor_load_template_from_custom_path_after', array( $this, 'drop_modal_buffer' ) );
		add_action( 'wp_footer', array( $this, 'print_click_redirect' ), 5 );
	}

	/** Swap Tutor's full-page login for EFA's "Log in to continue" template. */
	public function filter_template_path( $path, $template ) {
		return 'login' === $template ? EFA_PATH . 'templates/tutor/login.php' : $path;
	}

	public function start_modal_buffer( $template ): void {
		if ( self::is_modal( (string) $template ) && ! $this->buffering ) {
			$this->buffering = true;
			ob_start();
		}
	}

	public function drop_modal_buffer( $template ): void {
		if ( self::is_modal( (string) $template ) && $this->buffering ) {
			$this->buffering = false;
			ob_end_clean();
		}
	}

	/**
	 * Guests only; printed on every front-end page because Tutor's triggers
	 * appear in course cards as well as course pages, and the page itself is
	 * cached for guests anyway. The return URL is taken from the browser, so
	 * a cached copy still returns each visitor to the page they are on.
	 */
	public function print_click_redirect(): void {
		if ( is_user_logged_in() || ! function_exists( 'tutor_utils' ) ) {
			return;
		}

		$config = wp_json_encode(
			array(
				'login' => Urls::login_page(), // 0.17.0: the /login/ page (falls back to My Account).
				'param' => Urls::RETURN_PARAM,
			)
		);
		?>
		<script data-no-optimize="1" id="efa-tutor-login-redirect">
		(function (c) {
			window.addEventListener('click', function (e) {
				var t = e.target && e.target.closest ? e.target.closest('.tutor-open-login-modal') : null;
				if (!t) { return; }
				e.preventDefault();
				e.stopImmediatePropagation();
				window.location.href = c.login + (c.login.indexOf('?') < 0 ? '?' : '&') + c.param + '=' + encodeURIComponent(window.location.href.split('#')[0]);
			}, true);
		})(<?php echo $config; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() of two plugin-controlled strings. ?>);
		</script>
		<?php
	}

	/** The full-page template's "Log in" button: the /login/ page, then back to this page (0.17.0). */
	public static function login_url_for_current_page(): string {
		return Urls::login_page( self::current_url() );
	}

	/** Its "Create a free account" button: /sign-up/, then back to this page (0.17.0). */
	public static function signup_url_for_current_page(): string {
		return Urls::signup_page( self::current_url() );
	}

	private static function current_url(): string {
		return function_exists( 'tutor_utils' ) ? (string) tutor_utils()->get_current_url() : '';
	}

	private static function is_modal( string $template ): bool {
		return str_ends_with( str_replace( '\\', '/', $template ), self::MODAL_TEMPLATE );
	}
}
