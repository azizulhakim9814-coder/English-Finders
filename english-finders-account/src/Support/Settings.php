<?php
/**
 * This plugin's own settings option (separate from Core's efc_settings --
 * Turnstile is a registration concern local to this plugin, not something
 * WGP or EFS need to read).
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {
	public const OPTION = 'efa_settings';

	/** @return array<string,mixed> */
	public static function all(): array {
		$settings = get_option( self::OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	public static function get( string $key, mixed $default = '' ): mixed {
		return self::all()[ $key ] ?? $default;
	}

	/**
	 * Seeds default settings on activation. Never overwrites an existing
	 * option -- a re-activation must not wipe out keys already configured.
	 */
	public static function seed_defaults(): void {
		if ( is_array( get_option( self::OPTION, null ) ) ) {
			return;
		}

		add_option(
			self::OPTION,
			array(
				'turnstile_site_key'   => '',
				'turnstile_secret_key' => '',
			)
		);
	}
}
