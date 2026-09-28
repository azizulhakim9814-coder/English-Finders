<?php
/**
 * Public API facade.
 *
 * This is the only surface consumer plugins should touch. Word Games Pro and
 * Learning Toolkit Pro call these static methods; they never query Core's
 * tables directly and never instantiate Core's internals. Keeping the boundary
 * narrow is what makes it possible to change Core's implementation later
 * without breaking either consumer.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Support;

use EnglishFindersCore\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Api {
	/**
	 * Whether Core is loaded and booted.
	 *
	 * Consumers should call this before using anything else, so a missing or
	 * deactivated Core degrades gracefully instead of fataling. WordPress 6.5+
	 * dependency headers make that unlikely, but a runtime guard costs nothing
	 * and covers the cases headers do not — such as a partially failed update.
	 */
	public static function available(): bool {
		return class_exists( Plugin::class ) && defined( 'EFC_VERSION' );
	}

	public static function version(): string {
		return self::available() ? EFC_VERSION : '';
	}

	/**
	 * Whether Core's API is compatible with the version a consumer expects.
	 *
	 * WordPress dependency headers enforce that a required plugin is active,
	 * but do not check versions. This closes that gap.
	 *
	 * @param string $minimum Minimum acceptable Core version.
	 */
	public static function is_at_least( string $minimum ): bool {
		return self::available() && version_compare( EFC_VERSION, $minimum, '>=' );
	}

	/**
	 * Resolve a Core service.
	 *
	 * @param string $id Service identifier.
	 * @return mixed Null when Core is unavailable or the service is unknown.
	 */
	public static function service( string $id ): mixed {
		if ( ! self::available() ) {
			return null;
		}

		return Plugin::instance()->get( $id );
	}
}
