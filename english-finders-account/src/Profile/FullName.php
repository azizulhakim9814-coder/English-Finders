<?php
/**
 * The learner's full name (0.13.0: required at sign-up).
 *
 * Stored three ways, because three things read it: `display_name`
 * (everywhere WordPress shows a name), and `first_name` + `last_name`
 * (Core's certificates prefer those). Split on the first space: "Ana Maria
 * da Silva" -> "Ana" / "Maria da Silva"; a single word goes in first_name.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Profile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FullName {
	public const MIN_LENGTH = 2;
	public const MAX_LENGTH = 80;

	/** Collapses whitespace; strips tags. */
	public static function clean( string $raw ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', sanitize_text_field( $raw ) ) );
	}

	/**
	 * At least two characters, at most 80, containing a letter, and not an
	 * email address (which would then be printed on certificates and the
	 * leaderboard).
	 */
	public static function is_valid( string $name ): bool {
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $name ) : strlen( $name );

		return $length >= self::MIN_LENGTH
			&& $length <= self::MAX_LENGTH
			&& 1 === preg_match( '/\p{L}/u', $name )
			&& ! str_contains( $name, '@' );
	}

	/** @return array{0: string, 1: string} first name, last name */
	public static function split( string $name ): array {
		$parts = explode( ' ', $name, 2 );

		return array( $parts[0], $parts[1] ?? '' );
	}

	/** Saves display_name + first_name + last_name. */
	public static function save( int $user_id, string $name ): void {
		list( $first, $last ) = self::split( $name );

		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $name,
				'first_name'   => $first,
				'last_name'    => $last,
			)
		);
	}
}
