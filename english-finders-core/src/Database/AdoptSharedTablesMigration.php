<?php
/**
 * Adopt the shared dictionary tables.
 *
 * This migration performs no data movement. Against an existing install the
 * tables already exist with exactly these definitions, so dbDelta compares and
 * changes nothing — the migration's real effect is to record that Core is now
 * the owner. On a fresh install where Word Games Pro was never present, it
 * creates them.
 *
 * The safety of this rests on the definitions being byte-identical to the ones
 * Word Games Pro shipped through 2.1.15, which is asserted in the test suite
 * rather than assumed.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

use EnglishFindersCore\Contracts\MigrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdoptSharedTablesMigration implements MigrationInterface {
	public function version(): string {
		return '1.1.0';
	}

	public function description(): string {
		return 'Adopt the shared dictionary tables (words, senses, grams, packs, import jobs) into Core ownership.';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( Schema::shared_definitions() as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Rollback is intentionally a no-op.
	 *
	 * Adoption changed no data and no structure — it only recorded ownership.
	 * Reversing it must therefore also change nothing. Dropping these tables
	 * here would destroy the dictionary, which is the opposite of what rolling
	 * back a no-op should do.
	 */
	public function down(): void {}
}
