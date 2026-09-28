<?php
/**
 * Feeds Quiz And Survey Master (QSM) quiz submissions into the shared
 * activity log (Phase A2 step 4).
 *
 * Same approach as TutorIntegration: listens to QSM's real, documented
 * hook from the outside rather than modifying its files. Hook name and
 * signature confirmed against the live install's actual source (QSM,
 * `quiz-master-next` folder), not assumed from memory of its docs.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Integrations;

use EnglishFindersCore\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QsmIntegration {
	/**
	 * Always registers -- no class_exists() gate. Same reasoning as
	 * TutorIntegration::register_hooks(): a presence check this early
	 * (Core boots at plugins_loaded priority 5) risks a false negative if
	 * QSM initialises later than that, and an add_action() for a hook that
	 * never fires when QSM is inactive costs nothing.
	 */
	public function register_hooks(): void {
		add_action( 'qsm_quiz_submitted', array( $this, 'on_quiz_submitted' ), 10, 4 );
	}

	/**
	 * @param mixed                $results_array Raw per-question response data -- not used here.
	 * @param int                  $results_id    The results-table row id for this submission; QSM's own unique identifier per attempt.
	 * @param mixed                $quiz_options  Quiz settings object -- not used here.
	 * @param array<string,mixed>  $variables     Carries 'quiz_id' among other template variables.
	 */
	public function on_quiz_submitted( mixed $results_array, int $results_id, mixed $quiz_options, array $variables ): void {
		/*
		 * QSM's hook carries no user id -- the person submitting is
		 * whoever is logged in for this request, the same assumption every
		 * other integration in this codebase (WGP, EFS) already makes.
		 */
		$user_id = get_current_user_id();
		if ( $user_id <= 0 || $results_id <= 0 ) {
			return;
		}

		/*
		 * Keyed on results_id, not quiz_id -- a quiz can be retaken, and
		 * each distinct submission is its own completable unit worth
		 * crediting again, matching TutorIntegration's attempt_id policy
		 * for the same reason.
		 */
		$meta_key = '_efc_activity_credited_qsm_result_' . $results_id;
		if ( ! empty( get_user_meta( $user_id, $meta_key, true ) ) ) {
			return;
		}

		$activity = Plugin::instance()->get( 'activity' );
		if ( ! $activity instanceof \EnglishFindersCore\Activity\ActivityRecorder ) {
			return;
		}

		$quiz_id = isset( $variables['quiz_id'] ) ? (int) $variables['quiz_id'] : 0;
		$correct = $this->correct_count( $results_id, $variables );

		/*
		 * 1.14.0: 1 XP per correct answer (was a flat 15 per submission), the
		 * same currency as the Study tools and Tutor quizzes. A submission
		 * with no correct answers records nothing, as elsewhere.
		 */
		if ( $correct > 0 ) {
			$activity->record_event( $user_id, 'qsm', 'quiz_answer_correct', array( 'quiz_id' => $quiz_id, 'result_id' => $results_id ), $correct );
		}

		// Marked only after a successful record_event() call -- see TutorIntegration::record_once() for why.
		update_user_meta( $user_id, $meta_key, 1 );
	}

	/**
	 * How many questions this submission got right.
	 *
	 * QSM 11.2.7 puts its own count in the hook's variables as
	 * `total_correct` (the same value it stores in mlw_results.correct);
	 * the stored row is the fallback if a filter ever removes it.
	 *
	 * @param array<string,mixed> $variables
	 */
	private function correct_count( int $results_id, array $variables ): int {
		if ( isset( $variables['total_correct'] ) && is_numeric( $variables['total_correct'] ) ) {
			return max( 0, (int) $variables['total_correct'] );
		}

		global $wpdb;

		return max(
			0,
			(int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived.
					"SELECT correct FROM {$wpdb->prefix}mlw_results WHERE result_id = %d",
					$results_id
				)
			)
		);
	}
}
