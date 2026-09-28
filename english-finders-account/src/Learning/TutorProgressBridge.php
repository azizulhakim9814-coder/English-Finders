<?php
/**
 * Makes Tutor LMS record progress for learners on public courses (0.11.0).
 *
 * The problem (found live, 2026-09-24): all six CEFR courses are set to
 * "public" in Tutor, so anyone can read lessons and even take quizzes
 * without enrolling -- and Tutor records nothing for a learner who isn't
 * enrolled: no "Mark as Complete" button (Tutor shows it to enrolled
 * learners only), no lesson progress, so no course could ever be completed
 * and no certificate earned. 178 enrolments, 0 lessons completed by
 * learners; real learners had taken quizzes inside A1 without enrolling.
 *
 * The fix, chosen by the site owner: when a *logged-in* learner opens a
 * lesson or quiz of a free public course, enrol them through Tutor's own
 * EnrollmentModel::do_enroll(). Logged-out visitors can still read
 * everything (the courses stay public, search visibility unchanged) and see
 * a short note that logging in saves their progress.
 *
 * Runs at template_redirect priority 5, before Tutor's own lesson-complete
 * handler (priority 10), so a first visit's "Mark as Complete" post always
 * finds the learner enrolled.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Learning;

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TutorProgressBridge {
	/** Tutor post type => the content type get_course_id_by() expects. */
	private const CONTENT_TYPES = array(
		'lesson'     => 'lesson',
		'tutor_quiz' => 'quiz',
	);

	public function register_hooks(): void {
		add_action( 'template_redirect', array( $this, 'maybe_enrol' ), 5 );
		add_action( 'tutor_lesson/single/before/content', array( $this, 'login_note' ) );
		add_action( 'tutor_quiz/body/before', array( $this, 'login_note' ) );
	}

	public function maybe_enrol(): void {
		if ( ! is_user_logged_in() || ! is_singular( array_keys( self::CONTENT_TYPES ) ) ) {
			return;
		}

		$course_id = $this->course_of( (int) get_queried_object_id() );
		if ( $course_id > 0 ) {
			self::enrol_if_eligible( get_current_user_id(), $course_id );
		}
	}

	/**
	 * Enrol a learner in a free public course they aren't enrolled in yet.
	 *
	 * @return bool Whether a new enrolment was created.
	 */
	public static function enrol_if_eligible( int $user_id, int $course_id ): bool {
		if ( $user_id <= 0 || $course_id <= 0 || ! self::tutor_available() ) {
			return false;
		}

		// Only public, free courses: a paid course must go through its own checkout.
		if ( 'yes' !== get_post_meta( $course_id, '_tutor_is_public_course', true ) || tutor_utils()->is_course_purchasable( $course_id ) ) {
			return false;
		}

		if ( \Tutor\Models\EnrollmentModel::is_enrolled( $course_id, $user_id ) ) {
			return false;
		}

		return \Tutor\Models\EnrollmentModel::do_enroll( $course_id, 0, $user_id ) > 0;
	}

	/** Shown above a lesson or quiz to logged-out visitors only. */
	public function login_note(): void {
		if ( is_user_logged_in() ) {
			return;
		}
		?>
		<p class="efa-tutor-login-note" style="margin:0 0 16px;padding:10px 14px;border-radius:5px;background:#f0f6fc;color:#0e2a4a;font-size:14px;">
			<?php
			$here = (string) get_permalink( get_queried_object_id() );
			printf(
				/* translators: 1: "Create a free account" link, 2: "log in" link */
				esc_html__( 'Reading as a guest. %1$s or %2$s to save your progress, mark lessons complete and earn a certificate.', 'english-finders-account' ),
				// 0.17.0: the /sign-up/ and /login/ pages (not My Account), both returning to this lesson.
				'<a rel="nofollow" href="' . esc_url( Urls::signup_page( $here ) ) . '" style="color:#075aae;font-weight:500;">' . esc_html__( 'Create a free account', 'english-finders-account' ) . '</a>',
				'<a rel="nofollow" href="' . esc_url( Urls::login_page( $here ) ) . '" style="color:#075aae;font-weight:500;">' . esc_html__( 'log in', 'english-finders-account' ) . '</a>'
			);
			?>
		</p>
		<?php
	}

	private function course_of( int $post_id ): int {
		if ( $post_id <= 0 || ! self::tutor_available() ) {
			return 0;
		}

		$type = self::CONTENT_TYPES[ (string) get_post_type( $post_id ) ] ?? '';

		return '' !== $type ? (int) tutor_utils()->get_course_id_by( $type, $post_id ) : 0;
	}

	private static function tutor_available(): bool {
		return function_exists( 'tutor_utils' )
			&& class_exists( '\\Tutor\\Models\\EnrollmentModel' )
			&& method_exists( '\\Tutor\\Models\\EnrollmentModel', 'do_enroll' )
			&& method_exists( '\\Tutor\\Models\\EnrollmentModel', 'is_enrolled' );
	}
}
