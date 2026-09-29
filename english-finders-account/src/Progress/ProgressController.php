<?php
/**
 * Home / Streak & Goals / Score section of the My Account page (Phase A3).
 *
 * Deliberately narrow: personal XP, streak, and badge data only, all
 * already available from Core's Activity service (Phase A2). The full
 * nine-section design in my-account-design.md also calls for a CEFR level
 * badge (needs the level test -- Phase A4, not built), a leaderboard
 * (needs ranking across all users -- Phase A6), and a saved-words library
 * (needs a new read path into Word Games Pro's own data -- not yet built).
 * None of those are stubbed here; this section shows only what's real.
 *
 * Same class_exists()+is_at_least() guard MembershipController already
 * uses at its own Core call sites, not a new pattern invented here.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Progress;

use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProgressController {
	/** 1.7.4 is when Core's `BadgeCatalog::thresholds()` first existed -- needed for next_badges(). */
	private const MIN_CORE_VERSION = '1.7.4';

	/** Shown in the "next badges" strip -- a handful, not the whole remaining catalog, to keep the section scannable. */
	private const MAX_NEXT_BADGES = 2;

	/** Full weeks shown in the streak calendar, the current week last. */
	public const CALENDAR_WEEKS = 5;

	/**
	 * Data for the Progress section template.
	 *
	 * Returns null when Core isn't available or isn't new enough -- the
	 * template treats that as "don't render this section" rather than
	 * fataling, the same degrade-gracefully contract MembershipController
	 * already established for the Membership section.
	 *
	 * @return array{total_xp: int, current_streak_days: int, longest_streak_days: int, streak_freezes_available: int, badges: list<array{code: string, label: string, description: string}>, next_badges: list<array{code: string, label: string, description: string, current: int, threshold: int, percent: int}>}|null
	 */
	public function data_for_user( int $user_id ): ?array {
		if ( ! class_exists( '\\EnglishFindersCore\\Support\\Api' ) || ! Api::is_at_least( self::MIN_CORE_VERSION ) ) {
			return null;
		}

		$activity = Api::service( 'activity' );
		if ( ! $activity instanceof \EnglishFindersCore\Activity\ActivityRecorder ) {
			return null;
		}

		$stats  = $activity->stats_for_user( $user_id );
		$earned = $activity->badges_for_user( $user_id );

		$badges = array();
		foreach ( $earned as $code ) {
			$badges[] = array(
				'code'        => $code,
				'label'       => \EnglishFindersCore\Activity\BadgeCatalog::label( $code ),
				'description' => \EnglishFindersCore\Activity\BadgeCatalog::description( $code ),
			);
		}

		$daily = $this->goal_and_calendar( $activity, $user_id );

		return array(
			'total_xp'                 => $stats->total_xp,
			'current_streak_days'      => $stats->current_streak_days,
			'longest_streak_days'      => $stats->longest_streak_days,
			'streak_freezes_available' => $stats->streak_freezes_available,
			'badges'                   => $badges,
			'next_badges'              => $this->next_badges( $stats, $earned ),
			'goal'                     => $daily['goal'] ?? null,
			'calendar'                 => $daily['calendar'] ?? null,
		);
	}

	/**
	 * Today's goal progress and the streak calendar (0.9.0), both from Core
	 * 1.10.0's per-day XP totals. Null (the template leaves both out) with an
	 * older Core -- the rest of the section still renders.
	 *
	 * The calendar is whole Monday-to-Sunday weeks ending with the current
	 * one, so the grid lines up with a normal calendar; days after today are
	 * marked "future" rather than shown as missed. Freeze-covered days are
	 * not marked: Core doesn't record which days a freeze covered, and the
	 * page doesn't show what it can't know.
	 *
	 * @return array{goal: array<string,mixed>, calendar: array<string,mixed>}|null
	 */
	private function goal_and_calendar( \EnglishFindersCore\Activity\ActivityRecorder $activity, int $user_id ): ?array {
		if ( ! method_exists( $activity, 'daily_totals' ) || ! class_exists( '\\EnglishFindersCore\\Activity\\DailyGoal' ) ) {
			return null;
		}

		$tier = (string) ( new \EnglishFindersAccount\Profile\ProfileRepository() )->get( $user_id, 'daily_goal' );
		if ( ! \EnglishFindersCore\Activity\DailyGoal::is_valid( $tier ) ) {
			$tier = \EnglishFindersCore\Activity\DailyGoal::DEFAULT_TIER;
		}
		$goal_xp = \EnglishFindersCore\Activity\DailyGoal::xp( $tier );

		$today       = current_time( 'Y-m-d' );
		$weekday     = (int) gmdate( 'N', strtotime( $today . ' UTC' ) ); // 1 = Monday.
		$days_so_far = ( self::CALENDAR_WEEKS - 1 ) * 7 + $weekday;
		$totals      = $activity->daily_totals( $user_id, $days_so_far );

		$today_xp  = (int) ( $totals[ $today ]['xp'] ?? 0 );
		$last7     = array_slice( $totals, -7, 7, true );
		$met_last7 = count( array_filter( $last7, static fn ( array $d ): bool => $d['xp'] >= $goal_xp ) );

		$cells  = array();
		$first  = strtotime( (string) array_key_first( $totals ) . ' UTC' );
		$active = 0;
		$met    = 0;
		for ( $i = 0; $i < self::CALENDAR_WEEKS * 7; $i++ ) {
			$date = gmdate( 'Y-m-d', $first + $i * 86400 );
			$xp   = (int) ( $totals[ $date ]['xp'] ?? 0 );
			$ev   = (int) ( $totals[ $date ]['events'] ?? 0 );

			if ( $date > $today ) {
				$state = 'future';
			} elseif ( $xp >= $goal_xp ) {
				$state = 'goal';
				++$met;
				++$active;
			} elseif ( $ev > 0 ) {
				$state = 'active';
				++$active;
			} else {
				$state = 'none';
			}

			$cells[] = array(
				'date'  => $date,
				'day'   => (int) gmdate( 'j', strtotime( $date . ' UTC' ) ),
				'xp'    => $xp,
				'state' => $state,
				'today' => $date === $today,
			);
		}

		return array(
			'goal'     => array(
				'tier'           => $tier,
				'label'          => \EnglishFindersCore\Activity\DailyGoal::label( $tier ),
				'xp'             => $goal_xp,
				'today_xp'       => $today_xp,
				'percent'        => (int) min( 100, floor( $today_xp / $goal_xp * 100 ) ),
				'met'            => $today_xp >= $goal_xp,
				'met_days_last7' => $met_last7,
			),
			'calendar' => array(
				'weeks'       => array_chunk( $cells, 7 ),
				'active_days' => $active,
				'goal_days'   => $met,
			),
		);
	}

	/**
	 * Progress toward the badges closest to being earned, for the "next
	 * badges" strip (0.6.0) -- shows what's coming, not just what's already
	 * won, the same thing Duolingo's achievement cards and 7ESL's skill
	 * bars both do that a flat list of earned badges doesn't.
	 *
	 * @param list<string> $earned
	 * @return list<array{code: string, label: string, description: string, current: int, threshold: int, percent: int}>
	 */
	private function next_badges( \EnglishFindersCore\Activity\UserStats $stats, array $earned ): array {
		$candidates = array();

		foreach ( \EnglishFindersCore\Activity\BadgeCatalog::thresholds() as $code => $threshold ) {
			if ( in_array( $code, $earned, true ) ) {
				continue;
			}

			$current = 'streak' === $threshold['metric'] ? $stats->longest_streak_days : $stats->total_xp;
			$percent = (int) min( 99, floor( $current / $threshold['value'] * 100 ) );

			$candidates[] = array(
				'code'        => $code,
				'label'       => \EnglishFindersCore\Activity\BadgeCatalog::label( $code ),
				'description' => \EnglishFindersCore\Activity\BadgeCatalog::description( $code ),
				'current'     => $current,
				'threshold'   => $threshold['value'],
				'percent'     => $percent,
			);
		}

		usort( $candidates, static fn ( array $a, array $b ): int => $b['percent'] <=> $a['percent'] );

		return array_slice( $candidates, 0, self::MAX_NEXT_BADGES );
	}
}
