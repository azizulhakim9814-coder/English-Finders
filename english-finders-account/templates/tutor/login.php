<?php
/**
 * Replaces Tutor LMS's full-page login (0.12.0) -- see TutorLoginBridge.
 *
 * Same frame as Tutor's own templates/login.php (its custom header/footer
 * and wrap hooks), so the page around it is unchanged; only the form is
 * swapped for two buttons -- "Create a free account" (/sign-up/) and "Log in"
 * (/login/, 0.17.0) -- which return the learner here afterwards. Inline styles: account.css is not loaded on Tutor pages.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

use EnglishFindersAccount\Learning\TutorLoginBridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

tutor_utils()->tutor_custom_header();
$efa_login_url  = TutorLoginBridge::login_url_for_current_page();
$efa_signup_url = TutorLoginBridge::signup_url_for_current_page(); // 0.17.0.

//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Tutor's own hook name.
do_action( 'tutor/template/login/before/wrap' );
?>
<div <?php tutor_post_class( 'tutor-page-wrap tutor-w-full' ); ?>>
	<div class="efa-tutor-login" style="box-sizing:border-box;max-width:calc(100% - 32px);width:520px;margin:70px auto;padding:32px;background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 1px 2px rgba(16,24,40,.04);font-family:Lexend,sans-serif;text-align:center;">
		<h2 style="margin:0 0 12px;font-size:24px;font-weight:500;line-height:1.25;color:#0e2a4a;"><?php esc_html_e( 'Log in to continue', 'english-finders-account' ); ?></h2>
		<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;"><?php esc_html_e( 'Log in or create a free English Finders account to take quizzes, save your progress and earn a certificate. You will come straight back here afterwards.', 'english-finders-account' ); ?></p>
		<p style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin:0;">
			<a class="efa-tutor-login__button efa-tutor-login__signup" rel="nofollow" href="<?php echo esc_url( $efa_signup_url ); ?>" style="display:inline-block;box-sizing:border-box;padding:12px 24px;border-radius:5px;background:#075aae;color:#fff;font-size:15px;font-weight:500;line-height:1.2;text-decoration:none;"><?php esc_html_e( 'Create a free account', 'english-finders-account' ); ?></a>
			<a class="efa-tutor-login__button efa-tutor-login__login" rel="nofollow" href="<?php echo esc_url( $efa_login_url ); ?>" style="display:inline-block;box-sizing:border-box;padding:11px 23px;border:1px solid #075aae;border-radius:5px;background:#fff;color:#075aae;font-size:15px;font-weight:500;line-height:1.2;text-decoration:none;"><?php esc_html_e( 'Log in', 'english-finders-account' ); ?></a>
		</p>
	</div>
</div>
<?php
//phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Tutor's own hook name.
do_action( 'tutor/template/login/after/wrap' );
tutor_utils()->tutor_custom_footer();
