<?php
/**
 * Course certificates section, included from account-shell.php (0.11.0, Phase A6).
 *
 * Rendered only when CertificatesController::data_for_user() returns data
 * (Core 1.12.0+). Each certificate links to its printable page, and the
 * same link is shown in a read-only field to copy and share: whoever opens
 * it sees the certificate, which is how it gets verified.
 *
 * @package EnglishFindersAccount
 *
 * @var array{certificates: list<array{code:string,course_title:string,level:string,learner_name:string,issued_at:string,url:string}>}|null $certificates
 */

declare(strict_types=1);

use EnglishFindersAccount\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( null === $certificates ) {
	return;
}

$efa_courses_url = get_post_type_archive_link( 'courses' );
$efa_courses_url = is_string( $efa_courses_url ) && '' !== $efa_courses_url ? $efa_courses_url : home_url( '/courses/' );
?>
<section class="efa-account-section efa-certificates-section" id="efa-section-certificates">
	<h2><?php esc_html_e( 'Certificates', 'english-finders-account' ); ?></h2>

	<?php if ( array() === $certificates['certificates'] ) : ?>
		<div class="efa-empty-state">
			<?php echo Icons::svg( 'award', 'efa-empty-state__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() only ever returns this class's own fixed, trusted SVG markup. ?>
			<p><?php esc_html_e( 'Finish a course to earn your first certificate. Open a lesson while you are logged in, mark each lesson complete and pass each quiz. When the whole course is done, your certificate appears here.', 'english-finders-account' ); ?></p>
			<a class="efa-empty-state__cta" href="<?php echo esc_url( $efa_courses_url ); ?>"><?php esc_html_e( 'Browse courses', 'english-finders-account' ); ?></a>
		</div>
	<?php else : ?>
		<ul class="efa-certificates">
			<?php foreach ( $certificates['certificates'] as $efa_certificate ) : ?>
				<li class="efa-certificate">
					<span class="efa-certificate__level" aria-hidden="true">
						<?php
						if ( '' !== $efa_certificate['level'] ) {
							echo esc_html( $efa_certificate['level'] );
						} else {
							echo Icons::svg( 'award' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() only ever returns this class's own fixed, trusted SVG markup.
						}
						?>
					</span>
					<div class="efa-certificate__body">
						<p class="efa-certificate__title"><?php echo esc_html( $efa_certificate['course_title'] ); ?></p>
						<p class="efa-certificate__meta">
							<?php
							printf(
								/* translators: 1: date issued, 2: certificate ID */
								esc_html__( 'Issued %1$s · ID %2$s', 'english-finders-account' ),
								esc_html( mysql2date( get_option( 'date_format' ), get_date_from_gmt( $efa_certificate['issued_at'] ) ) ),
								esc_html( $efa_certificate['code'] )
							);
							?>
						</p>
						<label class="efa-certificate__link-label" for="efa-certificate-link-<?php echo esc_attr( $efa_certificate['code'] ); ?>"><?php esc_html_e( 'Link to share', 'english-finders-account' ); ?></label>
						<input class="efa-certificate__link" type="text" readonly id="efa-certificate-link-<?php echo esc_attr( $efa_certificate['code'] ); ?>" value="<?php echo esc_attr( $efa_certificate['url'] ); ?>" onfocus="this.select()">
					</div>
					<a class="efa-certificate__view" href="<?php echo esc_url( $efa_certificate['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View / print', 'english-finders-account' ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Certificates use the name in your profile at the time you finish a course. Anyone you share the link with can check that it is genuine.', 'english-finders-account' ); ?>
	</p>
</section>
