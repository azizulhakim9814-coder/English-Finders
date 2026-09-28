<?php
/**
 * Weekly leaderboard section, included from account-shell.php (Phase A6).
 *
 * Rendered only when LeaderboardController::data_for_user() returns data
 * (Core 1.11.0+). Opt-in: the board lists only members who joined; someone
 * who hasn't joined sees it, plus a Join card that shows the exact name
 * they'd appear under.
 *
 * @package EnglishFindersAccount
 *
 * @var array<string,mixed>|null $leaderboard
 */

declare(strict_types=1);

use EnglishFindersAccount\Leaderboard\LeaderboardOptinHandler;
use EnglishFindersAccount\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( null === $leaderboard ) {
	return;
}

$week      = $leaderboard['week'];
$viewer    = $leaderboard['viewer'];
$week_span = mysql2date( 'j M', $week['start'] ) . ' – ' . mysql2date( 'j M', $week['end'] );
?>
<section class="efa-account-section efa-leaderboard-section" id="efa-section-leaderboard">
	<h2><?php esc_html_e( 'Weekly leaderboard', 'english-finders-account' ); ?></h2>

	<p class="efa-leaderboard-week">
		<?php
		echo esc_html( $week_span ) . ' · ';
		if ( 0 === (int) $week['days_left'] ) {
			esc_html_e( 'resets at midnight tonight', 'english-finders-account' );
		} else {
			printf(
				/* translators: %d: days until the weekly reset */
				esc_html( _n( 'resets in %d day', 'resets in %d days', (int) $week['days_left'], 'english-finders-account' ) ),
				(int) $week['days_left']
			);
		}
		?>
	</p>

	<?php if ( ! $leaderboard['opted_in'] ) : ?>
		<div class="efa-leaderboard-join">
			<p class="efa-leaderboard-join__title"><?php esc_html_e( 'See how your week compares', 'english-finders-account' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %s: the learner's display name */
					esc_html__( 'Join to appear on the board as %s. Signed-in members see your name, profile photo and the XP you earn each week; visitors who aren\'t signed in see only anonymous ranks and XP. You can leave at any time.', 'english-finders-account' ),
					'<strong>' . esc_html( '' !== $leaderboard['public_name'] ? $leaderboard['public_name'] : __( 'your display name', 'english-finders-account' ) ) . '</strong>'
				);
				?>
				<a href="#efa-section-profile"><?php esc_html_e( 'Change your name', 'english-finders-account' ); ?></a>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( LeaderboardOptinHandler::ACTION ); ?>">
				<input type="hidden" name="join" value="1">
				<?php wp_nonce_field( LeaderboardOptinHandler::ACTION ); ?>
				<button type="submit"><?php esc_html_e( 'Join the leaderboard', 'english-finders-account' ); ?></button>
			</form>
		</div>
	<?php else : ?>
		<p class="efa-leaderboard-you">
			<?php
			if ( null !== $viewer['rank'] ) {
				printf(
					/* translators: 1: rank, 2: number of learners on the board, 3: XP this week */
					esc_html__( 'You are #%1$d of %2$d · %3$d XP this week', 'english-finders-account' ),
					(int) $viewer['rank'],
					(int) $leaderboard['participants'],
					(int) $viewer['xp']
				);
			} else {
				esc_html_e( 'Earn XP this week to get on the board.', 'english-finders-account' );
			}
			?>
		</p>
	<?php endif; ?>

	<?php if ( array() === $leaderboard['entries'] ) : ?>
		<div class="efa-empty-state">
			<?php echo Icons::svg( 'trophy', 'efa-empty-state__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() only ever returns this class's own fixed, trusted SVG markup. ?>
			<p><?php esc_html_e( 'Nobody is on the board yet this week.', 'english-finders-account' ); ?> <?php echo $leaderboard['opted_in'] ? esc_html__( 'Earn some XP to take first place.', 'english-finders-account' ) : ''; ?></p>
		</div>
	<?php else : ?>
		<ol class="efa-leaderboard">
			<?php foreach ( $leaderboard['entries'] as $entry ) : ?>
				<li class="efa-leaderboard__row<?php echo $entry['is_viewer'] ? ' is-viewer' : ''; ?><?php echo $entry['rank'] <= 3 ? ' is-top' : ''; ?>" data-rank="<?php echo esc_attr( (string) (int) $entry['rank'] ); ?>">
					<span class="efa-leaderboard__rank"><?php echo esc_html( (string) (int) $entry['rank'] ); ?></span>
					<span class="efa-leaderboard__avatar" aria-hidden="true">
						<?php if ( '' !== (string) ( $entry['avatar'] ?? '' ) ) : ?>
							<img src="<?php echo esc_url( (string) $entry['avatar'] ); ?>" alt="" width="36" height="36" loading="lazy" referrerpolicy="no-referrer">
						<?php else : ?>
							<?php echo esc_html( $entry['initials'] ); ?>
						<?php endif; ?>
					</span>
					<span class="efa-leaderboard__name">
						<?php echo esc_html( $entry['name'] ); ?>
						<?php if ( $entry['is_viewer'] ) : ?>
							<span class="efa-leaderboard__you"><?php esc_html_e( '(you)', 'english-finders-account' ); ?></span>
						<?php endif; ?>
					</span>
					<span class="efa-leaderboard__xp">
						<?php
						/* translators: %d: XP earned this week */
						printf( esc_html__( '%d XP', 'english-finders-account' ), (int) $entry['xp'] );
						?>
					</span>
				</li>
			<?php endforeach; ?>
			<?php if ( $leaderboard['opted_in'] && null !== $viewer['rank'] && ! $leaderboard['viewer_in_list'] ) : ?>
				<li class="efa-leaderboard__gap" aria-hidden="true">⋯</li>
				<li class="efa-leaderboard__row is-viewer" data-rank="<?php echo esc_attr( (string) (int) $viewer['rank'] ); ?>">
					<span class="efa-leaderboard__rank"><?php echo esc_html( (string) (int) $viewer['rank'] ); ?></span>
					<span class="efa-leaderboard__avatar" aria-hidden="true">
						<?php if ( '' !== (string) ( $leaderboard['public_avatar'] ?? '' ) ) : ?>
							<img src="<?php echo esc_url( (string) $leaderboard['public_avatar'] ); ?>" alt="" width="36" height="36" loading="lazy" referrerpolicy="no-referrer">
						<?php else : ?>
							<?php echo esc_html( \EnglishFindersAccount\Leaderboard\LeaderboardController::initials( $leaderboard['public_name'] ) ); ?>
						<?php endif; ?>
					</span>
					<span class="efa-leaderboard__name"><?php echo esc_html( $leaderboard['public_name'] ); ?> <span class="efa-leaderboard__you"><?php esc_html_e( '(you)', 'english-finders-account' ); ?></span></span>
					<span class="efa-leaderboard__xp">
						<?php
						/* translators: %d: XP earned this week */
						printf( esc_html__( '%d XP', 'english-finders-account' ), (int) $viewer['xp'] );
						?>
					</span>
				</li>
			<?php endif; ?>
		</ol>
	<?php endif; ?>

	<p class="description">
		<?php esc_html_e( 'Ranked by XP earned this week. Only members who join appear here, and the board starts again every Monday.', 'english-finders-account' ); ?>
	</p>

	<?php if ( $leaderboard['opted_in'] ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="efa-leaderboard-leave">
			<input type="hidden" name="action" value="<?php echo esc_attr( LeaderboardOptinHandler::ACTION ); ?>">
			<input type="hidden" name="join" value="0">
			<?php wp_nonce_field( LeaderboardOptinHandler::ACTION ); ?>
			<button type="submit" class="efa-link-button"><?php esc_html_e( 'Leave the leaderboard', 'english-finders-account' ); ?></button>
		</form>
	<?php endif; ?>
</section>
