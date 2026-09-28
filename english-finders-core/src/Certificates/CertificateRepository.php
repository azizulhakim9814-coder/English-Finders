<?php
/**
 * Course-completion certificates (1.12.0).
 *
 * Registered as Core service `certificates`. Issued automatically when
 * Tutor LMS reports a course completed (TutorIntegration), at most once per
 * learner per course. Each certificate gets a 12-character verification
 * code: anyone holding the link can confirm it's genuine, and nobody can
 * enumerate certificates (the code is random, not sequential).
 *
 * English Finders Account shows the learner's certificates and renders the
 * printable/verification page; it only ever reads through this service.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Certificates;

use EnglishFindersCore\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CertificateRepository {
	/** No 0/O/1/I/L -- the code is meant to be read and typed by people. */
	private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

	public const CODE_LENGTH = 12;

	/**
	 * Issue a certificate, or return the one already issued for this
	 * learner and course (the UNIQUE (user_id, course_id) key makes this
	 * safe even if Tutor fires its completion hook twice).
	 */
	public function issue( int $user_id, int $course_id, string $level, string $learner_name, string $course_title ): ?Certificate {
		global $wpdb;

		if ( $user_id <= 0 || $course_id <= 0 || '' === trim( $course_title ) ) {
			return null;
		}

		$existing = $this->for_user_course( $user_id, $course_id );
		if ( null !== $existing ) {
			return $existing;
		}

		$level = 1 === preg_match( '/^(A1|A2|B1|B2|C1|C2)$/', $level ) ? $level : '';
		$table = Schema::table( 'certificates' );

		// A collision in 31^12 codes is vanishingly unlikely, but the UNIQUE key would reject it -- retry rather than fail.
		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$ok = $wpdb->insert(
				$table,
				array(
					'user_id'      => $user_id,
					'course_id'    => $course_id,
					'level'        => $level,
					'learner_name' => mb_substr( trim( $learner_name ), 0, 191 ),
					'course_title' => mb_substr( trim( $course_title ), 0, 255 ),
					'code'         => self::new_code(),
					'issued_at'    => current_time( 'mysql', true ),
				),
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false !== $ok ) {
				return $this->for_user_course( $user_id, $course_id );
			}

			// Lost a race with a concurrent issue for the same course: that one is the certificate.
			$existing = $this->for_user_course( $user_id, $course_id );
			if ( null !== $existing ) {
				return $existing;
			}
		}

		return null;
	}

	/**
	 * Issue for a Tutor course, looking up the snapshot values: the
	 * learner's full name if they've set one (else their display name, never
	 * an email address), the course title, and the CEFR level from the title.
	 */
	public function issue_for_course( int $user_id, int $course_id ): ?Certificate {
		$user  = get_userdata( $user_id );
		// The raw title, not get_the_title(): display filters can turn quotes and dashes into HTML entities, which don't belong in a stored snapshot.
		$title = (string) get_post_field( 'post_title', $course_id );
		if ( ! $user || '' === $title ) {
			return null;
		}

		return $this->issue( $user_id, $course_id, self::level_from_title( $title ), self::learner_name( $user ), $title );
	}

	public function find_by_code( string $code ): ?Certificate {
		global $wpdb;

		$code = strtoupper( trim( $code ) );
		if ( 1 !== preg_match( '/^[' . self::CODE_ALPHABET . ']{' . self::CODE_LENGTH . '}$/', $code ) ) {
			return null;
		}

		$table = Schema::table( 'certificates' );
		$row   = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
			$wpdb->prepare( "SELECT * FROM {$table} WHERE code = %s LIMIT 1", $code ),
			ARRAY_A
		);

		return is_array( $row ) ? Certificate::from_row( $row ) : null;
	}

	/** @return list<Certificate> newest first */
	public function for_user( int $user_id ): array {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return array();
		}

		$table = Schema::table( 'certificates' );
		$rows  = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
			$wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d ORDER BY issued_at DESC, id DESC", $user_id ),
			ARRAY_A
		);

		return is_array( $rows ) ? array_map( static fn ( array $row ): Certificate => Certificate::from_row( $row ), $rows ) : array();
	}

	public function for_user_course( int $user_id, int $course_id ): ?Certificate {
		global $wpdb;

		$table = Schema::table( 'certificates' );
		$row   = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
			$wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d AND course_id = %d LIMIT 1", $user_id, $course_id ),
			ARRAY_A
		);

		return is_array( $row ) ? Certificate::from_row( $row ) : null;
	}

	/** For WordPress Privacy Tools erasure. Deleting also retires the verification link. @return int Rows deleted. */
	public function delete_for_user( int $user_id ): int {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return 0;
		}

		$deleted = $wpdb->delete( Schema::table( 'certificates' ), array( 'user_id' => $user_id ), array( '%d' ) );

		return is_int( $deleted ) ? $deleted : 0;
	}

	/** "English Finders B1 — Intermediate" -> "B1"; '' when the title names no CEFR level. */
	public static function level_from_title( string $title ): string {
		return 1 === preg_match( '/\b(A1|A2|B1|B2|C1|C2)\b/', $title, $m ) ? $m[1] : '';
	}

	/** First + last name if set, else the display name; never an email address. */
	public static function learner_name( object $user ): string {
		$full = trim( (string) get_user_meta( (int) $user->ID, 'first_name', true ) . ' ' . (string) get_user_meta( (int) $user->ID, 'last_name', true ) );
		$name = '' !== $full ? $full : trim( (string) ( $user->display_name ?? '' ) );
		if ( str_contains( $name, '@' ) ) {
			$name = trim( (string) strstr( $name, '@', true ) );
		}

		return '' !== $name ? $name : __( 'English Finders learner', 'english-finders-core' );
	}

	private static function new_code(): string {
		$code = '';
		$max  = strlen( self::CODE_ALPHABET ) - 1;
		for ( $i = 0; $i < self::CODE_LENGTH; $i++ ) {
			$code .= self::CODE_ALPHABET[ random_int( 0, $max ) ];
		}

		return $code;
	}
}
