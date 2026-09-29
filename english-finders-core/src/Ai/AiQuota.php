<?php
/**
 * Daily AI usage limits.
 *
 * Two limits, checked together on every paid request:
 *
 * - A per-user daily allowance: a small number for free accounts, a larger one
 *   for Pro (owner's decision 2026-09-29: AI feedback is the Pro benefit, which
 *   ties the running cost to paying users).
 * - A site-wide daily cap, the backstop that bounds the whole site's spend on a
 *   bad day (a bug, a burst of new accounts) regardless of any one user.
 *
 * Counts reset at midnight site time. A request that fails at the provider is
 * refunded, so an outage never eats a learner's allowance.
 *
 * Storage is deliberately light: a user meta row per user and one option for
 * the site total. Two requests racing can each pass the check before either
 * increments; the worst case is one extra request, which is acceptable for a
 * cost guard that is itself bounded by the site cap.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Ai;

use EnglishFindersCore\Database\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AiQuota {
	private const USER_META   = 'efc_ai_daily';
	private const SITE_OPTION = 'efc_ai_site_daily';

	public const DEFAULT_FREE_DAILY = 3;
	public const DEFAULT_PRO_DAILY  = 30;
	public const DEFAULT_SITE_CAP   = 300;

	/** @var callable(int):bool Whether a user has Pro. */
	private $is_pro;

	/**
	 * @param callable(int):bool $is_pro Resolves Pro status (the entitlements service in production).
	 */
	public function __construct( callable $is_pro ) {
		$this->is_pro = $is_pro;
	}

	public function daily_limit( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		return $this->daily_limit_for( (bool) ( $this->is_pro )( $user_id ) );
	}

	/**
	 * The daily allowance for a free or a Pro account, e.g. to tell a guest
	 * what signing up (or upgrading) gives them.
	 *
	 * @param bool $pro Pro allowance rather than free.
	 */
	public function daily_limit_for( bool $pro ): int {
		$key     = $pro ? 'ai_pro_daily' : 'ai_free_daily';
		$default = $pro ? self::DEFAULT_PRO_DAILY : self::DEFAULT_FREE_DAILY;

		return max( 0, (int) ( $this->settings()[ $key ] ?? $default ) );
	}

	public function is_pro( int $user_id ): bool {
		return $user_id > 0 && (bool) ( $this->is_pro )( $user_id );
	}

	public function used_today( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		$row = get_user_meta( $user_id, self::USER_META, true );

		return is_array( $row ) && ( $row['d'] ?? '' ) === $this->today() ? (int) ( $row['n'] ?? 0 ) : 0;
	}

	public function remaining( int $user_id ): int {
		return max( 0, $this->daily_limit( $user_id ) - $this->used_today( $user_id ) );
	}

	public function site_used_today(): int {
		$row = get_option( self::SITE_OPTION, array() );

		return is_array( $row ) && ( $row['d'] ?? '' ) === $this->today() ? (int) ( $row['n'] ?? 0 ) : 0;
	}

	public function site_cap(): int {
		return max( 0, (int) ( $this->settings()['ai_site_daily_cap'] ?? self::DEFAULT_SITE_CAP ) );
	}

	/**
	 * Reserve one request for a user.
	 *
	 * @param int $user_id The learner.
	 * @return string Empty on success, otherwise why not: 'user' (this user's
	 *                allowance is used up) or 'site' (the site cap is reached).
	 */
	public function reserve( int $user_id ): string {
		if ( $this->remaining( $user_id ) <= 0 ) {
			return 'user';
		}
		if ( $this->site_used_today() >= $this->site_cap() ) {
			return 'site';
		}

		$this->bump( $user_id, 1 );

		return '';
	}

	/**
	 * Give a reserved request back (the provider failed, so nothing was used).
	 *
	 * @param int $user_id The learner.
	 */
	public function refund( int $user_id ): void {
		$this->bump( $user_id, -1 );
	}

	private function bump( int $user_id, int $delta ): void {
		$today = $this->today();

		update_user_meta(
			$user_id,
			self::USER_META,
			array(
				'd' => $today,
				'n' => max( 0, $this->used_today( $user_id ) + $delta ),
			)
		);

		update_option(
			self::SITE_OPTION,
			array(
				'd' => $today,
				'n' => max( 0, $this->site_used_today() + $delta ),
			),
			false
		);
	}

	private function today(): string {
		return wp_date( 'Y-m-d' );
	}

	/** @return array<string,mixed> */
	private function settings(): array {
		$settings = get_option( Installer::SETTINGS_OPTION, array() );

		return is_array( $settings ) ? $settings : array();
	}
}
