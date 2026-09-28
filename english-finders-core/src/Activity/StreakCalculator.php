<?php
/**
 * Pure streak-continuation logic -- no database, no WordPress calls.
 *
 * Deliberately separate from ActivityRepository: the branching here (same
 * day / consecutive day / gap covered by a freeze / gap that breaks the
 * streak) is exactly the kind of logic worth unit-testing in isolation,
 * without needing a database fake to exercise every path.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Activity;

use DateTimeImmutable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StreakCalculator {
	/**
	 * Apply one day's activity to existing stats and return the new streak
	 * state. $today is a 'Y-m-d' string in the site's configured timezone
	 * (see ActivityRecorder::record_event(), which is the only caller).
	 *
	 * @return array{current_streak_days:int,longest_streak_days:int,streak_freezes_available:int,last_active_date:string}
	 */
	public function apply( UserStats $stats, string $today ): array {
		/*
		 * Same calendar day as the last recorded activity, or an out-of-order
		 * / backdated event -- string comparison is safe for 'Y-m-d' dates.
		 * Either way there is nothing to change: a streak only moves forward
		 * on a new day, and a backdated event has no meaningful "gap" to
		 * evaluate.
		 */
		if ( null !== $stats->last_active_date && $today <= $stats->last_active_date ) {
			return array(
				'current_streak_days'      => $stats->current_streak_days,
				'longest_streak_days'      => $stats->longest_streak_days,
				'streak_freezes_available' => $stats->streak_freezes_available,
				'last_active_date'         => $stats->last_active_date,
			);
		}

		$gap_days = null === $stats->last_active_date ? 1 : $this->days_between( $stats->last_active_date, $today );

		if ( 1 === $gap_days ) {
			// First-ever activity (null last_active_date) or the very next day.
			$current = $stats->current_streak_days + 1;
			$freezes = $stats->streak_freezes_available;
		} else {
			$skipped_days = $gap_days - 1;

			if ( $skipped_days <= $stats->streak_freezes_available ) {
				// Forgiving by design (my-account-design.md): a short gap the
				// user has freezes to cover continues the streak rather than
				// resetting it to an all-or-nothing cliff.
				$current = $stats->current_streak_days + 1;
				$freezes = $stats->streak_freezes_available - $skipped_days;
			} else {
				$current = 1;
				$freezes = $stats->streak_freezes_available;
			}
		}

		return array(
			'current_streak_days'      => $current,
			'longest_streak_days'      => max( $stats->longest_streak_days, $current ),
			'streak_freezes_available' => $freezes,
			'last_active_date'         => $today,
		);
	}

	private function days_between( string $from, string $to ): int {
		$from_dt = new DateTimeImmutable( $from );
		$to_dt   = new DateTimeImmutable( $to );

		return (int) $from_dt->diff( $to_dt )->days;
	}
}
