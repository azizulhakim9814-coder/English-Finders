<?php
/**
 * Plugin deactivation.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivator {
	/**
	 * Deactivation deliberately destroys nothing.
	 *
	 * Core owns the dictionary data that both consumer plugins depend on.
	 * Dropping tables here would mean an accidental deactivation destroys the
	 * word database. Data removal belongs in an explicit uninstall path only,
	 * where the user has chosen to delete the plugin outright.
	 */
	public static function deactivate(): void {
		wp_cache_flush();
	}
}
