<?php
/**
 * Membership & Billing section, included from account-shell.php.
 *
 * Rendered only when Core's billing services are actually available and
 * new enough (MembershipController::data_for_user() returns null
 * otherwise) -- silently absent rather than a broken or "coming soon"
 * section, the same honesty principle A1 applied to the other not-yet-built
 * sections.
 *
 * @package EnglishFindersAccount
 *
 * @var array{entitlement: object, transactions: list<array<string,mixed>>, checkout: array{client_side_token: string, price_plan_map: array<string,string>}}|null $membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( null === $membership ) {
	return;
}

$entitlement    = $membership['entitlement'];
$transactions   = $membership['transactions'];
$plan_labels = array(
	'pro'         => __( 'Pro', 'english-finders-account' ),
	'teacher_pro' => __( 'Teacher Pro', 'english-finders-account' ),
);
?>
<section class="efa-account-section efa-membership-section" id="efa-section-membership">
	<h2><?php esc_html_e( 'Membership & billing', 'english-finders-account' ); ?></h2>

	<p>
		<?php
		printf(
			/* translators: %s: plan name, e.g. "Free" or "Pro" */
			esc_html__( 'Current plan: %s', 'english-finders-account' ),
			esc_html( $plan_labels[ $entitlement->plan_code ] ?? ucfirst( $entitlement->plan_code ) )
		);
		?>
		<?php if ( $entitlement->unlocks_pro() && null !== $entitlement->current_period_end ) : ?>
			<br>
			<?php
			printf(
				/* translators: %s: renewal date */
				esc_html__( 'Renews %s', 'english-finders-account' ),
				esc_html( $entitlement->current_period_end )
			);
			?>
		<?php endif; ?>
	</p>

	<?php if ( $entitlement->unlocks_pro() ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="efa_manage_billing">
			<?php wp_nonce_field( 'efa_manage_billing' ); ?>
			<button type="submit"><?php esc_html_e( 'Manage billing', 'english-finders-account' ); ?></button>
		</form>
		<p class="description"><?php esc_html_e( 'Change plan, update payment method, view invoices, or cancel -- all on a secure Paddle page.', 'english-finders-account' ); ?></p>
	<?php elseif ( ! empty( $membership['offer']['on_sale'] ) ) : ?>
		<?php
		// 0.18.0: Pro = no ads + 5 streak freezes every month; monthly and yearly prices.
		$efa_offer  = $membership['offer'];
		$efa_prices = $efa_offer['prices'];
		$efa_labels = $efa_offer['labels'];
		?>
		<p class="efa-pro-pitch">
			<?php
			esc_html_e( 'Upgrade to Pro: no ads anywhere on English Finders, and 5 streak freezes topped up every month.', 'english-finders-account' );
			// 0.21.0: AI writing checks, only while the feature is on.
			$efa_ai = \EnglishFindersAccount\Membership\ProOffer::ai_allowances();
			if ( null !== $efa_ai ) {
				echo ' ';
				/* translators: %s: number of checks */
				echo esc_html( sprintf( _n( 'You also get %s AI writing check a day.', 'You also get %s AI writing checks a day.', $efa_ai['pro'], 'english-finders-account' ), number_format_i18n( $efa_ai['pro'] ) ) );
			}
			?>
		</p>
		<div class="efa-plan-upgrade-buttons">
			<?php if ( '' !== $efa_prices['year'] ) : ?>
				<button type="button" data-efa-price-id="<?php echo esc_attr( $efa_prices['year'] ); ?>">
					<?php
					/* translators: 1: yearly price, 2: saving */
					printf( esc_html__( 'Get Pro yearly · %1$s (save %2$s)', 'english-finders-account' ), esc_html( $efa_labels['year'] ), esc_html( $efa_labels['saving'] ) );
					?>
				</button>
			<?php endif; ?>
			<?php if ( '' !== $efa_prices['month'] ) : ?>
				<button type="button" data-efa-price-id="<?php echo esc_attr( $efa_prices['month'] ); ?>">
					<?php
					/* translators: %s: monthly price */
					printf( esc_html__( 'Get Pro monthly · %s', 'english-finders-account' ), esc_html( $efa_labels['month'] ) );
					?>
				</button>
			<?php endif; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Payments are handled securely by Paddle.com, our online reseller. Cancel any time here.', 'english-finders-account' ); ?></p>
	<?php endif; ?>

	<?php if ( array() !== $transactions ) : ?>
		<h3><?php esc_html_e( 'Recent transactions', 'english-finders-account' ); ?></h3>
		<table class="efa-transaction-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'english-finders-account' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'english-finders-account' ); ?></th>
					<th><?php esc_html_e( 'Status', 'english-finders-account' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $transactions as $transaction ) : ?>
					<tr>
						<td><?php echo esc_html( $transaction['occurred_at'] ); ?></td>
						<td>
							<?php
							echo '' !== $transaction['amount']
								? esc_html( $transaction['amount'] . ' ' . $transaction['currency'] )
								: '&mdash;';
							?>
						</td>
						<td><?php echo esc_html( $transaction['status'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</section>
