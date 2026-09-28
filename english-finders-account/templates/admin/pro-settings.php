<?php
/**
 * Settings -> English Finders Pro (0.18.0) -- see Membership\BillingSettingsPage.
 *
 * @package EnglishFindersAccount
 *
 * @var string $environment 'sandbox' | 'production'
 * @var string $token
 * @var array{month:string,year:string} $prices
 * @var array<string,bool> $checks
 * @var bool   $on_sale
 * @var string $webhook
 * @var string $result
 * @var bool   $promo  0.19.0: "Go Pro" links switched on
 */

declare(strict_types=1);

use EnglishFindersAccount\Membership\BillingSettingsPage;
use EnglishFindersAccount\Membership\ProOffer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$efa_labels = ProOffer::labels();
$efa_items  = array(
	'core'    => __( 'English Finders Core 1.6.0 or newer is active', 'english-finders-account' ),
	'secret'  => __( 'Webhook secret: EFC_PADDLE_WEBHOOK_SECRET in wp-config.php (needed so payments turn on Pro)', 'english-finders-account' ),
	'api_key' => __( 'API key: EFC_PADDLE_API_KEY in wp-config.php (needed for "Manage billing")', 'english-finders-account' ),
	'token'   => __( 'Client-side token (below)', 'english-finders-account' ),
	'prices'  => __( 'At least one Pro price ID (below)', 'english-finders-account' ),
	'match'   => __( 'The token matches the environment (test_… for sandbox, live_… for live)', 'english-finders-account' ),
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'English Finders Pro', 'english-finders-account' ); ?></h1>

	<?php if ( 'saved' === $result ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'mismatch' === $result ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'Saved, but the client-side token does not match the environment: live tokens start with live_, sandbox tokens with test_.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'invalid' === $result ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Not saved. The token must start with test_ or live_, price IDs with pri_, and the monthly and yearly prices must be different.', 'english-finders-account' ); ?></p></div>
	<?php endif; ?>

	<div class="notice <?php echo $on_sale ? 'notice-success' : 'notice-info'; ?> inline" style="margin:16px 0;">
		<p><strong>
			<?php
			echo $on_sale
				? esc_html__( 'Pro is on sale.', 'english-finders-account' )
				: esc_html__( 'Pro is not on sale yet. The pricing page shows "Coming soon" until every item below is ticked.', 'english-finders-account' );
			?>
		</strong>
		<?php if ( $on_sale ) : ?>
			<?php
			/* translators: %s: sandbox or live */
			printf( ' ' . esc_html__( 'Environment: %s.', 'english-finders-account' ), 'production' === $environment ? esc_html__( 'live', 'english-finders-account' ) : esc_html__( 'sandbox (test payments only)', 'english-finders-account' ) );
			?>
		<?php endif; ?>
		</p>
	</div>

	<h2><?php esc_html_e( 'Checklist', 'english-finders-account' ); ?></h2>
	<ul>
		<?php foreach ( $efa_items as $efa_key => $efa_label ) : ?>
			<li><?php echo ! empty( $checks[ $efa_key ] ) ? '<span style="color:#187a41;">&#10004;</span>' : '<span style="color:#b42318;">&#10008;</span>'; ?> <?php echo esc_html( $efa_label ); ?></li>
		<?php endforeach; ?>
	</ul>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( BillingSettingsPage::ACTION ); ?>">
		<?php wp_nonce_field( BillingSettingsPage::ACTION ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Environment', 'english-finders-account' ); ?></th>
				<td>
					<label><input type="radio" name="environment" value="sandbox" <?php checked( 'sandbox', $environment ); ?>> <?php esc_html_e( 'Sandbox (test payments)', 'english-finders-account' ); ?></label><br>
					<label><input type="radio" name="environment" value="production" <?php checked( 'production', $environment ); ?>> <?php esc_html_e( 'Live (real payments)', 'english-finders-account' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="efa-client-token"><?php esc_html_e( 'Client-side token', 'english-finders-account' ); ?></label></th>
				<td><input type="text" class="regular-text code" id="efa-client-token" name="client_token" value="<?php echo esc_attr( $token ); ?>" placeholder="live_…" autocomplete="off">
				<p class="description"><?php esc_html_e( 'Paddle → Developer tools → Authentication → Client-side tokens. This one is public by design (it goes into the page). Keep the API key and webhook secret in wp-config.php, not here.', 'english-finders-account' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="efa-price-month">
					<?php
					/* translators: %s: monthly price */
					printf( esc_html__( 'Monthly price ID (%s)', 'english-finders-account' ), esc_html( $efa_labels['month'] ) );
					?>
				</label></th>
				<td><input type="text" class="regular-text code" id="efa-price-month" name="price_month" value="<?php echo esc_attr( $prices['month'] ); ?>" placeholder="pri_…"></td>
			</tr>
			<tr>
				<th scope="row"><label for="efa-price-year">
					<?php
					/* translators: %s: yearly price */
					printf( esc_html__( 'Yearly price ID (%s)', 'english-finders-account' ), esc_html( $efa_labels['year'] ) );
					?>
				</label></th>
				<td><input type="text" class="regular-text code" id="efa-price-year" name="price_year" value="<?php echo esc_attr( $prices['year'] ); ?>" placeholder="pri_…">
				<p class="description"><?php esc_html_e( 'Paddle → Catalog → Products → English Finders Pro → Prices. The amounts shown on the site are $2.99 and $24.99, so set the same amounts in Paddle.', 'english-finders-account' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( '"Go Pro" links', 'english-finders-account' ); ?></th>
				<td>
					<label><input type="checkbox" name="promo" value="1" <?php checked( $promo ); ?>> <?php esc_html_e( 'Show "Go Pro" to signed-in free members: in the header next to My account, and a short note under articles', 'english-finders-account' ); ?></label>
					<p class="description"><?php esc_html_e( 'Only while Pro is on sale. Visitors who are not signed in never see these. Pro members always get a "PRO" badge on their My account button.', 'english-finders-account' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save', 'english-finders-account' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'Webhook (enter this in Paddle)', 'english-finders-account' ); ?></h2>
	<p><?php esc_html_e( 'Paddle → Developer tools → Notifications → New destination:', 'english-finders-account' ); ?></p>
	<p><code><?php echo esc_html( $webhook ); ?></code></p>
	<p><?php esc_html_e( 'Events:', 'english-finders-account' ); ?> <code><?php echo esc_html( implode( ', ', BillingSettingsPage::EVENTS ) ); ?></code></p>
</div>
