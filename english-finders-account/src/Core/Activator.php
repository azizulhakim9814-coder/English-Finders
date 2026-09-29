<?php
/**
 * Plugin activation.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Core;

use EnglishFindersAccount\Support\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {
	/**
	 * No table install step -- Phase A1 introduces no database tables,
	 * profile fields live in usermeta, written lazily the first time a
	 * user saves a value. See a1-account-foundation.md for why (a real
	 * Schema/Migrator pair is added only once Phase A2 gives it something
	 * to migrate). Settings::seed_defaults() adds the efa_settings option
	 * (Turnstile keys, empty until configured) -- introduced for the
	 * anti-bot registration hardening pass.
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( EFA_BASENAME );
			wp_die( esc_html__( 'English Finders Account requires PHP 8.1 or newer.', 'english-finders-account' ) );
		}

		Settings::seed_defaults();
	}
}
