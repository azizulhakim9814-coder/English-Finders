<?php
/**
 * Installation and schema upgrade handling.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {
	public const DB_VERSION_OPTION = 'efc_db_version';
	public const SETTINGS_OPTION   = 'efc_settings';

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

		update_option( self::DB_VERSION_OPTION, EFC_DB_VERSION, false );
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

		if ( EFC_DB_VERSION === $installed ) {
			return;
		}

		self::create_tables();
		( new Migrator() )->migrate();

		update_option( self::DB_VERSION_OPTION, EFC_DB_VERSION, false );
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
			 * Provider settings are declared here but not yet consumed. The
			 * provider system lands in a later step; seeding the keys now keeps
			 * the option shape stable so a later release adds behaviour rather
			 * than restructuring stored data.
			 */
			'ai_provider'        => 'openrouter',
			'ai_enabled'         => false,
			'tts_provider'       => 'openrouter',
			/*
			 * Off by default. Enabling it without a key does nothing, but an
			 * unattended install should never be able to start making paid
			 * calls simply because the plugin was activated.
			 */
			'tts_enabled'        => false,
			'tts_model'          => 'hexgrad/kokoro-82m',
			'tts_voice'          => 'af_bella',
			'openrouter_key'     => '',
			'dictionary_enabled' => true,

			/*
			 * Billing (Phase A0). Declared here but has no settings-screen UI
			 * yet -- that lands in Phase A5. `paddle_webhook_secret` is better
			 * set as the EFC_PADDLE_WEBHOOK_SECRET wp-config.php constant
			 * instead, same reasoning as the OpenRouter key above: it keeps a
			 * sensitive value out of the database, and a constant takes
			 * precedence over this option when both are present (see
			 * PaddleWebhookController::webhook_secret()).
			 */
			'paddle_webhook_secret' => '',
			'paddle_price_plan_map' => array(),

			/*
			 * Phase A5. paddle_api_key is a server-side secret (Customer Portal
			 * session creation) with the same wp-config.php-constant-first
			 * precedence as paddle_webhook_secret -- see
			 * PaddlePortalClient::api_key(). paddle_client_side_token is not a
			 * secret: Paddle's own client-side tokens are designed to be
			 * exposed in browser JavaScript, so it has no constant override.
			 */
			'paddle_api_key'            => '',
			'paddle_client_side_token'  => '',
			/*
			 * Defaults to sandbox deliberately: only the sandbox environment
			 * has ever been exercised (see the Phase A0 live-delivery test in
			 * billing-spec-a0.md). Switching a real site to 'production'
			 * requires deciding that explicitly, not inheriting it as a
			 * silent default.
			 */
			'paddle_environment'       => 'sandbox',
		);
	}
}
