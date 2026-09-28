<?php
/**
 * Certificates section of the My Account page (0.11.0, Phase A6).
 *
 * Lists the learner's course-completion certificates from Core 1.12.0's
 * `certificates` service, each with its printable page and shareable
 * verification link.
 *
 * Also a safety net for issuing: Core issues a certificate when Tutor fires
 * its course-completed hook, but if a completion ever happened without it
 * (e.g. before Core 1.12.0 was installed), loading My Account issues the
 * missing certificate for any course Tutor itself reports as completed.
 * Only the learner's enrolled courses are checked (a handful), and only
 * Tutor's own completion record counts -- never a guess from progress.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Certificates;

use EnglishFindersCore\Certificates\Certificate;
use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CertificatesController {
	/** 1.12.0 is when Core's `certificates` service first existed. */
	private const MIN_CORE_VERSION = '1.12.0';

	/**
	 * @return array{certificates: list<array<string,mixed>>}|null
	 */
	public function data_for_user( int $user_id ): ?array {
		$repo = self::repository();
		if ( null === $repo ) {
			return null;
		}

		$this->issue_missing( $repo, $user_id );

		return array(
			'certificates' => array_map( array( self::class, 'shape' ), $repo->for_user( $user_id ) ),
		);
	}

	/** @return array{code:string,course_title:string,level:string,learner_name:string,issued_at:string,url:string} */
	public static function shape( Certificate $certificate ): array {
		return array(
			'code'         => $certificate->code,
			'course_title' => $certificate->course_title,
			'level'        => $certificate->level,
			'learner_name' => $certificate->learner_name,
			'issued_at'    => $certificate->issued_at,
			'url'          => CertificatePage::url( $certificate->code ),
		);
	}

	/** Core's certificates service, or null when Core is missing or older than 1.12.0. */
	public static function repository(): ?\EnglishFindersCore\Certificates\CertificateRepository {
		if ( ! class_exists( '\\EnglishFindersCore\\Support\\Api' ) || ! Api::is_at_least( self::MIN_CORE_VERSION ) ) {
			return null;
		}

		$repo = Api::service( 'certificates' );

		return $repo instanceof \EnglishFindersCore\Certificates\CertificateRepository ? $repo : null;
	}

	private function issue_missing( \EnglishFindersCore\Certificates\CertificateRepository $repo, int $user_id ): void {
		if ( $user_id <= 0 || ! function_exists( 'tutor_utils' ) ) {
			return;
		}

		$utils = tutor_utils();
		if ( ! is_object( $utils ) || ! method_exists( $utils, 'is_completed_course' ) ) {
			return;
		}

		foreach ( (array) $utils->get_enrolled_courses_ids_by_user( $user_id ) as $course_id ) {
			$course_id = (int) $course_id;
			if ( $course_id > 0 && $utils->is_completed_course( $course_id, $user_id ) && null === $repo->for_user_course( $user_id, $course_id ) ) {
				$repo->issue_for_course( $user_id, $course_id );
			}
		}
	}
}
