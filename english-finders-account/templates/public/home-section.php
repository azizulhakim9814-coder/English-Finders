<?php
/**
 * Home strip at the top of My Account (0.12.0): "Continue where you left
 * off" and one suggested next step. See HomeController for the rules.
 *
 * @package EnglishFindersAccount
 *
 * @var array{continue: array<string,mixed>|null, next_step: array{key: string, title: string, text: string, url: string, cta: string}|null}|null $home
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( null === $home ) {
	return;
}

$efa_continue = $home['continue'];
$efa_next     = $home['next_step'];
?>
<div class="efa-home<?php echo ( null !== $efa_continue && null !== $efa_next ) ? ' efa-home--two' : ''; ?>" id="efa-section-home">
	<?php if ( null !== $efa_continue ) : ?>
		<section class="efa-home-card efa-home-card--continue" aria-labelledby="efa-home-continue-title">
			<p class="efa-home-card__eyebrow"><?php esc_html_e( 'Continue where you left off', 'english-finders-account' ); ?></p>
			<h2 class="efa-home-card__title" id="efa-home-continue-title">
				<span class="efa-home-card__level"><?php echo esc_html( $efa_continue['level'] ); ?></span>
				<?php echo esc_html( $efa_continue['title'] ); ?>
			</h2>
			<div class="efa-home-card__bar" aria-hidden="true"><span style="width: <?php echo esc_attr( (string) (int) $efa_continue['percent'] ); ?>%"></span></div>
			<p class="efa-home-card__meta">
				<?php
				if ( (int) $efa_continue['total'] > 0 ) {
					printf(
						/* translators: 1: percent done, 2: items done, 3: total items */
						esc_html__( '%1$d%% done · %2$d of %3$d', 'english-finders-account' ),
						(int) $efa_continue['percent'],
						(int) $efa_continue['done'],
						(int) $efa_continue['total']
					);
				} else {
					/* translators: %d: percent done */
					printf( esc_html__( '%d%% done', 'english-finders-account' ), (int) $efa_continue['percent'] );
				}
				?>
			</p>
			<p class="efa-home-card__next">
				<?php echo 'quiz' === $efa_continue['next']['type'] ? esc_html__( 'Next quiz:', 'english-finders-account' ) : esc_html__( 'Next lesson:', 'english-finders-account' ); ?>
				<strong><?php echo esc_html( $efa_continue['next']['title'] ); ?></strong>
			</p>
			<a class="efa-home-card__cta" href="<?php echo esc_url( $efa_continue['next']['url'] ); ?>"><?php esc_html_e( 'Continue', 'english-finders-account' ); ?></a>
		</section>
	<?php endif; ?>

	<?php if ( null !== $efa_next ) : ?>
		<section class="efa-home-card efa-home-card--next" aria-labelledby="efa-home-next-title">
			<p class="efa-home-card__eyebrow"><?php esc_html_e( 'Suggested next step', 'english-finders-account' ); ?></p>
			<h2 class="efa-home-card__title" id="efa-home-next-title"><?php echo esc_html( $efa_next['title'] ); ?></h2>
			<p class="efa-home-card__text"><?php echo esc_html( $efa_next['text'] ); ?></p>
			<a class="efa-home-card__cta<?php echo null !== $efa_continue ? ' efa-home-card__cta--secondary' : ''; ?>" href="<?php echo esc_url( $efa_next['url'] ); ?>"><?php echo esc_html( $efa_next['cta'] ); ?></a>
		</section>
	<?php endif; ?>
</div>
