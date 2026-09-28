<?php
/**
 * Initial Core schema.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

use EnglishFindersCore\Contracts\MigrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class InitialMigration implements MigrationInterface {
	public function version(): string {
		return '1.0.0';
	}

	public function description(): string {
		return 'Create the English Finders Core migration ledger.';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( Schema::definitions() as $sql ) {
			dbDelta( $sql );
		}
	}

	public function down(): void {
		global $wpdb;

		/*
		 * Intentionally does not drop the ledger.
		 *
		 * Dropping the table that records which migrations have run would erase
		 * the history needed to reason about the database state, and would make
		 * a subsequent re-activation believe nothing had ever been applied.
		 * Full teardown happens only on explicit uninstall.
		 */
		unset( $wpdb );
	}
}
