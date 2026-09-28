<?php
/**
 * My Level section, included from account-shell.php (Phase A4).
 *
 * Rendered only when LevelController::data_for_user() returns data --
 * silently absent otherwise, the same principle as every other section.
 * With no result yet, it shows only the invitation to take the test and
 * the learner's real course progress; it never shows a placeholder level.
 *
 * @package EnglishFindersAccount
 *
 * @var array{latest: array<string,mixed>|null, history: list<array<string,mixed>>, courses: list<array<string,mixed>>, test_url: string}|null $level
 */

declare(strict_types=1);

use EnglishFindersAccount\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( null === $level ) {
	return;
}

$latest = $level['latest'];
?>
<section class="efa-account-section efa-level-section" id="efa-section-level">
	<h2><?php esc_html_e( 'My level', 'english-finders-account' ); ?></h2>

	<?php if ( null === $latest ) : ?>
		<div class="efa-empty-state">
			<?php echo Icons::svg( 'chart', 'efa-empty-state__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() only ever returns this class's own fixed, trusted SVG markup. ?>
			<p><?php esc_html_e( 'Find out your CEFR level (A1–C2) in grammar, vocabulary and reading with our free level test. It adapts as you go and usually takes 10–15 minutes.', 'english-finders-account' ); ?></p>
			<?php if ( '' !== $level['test_url'] ) : ?>
				<a class="efa-empty-state__cta" href="<?php echo esc_url( $level['test_url'] ); ?>"><?php esc_html_e( 'Take the level test', 'english-finders-account' ); ?></a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="efa-level-hero">
			<div class="efa-level-badge" aria-hidden="true"><?php echo esc_html( $latest['overall_short'] ); ?></div>
			<div class="efa-level-hero__text">
				<p class="efa-level-hero__label"><?php echo esc_html( $latest['overall_label'] ); ?></p>
				<p class="efa-level-hero__desc"><?php echo esc_html( $latest['description'] ); ?></p>
				<p class="efa-level-hero__meta">
					<?php
					printf(
						/* translators: 1: date the test was taken, 2: correct answers, 3: questions answered */
						esc_html__( 'Level test taken %1$s · %2$d of %3$d correct', 'english-finders-account' ),
						esc_html( mysql2date( get_option( 'date_format' ), get_date_from_gmt( $latest['taken_at'] ) ) ),
						(int) $latest['correct_answers'],
						(int) $latest['questions_answered']
					);
					?>
				</p>
			</div>
		</div>

		<h3><?php esc_html_e( 'By skill', 'english-finders-account' ); ?></h3>
		<ul class="efa-skill-list">
			<?php foreach ( $latest['skills'] as $skill ) : ?>
				<li class="efa-skill">
					<span class="efa-skill__name"><?php echo esc_html( $skill['label'] ); ?></span>
					<span class="efa-skill__scale" aria-hidden="true">
						<?php for ( $i = 1; $i <= 6; $i++ ) : ?>
							<span class="efa-skill__seg<?php echo $i <= (int) $skill['index'] ? ' is-filled' : ''; ?>"></span>
						<?php endfor; ?>
					</span>
					<span class="efa-skill__level"><?php echo esc_html( $skill['short'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="efa-skill-scale-key" aria-hidden="true"><span>A1</span><span>A2</span><span>B1</span><span>B2</span><span>C1</span><span>C2</span></p>

		<?php if ( array() !== $level['history'] ) : ?>
			<h3><?php esc_html_e( 'Earlier results', 'english-finders-account' ); ?></h3>
			<div class="efa-table-scroll">
			<table class="efa-transaction-table efa-level-history">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'english-finders-account' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Overall', 'english-finders-account' ); ?></th>
						<?php foreach ( $latest['skills'] as $skill ) : ?>
							<th scope="col"><?php echo esc_html( $skill['label'] ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $level['history'] as $past ) : ?>
						<tr>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), get_date_from_gmt( $past['taken_at'] ) ) ); ?></td>
							<td><strong><?php echo esc_html( $past['overall_short'] ); ?></strong></td>
							<?php foreach ( $past['skills'] as $skill ) : ?>
								<td><?php echo esc_html( $skill['short'] ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $level['test_url'] ) : ?>
			<p><a class="efa-level-retake" href="<?php echo esc_url( $level['test_url'] ); ?>"><?php esc_html_e( 'Take the level test again', 'english-finders-account' ); ?></a></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( array() !== $level['courses'] ) : ?>
		<h3><?php esc_html_e( 'CEFR courses', 'english-finders-account' ); ?></h3>
		<ul class="efa-course-list">
			<?php foreach ( $level['courses'] as $course ) : ?>
				<li class="efa-course<?php echo ! empty( $course['completed'] ) ? ' is-complete' : ''; ?>">
					<span class="efa-course__level"><?php echo esc_html( $course['level'] ); ?></span>
					<div class="efa-course__main">
						<a class="efa-course__title" href="<?php echo esc_url( $course['url'] ); ?>"><?php echo esc_html( $course['title'] ); ?></a>
						<?php if ( ! empty( $course['completed'] ) ) : ?>
							<span class="efa-course__sub">
								<?php esc_html_e( 'Completed.', 'english-finders-account' ); ?>
								<?php if ( '' !== (string) ( $course['certificate_url'] ?? '' ) ) : ?>
									<a href="<?php echo esc_url( $course['certificate_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View certificate', 'english-finders-account' ); ?></a>
								<?php endif; ?>
							</span>
						<?php elseif ( $course['enrolled'] && ! empty( $course['next'] ) ) : ?>
							<span class="efa-course__sub">
								<?php
								if ( (int) ( $course['total'] ?? 0 ) > 0 ) {
									printf(
										/* translators: 1: course items done, 2: total course items */
										esc_html__( '%1$d of %2$d done', 'english-finders-account' ),
										(int) $course['done'],
										(int) $course['total']
									);
									echo ' · ';
								}
								esc_html_e( 'Next:', 'english-finders-account' );
								?>
								<a href="<?php echo esc_url( $course['next']['url'] ); ?>"><?php echo esc_html( $course['next']['title'] ); ?></a>
							</span>
						<?php endif; ?>
					</div>
					<?php if ( $course['enrolled'] ) : ?>
						<span class="efa-course__bar" aria-hidden="true"><span class="efa-course__bar-fill" style="width: <?php echo esc_attr( (string) (int) $course['percent'] ); ?>%"></span></span>
						<span class="efa-course__pct">
							<?php
							/* translators: %d: percentage of the course completed */
							printf( esc_html__( '%d%% done', 'english-finders-account' ), (int) $course['percent'] );
							?>
						</span>
					<?php else : ?>
						<span class="efa-course__status"><?php esc_html_e( 'Not started', 'english-finders-account' ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<p class="description"><?php esc_html_e( 'Your level is an estimate from our free test, not an official qualification. XP and badges (in Your progress) measure activity, not level.', 'english-finders-account' ); ?></p>
</section>
