<?php
/**
 * Plugin activation.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Core;

use EnglishFindersCore\Database\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {
	public static function activate( bool $network_wide = false ): void {
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( EFC_BASENAME );
			wp_die( esc_html__( 'English Finders Core requires PHP 8.1 or newer.', 'english-finders-core' ) );
		}

		if ( is_multisite() && $network_wide ) {
			self::for_each_site( array( self::class, 'install_site' ) );
			return;
		}

		self::install_site();
	}

	public static function install_site(): void {
		Installer::install();
	}

	/**
	 * Run a callback against every site on a multisite network.
	 *
	 * Batched rather than loaded all at once: a large network would otherwise
	 * exhaust memory building the full site list.
	 *
	 * @param callable():void $callback Per-site callback.
	 */
	private static function for_each_site( callable $callback ): void {
		$offset = 0;
		$limit  = 100;

		do {
			$sites = get_sites(
				array(
					'number'     => $limit,
					'offset'     => $offset,
					'fields'     => 'ids',
					'no_found_rows' => true,
				)
			);

			foreach ( $sites as $site_id ) {
				switch_to_blog( (int) $site_id );
				$callback();
				restore_current_blog();
			}

			$offset += $limit;
		} while ( count( $sites ) === $limit );
	}
}
