<?php
/**
 * Versioned migration runner with rollback support.
 *
 * Core keeps its own migration ledger, entirely separate from Word Games Pro's
 * `wuc_migrations` table. This is the point of the core-plugin approach: one
 * owner runs migrations against a given set of tables, so two plugins can never
 * apply conflicting schema changes to the same data.
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

final class Migrator {
	/** @return list<MigrationInterface> */
	public function migrations(): array {
		$migrations = array( new InitialMigration(), new AdoptSharedTablesMigration(), new CefrColumnsMigration(), new SenseCefrColumnsMigration(), new BillingTablesMigration(), new BillingEventsCustomerColumnMigration(), new ActivityTablesMigration(), new LevelResultsTableMigration(), new MistakesTableMigration(), new ActivityOccurredAtIndexMigration(), new CertificatesTableMigration() );

		/**
		 * Filter the registered Core migrations.
		 *
		 * @param list<MigrationInterface> $migrations Registered migrations.
		 */
		$migrations = apply_filters( 'efc_database_migrations', $migrations );
		$migrations = array_values(
			array_filter(
				is_array( $migrations ) ? $migrations : array(),
				static fn ( mixed $migration ): bool => $migration instanceof MigrationInterface
			)
		);

		usort(
			$migrations,
			static fn ( MigrationInterface $a, MigrationInterface $b ): int => version_compare( $a->version(), $b->version() )
		);

		return $migrations;
	}

	/**
	 * Apply all pending migrations.
	 *
	 * Each migration runs inside its own transaction: a failure rolls that
	 * migration back and aborts, rather than leaving the schema half-applied.
	 *
	 * @return list<string> Versions applied during this run.
	 */
	public function migrate(): array {
		global $wpdb;

		$table   = Schema::table( 'migrations' );
		$applied = $this->applied_versions();
		$batch   = $this->next_batch();
		$ran     = array();

		foreach ( $this->migrations() as $migration ) {
			if ( in_array( $migration->version(), $applied, true ) ) {
				continue;
			}

			$wpdb->query( 'START TRANSACTION' );

			try {
				$migration->up();
				$wpdb->replace(
					$table,
					array(
						'version'    => $migration->version(),
						'batch'      => $batch,
						'operation'  => 'up',
						'applied_at' => current_time( 'mysql', true ),
					),
					array( '%s', '%d', '%s', '%s' )
				);
				$wpdb->query( 'COMMIT' );
				$ran[] = $migration->version();
			} catch ( \Throwable $throwable ) {
				$wpdb->query( 'ROLLBACK' );
				throw new RuntimeException(
					'Core migration ' . $migration->version() . ' failed: ' . $throwable->getMessage(),
					0,
					$throwable
				);
			}
		}

		return $ran;
	}

	/** @return list<string> */
	public function applied_versions(): array {
		global $wpdb;

		$table = Schema::table( 'migrations' );
		if ( ! $this->ledger_exists() ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
		$versions = $wpdb->get_col( "SELECT version FROM {$table} WHERE operation = 'up'" );

		return array_map( 'strval', is_array( $versions ) ? $versions : array() );
	}

	public function pending_count(): int {
		$applied = $this->applied_versions();
		$pending = 0;

		foreach ( $this->migrations() as $migration ) {
			if ( ! in_array( $migration->version(), $applied, true ) ) {
				++$pending;
			}
		}

		return $pending;
	}

	private function next_batch(): int {
		global $wpdb;

		if ( ! $this->ledger_exists() ) {
			return 1;
		}

		$table = Schema::table( 'migrations' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
		$max = (int) $wpdb->get_var( "SELECT MAX(batch) FROM {$table}" );

		return $max + 1;
	}

	/**
	 * Whether the ledger table exists yet.
	 *
	 * On first activation the ledger is created by the installer before any
	 * migration runs, so this guards the window where it legitimately does not
	 * exist rather than emitting a database error.
	 */
	private function ledger_exists(): bool {
		global $wpdb;

		$table = Schema::table( 'migrations' );
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		return is_string( $found ) && $found === $table;
	}
}
