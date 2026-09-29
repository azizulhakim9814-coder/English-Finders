<?php
/**
 * The "Home" strip at the top of My Account (0.12.0, design section 1):
 * "Continue where you left off" plus one suggested next step.
 *
 * Built only from data the page already loads (My Level, Progress,
 * Mistakes, and the course list My Level carries), so it adds no queries
 * of its own beyond CourseProgress when My Level is unavailable. The
 * suggestion is a fixed priority list, first match wins -- predictable and
 * explainable, not a recommender:
 *
 *  1. No level test yet            -> take the level test
 *  2. No course in progress        -> start the course for their level
 *  3. Open mistakes in notebook    -> review them
 *  4. Today's goal not reached     -> practise to reach it
 *  5. Otherwise                    -> play a word game
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Pages;

use EnglishFindersAccount\Learning\CourseProgress;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HomeController {
	/**
	 * @param array<string,mixed>|null $level    LevelController::data_for_user() (carries 'courses').
	 * @param array<string,mixed>|null $progress ProgressController::data_for_user().
	 * @param array<string,mixed>|null $mistakes MistakesController::data_for_user().
	 * @return array{continue: array<string,mixed>|null, next_step: array{key: string, title: string, text: string, url: string, cta: string}|null}|null
	 */
	public function data_for_user( int $user_id, ?array $level, ?array $progress, ?array $mistakes ): ?array {
		$courses  = null !== $level ? (array) ( $level['courses'] ?? array() ) : ( new CourseProgress() )->for_user( $user_id );
		$continue = CourseProgress::continue_course( $courses );
		$next     = self::next_step( $level, $progress, $mistakes, $courses, null !== $continue );

		if ( null === $continue && null === $next ) {
			return null;
		}

		return array(
			'continue'  => $continue,
			'next_step' => $next,
		);
	}

	/**
	 * @param array<string,mixed>|null  $level
	 * @param array<string,mixed>|null  $progress
	 * @param array<string,mixed>|null  $mistakes
	 * @param list<array<string,mixed>> $courses
	 * @return array{key: string, title: string, text: string, url: string, cta: string}|null
	 */
	public static function next_step( ?array $level, ?array $progress, ?array $mistakes, array $courses, bool $has_continue ): ?array {
		// 1. Level test.
		if ( null !== $level && null === $level['latest'] && '' !== (string) $level['test_url'] ) {
			return array(
				'key'   => 'level_test',
				'title' => __( 'Find your English level', 'english-finders-account' ),
				'text'  => __( 'Take the free level test to get your CEFR level and see which course to start with.', 'english-finders-account' ),
				'url'   => (string) $level['test_url'],
				'cta'   => __( 'Take the level test', 'english-finders-account' ),
			);
		}

		// 2. A course to start.
		if ( ! $has_continue ) {
			$course = self::course_to_start( $level, $courses );
			if ( null !== $course ) {
				$tested = null !== $level && is_array( $level['latest'] ?? null );

				return array(
					'key'   => 'start_course',
					/* translators: %s: CEFR level, e.g. B1 */
					'title' => sprintf( __( 'Start the %s course', 'english-finders-account' ), $course['level'] ),
					'text'  => $tested
						/* translators: 1: the learner's tested level, 2: course title */
						? sprintf( __( 'Your level test result is %1$s, so %2$s is the place to start. Your progress is saved as you go.', 'english-finders-account' ), (string) $level['latest']['overall_short'], $course['title'] )
						/* translators: %s: course title */
						: sprintf( __( 'Open the first lesson of %s. Your progress is saved as you go, and finishing the course earns a certificate.', 'english-finders-account' ), $course['title'] ),
					'url'   => (string) $course['url'],
					'cta'   => __( 'Go to the course', 'english-finders-account' ),
				);
			}
		}

		// 3. Mistakes.
		$open = (int) ( $mistakes['counts']['open'] ?? 0 );
		if ( $open > 0 ) {
			return array(
				'key'   => 'mistakes',
				'title' => __( 'Review your mistakes', 'english-finders-account' ),
				'text'  => sprintf(
					/* translators: %d: number of open mistakes */
					_n( 'You have %d mistake to go over. Get it right in practice and it clears from your notebook.', 'You have %d mistakes to go over. Get them right in practice and they clear from your notebook.', $open, 'english-finders-account' ),
					$open
				),
				'url'   => '#efa-section-mistakes',
				'cta'   => __( 'See my mistakes', 'english-finders-account' ),
			);
		}

		// 4. Today's goal.
		$goal = $progress['goal'] ?? null;
		if ( is_array( $goal ) && empty( $goal['met'] ) ) {
			$left = max( 1, (int) $goal['xp'] - (int) $goal['today_xp'] );

			return array(
				'key'   => 'goal',
				'title' => __( "Reach today's goal", 'english-finders-account' ),
				'text'  => sprintf(
					/* translators: %d: XP still needed today */
					_n( '%d more XP reaches your daily goal. A quick practice round will do it.', '%d more XP reaches your daily goal. A quick practice round will do it.', $left, 'english-finders-account' ),
					$left
				),
				'url'   => (string) ( $mistakes['practice_url'] ?? home_url( '/practice/' ) ),
				'cta'   => __( 'Practise now', 'english-finders-account' ),
			);
		}

		// 5. Something fun.
		return array(
			'key'   => 'games',
			'title' => __( 'Play a word game', 'english-finders-account' ),
			'text'  => __( "You're all caught up for today. Keep your streak going with a quick game.", 'english-finders-account' ),
			'url'   => Urls::games(),
			'cta'   => __( 'Browse games', 'english-finders-account' ),
		);
	}

	/**
	 * The course matching the learner's tested level if it isn't finished,
	 * else the lowest unfinished course.
	 *
	 * @param array<string,mixed>|null  $level
	 * @param list<array<string,mixed>> $courses
	 * @return array<string,mixed>|null
	 */
	private static function course_to_start( ?array $level, array $courses ): ?array {
		$tested = (string) ( $level['latest']['overall'] ?? '' );
		if ( '' !== $tested ) {
			foreach ( $courses as $course ) {
				if ( ! $course['completed'] && str_starts_with( $tested, (string) $course['level'] ) ) {
					return $course;
				}
			}
		}

		foreach ( $courses as $course ) {
			if ( ! $course['completed'] ) {
				return $course;
			}
		}

		return null;
	}
}
