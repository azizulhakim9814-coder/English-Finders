<?php
/**
 * The fixed badge catalog for Phase A2.
 *
 * Deliberately small (five badges) -- this is the awarding mechanism, not
 * the display layer (that's A3) or an elaborate achievements system. Pure
 * logic, no database: takes a UserStats snapshot and the badges already
 * earned, returns what's newly earned.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BadgeCatalog {
	/**
	 * Display info for each badge code. Plain strings, not translated here
	 * -- this is a data catalog, not rendered HTML; a consumer (Phase A3's
	 * My Account UI) escapes and can localise at the point of display.
	 *
	 * @var array<string,array{label:string,description:string}>
	 */
	private const INFO = array(
		'first_activity' => array( 'label' => 'First Steps', 'description' => 'Completed your first activity.' ),
		'streak_7'       => array( 'label' => '7-Day Streak', 'description' => 'Kept a 7-day streak going.' ),
		'streak_30'      => array( 'label' => '30-Day Streak', 'description' => 'Kept a 30-day streak going.' ),
		'xp_100'         => array( 'label' => '100 XP', 'description' => 'Earned 100 total XP.' ),
		'xp_1000'        => array( 'label' => '1,000 XP', 'description' => 'Earned 1,000 total XP.' ),
	);

	/**
	 * Numeric thresholds for the badges that have a single clean one --
	 * omits `first_activity`, which fires on `total_xp > 0 OR
	 * current_streak_days > 0` (see newly_earned() below), so it has no
	 * single number a "you are X/Y of the way there" display could use.
	 * Added for Phase A3's My Account UI (0.6.0's visual pass), to show
	 * progress toward a badge not yet earned rather than only the ones
	 * already earned -- kept here, not duplicated in the consuming plugin,
	 * since this project always exposes catalog data through the plugin
	 * that owns it rather than hardcoding another plugin's business rules.
	 *
	 * @return array<string,array{metric:'xp'|'streak',value:int}>
	 */
	private const THRESHOLDS = array(
		'streak_7'  => array( 'metric' => 'streak', 'value' => 7 ),
		'streak_30' => array( 'metric' => 'streak', 'value' => 30 ),
		'xp_100'    => array( 'metric' => 'xp', 'value' => 100 ),
		'xp_1000'   => array( 'metric' => 'xp', 'value' => 1000 ),
	);

	/** @return list<string> */
	public static function codes(): array {
		return array( 'first_activity', 'streak_7', 'streak_30', 'xp_100', 'xp_1000' );
	}

	/** @return array<string,array{metric:'xp'|'streak',value:int}> */
	public static function thresholds(): array {
		return self::THRESHOLDS;
	}

	/** Falls back to the raw code for an unknown badge, rather than an empty string. */
	public static function label( string $code ): string {
		return self::INFO[ $code ]['label'] ?? $code;
	}

	public static function description( string $code ): string {
		return self::INFO[ $code ]['description'] ?? '';
	}

	/**
	 * Which badges the given stats newly qualify for.
	 *
	 * Excludes anything already in $already_earned so a caller never
	 * attempts a redundant award -- ActivityRepository::award_badge()'s
	 * unique-key INSERT IGNORE is the real idempotency guarantee, this is
	 * just to avoid the wasted query.
	 *
	 * @param list<string> $already_earned
	 * @return list<string>
	 */
	public static function newly_earned( UserStats $stats, array $already_earned ): array {
		$qualifies = array(
			'first_activity' => $stats->total_xp > 0 || $stats->current_streak_days > 0,
			'streak_7'       => $stats->longest_streak_days >= 7,
			'streak_30'      => $stats->longest_streak_days >= 30,
			'xp_100'         => $stats->total_xp >= 100,
			'xp_1000'        => $stats->total_xp >= 1000,
		);

		$earned = array();

		foreach ( $qualifies as $code => $does_qualify ) {
			if ( $does_qualify && ! in_array( $code, $already_earned, true ) ) {
				$earned[] = $code;
			}
		}

		return $earned;
	}
}
