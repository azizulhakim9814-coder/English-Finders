<?php
/**
 * Bridge to English Finders Core's shared activity log (Phase A2 step 3).
 *
 * Every quiz-style tool calls record_correct_answer() after determining an
 * answer was right, never before -- see each Tool's ajax_answer()/ajax_check().
 * PronunciationPractice has no answer-checking endpoint at all (see its own
 * class docblock) and does not call this.
 *
 * Event type is `quiz_answer_correct`, deliberately distinct from Core's
 * `quiz_completed` -- these tools score one question per request with no
 * server-side session concept, so "one full quiz completed" has no real
 * signal to hook here. See the english-learning-hub skill's
 * a2-activity-xp-streak.md.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Support;

use EnglishFindersStudy\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activity {
	/**
	 * Records one correct-answer activity event for the current user.
	 *
	 * Silently does nothing for a logged-out visitor, or when Core is
	 * missing or the activity service isn't registered (an older Core than
	 * 1.7.0) -- matches Plugin::core()'s own degrade-gracefully contract,
	 * the same way every other Core call in this plugin behaves.
	 */
	public static function record_correct_answer( string $tool_id ): void {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		$activity = Plugin::instance()->core( 'activity' );
		if ( ! $activity instanceof \EnglishFindersCore\Activity\ActivityRecorder ) {
			return;
		}

		$activity->record_event( $user_id, 'efs', 'quiz_answer_correct', array( 'tool' => $tool_id ) );
	}
}
