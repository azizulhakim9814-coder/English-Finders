<?php
/**
 * Progress section (Home / Streak & Goals / Score), included from
 * account-shell.php (Phase A3).
 *
 * Rendered only when Core's activity service is actually available and new
 * enough (ProgressController::data_for_user() returns null otherwise) --
 * silently absent rather than a broken or "coming soon" section, the same
 * honesty principle every earlier phase of this plugin has applied.
 *
 * Deliberately does not include a CEFR level badge (that lives in its own
 * My Level section since 0.7.0 -- level and XP are kept apart on purpose,
 * see my-account-design.md's Definitions) or the leaderboard (its own
 * section since 0.10.0).
 *
 * 0.6.0 visual pass: icons on each stat tile and a "next badges" progress
 * strip, closing the gap this page had against Duolingo's icon+stat grid
 * and achievement progress bars, and 7ESL's skill progress bars -- see the
 * conversation that scoped this update for the competitor comparison.
 *
 * @package EnglishFindersAccount
 *
 * @var array{total_xp: int, current_streak_days: int, longest_streak_days: int, streak_freezes_available: int, badges: list<array{code: string, label: string, description: string}>, next_badges: list<array{code: string, label: string, description: string, current: int, threshold: int, percent: int}>}|null $progress
 */

declare(strict_types=1);

