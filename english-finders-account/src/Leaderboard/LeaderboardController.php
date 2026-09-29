<?php
/**
 * Weekly leaderboard section of the My Account page (Phase A6).
 *
 * The top learners by XP earned this week (Monday-Sunday, site time), from
 * Core 1.11.0's weekly_leaderboard(). Opt-in only, per my-account-design.md
 * ("Rank = position on the weekly leaderboard -- opt-in, not shown by
 * default"; "Profiles are private until the user opts in"): a learner
 * appears only after pressing Join (or ticking the profile box), and a
 * learner who hasn't joined isn't ranked or counted anywhere.
 *
 * Names shown are WordPress display names -- the learner sees exactly the
 * name they'll appear under before joining. As a safety net, anything that
 * looks like an email address is cut to the part before the @, so an
 * address can never be published even if one ends up in a display name.
 *
 * Members-only: names and photos are rendered for signed-in members only
 * (My Account, and the [efa_weekly_top] widget for members). Visitors who
 * aren't signed in only ever get public_snapshot(): ranks and XP, no names,
 * no photos, no user IDs (0.15.0).
 *
 * 0.15.0: each row carries the member's own profile photo (uploaded, or from
 * Google sign-in) -- never a Gravatar lookup; initials when there is none.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Leaderboard;

use EnglishFindersAccount\Profile\Avatar;
use EnglishFindersAccount\Profile\ProfileRepository;
use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LeaderboardController {
	/** 1.11.0 is when Core's weekly_leaderboard() (and its occurred_at index) first existed. */
	private const MIN_CORE_VERSION = '1.11.0';

	/** Rows shown. The viewer's own rank is shown separately when they're below it. */
	public const TOP = 10;

	/**
	 * @return array{opted_in:bool,public_name:string,week:array<string,mixed>,entries:list<array<string,mixed>>,participants:int,viewer:array{xp:int,rank:?int},viewer_in_list:bool}|null
	 */
	public function data_for_user( int $user_id ): ?array {
		$activity = $this->activity();
		if ( null === $activity ) {
			return null;
		}

		$opted_in = (bool) ( new ProfileRepository() )->get( $user_id, 'leaderboard_optin' );
		$board    = $activity->weekly_leaderboard( (string) ProfileRepository::meta_key( 'leaderboard_optin' ), self::TOP, $user_id, $opted_in );

		$names = $this->names_for( array_column( $board['entries'], 'user_id' ) );

		$entries        = array();
		$viewer_in_list = false;
		foreach ( $board['entries'] as $row ) {
			$name      = $names[ $row['user_id'] ] ?? __( 'A learner', 'english-finders-account' );
			$is_viewer = $row['user_id'] === $user_id;
			if ( $is_viewer ) {
				$viewer_in_list = true;
			}
			$entries[] = array(
				'rank'      => $row['rank'],
				'name'      => $name,
				'initials'  => self::initials( $name ),
				'avatar'    => self::avatar_url( (int) $row['user_id'] ),
				'xp'        => $row['xp'],
				'is_viewer' => $is_viewer,
			);
		}

		$self = $this->names_for( array( $user_id ) );

		return array(
			'opted_in'       => $opted_in,
			'public_name'    => $self[ $user_id ] ?? '',
			'public_avatar'  => self::avatar_url( $user_id ),
			'week'           => $board['week'],
			'entries'        => $entries,
			'participants'   => $board['participants'],
			'viewer'         => $board['viewer'],
			'viewer_in_list' => $viewer_in_list,
		);
	}

	/**
	 * This week's board with nothing personal in it -- for visitors who
	 * aren't signed in (0.15.0). Ranks and XP only: no names, photos or ids.
	 *
	 * @return array{v:int,updated:int,week:array{start:string,end:string,span:string,ends:int},participants:int,rows:list<array{rank:int,xp:int}>}|null
	 */
	public function public_snapshot( int $limit = self::TOP ): ?array {
		$activity = $this->activity();
		if ( null === $activity ) {
			return null;
		}

		$board = $activity->weekly_leaderboard( (string) ProfileRepository::meta_key( 'leaderboard_optin' ), max( 1, min( self::TOP, $limit ) ) );
		$week  = $board['week'];
		$rows  = array();
		foreach ( $board['entries'] as $row ) {
			$rows[] = array(
				'rank' => (int) $row['rank'],
				'xp'   => (int) $row['xp'],
			);
		}

		return array(
			'v'            => 1,
			'updated'      => time(),
			'week'         => array(
				'start' => (string) $week['start'],
				'end'   => (string) $week['end'],
				'span'  => self::week_span( $week ),
				// When the board resets (Monday 00:00 site time), as a Unix timestamp.
				'ends'  => (int) strtotime( (string) $week['to_gmt'] . ' UTC' ),
			),
			'participants' => (int) $board['participants'],
			'rows'         => $rows,
		);
	}

	/** "21 Sep – 27 Sep" @param array<string,mixed> $week */
	public static function week_span( array $week ): string {
		return mysql2date( 'j M', (string) $week['start'] ) . ' – ' . mysql2date( 'j M', (string) $week['end'] );
	}

	/** The member's own photo, or '' (initials are shown instead). */
	public static function avatar_url( int $user_id ): string {
		return $user_id > 0 ? ( new Avatar() )->url( $user_id ) : '';
	}

	private function activity(): ?\EnglishFindersCore\Activity\ActivityRecorder {
		if ( ! class_exists( '\\EnglishFindersCore\\Support\\Api' ) || ! Api::is_at_least( self::MIN_CORE_VERSION ) ) {
			return null;
		}

		$activity = Api::service( 'activity' );

		return $activity instanceof \EnglishFindersCore\Activity\ActivityRecorder && method_exists( $activity, 'weekly_leaderboard' ) ? $activity : null;
	}

	/** First letters of up to the first two words -- same rule as the account header avatar. */
	public static function initials( string $name ): string {
		$words = array_values( array_filter( explode( ' ', trim( $name ) ) ) );
		$out   = '';
		foreach ( array_slice( $words, 0, 2 ) as $word ) {
			$first = mb_substr( $word, 0, 1 );
			// WordPress polyfills mb_substr() but not mb_strtoupper(); the live host has mbstring, this just keeps the board working if it ever doesn't.
			$out  .= function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first ) : strtoupper( $first );
		}

		return '' !== $out ? $out : '?';
	}

	/** A display name made safe to publish: never an email address. */
	public static function public_name( string $display_name ): string {
		$name = trim( $display_name );
		if ( str_contains( $name, '@' ) ) {
			$name = trim( (string) strstr( $name, '@', true ) );
		}

		return mb_substr( $name, 0, 40 );
	}

	/**
	 * @param list<int> $ids
	 * @return array<int,string> user id => publishable name
	 */
	private function names_for( array $ids ): array {
		$ids = array_values( array_filter( array_map( 'intval', $ids ), static fn ( int $id ): bool => $id > 0 ) );
		if ( array() === $ids ) {
			return array();
		}

		$users = get_users(
			array(
				'include' => $ids,
				'fields'  => array( 'ID', 'display_name' ),
			)
		);

		$out = array();
		foreach ( (array) $users as $user ) {
			$name = self::public_name( (string) $user->display_name );
			if ( '' !== $name ) {
				$out[ (int) $user->ID ] = $name;
			}
		}

		return $out;
	}
}
