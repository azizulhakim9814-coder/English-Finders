<?php
/**
 * Schema migration contract.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface MigrationInterface {
	/**
	 * Semantic version this migration introduces, e.g. "1.0.0".
	 *
	 * Migrations run in version order, so this determines sequencing.
	 */
	public function version(): string;

	/** Human-readable summary, surfaced in admin and logs. */
	public function description(): string;

	/** Apply the migration. Must throw on failure so the runner can roll back. */
	public function up(): void;

	/**
	 * Reverse the migration.
	 *
	 * Implementations that genuinely cannot be reversed should throw rather
	 * than silently doing nothing, so a failed rollback is visible instead of
	 * appearing to succeed.
	 */
	public function down(): void;
}
