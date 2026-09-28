<?php
/**
 * IP-based rate limiting for the registration form.
 *
 * A transient-backed counter, not a table -- this is throwaway state that
 * should expire on its own, exactly what transients are for. Caps total
 * registration attempts per IP per window regardless of whether each one
 * succeeds or fails, since a bot that succeeds every time is exactly the
 * case this exists to stop.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RegistrationThrottle {
	private const TRANSIENT_PREFIX = 'efa_reg_throttle_';
	private const MAX_ATTEMPTS     = 5;
	private const WINDOW_SECONDS   = 900; // 15 minutes.

	public function is_rate_limited( string $ip ): bool {
		return $this->count( $ip ) >= self::MAX_ATTEMPTS;
	}

	public function record_attempt( string $ip ): void {
		$key = $this->transient_key( $ip );
		set_transient( $key, $this->count( $ip ) + 1, self::WINDOW_SECONDS );
	}

	private function count( string $ip ): int {
		return (int) get_transient( $this->transient_key( $ip ) );
	}

	private function transient_key( string $ip ): string {
		return self::TRANSIENT_PREFIX . md5( $ip );
	}

	/**
	 * Best-effort client IP, checking common proxy headers before falling
	 * back to REMOTE_ADDR. This site's actual proxy chain (if any) isn't
	 * something this code can verify, so a spoofed X-Forwarded-For header
	 * is possible -- the consequence of getting it wrong is a diluted rate
	 * limit, not a blocked legitimate user, which is an acceptable trade
	 * given this is one layer of several (see also Honeypot, Turnstile).
	 */
	public static function resolve_ip(): string {
		foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			$first = trim( explode( ',', $value )[0] );

			if ( false !== filter_var( $first, FILTER_VALIDATE_IP ) ) {
				return $first;
			}
		}

		return '0.0.0.0';
	}
}
