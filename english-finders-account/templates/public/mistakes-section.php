<?php
/**
 * Mistake notebook section, included from account-shell.php.
 *
 * Rendered only when MistakesController::data_for_user() returns data (Core
 * 1.9.0+). Every entry shows what the learner actually saw and answered --
 * a snapshot taken by the practice tool at the time -- the right answer,
 * and the explanation where the tool has one.
 *
 * @package EnglishFindersAccount
 *
 * @var array{counts: array{open:int,fixed:int,by_skill:array<string,array{open:int,fixed:int}>}, entries: list<array<string,mixed>>, skill: string, practice_url: string}|null $mistakes
 */

declare(strict_types=1);

use EnglishFindersAccount\Mistakes\MistakesController;
use EnglishFindersAccount\Mistakes\ResolveMistakeHandler;
use EnglishFindersAccount\Support\Icons;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( null === $mistakes ) {
	return;
}

$counts      = $mistakes['counts'];
$skill       = $mistakes['skill'];
$shown_total = '' !== $skill ? (int) ( $counts['by_skill'][ $skill ]['open'] ?? 0 ) : $counts['open'];
$filter_url  = static fn ( string $slug ): string => ( '' !== $slug ? add_query_arg( MistakesController::SKILL_PARAM, $slug, Urls::my_account() ) : Urls::my_account() ) . '#efa-section-mistakes';
?>
<section class="efa-account-section efa-mistakes-section" id="efa-section-mistakes">
	<h2><?php esc_html_e( 'Mistake notebook', 'english-finders-account' ); ?></h2>

	<?php if ( 0 === $counts['open'] && 0 === $counts['fixed'] ) : ?>
		<div class="efa-empty-state">
			<?php echo Icons::svg( 'bookmark', 'efa-empty-state__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() only ever returns this class's own fixed, trusted SVG markup. ?>
			<p><?php esc_html_e( 'No mistakes yet. When you get a practice question wrong, it is saved here with the right answer, so you can review it later.', 'english-finders-account' ); ?></p>
			<?php if ( '' !== $mistakes['practice_url'] ) : ?>
				<a class="efa-empty-state__cta" href="<?php echo esc_url( $mistakes['practice_url'] ); ?>"><?php esc_html_e( 'Start practising', 'english-finders-account' ); ?></a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<p class="efa-mistakes-summary">
			<?php
			printf(
				/* translators: 1: number of open mistakes, 2: number of fixed mistakes */
				esc_html__( '%1$d to review · %2$d fixed', 'english-finders-account' ),
				(int) $counts['open'],
				(int) $counts['fixed']
			);
			?>
		</p>

		<?php if ( count( $counts['by_skill'] ) > 1 ) : ?>
			<nav class="efa-mistakes-filter" aria-label="<?php esc_attr_e( 'Filter mistakes by skill', 'english-finders-account' ); ?>">
				<a href="<?php echo esc_url( $filter_url( '' ) ); ?>"<?php echo '' === $skill ? ' aria-current="true"' : ''; ?>><?php esc_html_e( 'All', 'english-finders-account' ); ?> <span><?php echo esc_html( (string) (int) $counts['open'] ); ?></span></a>
				<?php foreach ( $counts['by_skill'] as $slug => $n ) : ?>
					<a href="<?php echo esc_url( $filter_url( (string) $slug ) ); ?>"<?php echo $slug === $skill ? ' aria-current="true"' : ''; ?>><?php echo esc_html( MistakesController::skill_label( (string) $slug ) ); ?> <span><?php echo esc_html( (string) (int) $n['open'] ); ?></span></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( array() === $mistakes['entries'] ) : ?>
			<div class="efa-empty-state">
				<?php echo Icons::svg( 'shield', 'efa-empty-state__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p><?php esc_html_e( 'All caught up. Every mistake here has been fixed, either by answering it correctly later or by marking it learned.', 'english-finders-account' ); ?></p>
			</div>
		<?php else : ?>
			<ul class="efa-mistake-list">
				<?php foreach ( $mistakes['entries'] as $entry ) : ?>
					<li class="efa-mistake">
						<div class="efa-mistake__meta">
							<span class="efa-mistake__tag"><?php echo esc_html( $entry['skill_label'] ); ?></span>
							<?php if ( '' !== $entry['level'] ) : ?>
								<span class="efa-mistake__tag"><?php echo esc_html( $entry['level'] ); ?></span>
							<?php endif; ?>
							<span class="efa-mistake__source">
								<?php
								echo esc_html( $entry['tool_label'] );
								if ( $entry['times_missed'] > 1 ) {
									echo ' · ';
									printf(
										/* translators: %d: how many times this question was answered wrongly */
										esc_html__( 'missed %d times', 'english-finders-account' ),
										(int) $entry['times_missed']
									);
								}
								?>
							</span>
						</div>

						<?php if ( '' !== $entry['context'] ) : ?>
							<details class="efa-mistake__context">
								<summary><?php esc_html_e( 'Show the passage', 'english-finders-account' ); ?></summary>
								<p><?php echo esc_html( $entry['context'] ); ?></p>
							</details>
						<?php endif; ?>

						<p class="efa-mistake__prompt"><?php echo esc_html( $entry['prompt'] ); ?></p>

						<dl class="efa-mistake__answers">
							<div class="efa-mistake__answer efa-mistake__answer--given">
								<dt><?php esc_html_e( 'Your answer', 'english-finders-account' ); ?></dt>
								<dd><?php echo esc_html( '' !== $entry['given'] ? $entry['given'] : __( '(no answer)', 'english-finders-account' ) ); ?></dd>
							</div>
							<div class="efa-mistake__answer efa-mistake__answer--correct">
								<dt><?php echo esc_html( 'error-correction' === $entry['tool'] ? __( 'The mistake was in', 'english-finders-account' ) : __( 'Correct answer', 'english-finders-account' ) ); ?></dt>
								<dd><?php echo esc_html( $entry['correct'] ); ?></dd>
							</div>
						</dl>

						<?php if ( '' !== $entry['explanation'] ) : ?>
							<p class="efa-mistake__explanation"><?php echo esc_html( $entry['explanation'] ); ?></p>
						<?php endif; ?>

						<div class="efa-mistake__actions">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="<?php echo esc_attr( ResolveMistakeHandler::ACTION ); ?>">
								<input type="hidden" name="mistake_id" value="<?php echo esc_attr( (string) (int) $entry['id'] ); ?>">
								<input type="hidden" name="skill" value="<?php echo esc_attr( $skill ); ?>">
								<?php wp_nonce_field( ResolveMistakeHandler::ACTION ); ?>
								<button type="submit" class="efa-mistake__got-it"><?php esc_html_e( 'Got it', 'english-finders-account' ); ?></button>
							</form>
							<?php if ( '' !== $entry['tool_url'] ) : ?>
								<a class="efa-mistake__practice" href="<?php echo esc_url( $entry['tool_url'] ); ?>">
									<?php
									printf(
										/* translators: %s: practice tool name */
										esc_html__( 'Practise in %s', 'english-finders-account' ),
										esc_html( $entry['tool_label'] )
									);
									?>
								</a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $shown_total > count( $mistakes['entries'] ) ) : ?>
				<p class="description">
					<?php
					printf(
						/* translators: 1: entries shown, 2: total open entries */
						esc_html__( 'Showing your %1$d most recent of %2$d. Fix or mark some as learned to see the rest.', 'english-finders-account' ),
						(int) count( $mistakes['entries'] ),
						(int) $shown_total
					);
					?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>

	<p class="description"><?php esc_html_e( 'An entry clears itself when you answer that question correctly in the same practice tool.', 'english-finders-account' ); ?></p>
</section>
