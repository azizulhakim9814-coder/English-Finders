<?php
/**
 * Mistake notebook section of the My Account page.
 *
 * Shows the questions a learner got wrong in the practice tools -- what they
 * answered, the right answer, and the explanation -- read from Core's
 * `mistakes` service (Core 1.9.0), which English Finders Study 1.13.0
 * writes to. An entry closes itself when the learner later answers the same
 * question correctly, or when they press "Got it".
 *
 * Returns null -- the section is simply absent -- when Core is too old. Same
 * degrade-gracefully contract as the other sections.
 *
 * my-account-design.md lists "last 10 mistakes free, full history Pro".
 * Billing isn't configured on the live site, so for now every account sees
 * everything; gating it would hide it from everyone. Revisit once Paddle is
 * live.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Mistakes;

use EnglishFindersCore\Mistakes\Mistake;
use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MistakesController {
	/** 1.9.0 is when Core's `mistakes` service first existed. */
	private const MIN_CORE_VERSION = '1.9.0';

	/** Open entries listed at once; the rest are counted, not hidden silently. */
	public const LIST_LIMIT = 20;

	/** Read-only filter query arg. */
	public const SKILL_PARAM = 'efa_mistakes_skill';

	/**
	 * Practice tool id => page slug on englishfinders.com (checked live
	 * 2026-09-24). Only linked when that page is actually published.
	 */
	private const TOOL_PAGES = array(
		'vocabulary-quiz'  => 'vocabulary-quiz',
		'definition-match' => 'match-the-definition',
		'grammar-quiz'     => 'grammar-quiz',
		'spelling-quiz'    => 'spelling-quiz',
		'reading-quiz'     => 'reading-quiz',
		'sentence-builder' => 'sentence-builder',
		'error-correction' => 'error-correction',
		'writing-feedback' => 'writing-feedback',
	);

	/**
	 * @return array{counts: array{open:int,fixed:int,by_skill:array<string,array{open:int,fixed:int}>}, entries: list<array<string,mixed>>, skill: string, practice_url: string}|null
	 */
	public function data_for_user( int $user_id, string $skill = '' ): ?array {
		$repo = self::repository();
		if ( null === $repo ) {
			return null;
		}

		$counts = $repo->counts_for_user( $user_id );
		$skill  = isset( $counts['by_skill'][ $skill ] ) ? $skill : '';

		$page_urls = array();
		$entries   = array();
		foreach ( $repo->open_for_user( $user_id, self::LIST_LIMIT, $skill ) as $mistake ) {
			$entries[] = $this->shape( $mistake, $page_urls );
		}

		return array(
			'counts'       => $counts,
			'entries'      => $entries,
			'skill'        => $skill,
			'practice_url' => $this->page_url( 'practice' ),
		);
	}

	/** Labels for skill slugs, matching English Finders Study's categories. */
	public static function skill_label( string $skill ): string {
		$labels = array(
			'vocabulary'    => __( 'Vocabulary', 'english-finders-account' ),
			'grammar'       => __( 'Grammar', 'english-finders-account' ),
			'reading'       => __( 'Reading', 'english-finders-account' ),
			'writing'       => __( 'Writing', 'english-finders-account' ),
			'spelling'      => __( 'Spelling', 'english-finders-account' ),
			'pronunciation' => __( 'Pronunciation', 'english-finders-account' ),
		);

		return $labels[ $skill ] ?? ucfirst( $skill );
	}

	public static function tool_label( string $tool ): string {
		$labels = array(
			'vocabulary-quiz'  => __( 'Vocabulary Quiz', 'english-finders-account' ),
			'definition-match' => __( 'Match the Definition', 'english-finders-account' ),
			'grammar-quiz'     => __( 'Grammar Quiz', 'english-finders-account' ),
			'spelling-quiz'    => __( 'Spelling Quiz', 'english-finders-account' ),
			'reading-quiz'     => __( 'Reading Quiz', 'english-finders-account' ),
			'sentence-builder' => __( 'Sentence Builder', 'english-finders-account' ),
			'error-correction' => __( 'Error Correction', 'english-finders-account' ),
			'writing-feedback' => __( 'AI Writing Feedback', 'english-finders-account' ),
		);

		return $labels[ $tool ] ?? ucwords( str_replace( '-', ' ', $tool ) );
	}

	/** Core's mistakes service, or null when Core is missing or older than 1.9.0. */
	public static function repository(): ?\EnglishFindersCore\Mistakes\MistakeRepository {
		if ( ! class_exists( '\\EnglishFindersCore\\Support\\Api' ) || ! Api::is_at_least( self::MIN_CORE_VERSION ) ) {
			return null;
		}

		$repo = Api::service( 'mistakes' );

		return $repo instanceof \EnglishFindersCore\Mistakes\MistakeRepository ? $repo : null;
	}

	/**
	 * @param array<string,string> $page_urls Per-request cache of tool page lookups.
	 * @return array<string,mixed>
	 */
	private function shape( Mistake $mistake, array &$page_urls ): array {
		if ( ! array_key_exists( $mistake->tool, $page_urls ) ) {
			$slug                        = self::TOOL_PAGES[ $mistake->tool ] ?? '';
			$page_urls[ $mistake->tool ] = '' !== $slug ? $this->page_url( $slug ) : '';
		}

		return array(
			'id'           => $mistake->id,
			'tool'         => $mistake->tool,
			'tool_label'   => self::tool_label( $mistake->tool ),
			'tool_url'     => $page_urls[ $mistake->tool ],
			'skill'        => $mistake->skill,
			'skill_label'  => self::skill_label( $mistake->skill ),
			'level'        => $mistake->level,
			'prompt'       => $mistake->prompt,
			'context'      => $mistake->context,
			'given'        => $mistake->given_answer,
			'correct'      => $mistake->correct_answer,
			'explanation'  => $mistake->explanation,
			'times_missed' => $mistake->times_missed,
			'last_missed'  => $mistake->last_missed_at,
		);
	}

	/** A published page's URL by slug, or '' -- never a link to a 404. */
	private function page_url( string $slug ): string {
		$page = get_page_by_path( $slug );

		return ( $page instanceof \WP_Post && 'publish' === $page->post_status ) ? (string) get_permalink( $page ) : '';
	}
}