use EnglishFindersAccount\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( null === $progress ) {
	return;
}
?>
<section class="efa-account-section efa-progress-section" id="efa-section-progress">
	<h2><?php esc_html_e( 'Your progress', 'english-finders-account' ); ?></h2>

	<?php
	/*
	 * Today's goal (0.9.0). A ring rather than a bar so it reads at a glance
	 * as "how full is today"; the numbers beside it carry the meaning for
	 * screen readers (the SVG itself is aria-hidden).
	 */
	if ( ! empty( $progress['goal'] ) ) :
		$goal         = $progress['goal'];
		$circumference = 2 * M_PI * 42;
		$filled       = $circumference * $goal['percent'] / 100;
		?>
		<div class="efa-goal<?php echo $goal['met'] ? ' is-met' : ''; ?>">
			<svg class="efa-goal__ring" viewBox="0 0 100 100" aria-hidden="true">
				<circle class="efa-goal__track" cx="50" cy="50" r="42"></circle>
				<circle class="efa-goal__fill" cx="50" cy="50" r="42" stroke-dasharray="<?php echo esc_attr( round( $filled, 2 ) . ' ' . round( $circumference, 2 ) ); ?>" transform="rotate(-90 50 50)"></circle>
				<text class="efa-goal__pct" x="50" y="55" text-anchor="middle"><?php echo esc_html( (string) (int) $goal['percent'] ); ?>%</text>
			</svg>
			<div class="efa-goal__text">
				<p class="efa-goal__title">
					<?php
					if ( $goal['met'] ) {
						esc_html_e( 'Daily goal reached', 'english-finders-account' );
					} else {
						esc_html_e( "Today's goal", 'english-finders-account' );
					}
					?>
				</p>
				<p class="efa-goal__numbers">
					<?php
					printf(
						/* translators: 1: XP earned today, 2: daily goal in XP, 3: goal name, e.g. "Regular" */
						esc_html__( '%1$d of %2$d XP today · %3$s goal', 'english-finders-account' ),
						(int) $goal['today_xp'],
						(int) $goal['xp'],
						esc_html( $goal['label'] )
					);
					?>
				</p>
				<p class="efa-goal__week">
					<?php
					printf(
						/* translators: %d: number of days (0-7) */
						esc_html( _n( 'Goal met on %d of the last 7 days', 'Goal met on %d of the last 7 days', (int) $goal['met_days_last7'], 'english-finders-account' ) ),
						(int) $goal['met_days_last7']
					);
					?>
					· <a href="#efa-section-profile"><?php esc_html_e( 'Change goal', 'english-finders-account' ); ?></a>
				</p>
			</div>
		</div>
	<?php endif; ?>

	<div class="efa-progress-stats">
		<div class="efa-progress-stat">
			<?php echo Icons::svg( 'bolt', 'efa-progress-stat__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() only ever returns this class's own fixed, trusted SVG markup, never user input. ?>
			<span class="efa-progress-stat__value"><?php echo esc_html( (string) $progress['total_xp'] ); ?></span>
			<span class="efa-progress-stat__label"><?php esc_html_e( 'Total XP', 'english-finders-account' ); ?></span>
		</div>
		<div class="efa-progress-stat">
			<?php echo Icons::svg( 'flame', 'efa-progress-stat__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="efa-progress-stat__value"><?php echo esc_html( (string) $progress['current_streak_days'] ); ?></span>
			<span class="efa-progress-stat__label"><?php esc_html_e( 'Day streak', 'english-finders-account' ); ?></span>
		</div>
		<div class="efa-progress-stat">
			<?php echo Icons::svg( 'trophy', 'efa-progress-stat__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="efa-progress-stat__value"><?php echo esc_html( (string) $progress['longest_streak_days'] ); ?></span>
			<span class="efa-progress-stat__label"><?php esc_html_e( 'Longest streak', 'english-finders-account' ); ?></span>
		</div>
		<div class="efa-progress-stat">
			<?php echo Icons::svg( 'shield', 'efa-progress-stat__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="efa-progress-stat__value"><?php echo esc_html( (string) $progress['streak_freezes_available'] ); ?></span>
			<span class="efa-progress-stat__label"><?php esc_html_e( 'Freezes available', 'english-finders-account' ); ?></span>
		</div>
	</div>

	<p class="description">
		<?php
		esc_html_e(
			'A streak day counts whenever you earn XP: a correct practice answer, a game, a quiz or a lesson. Missing a day is fine if you have a freeze available -- it covers the gap automatically.',
			'english-finders-account'
		);
		?>
	</p>

	<?php
	/*
	 * Streak calendar (0.9.0): whole Monday-Sunday weeks, current week last.
	 * The grid is aria-hidden; the summary sentence above it is the
	 * accessible version. Each cell's title gives the date and XP for mouse
	 * users.
	 */
	if ( ! empty( $progress['calendar'] ) ) :
		$calendar = $progress['calendar'];
		$state_labels = array(
			'none'   => __( 'no activity', 'english-finders-account' ),
			'active' => __( 'active', 'english-finders-account' ),
			'goal'   => __( 'goal reached', 'english-finders-account' ),
			'future' => '',
		);
		?>
		<h3><?php esc_html_e( 'Streak calendar', 'english-finders-account' ); ?></h3>
		<p class="efa-calendar-summary">
			<?php
			printf(
				/* translators: 1: active days, 2: days the goal was reached, 3: number of weeks shown */
				esc_html( _n( 'Active on %1$d day, goal reached on %2$d, over the last %3$d weeks.', 'Active on %1$d days, goal reached on %2$d, over the last %3$d weeks.', (int) $calendar['active_days'], 'english-finders-account' ) ),
				(int) $calendar['active_days'],
				(int) $calendar['goal_days'],
				(int) count( $calendar['weeks'] )
			);
			?>
		</p>
		<div class="efa-calendar" aria-hidden="true">
			<div class="efa-calendar__head">
				<?php foreach ( array( __( 'Mon', 'english-finders-account' ), __( 'Tue', 'english-finders-account' ), __( 'Wed', 'english-finders-account' ), __( 'Thu', 'english-finders-account' ), __( 'Fri', 'english-finders-account' ), __( 'Sat', 'english-finders-account' ), __( 'Sun', 'english-finders-account' ) ) as $efa_dow ) : ?>
					<span><?php echo esc_html( $efa_dow ); ?></span>
				<?php endforeach; ?>
			</div>
			<?php foreach ( $calendar['weeks'] as $week ) : ?>
				<div class="efa-calendar__week">
					<?php foreach ( $week as $cell ) : ?>
						<span class="efa-calendar__day is-<?php echo esc_attr( $cell['state'] ); ?><?php echo $cell['today'] ? ' is-today' : ''; ?>"
							<?php if ( 'future' !== $cell['state'] ) : ?>
								title="<?php echo esc_attr( mysql2date( get_option( 'date_format' ), $cell['date'] ) . ' · ' . $cell['xp'] . ' XP · ' . $state_labels[ $cell['state'] ] ); ?>"
							<?php endif; ?>
						><?php echo esc_html( (string) $cell['day'] ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="efa-calendar__legend" aria-hidden="true">
			<span class="efa-calendar__key is-none"></span><?php esc_html_e( 'No activity', 'english-finders-account' ); ?>
			<span class="efa-calendar__key is-active"></span><?php esc_html_e( 'Active', 'english-finders-account' ); ?>
			<span class="efa-calendar__key is-goal"></span><?php esc_html_e( 'Goal reached', 'english-finders-account' ); ?>
		</p>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Badges', 'english-finders-account' ); ?></h3>
	<?php if ( array() === $progress['badges'] ) : ?>
		<p class="description"><?php esc_html_e( 'No badges yet -- play a game, take a quiz, or finish a lesson to earn your first one.', 'english-finders-account' ); ?></p>
	<?php else : ?>
		<ul class="efa-badge-list">
			<?php foreach ( $progress['badges'] as $badge ) : ?>
				<li class="efa-badge" title="<?php echo esc_attr( $badge['description'] ); ?>">
					<?php echo Icons::svg( 'trophy', 'efa-badge__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( $badge['label'] ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( array() !== $progress['next_badges'] ) : ?>
		<h3><?php esc_html_e( 'Next up', 'english-finders-account' ); ?></h3>
		<ul class="efa-next-badge-list">
			<?php foreach ( $progress['next_badges'] as $next ) : ?>
				<li class="efa-next-badge" title="<?php echo esc_attr( $next['description'] ); ?>">
					<div class="efa-next-badge__head">
						<?php echo Icons::svg( 'lock', 'efa-next-badge__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="efa-next-badge__label"><?php echo esc_html( $next['label'] ); ?></span>
						<span class="efa-next-badge__count">
							<?php
							printf(
								/* translators: 1: current progress, 2: threshold to reach */
								esc_html__( '%1$d / %2$d', 'english-finders-account' ),
								(int) $next['current'],
								(int) $next['threshold']
							);
							?>
						</span>
					</div>
					<div class="efa-next-badge__bar">
						<div class="efa-next-badge__bar-fill" style="width: <?php echo esc_attr( (string) $next['percent'] ); ?>%"></div>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

</section>
