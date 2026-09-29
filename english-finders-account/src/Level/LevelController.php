<?php
/**
 * My Level section of the My Account page (Phase A4).
 *
 * Shows the learner's English Level Test result (overall CEFR level plus
 * grammar/vocabulary/reading), their test history, and their progress
 * through the six Tutor LMS CEFR courses -- every part of it read from
 * real stored data, nothing inferred:
 *
 * - Test results come from Core's `level_results` service (Core 1.8.0),
 *   written by English Finders Study's level test.
 * - Course progress comes from Tutor LMS itself (enrolment records plus
 *   tutor_utils()->get_course_completed_percent()).
 *
 * Returns null -- the section is simply absent -- when Core is too old, or
 * when there is neither a result to show nor a level test page to send
 * the learner to. Same degrade-gracefully contract as ProgressController.
 *
 * Per-skill levels are shown to every account for now, although
 * my-account-design.md lists per-skill detail as a Pro feature: billing
 * isn't configured on the live site, so gating it would hide it from
 * everyone. Revisit once Paddle is live.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Level;

use EnglishFindersAccount\Learning\CourseProgress;
use EnglishFindersCore\Assessment\LevelResult;
use EnglishFindersCore\Assessment\LevelScale;
use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LevelController {
	/** 1.8.0 is when Core's `level_results` service and LevelScale first existed. */
	private const MIN_CORE_VERSION = '1.8.0';

	/** The level test's page slug on englishfinders.com. */
	public const TEST_PAGE_SLUG = 'english-level-test';

	/** Past attempts listed under the current result. */
	private const HISTORY_LIMIT = 5;

	/**
	 * @return array{latest: array<string,mixed>|null, history: list<array<string,mixed>>, courses: list<array<string,mixed>>, test_url: string}|null
	 */
	public function data_for_user( int $user_id ): ?array {
		if ( ! class_exists( '\\EnglishFindersCore\\Support\\Api' ) || ! Api::is_at_least( self::MIN_CORE_VERSION ) ) {
			return null;
		}

		$results = Api::service( 'level_results' );
		if ( ! $results instanceof \EnglishFindersCore\Assessment\LevelResultRepository ) {
			return null;
		}

		$history  = $results->history_for_user( $user_id, self::HISTORY_LIMIT + 1 );
		$test_url = $this->test_url();

		if ( array() === $history && '' === $test_url ) {
			return null;
		}

		$shaped = array_map( array( $this, 'shape' ), $history );

		return array(
			'latest'   => $shaped[0] ?? null,
			'history'  => array_slice( $shaped, 1, self::HISTORY_LIMIT ),
			'courses'  => $this->course_progress( $user_id ),
			'test_url' => $test_url,
		);
	}

	/**
	 * @return array{overall: string, overall_short: string, overall_label: string, description: string, skills: list<array{skill: string, label: string, level: string, short: string, index: int}>, taken_at: string, questions_answered: int, correct_answers: int}
	 */
	private function shape( LevelResult $result ): array {
		$skills = array();
		foreach ( $result->skill_levels as $skill => $level ) {
			$skills[] = array(
				'skill' => $skill,
				'label' => LevelScale::skill_label( $skill ),
				'level' => $level,
				'short' => LevelScale::short_label( $level ),
				'index' => LevelScale::index( $level ),
			);
		}

		return array(
			'overall'            => $result->overall_level,
			'overall_short'      => LevelScale::short_label( $result->overall_level ),
			'overall_label'      => LevelScale::label( $result->overall_level ),
			'description'        => LevelScale::description( $result->overall_level ),
			'skills'             => $skills,
			'taken_at'           => $result->taken_at,
			'questions_answered' => $result->questions_answered,
			'correct_answers'    => $result->correct_answers,
		);
	}

	/**
	 * The level test page's URL, or '' when no such published page exists
	 * -- a link to a 404 would be worse than no link.
	 */
	private function test_url(): string {
		return self::test_page_url();
	}

	/** The same URL for callers outside My Level -- the sign-up welcome (0.20.0). */
	public static function test_page_url(): string {
		$page = get_page_by_path( self::TEST_PAGE_SLUG );
		$url  = ( $page instanceof \WP_Post && 'publish' === $page->post_status ) ? (string) get_permalink( $page ) : '';

		/**
		 * Filter the English Level Test URL linked from My Level.
		 *
		 * @param string $url Page URL, or '' when the page doesn't exist.
		 */
		return (string) apply_filters( 'efa_level_test_url', $url );
	}

	/**
	 * The CEFR courses with this learner's progress, lowest level first (0.12.0:
	 * now shared with the Home "Continue" card via Learning\CourseProgress).
	 *
	 * @return list<array<string,mixed>>
	 */
	private function course_progress( int $user_id ): array {
		return ( new CourseProgress() )->for_user( $user_id );
	}
}
