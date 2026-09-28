<?php
/**
 * The sign-up form (0.14.0: shared by /my-account/ and the [efa_signup] page).
 *
 * The Google button is a second submit button of this same form: its
 * name="action" comes after the hidden one, and PHP keeps the last value,
 * so the learner/teacher choice, the return address and the origin page
 * travel with it without any JavaScript.
 *
 * @package EnglishFindersAccount
 *
 * @var string $return             Validated page to return to after sign-up, or ''.
 * @var string $origin             The page this form is on (errors come back here), or '' for My Account.
 * @var string $turnstile_site_key Cloudflare Turnstile site key, or ''.
 * @var array{name: string, email: string, account_type: string} $draft Values kept after a failed attempt.
 */

declare(strict_types=1);

use EnglishFindersAccount\Auth\GoogleLogin;
use EnglishFindersAccount\Pages\AuthView;
use EnglishFindersAccount\Profile\AccountType;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
	<input type="hidden" name="action" value="efa_register">
	<?php wp_nonce_field( 'efa_register' ); ?>
	<?php if ( '' !== $return ) : ?>
		<input type="hidden" name="<?php echo esc_attr( Urls::RETURN_PARAM ); ?>" value="<?php echo esc_attr( $return ); ?>">
	<?php endif; ?>
	<?php if ( '' !== $origin ) : ?>
		<input type="hidden" name="<?php echo esc_attr( Urls::ORIGIN_PARAM ); ?>" value="<?php echo esc_attr( $origin ); ?>">
	<?php endif; ?>

	<fieldset class="efa-role-choice">
		<legend><?php esc_html_e( 'Which describes you?', 'english-finders-account' ); ?></legend>
		<?php foreach ( AccountType::options() as $efa_type => $efa_label ) : ?>
			<label class="efa-role-choice__option">
				<input type="radio" name="account_type" value="<?php echo esc_attr( $efa_type ); ?>" <?php checked( $draft['account_type'], $efa_type ); ?>>
				<span><?php echo esc_html( $efa_label ); ?></span>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<?php if ( GoogleLogin::is_configured() ) : ?>
		<button type="submit" class="efa-google-btn" name="action" value="<?php echo esc_attr( GoogleLogin::START_ACTION ); ?>" formnovalidate>
			<?php echo AuthView::google_mark(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG. ?>
			<span><?php esc_html_e( 'Sign up with Google', 'english-finders-account' ); ?></span>
		</button>
		<p class="efa-auth-divider"><span><?php esc_html_e( 'or sign up with email', 'english-finders-account' ); ?></span></p>
	<?php endif; ?>

	<p class="efa-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;">
		<label for="efa-reg-website"><?php esc_html_e( 'Website', 'english-finders-account' ); ?></label>
		<input type="text" id="efa-reg-website" name="efa_hp_website" tabindex="-1" autocomplete="off">
	</p>
	<p>
		<label for="efa-reg-display-name"><?php esc_html_e( 'Full name', 'english-finders-account' ); ?></label>
		<input type="text" id="efa-reg-display-name" name="display_name" required minlength="2" maxlength="80" autocomplete="name" value="<?php echo esc_attr( $draft['name'] ); ?>">
		<span class="efa-field-hint"><?php esc_html_e( 'Shown on your certificates.', 'english-finders-account' ); ?></span>
	</p>
	<p>
		<label for="efa-reg-email"><?php esc_html_e( 'Email', 'english-finders-account' ); ?></label>
		<input type="email" id="efa-reg-email" name="email" required autocomplete="email" value="<?php echo esc_attr( $draft['email'] ); ?>">
	</p>
	<p>
		<label for="efa-reg-password"><?php esc_html_e( 'Password', 'english-finders-account' ); ?></label>
		<span class="efa-pw-field" data-efa-password>
			<input type="password" id="efa-reg-password" name="password" required minlength="8" autocomplete="new-password">
			<button type="button" class="efa-pw-toggle" aria-controls="efa-reg-password" aria-pressed="false" hidden><?php esc_html_e( 'Show', 'english-finders-account' ); ?></button>
		</span>
		<span class="efa-field-hint"><?php esc_html_e( 'At least 8 characters.', 'english-finders-account' ); ?></span>
	</p>
	<p>
		<label for="efa-reg-password-confirm"><?php esc_html_e( 'Confirm password', 'english-finders-account' ); ?></label>
		<span class="efa-pw-field" data-efa-password>
			<input type="password" id="efa-reg-password-confirm" name="password_confirm" required minlength="8" autocomplete="new-password" data-efa-match="efa-reg-password" aria-describedby="efa-reg-password-match">
			<button type="button" class="efa-pw-toggle" aria-controls="efa-reg-password-confirm" aria-pressed="false" hidden><?php esc_html_e( 'Show', 'english-finders-account' ); ?></button>
		</span>
		<span class="efa-pw-match" id="efa-reg-password-match" data-efa-match-status aria-live="polite"></span>
	</p>
	<div class="efa-photo-field efa-photo-field--signup" data-efa-photo-picker>
		<div class="efa-photo-field__preview efa-photo-field__preview--empty" data-efa-photo-preview aria-hidden="true">
			<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
		</div>
		<div class="efa-photo-field__controls">
			<label for="efa-reg-avatar"><?php esc_html_e( 'Profile photo (optional)', 'english-finders-account' ); ?></label>
			<input type="file" id="efa-reg-avatar" name="avatar" accept="image/jpeg,image/png,.jpg,.jpeg,.png">
			<span class="efa-field-hint"><?php esc_html_e( 'JPG or PNG. Large photos are made smaller before upload.', 'english-finders-account' ); ?></span>
			<span class="efa-photo-field__status" data-efa-photo-status aria-live="polite"></span>
		</div>
	</div>
	<?php if ( '' !== $turnstile_site_key ) : ?>
		<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $turnstile_site_key ); ?>" data-size="flexible"></div>
	<?php endif; ?>
	<button type="submit" class="efa-auth-submit"><?php esc_html_e( 'Create account', 'english-finders-account' ); ?></button>
	<p class="efa-auth-terms">
		<?php
		printf(
			/* translators: 1: Terms of Service link, 2: Privacy Policy link */
			esc_html__( 'By signing up, you agree to our %1$s and %2$s.', 'english-finders-account' ),
			'<a href="' . esc_url( home_url( '/terms-of-use/' ) ) . '">' . esc_html__( 'Terms of Service', 'english-finders-account' ) . '</a>',
			'<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'english-finders-account' ) . '</a>'
		);
		?>
	</p>
</form>
