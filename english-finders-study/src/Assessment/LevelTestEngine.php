<?php
/**
 * Scoring rules for the English Level Test (Phase A4).
 *
 * Pure logic over a plain array state -- no WordPress, no DB, no question
 * content -- so the rules that decide a learner's level can be tested
 * exhaustively on their own. LevelTest owns everything else (questions,
 * sessions, storage).
 *
 * How a level is decided, per skill (grammar, vocabulary, reading):
 *
 * - The test climbs A1 -> C2. At each level a skill is asked questions
 *   until it has either 2 right (level passed) or 2 wrong (level failed) --
 *   best of three. One slip doesn't fail a level; one lucky guess doesn't
 *   pass it (the chance of guessing 2 of 3 four-option questions is ~16%).
 * - A skill stops at its first failed level. Its result is the highest
 *   level it passed, or PRE-A1 if it failed A1.
 * - The overall level is the median of the three skill levels (Core's
 *   LevelScale::overall()).
 *
 * Skills are interleaved within a level rather than tested one after
 * another, so a learner never sits through a long block of one question
 * type, and a skill that has already stopped simply drops out of the
 * rotation -- the test gets shorter for a learner as it finds their ceiling.
 *
 * Length: 6 questions minimum (every skill fails A1 outright), 36 for a
 * learner who answers everything right, 54 at the theoretical maximum.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Assessment;

use EnglishFindersCore\Assessment\LevelScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LevelTestEngine {
	/** Right answers needed at a level to pass it. */
	public const PASS_RIGHT = 2;

	/** Wrong answers at a level that fail it (and stop that skill). */
	public const FAIL_WRONG = 2;

	/** The levels the test climbs through, lowest first. */
	public const LEVELS = array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' );

	/** Skills measured, in the fixed order used for rotation and results. */
	public const SKILLS = array( 'grammar', 'vocabulary', 'reading' );

	/**
	 * A fresh test.
	 *
	 * @return array<string,mixed>
	 */
	public static function start(): array {
		$skills = array();
		$log    = array();
		foreach ( self::SKILLS as $skill ) {
			$skills[ $skill ] = array(
				'active' => true,
				'passed' => '',
				'right'  => 0,
				'wrong'  => 0,
			);
			$log[ $skill ]    = array();
		}

		return array(
			'level_index' => 0,
			'skills'      => $skills,
			'turn'        => 0,
			'answered'    => 0,
			'correct'     => 0,
			'log'         => $log,
		);
	}

	/**
	 * The next question to ask, or null when the test is over.
	 *
	 * @param array<string,mixed> $state
	 * @return array{skill:string,level:string}|null
	 */
	public static function next( array $state ): ?array {
		$index = (int) $state['level_index'];
		if ( $index >= count( self::LEVELS ) ) {
			return null;
		}

		$candidates = self::undecided( $state );
		if ( array() === $candidates ) {
			return null;
		}

		return array(
			'skill' => $candidates[ (int) $state['turn'] % count( $candidates ) ],
			'level' => self::LEVELS[ $index ],
		);
	}

	/**
	 * Apply one answer to the skill next() asked about.
	 *
	 * A skill that isn't currently being asked is ignored, not applied --
	 * LevelTest already rejects out-of-sequence answers, and this keeps the
	 * engine's own invariants from depending on that.
	 *
	 * @param array<string,mixed> $state
	 * @return array<string,mixed> The new state.
	 */
	public static function answer( array $state, string $skill, bool $correct ): array {
		$step = self::next( $state );
		if ( null === $step || $step['skill'] !== $skill ) {
			return $state;
		}

		$level = self::LEVELS[ (int) $state['level_index'] ];
		$entry = &$state['skills'][ $skill ];

		if ( $correct ) {
			++$entry['right'];
			++$state['correct'];
		} else {
			++$entry['wrong'];
		}
		++$state['answered'];
		++$state['turn'];

		$state['log'][ $skill ][ $level ] = array(
			'right' => $entry['right'],
			'wrong' => $entry['wrong'],
		);

		if ( $entry['right'] >= self::PASS_RIGHT ) {
			$entry['passed'] = $level;
		} elseif ( $entry['wrong'] >= self::FAIL_WRONG ) {
			$entry['active'] = false;
		}
		unset( $entry );

		// Every skill still climbing has a verdict at this level: move up.
		if ( array() === self::undecided( $state ) ) {
			++$state['level_index'];
			foreach ( $state['skills'] as &$other ) {
				$other['right'] = 0;
				$other['wrong'] = 0;
			}
			unset( $other );
		}

		return $state;
	}

	public static function is_finished( array $state ): bool {
		return null === self::next( $state );
	}

	/**
	 * Per-skill and overall levels. Only meaningful once is_finished().
	 *
	 * @param array<string,mixed> $state
	 * @return array{overall:string,skills:array<string,string>,answered:int,correct:int,details:array<string,mixed>}
	 */
	public static function result( array $state ): array {
		$skills = array();
		foreach ( self::SKILLS as $skill ) {
			$passed           = (string) ( $state['skills'][ $skill ]['passed'] ?? '' );
			$skills[ $skill ] = '' !== $passed ? $passed : 'PRE-A1';
		}

		return array(
			'overall'  => LevelScale::overall( $skills ),
			'skills'   => $skills,
			'answered' => (int) $state['answered'],
			'correct'  => (int) $state['correct'],
			'details'  => (array) $state['log'],
		);
	}

	/**
	 * How far through the A1 -> C2 climb the test is, 0-100, for a progress
	 * bar. Measured by level rather than question count, since the number
	 * of questions left isn't knowable in advance.
	 *
	 * @param array<string,mixed> $state
	 */
	public static function progress_percent( array $state ): int {
		if ( self::is_finished( $state ) ) {
			return 100;
		}

		return (int) floor( (int) $state['level_index'] / count( self::LEVELS ) * 100 );
	}

	/**
	 * Skills still climbing that don't yet have a verdict at the current level.
	 *
	 * @param array<string,mixed> $state
	 * @return list<string>
	 */
	private static function undecided( array $state ): array {
		$out = array();
		foreach ( self::SKILLS as $skill ) {
			$entry = $state['skills'][ $skill ] ?? null;
			if ( ! is_array( $entry ) || ! $entry['active'] ) {
				continue;
			}
			if ( $entry['right'] < self::PASS_RIGHT && $entry['wrong'] < self::FAIL_WRONG ) {
				$out[] = $skill;
			}
		}

		return $out;
	}
}
