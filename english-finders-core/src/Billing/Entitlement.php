<?php
/**
 * What a user is entitled to, independent of how billing arrived at it.
 *
 * Every plugin that needs to know "can this user see the full mistake
 * notebook" reads one of these, never Paddle's API and never the raw
 * database row. That indirection is the whole point of Phase A0: it is what
 * lets the billing provider change later without every consumer changing
 * with it.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Billing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Entitlement {
	/** Plan codes that unlock paid features, when the subscription is active. */
	private const PAID_PLANS = array( 'pro', 'teacher_pro' );

	private function __construct(
		public readonly string $plan_code,
		public readonly string $status,
		public readonly ?string $current_period_end,
		public readonly bool $cancel_at_period_end,
		public readonly ?int $seats
	) {}

	/**
	 * The default for any user with no entitlement row.
	 *
	 * This is deliberately the only place "no row" is interpreted -- callers
	 * never need their own fallback logic for a missing subscriber.
	 */
	public static function free(): self {
		return new self( 'free', 'active', null, false, null );
	}

	/** @param array<string,mixed> $row */
	public static function from_row( array $row ): self {
		return new self(
			'' !== (string) ( $row['plan_code'] ?? '' ) ? (string) $row['plan_code'] : 'free',
			'' !== (string) ( $row['status'] ?? '' ) ? (string) $row['status'] : 'active',
			! empty( $row['current_period_end'] ) ? (string) $row['current_period_end'] : null,
			! empty( $row['cancel_at_period_end'] ),
			isset( $row['seats'] ) && null !== $row['seats'] ? (int) $row['seats'] : null
		);
	}

	/**
	 * Whether the subscription itself is in good standing.
	 *
	 * Free is always "active" by definition (see free() above). A paid plan
	 * that has lapsed into past_due, paused, or canceled is not active, even
	 * though the row's plan_code still says "pro" -- the grace-period policy
	 * for exactly how long a past_due user keeps access is an open question
	 * (see billing-spec-a0.md) and is not decided by this class.
	 */
	public function is_active(): bool {
		return 'active' === $this->status;
	}

	/** Whether this entitlement currently unlocks paid (Pro-tier) features. */
	public function unlocks_pro(): bool {
		return $this->is_active() && in_array( $this->plan_code, self::PAID_PLANS, true );
	}

	public function is_teacher(): bool {
		return $this->is_active() && 'teacher_pro' === $this->plan_code;
	}
}
