<?php
/**
 * Plugin deactivation.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivator {
	/**
	 * Deactivation destroys nothing.
	 *
	 * A deactivated account plugin should not delete anyone's profile data
	 * or lock anyone out permanently -- that belongs only in the explicit,
	 * opt-in uninstall path, where the user has chosen to delete the
	 * plugin outright.
	 */
	public static function deactivate(): void {
		wp_cache_flush();
	}
}
