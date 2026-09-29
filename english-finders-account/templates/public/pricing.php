<?php
/**
 * [efa_pricing] (0.18.0) -- see Membership\PricingPage.
 *
 * @package EnglishFindersAccount
 *
 * @var bool   $member
 * @var bool   $pro
 * @var bool   $on_sale
 * @var array{month:string,year:string} $prices  Paddle price ids ('' = not set).
 * @var array{month:string,year:string,saving:string} $labels
 * @var string $signup    /sign-up/, returning to this page.
 * @var bool   $print_css
 */

declare(strict_types=1);

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$efa_free = array(
	__( 'All A1–C2 courses and lessons', 'english-finders-account' ),
	__( '9 practice tools and every word game', 'english-finders-account' ),
	__( 'The free level test and course certificates', 'english-finders-account' ),
	__( 'XP, daily streaks and the weekly leaderboard', 'english-finders-account' ),
	__( '2 streak freezes', 'english-finders-account' ),
);
$efa_pro = array(
	__( 'Everything in Free', 'english-finders-account' ),
	__( 'No ads anywhere on English Finders', 'english-finders-account' ),
	__( '5 streak freezes, topped up every month', 'english-finders-account' ),
	__( 'Supports new lessons, tools and games', 'english-finders-account' ),
);

// 0.21.0: AI writing checks, listed only while the feature is on.
$efa_ai = \EnglishFindersAccount\Membership\ProOffer::ai_allowances();
if ( null !== $efa_ai ) {
	if ( $efa_ai['free'] > 0 ) {
		/* translators: %s: number of checks */
		array_splice( $efa_free, 2, 0, array( sprintf( _n( '%s AI writing check a day', '%s AI writing checks a day', $efa_ai['free'], 'english-finders-account' ), number_format_i18n( $efa_ai['free'] ) ) ) );
	}
	/* translators: %s: number of checks */
	array_splice( $efa_pro, 2, 0, array( sprintf( _n( '%s AI writing check a day', '%s AI writing checks a day', $efa_ai['pro'], 'english-finders-account' ), number_format_i18n( $efa_ai['pro'] ) ) ) );
}
?>
<?php if ( $print_css ) : ?>
<style id="efa-pricing-css" data-no-optimize="1">
.efa-pricing{box-sizing:border-box;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;max-width:860px;margin:0 auto 24px;font-family:Lexend,sans-serif;color:#0e2a4a}
.efa-pricing *{box-sizing:border-box}
.efa-plan{display:flex;flex-direction:column;padding:26px 24px;border:1px solid #e3e8ef;border-radius:16px;background:#fff}
.efa-plan--pro{border:2px solid #075aae;box-shadow:0 8px 24px rgba(7,90,174,.12)}
.efa-plan__badge{align-self:flex-start;margin:0 0 10px;padding:3px 10px;border-radius:25px;background:#e8f1fb;color:#075aae;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}
.efa-plan .efa-plan__name{margin:0 0 4px;font-size:22px;font-weight:600;line-height:1.25;color:#0e2a4a}
.efa-plan__price{margin:0 0 4px;font-size:15px;color:#4d5c72}
.efa-plan__price strong{font-size:32px;font-weight:700;color:#0e2a4a}
.efa-plan__alt{margin:0 0 16px;font-size:14px;color:#4d5c72}
.efa-plan ul.efa-plan__list{list-style:none;margin:8px 0 22px;padding:0;flex:1}
.efa-plan ul.efa-plan__list li{position:relative;margin:0 0 10px;padding-left:26px;font-size:15px;line-height:1.45}
.efa-plan ul.efa-plan__list li::before{content:"";position:absolute;left:0;top:4px;width:16px;height:16px;border-radius:50%;background:#e7f7ee url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M4 8.2l2.6 2.6L12 5.4' fill='none' stroke='%23187a41' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/12px no-repeat}
.efa-plan__actions{display:grid;gap:10px}
.efa-plan a.efa-plan__btn,.efa-plan button.efa-plan__btn{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;min-height:46px;margin:0;padding:10px 18px;border:2px solid #075aae;border-radius:25px;background:#075aae;color:#fff;font:inherit;font-size:15px;font-weight:600;line-height:1.2;text-align:center;text-decoration:none;cursor:pointer}
.efa-plan a.efa-plan__btn:hover,.efa-plan button.efa-plan__btn:hover{background:#067ae0;border-color:#067ae0;color:#fff}
.efa-plan .efa-plan__btn--ghost,.efa-plan a.efa-plan__btn--ghost{background:#fff;color:#075aae}
.efa-plan .efa-plan__btn--ghost:hover,.efa-plan a.efa-plan__btn--ghost:hover{background:#e8f1fb;color:#075aae}
.efa-plan .efa-plan__btn[disabled]{border-color:#c9d3df;background:#eef1f5;color:#5b6b7f;cursor:default}
.efa-plan__note{margin:4px 0 0;font-size:13px;line-height:1.45;color:#5b6b7f;text-align:center}
.efa-plan__current{display:flex;align-items:center;justify-content:center;min-height:46px;margin:0;border-radius:25px;background:#e7f7ee;color:#187a41;font-size:15px;font-weight:600}
.efa-pricing-foot{max-width:860px;margin:0 auto;font-family:Lexend,sans-serif;font-size:13px;line-height:1.5;color:#5b6b7f;text-align:center}
.efa-pricing-foot a{color:#075aae}
.efa-pricing a:focus-visible,.efa-pricing button:focus-visible{outline:2px solid #075aae;outline-offset:3px}
</style>
<?php endif; ?>
<div class="efa-pricing">
	<section class="efa-plan efa-plan--free" aria-labelledby="efa-plan-free">
		<p class="efa-plan__name" id="efa-plan-free"><?php esc_html_e( 'Free', 'english-finders-account' ); ?></p>
		<p class="efa-plan__price"><strong>$0</strong></p>
		<p class="efa-plan__alt"><?php esc_html_e( 'Everything you need to learn English, free for good.', 'english-finders-account' ); ?></p>
		<ul class="efa-plan__list">
			<?php foreach ( $efa_free as $efa_item ) : ?>
				<li><?php echo esc_html( $efa_item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<div class="efa-plan__actions">
			<?php if ( ! $member ) : ?>
				<a class="efa-plan__btn efa-plan__btn--ghost" rel="nofollow" href="<?php echo esc_url( $signup ); ?>"><?php esc_html_e( 'Sign up free', 'english-finders-account' ); ?></a>
			<?php elseif ( ! $pro ) : ?>
				<p class="efa-plan__current"><?php esc_html_e( 'Your current plan', 'english-finders-account' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="efa-plan efa-plan--pro" aria-labelledby="efa-plan-pro">
		<p class="efa-plan__badge"><?php esc_html_e( 'Pro', 'english-finders-account' ); ?></p>
		<p class="efa-plan__name" id="efa-plan-pro"><?php esc_html_e( 'English Finders Pro', 'english-finders-account' ); ?></p>
		<p class="efa-plan__price">
			<?php
			/* translators: %s: monthly price, e.g. "$2.99" */
			printf( esc_html__( '%s / month', 'english-finders-account' ), '<strong>' . esc_html( $labels['month'] ) . '</strong>' );
			?>
		</p>
		<p class="efa-plan__alt">
			<?php
			/* translators: 1: yearly price, e.g. "$24.99", 2: saving, e.g. "30%" */
			printf( esc_html__( 'or %1$s a year (save %2$s)', 'english-finders-account' ), esc_html( $labels['year'] ), esc_html( $labels['saving'] ) );
			?>
		</p>
		<ul class="efa-plan__list">
			<?php foreach ( $efa_pro as $efa_item ) : ?>
				<li><?php echo esc_html( $efa_item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<div class="efa-plan__actions">
			<?php if ( $pro ) : ?>
				<p class="efa-plan__current"><?php esc_html_e( '✓ You’re Pro', 'english-finders-account' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="efa_manage_billing">
					<?php wp_nonce_field( 'efa_manage_billing' ); ?>
					<button type="submit" class="efa-plan__btn efa-plan__btn--ghost"><?php esc_html_e( 'Manage billing', 'english-finders-account' ); ?></button>
				</form>
			<?php elseif ( ! $on_sale ) : ?>
				<button type="button" class="efa-plan__btn" disabled><?php esc_html_e( 'Coming soon', 'english-finders-account' ); ?></button>
			<?php elseif ( ! $member ) : ?>
				<a class="efa-plan__btn" rel="nofollow" href="<?php echo esc_url( $signup ); ?>"><?php esc_html_e( 'Sign up to get Pro', 'english-finders-account' ); ?></a>
				<p class="efa-plan__note"><?php esc_html_e( 'Create your free account first; you’ll come straight back here.', 'english-finders-account' ); ?></p>
			<?php else : ?>
				<?php if ( '' !== $prices['year'] ) : ?>
					<button type="button" class="efa-plan__btn" data-efa-price-id="<?php echo esc_attr( $prices['year'] ); ?>">
						<?php
						/* translators: %s: yearly price */
						printf( esc_html__( 'Get Pro yearly · %s', 'english-finders-account' ), esc_html( $labels['year'] ) );
						?>
					</button>
				<?php endif; ?>
				<?php if ( '' !== $prices['month'] ) : ?>
					<button type="button" class="efa-plan__btn efa-plan__btn--ghost" data-efa-price-id="<?php echo esc_attr( $prices['month'] ); ?>">
						<?php
						/* translators: %s: monthly price */
						printf( esc_html__( 'Get Pro monthly · %s', 'english-finders-account' ), esc_html( $labels['month'] ) );
						?>
					</button>
				<?php endif; ?>
				<p class="efa-plan__note"><?php esc_html_e( 'Cancel any time in My Account.', 'english-finders-account' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
</div>
<p class="efa-pricing-foot">
	<?php
	printf(
		/* translators: 1: Terms of Use link, 2: Refund Policy link, 3: Privacy Policy link */
		esc_html__( 'Payments are handled securely by Paddle.com, our online reseller, which may add sales tax or VAT where it applies. See our %1$s, %2$s and %3$s.', 'english-finders-account' ),
		'<a href="' . esc_url( home_url( '/terms-of-use/' ) ) . '">' . esc_html__( 'Terms of Use', 'english-finders-account' ) . '</a>',
		'<a href="' . esc_url( home_url( '/refund-policy/' ) ) . '">' . esc_html__( 'Refund Policy (14 days)', 'english-finders-account' ) . '</a>',
		'<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'english-finders-account' ) . '</a>'
	);
	?>
</p>
