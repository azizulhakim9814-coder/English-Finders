<?php
/**
 * One course-completion certificate (1.12.0). Plain value object.
 *
 * The learner name and course title are snapshots taken when the
 * certificate was issued: a certificate is a record of what was completed,
 * so it doesn't change if the learner later renames their account or the
 * course is retitled.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Certificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Certificate {
	private function __construct(
		public readonly int $id,
		public readonly int $user_id,
		public readonly int $course_id,
		public readonly string $level,
		public readonly string $learner_name,
		public readonly string $course_title,
		public readonly string $code,
		public readonly string $issued_at
	) {}

	/** @param array<string,mixed> $row */
	public static function from_row( array $row ): self {
		return new self(
			(int) ( $row['id'] ?? 0 ),
			(int) ( $row['user_id'] ?? 0 ),
			(int) ( $row['course_id'] ?? 0 ),
			(string) ( $row['level'] ?? '' ),
			(string) ( $row['learner_name'] ?? '' ),
			(string) ( $row['course_title'] ?? '' ),
			(string) ( $row['code'] ?? '' ),
			(string) ( $row['issued_at'] ?? '' )
		);
	}
}
