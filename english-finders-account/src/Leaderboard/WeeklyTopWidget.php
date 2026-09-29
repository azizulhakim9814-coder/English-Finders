<?php
/**
 * [efa_weekly_top] -- the weekly leaderboard's top rows for a sidebar (0.15.0).
 *
 *   [efa_weekly_top]                     Top 5, title "Weekly Top 5"
 *   [efa_weekly_top limit="3"]           Top 3 (1-10)
 *   [efa_weekly_top title="This week"]   Own title
 *
 * Who sees what (the leaderboard is opt-in and its Join text promises names
 * and photos go to signed-in members only):
 *  - Signed-in members: names, photos and XP, rendered fresh (logged-in pages
 *    are never page-cached), their own row highlighted, and their rank or a
 *    Join link underneath.
 *  - Everyone else: the same ranks and XP with the names hidden, and a
 *    "Sign up free" button. Their pages are cached for days, so the numbers
 *    are refreshed on load from PublicBoardFile's static JSON (no PHP).
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Leaderboard;

use EnglishFindersAccount\Pages\AuthPages;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WeeklyTopWidget {
	public const TAG = 'efa_weekly_top';

	private static bool $assets_printed = false;

	public function register_hooks(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/** @param array<string,string>|string $atts */
	public function render( $atts = array() ): string {
		$atts  = shortcode_atts(
			array(
				'limit' => '5',
				'title' => '',
			),
			(array) $atts,
			self::TAG
		);
		$limit = max( 1, min( LeaderboardController::TOP, (int) $atts['limit'] ) );
		/* translators: %d: number of learners shown, e.g. "Weekly Top 5" */
		$title = '' !== trim( (string) $atts['title'] ) ? trim( (string) $atts['title'] ) : sprintf( __( 'Weekly Top %d', 'english-finders-account' ), $limit );

		$user_id = get_current_user_id();
		$member  = $user_id > 0;

		if ( $member ) {
			$data = ( new LeaderboardController() )->data_for_user( $user_id );
			if ( null === $data ) {
				return '';
			}
			$week    = $data['week'];
			$rows    = array_slice( $data['entries'], 0, $limit );
			$in_rows = in_array( true, array_column( $rows, 'is_viewer' ), true );
			$viewer  = $data['viewer'];
			$joined  = $data['opted_in'];
		} else {
			$snap = PublicBoardFile::read();
			if ( null === $snap ) {
				return '';
			}
			$week    = $snap['week'];
			$rows    = array_slice( $snap['rows'], 0, $limit );
			$in_rows = false;
			$viewer  = array(
				'xp'   => 0,
				'rank' => null,
			);
			$joined  = false;
		}

		$span        = isset( $week['span'] ) ? (string) $week['span'] : LeaderboardController::week_span( $week );
		$days_left   = isset( $week['days_left'] ) ? (int) $week['days_left'] : self::days_left( (int) ( $week['ends'] ?? 0 ) );
		$reset_text  = self::reset_text( $days_left );
		$board_url   = Urls::my_account() . '#efa-section-leaderboard';
		$here        = AuthPages::current_page_url();
		$signup_url  = Urls::signup_page( $here );
		$login_url   = Urls::login_page( $here );
		$src         = $member ? '' : PublicBoardFile::url();
		$print_assets = ! self::$assets_printed;
		self::$assets_printed = true;

		ob_start();
		include EFA_PATH . 'templates/public/weekly-top.php';

		return (string) ob_get_clean();
	}

	/** Whole days until the Monday reset (0 on Sunday = "at midnight tonight"). */
	public static function days_left( int $ends ): int {
		return $ends > 0 ? max( 0, (int) floor( ( $ends - time() ) / DAY_IN_SECONDS ) ) : 0;
	}

	public static function reset_text( int $days_left ): string {
		if ( $days_left <= 0 ) {
			return __( 'resets at midnight tonight', 'english-finders-account' );
		}

		/* translators: %d: days until the weekly reset */
		return sprintf( _n( 'resets in %d day', 'resets in %d days', $days_left, 'english-finders-account' ), $days_left );
	}
}
