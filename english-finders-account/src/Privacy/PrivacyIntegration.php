<?php
/**
 * Plugs profile data into WordPress core's own Privacy Tools.
 *
 * WordPress has shipped a GDPR-oriented export/erase request system since
 * 4.9.6 (wp_privacy_personal_data_exporters/_erasers, wp_create_user_request()).
 * This registers our profile fields with it rather than building a second,
 * parallel export/delete UI -- see a1-account-foundation.md point 3.
 *
 * The one addition on top of what core provides out of the box: a
 * self-service action on the My Account page itself. Core's own admin UI
 * only lets an *administrator* initiate an export/erase request by typing
 * an email address; wp_create_user_request() is a public API that also
 * supports a user doing this for themselves, which is what these two
 * admin-post handlers add.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Privacy;

use EnglishFindersAccount\Profile\ProfileRepository;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PrivacyIntegration {
	private const EXPORTER_ID = 'english-finders-account';
	private const ERASER_ID   = 'english-finders-account';

	/** Separate exporter/eraser ids for level test results (0.7.0), so WordPress lists them as their own item. */
	private const LEVEL_EXPORTER_ID = 'english-finders-level-results';
	private const LEVEL_ERASER_ID   = 'english-finders-level-results';

	/** Mistake notebook (0.8.0). */
	private const MISTAKES_ID = 'english-finders-mistakes';

	/** Course certificates (0.11.0). */
	private const CERTIFICATES_ID = 'english-finders-certificates';

	public function register_hooks(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );

		add_action( 'admin_post_efa_request_export', array( $this, 'handle_self_service_export' ) );
		add_action( 'admin_post_efa_request_erase', array( $this, 'handle_self_service_erase' ) );
	}

	/** @param array<string,array<string,mixed>> $exporters */
	public function register_exporter( array $exporters ): array {
		$exporters[ self::EXPORTER_ID ] = array(
			'exporter_friendly_name' => __( 'English Finders Account', 'english-finders-account' ),
			'callback'               => array( $this, 'export_profile' ),
		);

		$exporters[ self::LEVEL_EXPORTER_ID ] = array(
			'exporter_friendly_name' => __( 'English Level Test results', 'english-finders-account' ),
			'callback'               => array( $this, 'export_level_results' ),
		);

		$exporters[ self::MISTAKES_ID ] = array(
			'exporter_friendly_name' => __( 'Mistake notebook', 'english-finders-account' ),
			'callback'               => array( $this, 'export_mistakes' ),
		);

		$exporters[ self::CERTIFICATES_ID ] = array(
			'exporter_friendly_name' => __( 'Course certificates', 'english-finders-account' ),
			'callback'               => array( $this, 'export_certificates' ),
		);

		return $exporters;
	}

	/** @return array{data: list<array<string,mixed>>, done: bool} */
	public function export_profile( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$profile = ( new ProfileRepository() )->get_all( $user->ID );

		$fields = array();
		foreach ( $profile as $key => $value ) {
			$fields[] = array(
				'name'  => $key,
				'value' => is_bool( $value ) ? ( $value ? 'yes' : 'no' ) : (string) $value,
			);
		}

		// 0.13.0.
		$photo = ( new \EnglishFindersAccount\Profile\Avatar() )->url( (int) $user->ID );
		if ( '' !== $photo ) {
			$fields[] = array(
				'name'  => __( 'Profile photo', 'english-finders-account' ),
				'value' => $photo,
			);
		}
		$fields[] = array(
			'name'  => __( 'Signed in with Google', 'english-finders-account' ),
			'value' => '' !== (string) get_user_meta( $user->ID, \EnglishFindersAccount\Auth\GoogleLogin::SUB_META, true ) ? 'yes' : 'no',
		);

		return array(
			'data' => array(
				array(
					'group_id'    => 'efa-profile',
					'group_label' => __( 'Account profile', 'english-finders-account' ),
					'item_id'     => 'efa-profile-' . $user->ID,
					'data'        => $fields,
				),
			),
			'done' => true,
		);
	}

	/** @param array<string,array<string,mixed>> $erasers */
	public function register_eraser( array $erasers ): array {
		$erasers[ self::ERASER_ID ] = array(
			'eraser_friendly_name' => __( 'English Finders Account', 'english-finders-account' ),
			'callback'             => array( $this, 'erase_profile' ),
		);

		$erasers[ self::LEVEL_ERASER_ID ] = array(
			'eraser_friendly_name' => __( 'English Level Test results', 'english-finders-account' ),
			'callback'             => array( $this, 'erase_level_results' ),
		);

		$erasers[ self::MISTAKES_ID ] = array(
			'eraser_friendly_name' => __( 'Mistake notebook', 'english-finders-account' ),
			'callback'             => array( $this, 'erase_mistakes' ),
		);

		$erasers[ self::CERTIFICATES_ID ] = array(
			'eraser_friendly_name' => __( 'Course certificates', 'english-finders-account' ),
			'callback'             => array( $this, 'erase_certificates' ),
		);

		return $erasers;
	}

	/** @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool} */
	public function erase_profile( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$removed = ( new ProfileRepository() )->delete_all( $user->ID );

		// 0.13.0: the photo file itself, the remembered Google photo and the Google link.
		$had_extra = '' !== (string) get_user_meta( $user->ID, \EnglishFindersAccount\Profile\Avatar::FILE_META, true )
			|| '' !== (string) get_user_meta( $user->ID, \EnglishFindersAccount\Profile\Avatar::REMOTE_META, true )
			|| '' !== (string) get_user_meta( $user->ID, \EnglishFindersAccount\Auth\GoogleLogin::SUB_META, true );
		( new \EnglishFindersAccount\Profile\Avatar() )->delete( (int) $user->ID );
		delete_user_meta( $user->ID, \EnglishFindersAccount\Auth\GoogleLogin::SUB_META );
		delete_user_meta( $user->ID, \EnglishFindersAccount\Auth\GoogleLogin::PASSWORD_SET_META );
		$removed = $removed || $had_extra;

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Level test results (Phase A4) live in Core's level_results table, not
	 * usermeta, so they need their own exporter. Degrades to "nothing to
	 * export" when Core is too old to have the service.
	 *
	 * @return array{data: list<array<string,mixed>>, done: bool}
	 */
	public function export_level_results( string $email_address, int $page = 1 ): array {
		$user    = get_user_by( 'email', $email_address );
		$results = $this->level_results_service();

		if ( ! $user || null === $results ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$items = array();
		foreach ( $results->history_for_user( (int) $user->ID, 50 ) as $result ) {
			$fields = array(
				array(
					'name'  => __( 'Taken at (UTC)', 'english-finders-account' ),
					'value' => $result->taken_at,
				),
				array(
					'name'  => __( 'Overall level', 'english-finders-account' ),
					'value' => $result->overall_level,
				),
			);
			foreach ( $result->skill_levels as $skill => $level ) {
				$fields[] = array(
					'name'  => ucfirst( $skill ),
					'value' => $level,
				);
			}
			$fields[] = array(
				'name'  => __( 'Correct answers', 'english-finders-account' ),
				'value' => $result->correct_answers . ' / ' . $result->questions_answered,
			);

			$items[] = array(
				'group_id'    => 'efa-level-results',
				'group_label' => __( 'English Level Test results', 'english-finders-account' ),
				'item_id'     => 'efa-level-result-' . $result->id,
				'data'        => $fields,
			);
		}

		return array(
			'data' => $items,
			'done' => true,
		);
	}

	/** @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool} */
	public function erase_level_results( string $email_address, int $page = 1 ): array {
		$user    = get_user_by( 'email', $email_address );
		$results = $this->level_results_service();

		$removed = ( $user && null !== $results ) ? $results->delete_for_user( (int) $user->ID ) > 0 : false;

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Every notebook entry, open and fixed (Core 1.9.0's mistakes table).
	 *
	 * @return array{data: list<array<string,mixed>>, done: bool}
	 */
	public function export_mistakes( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );
		$repo = \EnglishFindersAccount\Mistakes\MistakesController::repository();

		if ( ! $user || null === $repo ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$items = array();
		foreach ( $repo->all_for_user( (int) $user->ID ) as $mistake ) {
			$items[] = array(
				'group_id'    => 'efa-mistakes',
				'group_label' => __( 'Mistake notebook', 'english-finders-account' ),
				'item_id'     => 'efa-mistake-' . $mistake->id,
				'data'        => array(
					array( 'name' => __( 'Practice tool', 'english-finders-account' ), 'value' => $mistake->tool ),
					array( 'name' => __( 'Question', 'english-finders-account' ), 'value' => $mistake->prompt ),
					array( 'name' => __( 'Your answer', 'english-finders-account' ), 'value' => $mistake->given_answer ),
					array( 'name' => __( 'Correct answer', 'english-finders-account' ), 'value' => $mistake->correct_answer ),
					array( 'name' => __( 'Times missed', 'english-finders-account' ), 'value' => (string) $mistake->times_missed ),
					array( 'name' => __( 'Last missed (UTC)', 'english-finders-account' ), 'value' => $mistake->last_missed_at ),
					array( 'name' => __( 'Fixed (UTC)', 'english-finders-account' ), 'value' => (string) ( $mistake->resolved_at ?? '' ) ),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => true,
		);
	}

	/** @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool} */
	public function erase_mistakes( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );
		$repo = \EnglishFindersAccount\Mistakes\MistakesController::repository();

		return array(
			'items_removed'  => ( $user && null !== $repo ) ? $repo->delete_for_user( (int) $user->ID ) > 0 : false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Course certificates (Core 1.12.0's certificates table). Erasing them
	 * also retires their public verification links: the certificate page
	 * looks each code up live, so a deleted certificate stops verifying.
	 *
	 * @return array{data: list<array<string,mixed>>, done: bool}
	 */
	public function export_certificates( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );
		$repo = \EnglishFindersAccount\Certificates\CertificatesController::repository();

		if ( ! $user || null === $repo ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$items = array();
		foreach ( $repo->for_user( (int) $user->ID ) as $certificate ) {
			$items[] = array(
				'group_id'    => 'efa-certificates',
				'group_label' => __( 'Course certificates', 'english-finders-account' ),
				'item_id'     => 'efa-certificate-' . $certificate->id,
				'data'        => array(
					array( 'name' => __( 'Course', 'english-finders-account' ), 'value' => $certificate->course_title ),
					array( 'name' => __( 'Level', 'english-finders-account' ), 'value' => $certificate->level ),
					array( 'name' => __( 'Name on certificate', 'english-finders-account' ), 'value' => $certificate->learner_name ),
					array( 'name' => __( 'Issued (UTC)', 'english-finders-account' ), 'value' => $certificate->issued_at ),
					array( 'name' => __( 'Certificate ID', 'english-finders-account' ), 'value' => $certificate->code ),
					array( 'name' => __( 'Verification link', 'english-finders-account' ), 'value' => \EnglishFindersAccount\Certificates\CertificatePage::url( $certificate->code ) ),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => true,
		);
	}

	/** @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool} */
	public function erase_certificates( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );
		$repo = \EnglishFindersAccount\Certificates\CertificatesController::repository();

		return array(
			'items_removed'  => ( $user && null !== $repo ) ? $repo->delete_for_user( (int) $user->ID ) > 0 : false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	private function level_results_service(): ?\EnglishFindersCore\Assessment\LevelResultRepository {
		if ( ! class_exists( '\\EnglishFindersCore\\Support\\Api' ) || ! \EnglishFindersCore\Support\Api::is_at_least( '1.8.0' ) ) {
			return null;
		}

		$service = \EnglishFindersCore\Support\Api::service( 'level_results' );

		return $service instanceof \EnglishFindersCore\Assessment\LevelResultRepository ? $service : null;
	}

	public function handle_self_service_export(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to do that.', 'english-finders-account' ) );
		}

		check_admin_referer( 'efa_request_export' );

		$this->submit_request( 'export_personal_data', 'export_requested' );
	}

	public function handle_self_service_erase(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to do that.', 'english-finders-account' ) );
		}

		check_admin_referer( 'efa_request_erase' );

		$this->submit_request( 'remove_personal_data', 'erase_requested' );
	}

	/**
	 * Create and send a self-service privacy request for the current user.
	 *
	 * wp_create_user_request() followed by wp_send_user_request() is the
	 * same two-step core uses internally -- create the request record,
	 * then email the confirmation link. The user still has to click that
	 * link, which is deliberate: it is what stops someone else triggering
	 * a data deletion against an account merely by having its email
	 * address, since only the account's actual inbox receives the link.
	 */
	private function submit_request( string $action_name, string $notice_on_success ): void {
		$user = wp_get_current_user();

		$request_id = wp_create_user_request( $user->user_email, $action_name );

		if ( is_wp_error( $request_id ) ) {
			wp_safe_redirect( add_query_arg( 'efa_notice', 'request_failed', Urls::my_account() ) );
			exit;
		}

		wp_send_user_request( $request_id );

		wp_safe_redirect( add_query_arg( 'efa_notice', $notice_on_success, Urls::my_account() ) );
		exit;
	}
}
