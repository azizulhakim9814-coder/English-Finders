<?php
/**
 * My Library section of the My Account page (Phase A3).
 *
 * The first place this plugin reads from Word Games Pro directly, rather
 * than only from Core -- through WGP's own new public facade
 * (`WordUnscrambleCheats\Support\Api`, added in WGP 2.12.12), the exact same
 * class_exists()+is_at_least()+service() pattern already used for Core.
 * Never queries WGP's `wuc_user_data` table directly.
 *
 * Deliberately shows only saved words and recent searches -- the "review
 * queue" and "mistake notebook" named in my-account-design.md's section 5
 * have no data behind them yet (nothing tracks a wrong answer anywhere in
 * this project), so they are not stubbed here, the same honesty principle
 * every earlier section of this page has applied.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Library;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LibraryController {
	/** 2.12.12 is the WGP release that added the Support\Api facade this class depends on. */
	private const MIN_WGP_VERSION = '2.12.12';

	private const LIST_LIMIT = 10;

	/**
	 * Data for the Library section template.
	 *
	 * Returns null when WGP isn't available or isn't new enough -- the
	 * template treats that as "don't render this section", the same
	 * degrade-gracefully contract every other section on this page follows.
	 *
	 * $user_id is accepted for interface consistency with
	 * ProgressController/MembershipController, but WGP's own
	 * UserDataRepository::list() scopes to get_current_user_id()
	 * internally, not a passed-in id -- on this page that is always the
	 * same user, since only a logged-in visitor ever reaches this
	 * template, but it is worth being honest that the parameter is not
	 * actually threaded through WGP's existing, already-tested repository.
	 *
	 * @return array{favorites: list<array<string,mixed>>, history: list<array<string,mixed>>}|null
	 */
	public function data_for_user( int $user_id ): ?array {
		if ( ! class_exists( '\\WordUnscrambleCheats\\Support\\Api' ) || ! \WordUnscrambleCheats\Support\Api::is_at_least( self::MIN_WGP_VERSION ) ) {
			return null;
		}

		$user_data = \WordUnscrambleCheats\Support\Api::service( 'user_data' );
		if ( ! is_object( $user_data ) || ! method_exists( $user_data, 'list' ) ) {
			return null;
		}

		return array(
			'favorites' => $user_data->list( 'favorite', self::LIST_LIMIT ),
			'history'   => $user_data->list( 'history', self::LIST_LIMIT ),
		);
	}
}
