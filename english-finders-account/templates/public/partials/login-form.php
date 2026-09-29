<?php
/**
 * The login form (0.14.0: shared by /my-account/ and the [efa_login] page).
 *
 * @package EnglishFindersAccount
 *
 * @var string $return             Validated page to return to after login, or ''.
 * @var string $origin             The page this form is on (errors come back here), or '' for My Account.
 * @var string $turnstile_site_key Cloudflare Turnstile site key, or ''.
 */

declare(strict_types=1);

use EnglishFindersAccount\Auth\GoogleLogin;
use EnglishFindersAccount\Pages\AuthView;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( GoogleLogin::is_configured() ) : ?>
	<a class="efa-google-btn" href="<?php echo esc_url( GoogleLogin::start_url( $return, $origin ) ); ?>">
		<?php echo AuthView::google_mark(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG. ?>
		<span><?php esc_html_e( 'Continue with Google', 'english-finders-account' ); ?></span>
	</a>
	<p class="efa-auth-divider"><span><?php esc_html_e( 'or log in with email', 'english-finders-account' ); ?></span></p>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="efa_login">
	<?php wp_nonce_field( 'efa_login' ); ?>
	<?php if ( '' !== $return ) : ?>
		<input type="hidden" name="<?php echo esc_attr( Urls::RETURN_PARAM ); ?>" value="<?php echo esc_attr( $return ); ?>">
	<?php endif; ?>
	<?php if ( '' !== $origin ) : ?>
		<input type="hidden" name="<?php echo esc_attr( Urls::ORIGIN_PARAM ); ?>" value="<?php echo esc_attr( $origin ); ?>">
	<?php endif; ?>
	<p>
		<label for="efa-login-log"><?php esc_html_e( 'Email or username', 'english-finders-account' ); ?></label>
		<input type="text" id="efa-login-log" name="log" required autocomplete="username">
	</p>
	<p>
		<label for="efa-login-pwd"><?php esc_html_e( 'Password', 'english-finders-account' ); ?></label>
		<span class="efa-pw-field" data-efa-password>
			<input type="password" id="efa-login-pwd" name="pwd" required autocomplete="current-password">
			<button type="button" class="efa-pw-toggle" aria-controls="efa-login-pwd" aria-pressed="false" hidden><?php esc_html_e( 'Show', 'english-finders-account' ); ?></button>
		</span>
	</p>
	<p class="efa-auth-row">
		<label><input type="checkbox" name="remember" value="1"> <?php esc_html_e( 'Remember me', 'english-finders-account' ); ?></label>
		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot your password?', 'english-finders-account' ); ?></a>
	</p>
	<?php if ( '' !== $turnstile_site_key ) : ?>
		<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $turnstile_site_key ); ?>" data-size="flexible"></div>
	<?php endif; ?>
	<button type="submit" class="efa-auth-submit"><?php esc_html_e( 'Log in', 'english-finders-account' ); ?></button>
</form>
