<?php
/**
 * Logged-out /my-account/ view: login and registration, side by side.
 *
 * 0.13.0: "Continue with Google", learner/teacher choice, required full
 * name, optional profile photo, the Terms/Privacy line, and the values
 * typed so far kept after a failed sign-up.
 * 0.14.0: the two forms live in partials/ and are shared with the
 * standalone [efa_login] / [efa_signup] pages (Pages\AuthPages).
 *
 * @package EnglishFindersAccount
 *
 * @var string $error              Error code from a failed submission, or ''.
 * @var string $form               Which form the error belongs to ('login'|'register'), or ''.
 * @var string $turnstile_site_key Cloudflare Turnstile site key, or '' if not configured.
 * @var string $return             Validated page to return to after login/registration (0.12.0), or ''.
 * @var array{name: string, email: string, account_type: string} $draft Values kept after a failed sign-up (0.13.0).
 */

declare(strict_types=1);

use EnglishFindersAccount\Pages\AuthView;
use EnglishFindersAccount\Profile\AccountType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error_message = AuthView::message( (string) $error );
$google_error  = str_starts_with( (string) $error, 'google_' );
$return        = isset( $return ) ? (string) $return : '';
$origin        = ''; // Errors from this page come back to My Account, the handlers' default.
$draft         = isset( $draft ) && is_array( $draft ) ? $draft : array(
	'name'         => '',
	'email'        => '',
	'account_type' => AccountType::LEARNER,
);
?>
<div class="efa-auth-page">
	<h1><?php esc_html_e( 'My account', 'english-finders-account' ); ?></h1>

	<?php if ( '' !== $return ) : ?>
		<div class="efa-notice efa-notice-info"><p><?php esc_html_e( 'Log in or create a free account to continue. You will go straight back to where you were.', 'english-finders-account' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $google_error && '' !== $error_message ) : ?>
		<div class="efa-notice efa-notice-error"><p><?php echo esc_html( $error_message ); ?></p></div>
	<?php endif; ?>

	<div class="efa-auth-grid">
		<section class="efa-auth-card">
			<h2><?php esc_html_e( 'Log in', 'english-finders-account' ); ?></h2>
			<?php if ( '' !== $error_message && 'login' === $form && ! $google_error ) : ?>
				<div class="efa-notice efa-notice-error"><p><?php echo esc_html( $error_message ); ?></p></div>
			<?php endif; ?>
			<?php include EFA_PATH . 'templates/public/partials/login-form.php'; ?>
		</section>

		<section class="efa-auth-card">
			<h2><?php esc_html_e( 'Create a free account', 'english-finders-account' ); ?></h2>
			<p class="efa-auth-lead"><?php esc_html_e( 'Save your progress, keep a streak and earn course certificates.', 'english-finders-account' ); ?></p>
			<?php if ( '' !== $error_message && 'register' === $form && ! $google_error ) : ?>
				<div class="efa-notice efa-notice-error"><p><?php echo esc_html( $error_message ); ?></p></div>
			<?php endif; ?>
			<?php include EFA_PATH . 'templates/public/partials/signup-form.php'; ?>
		</section>
	</div>
</div>
