<?php
/**
 * Feeds Tutor LMS lesson, course, and quiz completions into the shared
 * activity log (Phase A2 step 4).
 *
 * Unlike Word Games Pro and English Finders Study (our own plugins, edited
 * directly), Tutor LMS is third-party -- this listens to its real,
 * documented action hooks from the outside rather than modifying its files.
 * Hook names and signatures were confirmed against the live install's
 * actual source (Tutor LMS 4.0.9), not assumed from memory of its docs.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Integrations;

use EnglishFindersCore\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TutorIntegration {
	/**
	 * Always registers -- no function_exists()/class_exists() gate here on
	 * purpose. Core boots at plugins_loaded priority 5, before Tutor LMS is
	 * guaranteed to have initialised its own globals if Tutor does that
	 * inside its own plugins_loaded callback rather than at file-load time;
	 * a presence check at this point could produce a false negative purely
	 * from load-order timing. add_action() for a hook name that never
	 * fires (because Tutor is inactive) costs nothing and calls nothing --
	 * these handlers never touch a Tutor function or class directly, only
	 * the primitive arguments the hook itself passes, so there is nothing
	 * here that actually needs Tutor to be confirmed present in advance.
	 */
	public function register_hooks(): void {
		/*
		 * tutor_lesson_completed_after also fires tutor_mark_lesson_complete_after
		 * internally (Lesson.php calls LessonModel::mark_lesson_complete(),
		 * which fires its own hook) -- only the outer one is hooked here, so
		 * a single user-facing completion is never counted twice.
		 */
		add_action( 'tutor_lesson_completed_after', array( $this, 'on_lesson_completed' ), 10, 2 );
		add_action( 'tutor_course_complete_after', array( $this, 'on_course_completed' ), 10, 2 );
		/*
		 * 1.14.0: quiz XP is 1 per correct answer, and hooks the event that
		 * actually fires. A normal submission in Tutor 4.0.9 ends in
		 * Quiz::answering_quiz(), which fires `tutor_quiz/attempt_ended`
		 * ($attempt_id, $course_id, $user_id). `tutor_quiz_finished` (the only
		 * hook used before 1.14.0) fires solely from finishing_quiz_attempt(),
		 * the "finish without answering" path -- so no normal quiz ever earned
		 * XP. Both are hooked, plus the timeout path; crediting is keyed on
		 * the attempt, so an attempt reaching more than one of them counts once.
		 */
		add_action( 'tutor_quiz/attempt_ended', array( $this, 'on_attempt_ended' ), 10, 3 );
		add_action( 'tutor_quiz_finished', array( $this, 'on_quiz_finished' ), 10, 3 );
		add_action( 'tutor_quiz_timeout', array( $this, 'on_quiz_finished' ), 10, 3 );
		// An instructor marking a reviewed answer correct later earns that answer's XP then.
		add_action( 'tutor_quiz_review_answer_after', array( $this, 'on_answer_reviewed' ), 10, 3 );
		// One-off: credit attempts the old hook missed (see backfill_missed_quiz_attempts()).
		add_action( 'init', array( $this, 'backfill_missed_quiz_attempts' ), 20 );
	}

	/** Option set once the 1.14.0 backfill has run. */
	public const BACKFILL_OPTION = 'efc_tutor_quiz_backfill_1140';

	/** Attempts before this (site time) are not backfilled: the activity log starts here. */
	private const BACKFILL_FROM = '2026-09-22 00:00:00';

	/** Usermeta prefix holding the correct answers already credited for one attempt. */
	private const ATTEMPT_META = '_efc_activity_credited_quiz_attempt_';

	public function on_lesson_completed( int $lesson_id, int $user_id ): void {
		$this->record_once( $user_id, 'lesson_completed', 'lesson', $lesson_id, array( 'lesson_id' => $lesson_id ) );
	}

	public function on_course_completed( int $course_id, int $user_id ): void {
		/*
		 * Tutor's own CourseModel::mark_course_as_completed() has no
		 * idempotency guard of its own (confirmed by reading its source --
		 * it generates a unique identification hash per insert, but never
		 * checks whether the course was already marked complete for this
		 * user before inserting again), so the guard here is load-bearing,
		 * not defensive-for-its-own-sake.
		 */
		$this->record_once( $user_id, 'course_completed', 'course', $course_id, array( 'course_id' => $course_id ) );

		/*
		 * 1.12.0: issue the course certificate. Idempotent on its own (one
		 * per learner per course, enforced by a UNIQUE key), so it doesn't
		 * need record_once()'s usermeta guard.
		 */
		$certificates = Plugin::instance()->get( 'certificates' );
		if ( $certificates instanceof \EnglishFindersCore\Certificates\CertificateRepository && $user_id > 0 && $course_id > 0 ) {
			$certificates->issue_for_course( $user_id, $course_id );
		}
	}

	/** `tutor_quiz/attempt_ended` -- the normal submission path. */
	public function on_attempt_ended( int $attempt_id, int $course_id, int $user_id ): void {
		$this->credit_attempt( $attempt_id, $user_id );
	}

	/** `tutor_quiz_finished` / `tutor_quiz_timeout`. */
	public function on_quiz_finished( int $attempt_id, int $quiz_id, int $user_id ): void {
		$this->credit_attempt( $attempt_id, $user_id );
	}

	/**
	 * Credit 1 XP per correct answer in one attempt, once.
	 *
	 * Keyed on the attempt, not the quiz -- a retake is new work and earns
	 * again (Tutor's own attempt limit bounds that). The usermeta marker
	 * holds the ids of the answers credited, so a later manual review (see
	 * on_answer_reviewed()) can tell which answers are still owed XP. An
	 * attempt with no correct answers records no event -- the same as the
	 * Study tools, where only a correct answer counts as activity.
	 */
	private function credit_attempt( int $attempt_id, int $user_id, string $occurred_gmt = '' ): void {
		if ( $attempt_id <= 0 || $user_id <= 0 ) {
			return;
		}

		$meta_key = self::ATTEMPT_META . $attempt_id;
		if ( ! empty( get_user_meta( $user_id, $meta_key, true ) ) ) {
			return;
		}

		$activity = Plugin::instance()->get( 'activity' );
		if ( ! $activity instanceof \EnglishFindersCore\Activity\ActivityRecorder ) {
			return;
		}

		$attempt = $this->attempt( $attempt_id );
		if ( null === $attempt || (int) $attempt['user_id'] !== $user_id ) {
			return;
		}

		$correct  = $this->correct_answer_ids( $attempt_id );
		$metadata = array( 'quiz_id' => (int) $attempt['quiz_id'], 'attempt_id' => $attempt_id );

		if ( array() !== $correct ) {
			if ( '' !== $occurred_gmt ) {
				$activity->record_backdated( $user_id, 'tutor', 'quiz_answer_correct', count( $correct ), $metadata, $occurred_gmt );
			} else {
				$activity->record_event( $user_id, 'tutor', 'quiz_answer_correct', $metadata, count( $correct ) );
			}
		}

		// Marked after recording (see record_once() for why); never empty, so an all-wrong attempt is still "done".
		update_user_meta( $user_id, $meta_key, array( 'answers' => $correct ) );
	}

	/**
	 * `tutor_quiz_review_answer_after` ($attempt_answer_id, $attempt_id, $mark_as):
	 * an instructor marked one answer. Marking it correct earns 1 XP for the
	 * learner, once per answer.
	 */
	public function on_answer_reviewed( int $attempt_answer_id, int $attempt_id, string $mark_as ): void {
		if ( 'correct' !== $mark_as || $attempt_answer_id <= 0 ) {
			return;
		}

		$attempt = $this->attempt( $attempt_id );
		if ( null === $attempt ) {
			return;
		}
		$user_id = (int) $attempt['user_id'];

		$marker = get_user_meta( $user_id, self::ATTEMPT_META . $attempt_id, true );
		if ( empty( $marker ) ) {
			// Not credited yet at all: crediting the attempt now includes this answer.
			$this->credit_attempt( $attempt_id, $user_id );
			return;
		}

		// A pre-1.14.0 marker ("1") means the attempt was settled under the old rules.
		if ( ! is_array( $marker ) ) {
			return;
		}

		$credited = array_map( 'intval', (array) ( $marker['answers'] ?? array() ) );
		if ( in_array( $attempt_answer_id, $credited, true ) ) {
			return;
		}

		$activity = Plugin::instance()->get( 'activity' );
		if ( ! $activity instanceof \EnglishFindersCore\Activity\ActivityRecorder ) {
			return;
		}

		$activity->record_event( $user_id, 'tutor', 'quiz_answer_correct', array( 'quiz_id' => (int) $attempt['quiz_id'], 'attempt_id' => $attempt_id, 'reviewed' => true ) );
		$credited[] = $attempt_answer_id;
		update_user_meta( $user_id, self::ATTEMPT_META . $attempt_id, array( 'answers' => $credited ) );
	}

	/**
	 * 1.14.0 one-off: credit quiz attempts that ended while only the old
	 * hook was listened to. Runs once (option flag), after Tutor is loaded;
	 * each attempt's XP is dated when it really ended and leaves streaks as
	 * they are (ActivityRecorder::record_backdated()). Attempts that already
	 * carry a marker are skipped, so running it twice can never double-credit.
	 */
	public function backfill_missed_quiz_attempts(): void {
		if ( get_option( self::BACKFILL_OPTION ) ) {
			return;
		}

		global $wpdb;
		$attempts = $wpdb->prefix . 'tutor_quiz_attempts';
		if ( $attempts !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $attempts ) ) ) {
			// No Tutor tables means no missed attempts; don't re-check on every request.
			update_option( self::BACKFILL_OPTION, current_time( 'mysql', true ), false );
			return;
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived.
				"SELECT attempt_id, user_id, attempt_ended_at FROM {$attempts} WHERE attempt_status IN ('attempt_ended','review_required') AND attempt_ended_at >= %s ORDER BY attempt_id",
				self::BACKFILL_FROM
			),
			ARRAY_A
		);

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$ended = (string) $row['attempt_ended_at'];
			$gmt   = function_exists( 'get_gmt_from_date' ) ? get_gmt_from_date( $ended ) : $ended;
			$this->credit_attempt( (int) $row['attempt_id'], (int) $row['user_id'], $gmt );
		}

		update_option( self::BACKFILL_OPTION, current_time( 'mysql', true ), false );
	}

	/** @return array{user_id:int|string,quiz_id:int|string}|null */
	private function attempt( int $attempt_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived.
				"SELECT user_id, quiz_id FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id = %d",
				$attempt_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/** @return list<int> Ids of the attempt's answers Tutor has marked correct. */
	private function correct_answer_ids( int $attempt_id ): array {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived.
				"SELECT attempt_answer_id FROM {$wpdb->prefix}tutor_quiz_attempt_answers WHERE quiz_attempt_id = %d AND is_correct = 1",
				$attempt_id
			)
		);

		return array_values( array_map( 'intval', is_array( $ids ) ? $ids : array() ) );
	}

	/**
	 * Records one activity event at most once per (user, unit-of-work) --
	 * a permanent usermeta marker, not a transient: lesson/course/quiz
	 * completion never expires the way a game round does, so a TTL-based
	 * guard would be the wrong tool here.
	 *
	 * @param array<string,mixed> $metadata
	 */
	private function record_once( int $user_id, string $event_type, string $unit, int $unit_id, array $metadata ): void {
		if ( $user_id <= 0 || $unit_id <= 0 ) {
			return;
		}

		$meta_key = '_efc_activity_credited_' . $unit . '_' . $unit_id;
		if ( ! empty( get_user_meta( $user_id, $meta_key, true ) ) ) {
			return;
		}

		$activity = Plugin::instance()->get( 'activity' );
		if ( ! $activity instanceof \EnglishFindersCore\Activity\ActivityRecorder ) {
			return;
		}

		/*
		 * Marked only after a successful record_event() call, not before --
		 * if resolving the activity service ever failed, marking "credited"
		 * first would silently lose that XP forever with no way to retry.
		 */
		$activity->record_event( $user_id, 'tutor', $event_type, $metadata );
		update_user_meta( $user_id, $meta_key, 1 );
	}
}
