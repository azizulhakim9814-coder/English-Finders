<?php
/**
 * Create the certificates table (1.12.0).
 *
 * A brand-new table, so dbDelta is the right tool -- same as the other
 * table migrations.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

use RuntimeException;
use EnglishFindersCore\Contracts\MigrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CertificatesTableMigration implements MigrationInterface {
	public function version(): string {
		return '1.12.0';
	}

	public function description(): string {
		return 'Create the certificates table for course-completion certificates.';
	}

	public function up(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( Schema::definitions()['certificates'] );
	}

	/** Refuses while any certificate exists: issued certificates (and their verification links) can't be regenerated. */
	public function down(): void {
		global $wpdb;

		$name = Schema::table( 'certificates' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$name}" );

		if ( $count > 0 ) {
			throw new RuntimeException( "Refusing to drop {$name}: it holds {$count} issued certificate(s). Roll back manually if this is genuinely intended." );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$name}" );
	}
}
