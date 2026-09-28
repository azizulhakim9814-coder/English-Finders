<?php
/**
 * Bridge to English Finders Core's mistake notebook (Core 1.9.0).
 *
 * Every answer-checked practice tool calls record_miss() when an answer is
 * wrong and record_correct() when it is right -- the latter closes the
 * notebook entry for that same question if the learner had missed it
 * before, so the notebook empties itself as they improve.
 *
 * Not called by the English Level Test: it deliberately gives no feedback,
 * and listing its questions with their answers afterwards would expose the
 * test. Not called by Pronunciation Practice either: it has no
 * answer-checking endpoint (see its class docblock).
 *
 * Same degrade-silently contract as Support\Activity: nothing happens for a
 * logged-out visitor, or when Core is older than 1.9.0.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Support;

use EnglishFindersStudy\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Mistakes {
	/**
	 * @param array{skill:string,level?:string,prompt:string,context?:string,given:string,correct:string,explanation?:string} $snapshot What the learner saw and answered.
	 */
	public static function record_miss( string $tool_id, string $item_key, array $snapshot ): void {
		$repo = self::repository();
		if ( null !== $repo ) {
			$repo->record_miss( get_current_user_id(), $tool_id, $item_key, $snapshot );
		}
	}

	public static function record_correct( string $tool_id, string $item_key ): void {
		$repo = self::repository();
		if ( null !== $repo ) {
			$repo->record_correct( get_current_user_id(), $tool_id, $item_key );
		}
	}

	private static function repository(): ?\EnglishFindersCore\Mistakes\MistakeRepository {
		if ( get_current_user_id() <= 0 ) {
			return null;
		}

		$repo = Plugin::instance()->core( 'mistakes' );

		return $repo instanceof \EnglishFindersCore\Mistakes\MistakeRepository ? $repo : null;
	}
}
