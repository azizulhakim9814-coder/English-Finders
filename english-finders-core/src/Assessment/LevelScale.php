<?php
/**
 * The CEFR scale as used by the English Level Test (Phase A4).
 *
 * Pure logic, no DB -- the same role BadgeCatalog plays for Activity. Lives
 * in Core rather than in English Finders Study (which runs the test) or
 * English Finders Account (which displays the result) because both need the
 * same ordering, labels, and "overall from per-skill" rule, and this
 * project's standing rule is that shared business rules live in Core rather
 * than being duplicated per consumer.
 *
 * `PRE-A1` exists because a real learner can fail the A1 items too; forcing
 * them onto A1 would overstate their level, which is exactly the kind of
 * inflated result this test is meant not to give.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Assessment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LevelScale {
	/** Every level a result can hold, lowest first. */
	public const LEVELS = array( 'PRE-A1', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' );

	/** Only the six real CEFR levels -- the ones test items are written at. */
	public const TESTABLE_LEVELS = array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' );

	/** Skills the level test measures, in the order results are shown. */
	public const SKILLS = array( 'grammar', 'vocabulary', 'reading' );

	public static function is_valid( string $level ): bool {
		return in_array( $level, self::LEVELS, true );
	}

	/** Position on the scale; -1 for an unknown code. */
	public static function index( string $level ): int {
		$index = array_search( $level, self::LEVELS, true );

		return false === $index ? -1 : (int) $index;
	}

	/**
	 * Overall level from per-skill levels: the median, rounding down when
	 * there is an even count.
	 *
	 * Median rather than mean or minimum: one unusually strong or weak skill
	 * shouldn't drag the headline level with it, but the headline also
	 * shouldn't claim a level only one skill reached. Rounding down on an
	 * even count keeps it conservative -- the same reason PRE-A1 exists.
	 *
	 * @param array<string,string> $skill_levels skill => level code.
	 */
	public static function overall( array $skill_levels ): string {
		$indexes = array();
		foreach ( $skill_levels as $level ) {
			$index = self::index( (string) $level );
			if ( $index >= 0 ) {
				$indexes[] = $index;
			}
		}

		if ( array() === $indexes ) {
			return 'PRE-A1';
		}

		sort( $indexes );

		return self::LEVELS[ $indexes[ intdiv( count( $indexes ) - 1, 2 ) ] ];
	}

	public static function label( string $level ): string {
		$labels = array(
			'PRE-A1' => __( 'Pre-A1 Starter', 'english-finders-core' ),
			'A1'     => __( 'A1 Beginner', 'english-finders-core' ),
			'A2'     => __( 'A2 Elementary', 'english-finders-core' ),
			'B1'     => __( 'B1 Intermediate', 'english-finders-core' ),
			'B2'     => __( 'B2 Upper Intermediate', 'english-finders-core' ),
			'C1'     => __( 'C1 Advanced', 'english-finders-core' ),
			'C2'     => __( 'C2 Proficiency', 'english-finders-core' ),
		);

		return $labels[ $level ] ?? $level;
	}

	/** Short display code: "Pre-A1" rather than the stored "PRE-A1". */
	public static function short_label( string $level ): string {
		return 'PRE-A1' === $level ? __( 'Pre-A1', 'english-finders-core' ) : $level;
	}

	/**
	 * One-sentence summary of what a learner at this level can do,
	 * paraphrasing the Council of Europe's CEFR global scale.
	 */
	public static function description( string $level ): string {
		$descriptions = array(
			'PRE-A1' => __( 'You are just starting out. Learning everyday words and simple phrases is the best next step.', 'english-finders-core' ),
			'A1'     => __( 'You can understand and use familiar everyday expressions and very basic phrases.', 'english-finders-core' ),
			'A2'     => __( 'You can understand common sentences about familiar topics and handle simple, routine exchanges.', 'english-finders-core' ),
			'B1'     => __( 'You can understand the main points of clear standard English on familiar matters and deal with most everyday situations.', 'english-finders-core' ),
			'B2'     => __( 'You can understand the main ideas of complex text and interact with a good degree of fluency.', 'english-finders-core' ),
			'C1'     => __( 'You can understand demanding, longer texts and use English flexibly for social, academic and professional purposes.', 'english-finders-core' ),
			'C2'     => __( 'You can understand virtually everything you read or hear and express yourself precisely, even in complex situations.', 'english-finders-core' ),
		);

		return $descriptions[ $level ] ?? '';
	}

	public static function skill_label( string $skill ): string {
		$labels = array(
			'grammar'    => __( 'Grammar', 'english-finders-core' ),
			'vocabulary' => __( 'Vocabulary', 'english-finders-core' ),
			'reading'    => __( 'Reading', 'english-finders-core' ),
		);

		return $labels[ $skill ] ?? ucfirst( $skill );
	}
}
