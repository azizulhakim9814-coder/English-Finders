<?php
/**
 * Study table definitions.
 *
 * Only this plugin's own tables. The dictionary, CEFR levels and word helpers
 * belong to English Finders Core and are read through it — duplicating them
 * here is exactly the drift the Core plugin exists to prevent.
 *
 * At this stage there is only the migration ledger. Content tables for grammar
 * items, reading passages and attempt history arrive with the categories that
 * need them, rather than being created empty in advance.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {
	/** Distinct prefix, so ownership is readable straight from a database dump. */
	public const PREFIX = 'efs_';

	public static function prefix(): string {
		global $wpdb;
		return $wpdb->prefix . self::PREFIX;
	}

	public static function table( string $name ): string {
		return self::prefix() . $name;
	}

	/**
	 * @return array<string,string>
	 */
	public static function definitions(): array {
		global $wpdb;
		$prefix  = self::prefix();
		$collate = $wpdb->get_charset_collate();

		return array(
			'migrations' => "CREATE TABLE {$prefix}migrations (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				version varchar(32) NOT NULL,
				batch int(10) unsigned NOT NULL DEFAULT 0,
				operation varchar(8) NOT NULL DEFAULT 'up',
				applied_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY version_operation (version,operation),
				KEY batch (batch)
			) {$collate};",
		);
	}

	/** @return list<string> */
	public static function table_names(): array {
		return array_map(
			static fn ( string $name ): string => self::table( $name ),
			array_keys( self::definitions() )
		);
	}
}
