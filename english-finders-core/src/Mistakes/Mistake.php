<?php
/**
 * One mistake-notebook entry: a question a learner got wrong in a practice
 * tool, with a snapshot of what they saw and answered.
 *
 * Plain value object, same role as LevelResult / UserStats.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Mistakes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Mistake {
	private function __construct(
		public readonly int $id,
		public readonly int $user_id,
		public readonly string $tool,
		public readonly string $skill,
		public readonly string $item_key,
		public readonly string $level,
		public readonly string $prompt,
		public readonly string $context,
		public readonly string $given_answer,
		public readonly string $correct_answer,
		public readonly string $explanation,
		public readonly int $times_missed,
		public readonly string $first_missed_at,
		public readonly string $last_missed_at,
		public readonly ?string $resolved_at
	) {}

	/** @param array<string,mixed> $row */
	public static function from_row( array $row ): self {
		return new self(
			(int) ( $row['id'] ?? 0 ),
			(int) ( $row['user_id'] ?? 0 ),
			(string) ( $row['tool'] ?? '' ),
			(string) ( $row['skill'] ?? '' ),
			(string) ( $row['item_key'] ?? '' ),
			(string) ( $row['level'] ?? '' ),
			(string) ( $row['prompt'] ?? '' ),
			(string) ( $row['context'] ?? '' ),
			(string) ( $row['given_answer'] ?? '' ),
			(string) ( $row['correct_answer'] ?? '' ),
			(string) ( $row['explanation'] ?? '' ),
			max( 1, (int) ( $row['times_missed'] ?? 1 ) ),
			(string) ( $row['first_missed_at'] ?? '' ),
			(string) ( $row['last_missed_at'] ?? '' ),
			! empty( $row['resolved_at'] ) ? (string) $row['resolved_at'] : null
		);
	}

	public function is_open(): bool {
		return null === $this->resolved_at;
	}
}
