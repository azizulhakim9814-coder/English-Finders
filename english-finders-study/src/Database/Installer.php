<?php
/**
 * Installation and schema upgrade handling.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {
	public const DB_VERSION_OPTION = 'efs_db_version';
	public const SETTINGS_OPTION   = 'efs_settings';

	/**
	 * Run on activation.
	 *
	 * Creates tables directly via dbDelta first so the ledger exists, then runs
	 * the migration runner to record state. Both are idempotent, so repeat
	 * activation is safe.
	 */
	public static function install(): void {
		self::create_tables();

		( new Migrator() )->migrate();

		self::seed_settings();

		update_option( self::DB_VERSION_OPTION, EFS_DB_VERSION, false );
	}

	/**
	 * Run on every load to catch schema drift.
	 *
	 * Activation hooks do not fire on plugin *update*, only on activation, so
	 * a version check on boot is what actually applies migrations after an
	 * update. Cheap: one non-autoloaded option read, then an early return.
	 */
	public static function maybe_upgrade(): void {
		$installed = (string) get_option( self::DB_VERSION_OPTION, '' );

		if ( EFS_DB_VERSION === $installed ) {
			return;
		}

		self::create_tables();
		( new Migrator() )->migrate();

		update_option( self::DB_VERSION_OPTION, EFS_DB_VERSION, false );
	}

	private static function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( Schema::definitions() as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Seed default settings.
	 *
	 * Stored with autoload = false, matching the discipline already applied to
	 * `wuc_settings`: options that are not needed on every request should not
	 * be loaded on every request.
	 */
	private static function seed_settings(): void {
		$existing = get_option( self::SETTINGS_OPTION, null );

		if ( is_array( $existing ) ) {
			return;
		}

		update_option( self::SETTINGS_OPTION, self::default_settings(), false );
	}

	/** @return array<string,mixed> */
	public static function default_settings(): array {
		return array(
			/*
			 * A disable-list, not an allow-list: a tool is on unless its id is
			 * named here. Empty means every registered tool is enabled, which
			 * is what a fresh install should be — see
			 * `ToolCatalog::is_enabled()` for why the list is a disable-list
			 * rather than the reverse. Provider settings (API keys, TTS, AI)
			 * belong to Core: this plugin asks Core for those services rather
			 * than holding its own credentials.
			 */
			'disabled_tools'           => array(),
			/*
			 * Read by uninstall.php. Off by default: deleting the plugin
			 * should not silently discard data that may have taken time to
			 * build, unless an admin has explicitly opted in.
			 */
			'delete_data_on_uninstall' => false,
		);
	}
}
