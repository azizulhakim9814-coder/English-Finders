<?php
/**
 * Daily goal tiers, in XP per day (1.10.0).
 *
 * XP rather than minutes because XP is what the site actually records for
 * every activity (ActivityRecorder::XP_TABLE); nothing measures time spent,
 * so a minutes goal would have to be invented. Same tier idea Duolingo
 * uses. Lives in Core next to the XP table it is calibrated against --
 * roughly, Regular (20 XP) is about 20 correct answers, four solved
 * puzzles, or one lesson.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DailyGoal {
	/** Tier key => XP per day, easiest first. */
	public const TIERS = array(
		'casual'  => 10,
		'regular' => 20,
		'serious' => 30,
		'intense' => 50,
	);

	public const DEFAULT_TIER = 'regular';

	public static function is_valid( string $tier ): bool {
		return isset( self::TIERS[ $tier ] );
	}

	/** XP for a tier; an unknown tier falls back to the default. */
	public static function xp( string $tier ): int {
		return self::TIERS[ $tier ] ?? self::TIERS[ self::DEFAULT_TIER ];
	}

	public static function label( string $tier ): string {
		$labels = array(
			'casual'  => __( 'Casual', 'english-finders-core' ),
			'regular' => __( 'Regular', 'english-finders-core' ),
			'serious' => __( 'Serious', 'english-finders-core' ),
			'intense' => __( 'Intense', 'english-finders-core' ),
		);

		return $labels[ $tier ] ?? $labels[ self::DEFAULT_TIER ];
	}
}
