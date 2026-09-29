<?php
/**
 * Learner or teacher (0.13.0).
 *
 * Asked at sign-up (and carried through "Continue with Google"), editable
 * in Profile, stored as the `account_type` profile field. It only labels
 * the account for now: the teacher tools (Phase A7) aren't built, so no
 * WordPress role or capability depends on it -- in particular it is NOT
 * Tutor's "instructor" role, which means course author.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Profile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AccountType {
	public const LEARNER = 'learner';
	public const TEACHER = 'teacher';

	/** @return array<string,string> value => label */
	public static function options(): array {
		return array(
			self::LEARNER => __( "I'm learning English", 'english-finders-account' ),
			self::TEACHER => __( 'I teach English', 'english-finders-account' ),
		);
	}

	/** Anything unexpected becomes "learner" -- the safe, most common default. */
	public static function normalize( string $value ): string {
		return self::TEACHER === $value ? self::TEACHER : self::LEARNER;
	}
}
