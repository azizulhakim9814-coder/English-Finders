<?php
/**
 * Reads and writes the small set of profile fields Phase A1 owns.
 *
 * Stored as usermeta, not a custom table -- see a1-account-foundation.md
 * point 2 for why: simple key-value fields don't need relational
 * structure, and WordPress's own privacy-export/erase tooling already
 * knows how to work with usermeta cheaply.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Profile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProfileRepository {
	private const META_PREFIX = 'efa_';

	/**
	 * Field defaults. The value's PHP type also tells set()/get_all() how
	 * to sanitise and cast that field -- see the match() in set() below.
	 *
	 * @var array<string,bool|int|string>
	 */
	private const DEFAULTS = array(
		'native_language'       => '',
		// 0.13.0: 'learner' | 'teacher' -- see AccountType.
		'account_type'          => 'learner',
		/*
		 * Superseded by `daily_goal` in 0.9.0 (the site records XP, not time
		 * -- see Core's DailyGoal). No longer in the form and nothing reads
		 * it; kept so uninstall and privacy erasure still clean up any value
		 * saved before 0.9.0 (none existed on the live site at the switch).
		 */
		'daily_goal_minutes'    => 10,
		/* A Core DailyGoal tier key: casual / regular / serious / intense. Validated in ProfileSaveHandler. */
		'daily_goal'            => 'regular',
		'public_profile'        => false,
		'leaderboard_optin'     => false,
		'notifications_enabled' => true,
	);

	/**
	 * The usermeta key a field is stored under -- for the one place that
	 * has to name it in SQL (Core's weekly leaderboard filters on the
	 * `leaderboard_optin` flag). Null for an unknown field.
	 */
	public static function meta_key( string $field ): ?string {
		return array_key_exists( $field, self::DEFAULTS ) ? self::META_PREFIX . $field : null;
	}

	/** @return array<string,bool|int|string> */
	public function get_all( int $user_id ): array {
		$profile = array();

		foreach ( self::DEFAULTS as $field => $default ) {
			$profile[ $field ] = $this->get( $user_id, $field );
		}

		return $profile;
	}

	public function get( int $user_id, string $field ): bool|int|string|null {
		if ( ! array_key_exists( $field, self::DEFAULTS ) ) {
			return null;
		}

		$default = self::DEFAULTS[ $field ];
		$raw     = get_user_meta( $user_id, self::META_PREFIX . $field, true );

		/*
		 * get_user_meta() with single=true returns '' both when the value
		 * was never set and when it was genuinely saved as an empty
		 * string. That ambiguity is harmless here: every default is
		 * itself the "nothing set yet" value (0/false/''), so falling
		 * back to the default in either case produces the right answer.
		 */
		if ( '' === $raw ) {
			return $default;
		}

		return match ( true ) {
			is_bool( $default ) => (bool) $raw,
			is_int( $default )  => (int) $raw,
			default              => (string) $raw,
		};
	}

	public function set( int $user_id, string $field, mixed $value ): bool {
		if ( ! array_key_exists( $field, self::DEFAULTS ) ) {
			return false;
		}

		$default    = self::DEFAULTS[ $field ];
		$sanitized  = match ( true ) {
			is_bool( $default ) => $value ? '1' : '0',
			is_int( $default )  => (string) max( 0, absint( $value ) ),
			default              => sanitize_text_field( (string) $value ),
		};

		return false !== update_user_meta( $user_id, self::META_PREFIX . $field, $sanitized );
	}

	/**
	 * Delete every field this repository owns for a user.
	 *
	 * Used by the privacy eraser (PrivacyIntegration) -- returns whether
	 * anything was actually removed, since the eraser API needs to report
	 * that back.
	 */
	public function delete_all( int $user_id ): bool {
		$removed_anything = false;

		foreach ( array_keys( self::DEFAULTS ) as $field ) {
			if ( delete_user_meta( $user_id, self::META_PREFIX . $field ) ) {
				$removed_anything = true;
			}
		}

		return $removed_anything;
	}
}
