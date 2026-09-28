<?php
/**
 * A user's derived activity state -- XP, streak, freezes.
 *
 * Every consumer that needs to show or check this reads one of these, never
 * the raw activity_events log directly, the same "derived state, not the
 * log" boundary Entitlement draws for billing.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UserStats {
	private function __construct(
		public readonly int $user_id,
		public readonly int $total_xp,
		public readonly int $current_streak_days,
		public readonly int $longest_streak_days,
		public readonly ?string $last_active_date,
		public readonly int $streak_freezes_available
	) {}

	/**
	 * The default for a user with no activity yet.
	 *
	 * $starting_freezes is a caller-supplied policy value (see
	 * ActivityRecorder::STARTING_FREEZES), not hardcoded here -- this class
	 * has no opinion on freeze policy, it only holds the shape.
	 */
	public static function zero( int $user_id, int $starting_freezes = 0 ): self {
		return new self( $user_id, 0, 0, 0, null, $starting_freezes );
	}

	/** The same stats with a different number of streak freezes (1.13.0: Pro's monthly top-up). */
	public function with_freezes( int $freezes ): self {
		return new self( $this->user_id, $this->total_xp, $this->current_streak_days, $this->longest_streak_days, $this->last_active_date, max( 0, $freezes ) );
	}

	/** The same stats with a different current streak (1.14.0: a lapsed streak reads as 0). */
	public function with_current_streak( int $days ): self {
		return new self( $this->user_id, $this->total_xp, max( 0, $days ), $this->longest_streak_days, $this->last_active_date, $this->streak_freezes_available );
	}

	/** @param array<string,mixed> $row */
	public static function from_row( array $row ): self {
		return new self(
			(int) ( $row['user_id'] ?? 0 ),
			(int) ( $row['total_xp'] ?? 0 ),
			(int) ( $row['current_streak_days'] ?? 0 ),
			(int) ( $row['longest_streak_days'] ?? 0 ),
			! empty( $row['last_active_date'] ) ? (string) $row['last_active_date'] : null,
			(int) ( $row['streak_freezes_available'] ?? 0 )
		);
	}
}
