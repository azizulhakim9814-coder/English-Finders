<?php
/**
 * Core table definitions.
 *
 * Core owns the six shared dictionary tables. They deliberately keep their
 * existing `wuc_` prefix rather than being renamed or copied:
 *
 *  - Renaming would require changing 61 hardcoded table-name strings across 11
 *    files in Word Games Pro. Missing one produces a fatal error on a live
 *    page — a poor trade for a cosmetic naming improvement.
 *  - Copying would mean moving `word_grams` (one row per 3-letter substring per
 *    word, likely hundreds of thousands of rows) on shared hosting, risking
 *    exactly the CPU pressure this site previously had to resolve.
 *
 * Ownership is about which code manages the schema, not what the tables are
 * called. Core defines and migrates them; Word Games Pro no longer does. Names
 * can be normalised later, once every consumer reads through Core's service
 * layer and no table name is hardcoded anywhere.
 *
 * The shared definitions below are copied verbatim from Word Games Pro 2.1.15
 * so dbDelta sees no difference against a live install and makes no change.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {
	/**
	 * Prefix for Core's own internal tables.
	 *
	 * Started as just the migration ledger; from 1.5.0 also covers the
	 * billing entitlement tables (Phase A0 of the account/billing build).
	 */
	public const PREFIX = 'efc_';

	/** Prefix of the adopted shared tables. Historical, retained deliberately. */
	public const SHARED_PREFIX = 'wuc_';

	/**
	 * The shared tables Core owns.
	 *
	 * Word Games Pro retains `analytics`, `user_data`, `daily_puzzles` and its
	 * own `migrations` ledger: those are specific to how its games work and have
	 * no meaning to a grammar quiz or a teacher worksheet.
	 */
	public const SHARED_TABLES = array(
		'words',
		'word_grams',
		'word_senses',
		'dictionary_packs',
		'import_jobs',
		'import_job_words',
	);

	public static function prefix(): string {
		global $wpdb;
		return $wpdb->prefix . self::PREFIX;
	}

	public static function shared_prefix(): string {
		global $wpdb;
		return $wpdb->prefix . self::SHARED_PREFIX;
	}

	/** Core-internal table name, e.g. migrations. */
	public static function table( string $name ): string {
		return self::prefix() . $name;
	}

	/** Shared dictionary table name, e.g. words. */
	public static function shared_table( string $name ): string {
		return self::shared_prefix() . $name;
	}

	/**
	 * Core's own internal tables.
	 *
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

			/*
			 * One row per subscriber. This is the only table any plugin should
			 * read to decide what a user can do -- never Paddle's API directly,
			 * and never the billing_events log below. That indirection is what
			 * lets the billing provider be swapped later without touching every
			 * place that checks entitlement.
			 *
			 * `user_id` is nullable: a subscription created at checkout before an
			 * account exists lands here unclaimed (user_id NULL, paddle_customer_id
			 * set) and is attached to a WordPress user on next login. MySQL allows
			 * more than one NULL in a UNIQUE key, so several unclaimed rows can
			 * coexist without colliding.
			 */
			'entitlements' => "CREATE TABLE {$prefix}entitlements (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NULL,
				paddle_customer_id varchar(64) NOT NULL DEFAULT '',
				paddle_subscription_id varchar(64) NOT NULL DEFAULT '',
				plan_code varchar(32) NOT NULL DEFAULT 'free',
				status varchar(32) NOT NULL DEFAULT 'active',
				current_period_end datetime NULL,
				cancel_at_period_end tinyint(1) unsigned NOT NULL DEFAULT 0,
				seats smallint(5) unsigned NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY user_id (user_id),
				UNIQUE KEY paddle_subscription_id (paddle_subscription_id),
				KEY paddle_customer_id (paddle_customer_id),
				KEY status (status)
			) {$collate};",

			/*
			 * Full log of every webhook received, kept indefinitely. Two jobs:
			 * the `paddle_event_id` unique key makes redelivery idempotent (an
			 * INSERT that hits the key is treated as "already handled", not
			 * reprocessed), and the log itself is the audit trail if entitlement
			 * state in the table above ever looks wrong.
			 *
			 * `paddle_customer_id` (1.6.0, Phase A5): indexed so the billing UI's
			 * transaction history (TransactionLog) can look up one customer's
			 * events without scanning the whole table. Populated on insert by
			 * PaddleWebhookController; BillingEventsCustomerColumnMigration
			 * backfills it for rows written before this column existed.
			 */
			'billing_events' => "CREATE TABLE {$prefix}billing_events (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				paddle_event_id varchar(64) NOT NULL,
				event_type varchar(64) NOT NULL DEFAULT '',
				paddle_customer_id varchar(64) NOT NULL DEFAULT '',
				payload_json longtext NULL,
				processed_at datetime NULL,
				received_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY paddle_event_id (paddle_event_id),
				KEY event_type (event_type),
				KEY received_at (received_at),
				KEY paddle_customer_id (paddle_customer_id)
			) {$collate};",

			/*
			 * Phase A2 (1.7.0). Append-only log of every XP-worthy action, one
			 * row per event, never edited or deleted. `xp_earned` is stored
			 * redundantly at write time rather than looked up fresh from the
			 * XP table on every read, so tuning that table later never rewrites
			 * history. `user_stats` below is the derived-state table (same
			 * raw-log/derived-state split as billing_events/entitlements above)
			 * -- nothing should compute XP or streaks by scanning this table
			 * directly.
			 */
			'activity_events' => "CREATE TABLE {$prefix}activity_events (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				source varchar(20) NOT NULL,
				event_type varchar(50) NOT NULL,
				xp_earned int(11) NOT NULL DEFAULT 0,
				metadata_json longtext NULL,
				occurred_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id_occurred_at (user_id,occurred_at),
				KEY event_type (event_type),
				KEY occurred_at (occurred_at)
			) {$collate};",

			/*
			 * Phase A2 (1.7.0). One row per user, derived entirely from
			 * activity_events -- ActivityRecorder is the only writer.
			 * `last_active_date` (not datetime) is what streak continuation
			 * compares against, one calendar day at a time.
			 */
			'user_stats' => "CREATE TABLE {$prefix}user_stats (
				user_id bigint(20) unsigned NOT NULL,
				total_xp bigint(20) unsigned NOT NULL DEFAULT 0,
				current_streak_days int(11) unsigned NOT NULL DEFAULT 0,
				longest_streak_days int(11) unsigned NOT NULL DEFAULT 0,
				last_active_date date NULL,
				streak_freezes_available int(11) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (user_id)
			) {$collate};",

			/*
			 * Phase A2 (1.7.0). Small, fixed badge catalog -- see
			 * BadgeCatalog::codes(). The unique key is what makes awarding
			 * idempotent: a repeat award attempt for a badge the user already
			 * has is a no-op (INSERT IGNORE), not a duplicate row.
			 */
			'user_badges' => "CREATE TABLE {$prefix}user_badges (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				badge_code varchar(50) NOT NULL,
				earned_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY user_badge (user_id,badge_code),
				KEY user_id (user_id)
			) {$collate};",

			/*
			 * Phase A4 (1.8.0). One row per finished English Level Test
			 * attempt -- LevelResultRepository is the only writer. `user_id`
			 * is NULL for an anonymous taker until claim() attaches the row
			 * to an account via `claim_token` (32 hex chars, NULLed once
			 * claimed). Skill levels are JSON rather than one column per
			 * skill so a later skill (listening, writing) needs no schema
			 * change; `overall_level` stays its own column since it is the
			 * one value anything would ever filter or sort on.
			 */
			'level_results' => "CREATE TABLE {$prefix}level_results (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NULL,
				claim_token char(32) NULL,
				overall_level varchar(8) NOT NULL,
				skill_levels_json text NOT NULL,
				questions_answered smallint(5) unsigned NOT NULL DEFAULT 0,
				correct_answers smallint(5) unsigned NOT NULL DEFAULT 0,
				details_json longtext NULL,
				test_version varchar(16) NOT NULL DEFAULT '',
				taken_at datetime NOT NULL,
				claimed_at datetime NULL,
				PRIMARY KEY  (id),
				KEY user_id_taken_at (user_id,taken_at),
				UNIQUE KEY claim_token (claim_token)
			) {$collate};",

			/*
			 * Mistake notebook (1.9.0). One row per user per question they have
			 * got wrong in a practice tool -- MistakeRepository is the only
			 * writer. A repeat miss updates the same row (times_missed + 1),
			 * so the table grows with distinct questions missed, not with
			 * attempts. `resolved_at` NULL = still open; it is set when the
			 * learner later answers that same question correctly, or marks it
			 * learned. The text columns are a snapshot of what the learner saw,
			 * so the notebook still makes sense if a bank item is later edited.
			 */
			'mistakes' => "CREATE TABLE {$prefix}mistakes (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				tool varchar(40) NOT NULL,
				skill varchar(20) NOT NULL,
				item_key varchar(120) NOT NULL,
				level varchar(8) NOT NULL DEFAULT '',
				prompt text NOT NULL,
				context text NULL,
				given_answer varchar(255) NOT NULL DEFAULT '',
				correct_answer varchar(255) NOT NULL DEFAULT '',
				explanation text NULL,
				times_missed int(11) unsigned NOT NULL DEFAULT 1,
				first_missed_at datetime NOT NULL,
				last_missed_at datetime NOT NULL,
				resolved_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY user_tool_item (user_id,tool,item_key),
				KEY user_open (user_id,resolved_at,last_missed_at)
			) {$collate};",

			/*
			 * Course-completion certificates (1.12.0). One per learner per
			 * course (UNIQUE user_course). `code` is the random verification
			 * code in the public certificate link. learner_name/course_title
			 * are snapshots from the moment of issue.
			 */
			'certificates' => "CREATE TABLE {$prefix}certificates (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				course_id bigint(20) unsigned NOT NULL,
				level varchar(8) NOT NULL DEFAULT '',
				learner_name varchar(191) NOT NULL,
				course_title varchar(255) NOT NULL,
				code char(12) NOT NULL,
				issued_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY user_course (user_id,course_id),
				UNIQUE KEY code (code)
			) {$collate};",
		);
	}

	/**
	 * The shared dictionary tables Core now owns.
	 *
	 * @return array<string,string>
	 */
	public static function shared_definitions(): array {
		global $wpdb;
		$prefix  = self::shared_prefix();
		$charset = $wpdb->get_charset_collate();

		return array(
			'words' => "CREATE TABLE {$prefix}words (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				word varchar(64) NOT NULL,
				normalized varchar(64) NOT NULL,
				reversed_word varchar(64) NOT NULL,
				letter_signature varchar(64) NOT NULL,
				letter_mask bigint(20) unsigned NOT NULL DEFAULT 0,
				definition text NULL,
				part_of_speech varchar(32) NOT NULL DEFAULT '',
				frequency int(10) unsigned NOT NULL DEFAULT 0,
				length tinyint(3) unsigned NOT NULL DEFAULT 0,
				scrabble_score smallint(5) unsigned NOT NULL DEFAULT 0,
				popularity int(10) unsigned NOT NULL DEFAULT 0,
				phonetics varchar(255) NOT NULL DEFAULT '',
				synonyms longtext NULL,
				antonyms longtext NULL,
				example_sentence text NULL,
				language varchar(10) NOT NULL DEFAULT 'en',
				is_wordle_allowed tinyint(1) unsigned NOT NULL DEFAULT 0,
				is_wordle_solution tinyint(1) unsigned NOT NULL DEFAULT 0,
				created_by_pack varchar(64) NOT NULL DEFAULT '',
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY normalized_language (normalized,language),
				KEY normalized (normalized),
				KEY reversed_word (reversed_word),
				KEY letter_signature (letter_signature),
				KEY length_score (length,scrabble_score),
				KEY frequency (frequency),
				KEY language (language),
				KEY wordle_allowed (is_wordle_allowed,length),
				KEY wordle_solution (is_wordle_solution,length),
				KEY created_by_pack (created_by_pack)
			) {$charset};",
			'word_grams' => "CREATE TABLE {$prefix}word_grams (
				gram char(3) NOT NULL,
				word_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY  (gram,word_id),
				KEY word_id (word_id)
			) {$charset};",
			'word_senses' => "CREATE TABLE {$prefix}word_senses (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				word_id bigint(20) unsigned NOT NULL,
				source_pack varchar(64) NOT NULL,
				source_id varchar(64) NOT NULL,
				sense_order smallint(5) unsigned NOT NULL DEFAULT 0,
				part_of_speech varchar(32) NOT NULL DEFAULT '',
				definition text NULL,
				examples longtext NULL,
				synonyms longtext NULL,
				antonyms longtext NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY source_word_sense (source_pack,source_id,word_id),
				KEY word_order (word_id,sense_order),
				KEY source_pack (source_pack)
			) {$charset};",
			'dictionary_packs' => "CREATE TABLE {$prefix}dictionary_packs (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				pack_id varchar(64) NOT NULL,
				pack_version varchar(32) NOT NULL,
				name varchar(191) NOT NULL,
				license_name varchar(191) NOT NULL DEFAULT '',
				word_count int(10) unsigned NOT NULL DEFAULT 0,
				sense_count int(10) unsigned NOT NULL DEFAULT 0,
				status varchar(24) NOT NULL DEFAULT 'installed',
				manifest_json longtext NULL,
				installed_job_id char(36) NOT NULL DEFAULT '',
				installed_at datetime NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY pack_id (pack_id),
				KEY status (status),
				KEY installed_at (installed_at)
			) {$charset};",
			'import_jobs' => "CREATE TABLE {$prefix}import_jobs (
				job_id char(36) NOT NULL,
				pack_id varchar(64) NOT NULL,
				pack_version varchar(32) NOT NULL DEFAULT '',
				status varchar(24) NOT NULL DEFAULT 'pending',
				staging_path text NOT NULL,
				manifest_json longtext NULL,
				chunk_index int(10) unsigned NOT NULL DEFAULT 0,
				byte_offset bigint(20) unsigned NOT NULL DEFAULT 0,
				rollback_cursor bigint(20) unsigned NOT NULL DEFAULT 0,
				total_words int(10) unsigned NOT NULL DEFAULT 0,
				total_senses int(10) unsigned NOT NULL DEFAULT 0,
				imported_words int(10) unsigned NOT NULL DEFAULT 0,
				imported_senses int(10) unsigned NOT NULL DEFAULT 0,
				skipped_rows int(10) unsigned NOT NULL DEFAULT 0,
				error_message text NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				completed_at datetime NULL,
				PRIMARY KEY  (job_id),
				KEY pack_status (pack_id,status),
				KEY created_at (created_at)
			) {$charset};",
			'import_job_words' => "CREATE TABLE {$prefix}import_job_words (
				job_id char(36) NOT NULL,
				word_id bigint(20) unsigned NOT NULL,
				was_created tinyint(1) unsigned NOT NULL DEFAULT 0,
				previous_data longtext NULL,
				PRIMARY KEY  (job_id,word_id),
				KEY word_id (word_id),
				KEY created_lookup (job_id,was_created)
			) {$charset};",
		);
	}

	/** @return list<string> */
	public static function shared_table_names(): array {
		return array_map(
			static fn ( string $name ): string => self::shared_table( $name ),
			self::SHARED_TABLES
		);
	}
}
