<?php
/**
 * Unique usernames for new accounts (0.13.0: shared by email sign-up and
 * "Continue with Google"; previously private to RegistrationHandler).
 *
 * Learners log in by email (the login field accepts either), so the
 * username is mostly invisible -- it only has to be unique.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Usernames {
	public static function from_email( string $email ): string {
		$local = current( explode( '@', $email ) );
		$base  = sanitize_user( (string) $local, true );
		$base  = '' !== $base ? $base : 'user';

		$username = $base;
		$suffix   = 1;

		while ( username_exists( $username ) ) {
			++$suffix;
			$username = $base . $suffix;
		}

		return $username;
	}
}
