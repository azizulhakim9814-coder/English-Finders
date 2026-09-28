<?php
/**
 * Verifies the Paddle-Signature header on an inbound webhook request.
 *
 * This is the actual security boundary for the webhook route -- Paddle
 * cannot authenticate as a WordPress user or send a nonce, so a request that
 * fails this check is rejected outright rather than trusted and filtered
 * some other way.
 *
 * Header format: `ts=<unix timestamp>;h1=<hex-encoded HMAC-SHA256>`. The
 * signed payload is `"{ts}:{raw body}"`, HMAC'd with the webhook signing
 * secret from the Paddle dashboard. This mirrors Paddle's documented webhook
 * signature verification scheme -- confirm the current version of that
 * documentation before this goes live, since Paddle owns the format and can
 * revise it.
 *
 * Deliberately pure PHP with no WordPress dependency: signature verification
 * is exactly the kind of logic that should be testable without a database or
 * a WP bootstrap, and every WordPress-specific concern (reading the header,
 * looking up the secret) belongs in the controller that calls this, not here.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Billing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PaddleSignatureVerifier {
	/**
	 * @param string $raw_body          Exact, unmodified request body -- not a re-encoded copy of the decoded JSON, since re-encoding is not guaranteed to reproduce the exact bytes Paddle signed.
	 * @param string $signature_header  Value of the Paddle-Signature request header.
	 * @param string $secret            Webhook signing secret from the Paddle dashboard.
	 * @param int    $tolerance_seconds Reject a signature whose timestamp is further than this from now, to blunt replay of a captured request. Paddle's own retries are recent, so 300s is generous rather than tight.
	 */
	public static function verify(
		string $raw_body,
		string $signature_header,
		string $secret,
		int $tolerance_seconds = 300
	): bool {
		if ( '' === $secret || '' === $signature_header ) {
			return false;
		}

		$parsed = self::parse_header( $signature_header );
		if ( null === $parsed ) {
			return false;
		}

		list( $timestamp, $hash ) = $parsed;

		if ( abs( time() - $timestamp ) > $tolerance_seconds ) {
			return false;
		}

		$computed = hash_hmac( 'sha256', $timestamp . ':' . $raw_body, $secret );

		// hash_equals for a timing-safe comparison -- a plain === here would
		// leak how many leading bytes of a guessed signature were correct.
		return hash_equals( $computed, strtolower( $hash ) );
	}

	/**
	 * Parse `ts=...;h1=...` into its parts.
	 *
	 * Order-independent and tolerant of extra whitespace around segments,
	 * since neither is specified to be fixed and rejecting on that basis
	 * would be a self-inflicted fragility.
	 *
	 * @return array{0:int,1:string}|null
	 */
	private static function parse_header( string $header ): ?array {
		$timestamp = null;
		$hash      = null;

		foreach ( explode( ';', $header ) as $segment ) {
			$pair = explode( '=', trim( $segment ), 2 );
			if ( 2 !== count( $pair ) ) {
				continue;
			}

			list( $key, $value ) = $pair;

			if ( 'ts' === $key ) {
				$timestamp = trim( $value );
			} elseif ( 'h1' === $key ) {
				$hash = trim( $value );
			}
		}

		if ( null === $timestamp || null === $hash || ! ctype_digit( $timestamp ) || '' === $hash ) {
			return null;
		}

		return array( (int) $timestamp, $hash );
	}
}
