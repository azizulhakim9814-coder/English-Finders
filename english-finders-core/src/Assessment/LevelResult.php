<?php
/**
 * One completed English Level Test attempt (Phase A4).
 *
 * Plain value object, mirroring UserStats's role for Activity -- the shape
 * consumers (English Finders Account's My Level section) read, never a row
 * array they have to know the column names of.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Assessment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LevelResult {
	/**
	 * @param array<string,string> $skill_levels skill => level code, in LevelScale::SKILLS order.
	 */
	private function __construct(
		public readonly int $id,
		public readonly ?int $user_id,
		public readonly string $overall_level,
		public readonly array $skill_levels,
		public readonly int $questions_answered,
		public readonly int $correct_answers,
		public readonly string $test_version,
		public readonly string $taken_at
	) {}

	/** @param array<string,mixed> $row */
	public static function from_row( array $row ): self {
		$decoded = json_decode( (string) ( $row['skill_levels_json'] ?? '' ), true );
		$skills  = array();

		/*
		 * Normalise to the known skills in their fixed order, dropping
		 * anything unrecognised -- a hand-edited or future-version row can't
		 * put an unknown skill or level code in front of a template.
		 */
		foreach ( LevelScale::SKILLS as $skill ) {
			$level = is_array( $decoded ) ? (string) ( $decoded[ $skill ] ?? '' ) : '';
			if ( LevelScale::is_valid( $level ) ) {
				$skills[ $skill ] = $level;
			}
		}

		$overall = (string) ( $row['overall_level'] ?? '' );

		return new self(
			(int) ( $row['id'] ?? 0 ),
			isset( $row['user_id'] ) && null !== $row['user_id'] ? (int) $row['user_id'] : null,
			LevelScale::is_valid( $overall ) ? $overall : 'PRE-A1',
			$skills,
			(int) ( $row['questions_answered'] ?? 0 ),
			(int) ( $row['correct_answers'] ?? 0 ),
			(string) ( $row['test_version'] ?? '' ),
			(string) ( $row['taken_at'] ?? '' )
		);
	}
}
