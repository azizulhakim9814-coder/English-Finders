<?php
/**
 * A learner's progress through the six CEFR courses in Tutor LMS (0.12.0).
 *
 * Moved out of LevelController (0.7.0), which only needed an enrolled flag
 * and a percentage, and extended for the "Continue where you left off"
 * card: items done out of total, the next lesson/quiz to open, whether the
 * course is completed (and its certificate), and when the learner was last
 * active in it.
 *
 * Every number comes from Tutor's own records, counted the way Tutor
 * counts them, so what My Account shows always matches Tutor's own
 * progress bar:
 *  - percent/done/total: Tutor's get_course_completed_percent() (completed
 *    lessons + quizzes with a finished attempt, out of all course items);
 *  - the next item: the same walk as Tutor's get_course_first_lesson()
 *    (topics then items in menu order; the first lesson not completed and
 *    quiz not attempted), done here for any user id rather than only the
 *    current user;
 *  - completed: Tutor's is_completed_course().
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Learning;

use EnglishFindersAccount\Certificates\CertificatePage;
use EnglishFindersAccount\Certificates\CertificatesController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CourseProgress {
	/** CEFR levels with a course, lowest first. Matched in course titles. */
	public const LEVELS = array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' );

	/** Tutor's attempt status for a quiz that was opened but never submitted. */
	private const ATTEMPT_STARTED = 'attempt_started';

	/**
	 * @return list<array{id: int, level: string, title: string, url: string, enrolled: bool, percent: int, done: int, total: int, completed: bool, next: array{id: int, title: string, url: string, type: string}|null, last_active: int, certificate_url: string}>
	 */
	public function for_user( int $user_id ): array {
		if ( ! function_exists( 'tutor_utils' ) || ! post_type_exists( 'courses' ) ) {
			return array();
		}

		$utils    = tutor_utils();
		$enrolled = $this->enrolled_course_ids( $user_id );

		$out = array();
		foreach ( $this->cefr_courses() as $level => $course ) {
			$course_id   = $course['id'];
			$is_enrolled = in_array( $course_id, $enrolled, true );
			$stats       = array(
				'percent' => 0,
				'done'    => 0,
				'total'   => 0,
			);
			$completed   = false;
			$next        = null;
			$last_active = 0;

			if ( $is_enrolled && $user_id > 0 ) {
				$stats     = $this->stats( $utils, $course_id, $user_id );
				$completed = is_object( $utils ) && method_exists( $utils, 'is_completed_course' ) && (bool) $utils->is_completed_course( $course_id, $user_id );
				$items     = $this->items( $course_id );
				$attempted = $this->attempted_quiz_ids( $course_id, $user_id );
				if ( ! $completed ) {
					$next = $this->next_item( $items, $attempted, $user_id );
				}
				$last_active = $this->last_active( $course_id, $user_id, $items );
			}

			$out[] = array(
				'id'              => $course_id,
				'level'           => $level,
				'title'           => $course['title'],
				'url'             => (string) get_permalink( $course_id ),
				'enrolled'        => $is_enrolled,
				'percent'         => $completed ? 100 : $stats['percent'],
				'done'            => $stats['done'],
				'total'           => $stats['total'],
				'completed'       => $completed,
				'next'            => $next,
				'last_active'     => $last_active,
				'certificate_url' => $completed ? $this->certificate_url( $user_id, $course_id ) : '',
			);
		}

		return $out;
	}

	/**
	 * The course to "continue": enrolled, not finished, with something left
	 * to open, and most recently active (ties: the lower level).
	 *
	 * @param list<array<string,mixed>> $courses Output of for_user().
	 * @return array<string,mixed>|null
	 */
	public static function continue_course( array $courses ): ?array {
		$best = null;
		foreach ( $courses as $course ) {
			if ( ! $course['enrolled'] || $course['completed'] || null === $course['next'] ) {
				continue;
			}
			if ( null === $best || $course['last_active'] > $best['last_active'] ) {
				$best = $course;
			}
		}

		return $best;
	}

	/** @return array<string, array{id: int, title: string}> Level => course, A1 first; the first published course whose title names each level. */
	private function cefr_courses(): array {
		$ids = get_posts(
			array(
				'post_type'      => 'courses',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$by_level = array();
		foreach ( (array) $ids as $id ) {
			$title = (string) get_the_title( (int) $id );
			foreach ( self::LEVELS as $level ) {
				if ( ! isset( $by_level[ $level ] ) && 1 === preg_match( '/\b' . $level . '\b/', $title ) ) {
					$by_level[ $level ] = array(
						'id'    => (int) $id,
						'title' => $title,
					);
					break;
				}
			}
		}

		$ordered = array();
		foreach ( self::LEVELS as $level ) {
			if ( isset( $by_level[ $level ] ) ) {
				$ordered[ $level ] = $by_level[ $level ];
			}
		}

		return $ordered;
	}

	/** @return array{percent: int, done: int, total: int} */
	private function stats( $utils, int $course_id, int $user_id ): array {
		if ( ! is_object( $utils ) || ! method_exists( $utils, 'get_course_completed_percent' ) ) {
			return array(
				'percent' => 0,
				'done'    => 0,
				'total'   => 0,
			);
		}

		$raw = $utils->get_course_completed_percent( $course_id, $user_id, true );
		if ( is_array( $raw ) ) {
			return array(
				'percent' => (int) max( 0, min( 100, (float) ( $raw['completed_percent'] ?? 0 ) ) ),
				'done'    => max( 0, (int) ( $raw['completed_count'] ?? 0 ) ),
				'total'   => max( 0, (int) ( $raw['total_count'] ?? 0 ) ),
			);
		}

		return array(
			'percent' => (int) max( 0, min( 100, (float) $raw ) ),
			'done'    => 0,
			'total'   => 0,
		);
	}

	/**
	 * Published course items in Tutor's own order (topic, then item).
	 *
	 * @return list<array{id: int, type: string, title: string}>
	 */
	private function items( int $course_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT items.ID, items.post_type, items.post_title FROM {$wpdb->posts} topic INNER JOIN {$wpdb->posts} items ON topic.ID = items.post_parent WHERE topic.post_parent = %d AND items.post_status = 'publish' ORDER BY topic.menu_order ASC, items.menu_order ASC, items.ID ASC",
				$course_id
			),
			ARRAY_A
		);

		$items = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$items[] = array(
				'id'    => (int) $row['ID'],
				'type'  => (string) $row['post_type'],
				'title' => (string) $row['post_title'],
			);
		}

		return $items;
	}

	/** @return list<int> Quizzes in this course with a submitted attempt (Tutor's own rule for counting a quiz as done). */
	private function attempted_quiz_ids( int $course_id, int $user_id ): array {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT quiz_id FROM {$wpdb->prefix}tutor_quiz_attempts WHERE course_id = %d AND user_id = %d AND attempt_status != %s",
				$course_id,
				$user_id,
				self::ATTEMPT_STARTED
			)
		);

		return array_map( 'intval', is_array( $ids ) ? $ids : array() );
	}

	/**
	 * @param list<array{id: int, type: string, title: string}> $items
	 * @param list<int>                                          $attempted
	 * @return array{id: int, title: string, url: string, type: string}|null
	 */
	private function next_item( array $items, array $attempted, int $user_id ): ?array {
		foreach ( $items as $item ) {
			if ( 'tutor_quiz' === $item['type'] ) {
				$done = in_array( $item['id'], $attempted, true );
			} elseif ( 'lesson' === $item['type'] ) {
				$done = '' !== (string) get_user_meta( $user_id, '_tutor_completed_lesson_id_' . $item['id'], true );
			} else {
				continue; // Assignments, meetings etc.: not used on this site; never block "continue" on them.
			}

			if ( ! $done ) {
				return array(
					'id'    => $item['id'],
					'title' => $item['title'],
					'url'   => (string) get_permalink( $item['id'] ),
					'type'  => 'tutor_quiz' === $item['type'] ? 'quiz' : 'lesson',
				);
			}
		}

		return null;
	}

	/**
	 * Unix time of the learner's latest activity in the course: enrolment,
	 * a completed lesson (Tutor stores the time as the meta value) or a quiz
	 * attempt (stored in site time).
	 *
	 * @param list<array{id: int, type: string, title: string}> $items
	 */
	private function last_active( int $course_id, int $user_id, array $items ): int {
		global $wpdb;

		$times = array();

		$enrolled_at = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(post_date_gmt) FROM {$wpdb->posts} WHERE post_type = 'tutor_enrolled' AND post_parent = %d AND post_author = %d",
				$course_id,
				$user_id
			)
		);
		if ( '' !== $enrolled_at ) {
			$times[] = (int) strtotime( $enrolled_at . ' UTC' );
		}

		foreach ( $items as $item ) {
			if ( 'lesson' === $item['type'] ) {
				$at = get_user_meta( $user_id, '_tutor_completed_lesson_id_' . $item['id'], true );
				if ( is_numeric( $at ) ) {
					$times[] = (int) $at;
				}
			}
		}

		$attempt_at = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(attempt_started_at) FROM {$wpdb->prefix}tutor_quiz_attempts WHERE course_id = %d AND user_id = %d",
				$course_id,
				$user_id
			)
		);
		if ( '' !== $attempt_at ) {
			$times[] = (int) strtotime( get_gmt_from_date( $attempt_at ) . ' UTC' );
		}

		return array() !== $times ? max( $times ) : 0;
	}

	private function certificate_url( int $user_id, int $course_id ): string {
		$repo = CertificatesController::repository();
		if ( null === $repo ) {
			return '';
		}

		$certificate = $repo->for_user_course( $user_id, $course_id );

		return null !== $certificate ? CertificatePage::url( $certificate->code ) : '';
	}

	/**
	 * Course ids this user is enrolled in, from Tutor's own `tutor_enrolled`
	 * records (post_parent = course, post_author = student). Read directly
	 * because Tutor 4.x no longer exposes tutor_utils()->is_enrolled(),
	 * confirmed against the live install.
	 *
	 * @return list<int>
	 */
	private function enrolled_course_ids( int $user_id ): array {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return array();
		}

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'tutor_enrolled' AND post_author = %d AND post_status IN ('completed','publish')",
				$user_id
			)
		);

		return array_map( 'intval', is_array( $ids ) ? $ids : array() );
	}
}
