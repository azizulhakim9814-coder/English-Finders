<?php
/**
 * The one entry point any plugin should use to record activity.
 *
 * Word Games Pro, English Finders Study, and eventually the Tutor/QSM
 * integrations all call record_event() here rather than writing to
 * activity_events themselves -- the same "one shared service, not one
 * per consumer" role AudioResolver and EntitlementRepository already play.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivityRecorder {
	/**
	 * Freezes granted to a brand-new user, applied the first time
	 * record_event() creates their stats row. A policy placeholder, not a
	 * researched number -- see a2-activity-xp-streak.md's open decisions.
	 */
	private const STARTING_FREEZES = 2;

	/**
	 * 1.13.0: Pro members' streak freezes are topped up to this many once a
	 * calendar month (free members keep the one-time STARTING_FREEZES).
	 * Filter: efc_pro_monthly_streak_freezes.
	 */
	public const PRO_MONTHLY_FREEZES = 5;

	/** User meta holding the month ('Y-m') of the last Pro top-up. */
	public const PRO_REFILL_META = 'efc_pro_freeze_month';

	/**
	 * Fixed XP per event type. Placeholder values pending a real tuning
	 * pass once there is usage data -- see a2-activity-xp-streak.md. An
	 * event type not in this table earns 0 XP rather than failing, so a
	 * new source can call record_event() before its XP value is decided.
	 */
	private const XP_TABLE = array(
		'game_completed'       => 10,
		'quiz_completed'       => 15,
		'lesson_completed'     => 20,
		/*
		 * Added for A2 step 4 (Tutor LMS, 2026-09-22). A course is a much
		 * bigger unit of work than a single lesson -- deliberately a large
		 * milestone value, not a small increment. Placeholder, same as
		 * every other value in this table.
		 */
		'course_completed'     => 100,
		/*
		 * Added for A2 step 3 (English Finders Study, 2026-09-22). EFS's
		 * quiz tools score one question per request with no server-side
		 * session concept -- there is no "quiz_completed" moment to hook.
		 * This fires per correct answer instead, at a deliberately smaller
		 * value than quiz_completed so a full session doesn't dwarf a
		 * Wordle win. See a2-activity-xp-streak.md.
		 */
		'quiz_answer_correct'  => 1,
		/*
		 * 1.14.0: one XP currency everywhere -- 1 XP per correct answer.
		 * Tutor LMS and QSM quizzes now credit quiz_answer_correct per
		 * correct answer (quiz_completed stays in the table only so older
		 * events keep their meaning). Word Games Pro: a correct answer or an
		 * accepted word is game_answer_correct; a whole puzzle solved
		 * (Wordle, Hangman, Word Ladder, Daily Unscramble) is game_solved;
		 * a puzzle played to the end without solving it is game_finished.
		 * game_completed (10) is the pre-1.14.0 Wordle value, kept for history.
		 */
		'game_answer_correct'  => 1,
		'game_solved'          => 5,
		'game_finished'        => 1,
	);

	/**
	 * 1.14.0: most XP one user can earn from a source in one site day.
	 * Games are quick to repeat and feed the weekly leaderboard, so they are
	 * capped; lessons, courses, quizzes and the Study tools are not. Events
	 * past the cap are still recorded (they count as activity for the
	 * streak), with 0 XP and `capped` in their metadata.
	 * Filter: efc_daily_xp_caps (array source => cap; 0 or missing = none).
	 */
	public const DAILY_XP_CAPS = array( 'wgp' => 200 );

	private ActivityRepository $repository;
	private StreakCalculator $streak_calculator;

	/** @var (callable(int): bool)|null Whether a user has Pro (1.13.0); null = nobody does. */
	private $is_pro;

	public function __construct( ?ActivityRepository $repository = null, ?StreakCalculator $streak_calculator = null, ?callable $is_pro = null ) {
		$this->repository        = $repository ?? new ActivityRepository();
		$this->streak_calculator = $streak_calculator ?? new StreakCalculator();
		$this->is_pro            = $is_pro;
	}

	/**
	 * Record one activity event, updating XP, streak, and badges in the
	 * same call. Silently ignores non-positive user ids rather than
	 * throwing -- a caller passing 0 (no logged-in user) is a normal case
	 * for anonymous activity, not a bug to surface as an error.
	 *
	 * 1.14.0: $quantity credits several of the same unit in one event (a
	 * quiz with 9 correct answers is one event worth 9 XP), and sources in
	 * DAILY_XP_CAPS are clamped to their daily cap.
	 *
	 * @param array<string,mixed> $metadata Display-only detail (which game/quiz/lesson); never read back into XP or streak logic.
	 */
	public function record_event( int $user_id, string $source, string $event_type, array $metadata = array(), int $quantity = 1 ): void {
		if ( $user_id <= 0 || $quantity <= 0 ) {
			return;
		}

		$xp    = ( self::XP_TABLE[ $event_type ] ?? 0 ) * $quantity;
		$now   = current_time( 'mysql', true );
		$today = current_time( 'Y-m-d' );

		if ( $quantity > 1 ) {
			$metadata['quantity'] = $quantity;
		}

		$allowed = $this->xp_under_daily_cap( $user_id, $source, $xp, $today );
		if ( $allowed < $xp ) {
			$metadata['capped'] = $xp - $allowed;
			$xp                 = $allowed;
		}

		$this->repository->insert_event( $user_id, $source, $event_type, $xp, $metadata, $now );

		$existing_row = $this->repository->get_stats( $user_id );
		$stats        = is_array( $existing_row )
			? UserStats::from_row( $existing_row )
			: UserStats::zero( $user_id, self::STARTING_FREEZES );
		// 1.13.0: Pro's monthly top-up comes first, so a gap since the last visit can use it.
		$stats        = $this->with_pro_refill( $stats, $today, true );

		$streak = $this->streak_calculator->apply( $stats, $today );
		$total_xp = $stats->total_xp + $xp;

		$this->repository->upsert_stats(
			$user_id,
			$total_xp,
			$streak['current_streak_days'],
			$streak['longest_streak_days'],
			$streak['last_active_date'],
			$streak['streak_freezes_available']
		);

		$updated_stats = UserStats::from_row(
			array(
				'user_id'                  => $user_id,
				'total_xp'                 => $total_xp,
				'current_streak_days'      => $streak['current_streak_days'],
				'longest_streak_days'      => $streak['longest_streak_days'],
				'last_active_date'         => $streak['last_active_date'],
				'streak_freezes_available' => $streak['streak_freezes_available'],
			)
		);

		$already_earned = $this->repository->badges_for_user( $user_id );

		foreach ( BadgeCatalog::newly_earned( $updated_stats, $already_earned ) as $badge_code ) {
			$this->repository->award_badge( $user_id, $badge_code, $now );
		}
	}

	/**
	 * XP and event count for each of the last $days calendar days (site
	 * timezone), oldest first, today last -- every day present, zeros for
	 * inactive days. Feeds the daily goal and the streak calendar (1.10.0).
	 *
	 * @return array<string,array{xp:int,events:int}> keyed by Y-m-d.
	 */
	public function daily_totals( int $user_id, int $days = 35 ): array {
		$days  = max( 1, min( 366, $days ) );
		$today = current_time( 'Y-m-d' );
		$first = gmdate( 'Y-m-d', strtotime( $today . ' UTC' ) - ( $days - 1 ) * 86400 );

		$out = array();
		for ( $i = 0; $i < $days; $i++ ) {
			$out[ gmdate( 'Y-m-d', strtotime( $first . ' UTC' ) + $i * 86400 ) ] = array(
				'xp'     => 0,
				'events' => 0,
			);
		}

		if ( $user_id <= 0 ) {
			return $out;
		}

		$offset   = self::site_offset_seconds();
		$from_gmt = gmdate( 'Y-m-d H:i:s', strtotime( $first . ' 00:00:00 UTC' ) - $offset );

		foreach ( $this->repository->daily_xp( $user_id, $from_gmt, $offset ) as $day => $totals ) {
			if ( isset( $out[ $day ] ) ) {
				$out[ $day ] = $totals;
			}
		}

		return $out;
	}

	/**
	 * The current leaderboard week: Monday 00:00 to the next Monday 00:00 in
	 * site time, plus the same bounds in UTC for querying (1.11.0). The week
	 * is the same calendar week the streak calendar shows.
	 *
	 * @return array{start:string,end:string,from_gmt:string,to_gmt:string,days_left:int}
	 */
	public function current_week(): array {
		$today    = current_time( 'Y-m-d' );
		$today_ts = strtotime( $today . ' UTC' );
		$weekday  = (int) gmdate( 'N', $today_ts ); // 1 = Monday.
		$monday   = $today_ts - ( $weekday - 1 ) * 86400;
		$offset   = self::site_offset_seconds();

		return array(
			'start'     => gmdate( 'Y-m-d', $monday ),
			'end'       => gmdate( 'Y-m-d', $monday + 6 * 86400 ),
			'from_gmt'  => gmdate( 'Y-m-d H:i:s', $monday - $offset ),
			'to_gmt'    => gmdate( 'Y-m-d H:i:s', $monday + 7 * 86400 - $offset ),
			'days_left' => 7 - $weekday,
		);
	}

	/**
	 * This week's leaderboard (1.11.0): the top $limit opted-in learners by
	 * XP earned this week, with competition ranking (equal XP = equal rank,
	 * the next rank skips: 1, 2, 2, 4), and the viewer's own weekly XP and
	 * rank.
	 *
	 * $optin_meta_key names the usermeta flag that opts a learner in (owned
	 * by English Finders Account). The viewer's rank is only computed when
	 * $viewer_opted_in is true -- someone who hasn't joined isn't ranked,
	 * and isn't counted, anywhere.
	 *
	 * @return array{week:array<string,mixed>,entries:list<array{user_id:int,xp:int,rank:int}>,participants:int,viewer:array{xp:int,rank:?int}}
	 */
	public function weekly_leaderboard( string $optin_meta_key, int $limit = 10, int $viewer_id = 0, bool $viewer_opted_in = false ): array {
		$week = $this->current_week();
		$rows = $this->repository->xp_ranking( $week['from_gmt'], $week['to_gmt'], $optin_meta_key, $limit );

		$entries   = array();
		$prev_xp   = null;
		$prev_rank = 0;
		foreach ( $rows as $i => $row ) {
			$rank      = ( $row['xp'] === $prev_xp ) ? $prev_rank : $i + 1;
			$entries[] = $row + array( 'rank' => $rank );
			$prev_xp   = $row['xp'];
			$prev_rank = $rank;
		}

		/*
		 * A tie that crosses the cut-off: the last shown row's rank is only
		 * right if nobody outside the list has more XP, which the ORDER BY
		 * guarantees -- so no correction is needed there.
		 */
		$viewer_xp   = $viewer_id > 0 ? $this->repository->xp_between( $viewer_id, $week['from_gmt'], $week['to_gmt'] ) : 0;
		$viewer_rank = null;
		if ( $viewer_opted_in && $viewer_xp > 0 ) {
			$viewer_rank = 1 + $this->repository->count_ranked_above( $week['from_gmt'], $week['to_gmt'], $optin_meta_key, $viewer_xp );
		}

		return array(
			'week'         => $week,
			'entries'      => $entries,
			'participants' => $this->repository->count_ranked_above( $week['from_gmt'], $week['to_gmt'], $optin_meta_key, 0 ),
			'viewer'       => array(
				'xp'   => $viewer_xp,
				'rank' => $viewer_rank,
			),
		);
	}

	/**
	 * The site timezone's current UTC offset in seconds (englishfinders.com:
	 * a fixed +06:00). Uses WordPress's own timezone when available.
	 */
	private static function site_offset_seconds(): int {
		if ( function_exists( 'wp_timezone' ) ) {
			return (int) wp_timezone()->getOffset( new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) ) );
		}

		return function_exists( 'get_option' ) ? (int) round( (float) get_option( 'gmt_offset', 0 ) * 3600 ) : 0;
	}

	public function stats_for_user( int $user_id ): UserStats {
		$row = $this->repository->get_stats( $user_id );

		$stats = is_array( $row ) ? UserStats::from_row( $row ) : UserStats::zero( $user_id, self::STARTING_FREEZES );
		$today = current_time( 'Y-m-d' );

		// Shows a due Pro top-up straight away; it is saved with the next activity.
		$stats = $this->with_pro_refill( $stats, $today, false );

		/*
		 * 1.14.0: the stored streak only changes when the learner is next
		 * active, so on its own it keeps showing a streak that has already
		 * broken. Read it the way the next activity would: a gap the
		 * learner's freezes can cover still counts (StreakCalculator uses
		 * them then); a longer gap reads as 0. Nothing is written here.
		 */
		if ( null !== $stats->last_active_date && $stats->current_streak_days > 0 && $today > $stats->last_active_date ) {
			$skipped = (int) round( ( strtotime( $today . ' UTC' ) - strtotime( $stats->last_active_date . ' UTC' ) ) / 86400 ) - 1;
			if ( $skipped > $stats->streak_freezes_available ) {
				$stats = $stats->with_current_streak( 0 );
			}
		}

		return $stats;
	}

	/**
	 * Credit XP for work done earlier (1.14.0: missed Tutor quiz attempts).
	 * The event keeps its real time, and total XP and badges are updated.
	 * The streak only moves if that day is later than the learner's last
	 * recorded activity (or they have none) -- they really were active that
	 * day. A day at or before it changes nothing (StreakCalculator ignores
	 * it), so a backfill never resets or re-counts a streak that has moved
	 * on. Not capped: it is for one-off corrections, not a live source.
	 *
	 * @param array<string,mixed> $metadata
	 */
	public function record_backdated( int $user_id, string $source, string $event_type, int $quantity, array $metadata, string $occurred_gmt ): void {
		if ( $user_id <= 0 || $quantity <= 0 ) {
			return;
		}

		$xp = ( self::XP_TABLE[ $event_type ] ?? 0 ) * $quantity;
		$metadata['quantity']   = $quantity;
		$metadata['backfilled'] = true;
		$this->repository->insert_event( $user_id, $source, $event_type, $xp, $metadata, $occurred_gmt );

		$row    = $this->repository->get_stats( $user_id );
		$stats  = is_array( $row ) ? UserStats::from_row( $row ) : UserStats::zero( $user_id, self::STARTING_FREEZES );
		$day    = gmdate( 'Y-m-d', strtotime( $occurred_gmt . ' UTC' ) + self::site_offset_seconds() );
		$streak = $this->streak_calculator->apply( $stats, $day );

		$this->repository->upsert_stats(
			$user_id,
			$stats->total_xp + $xp,
			$streak['current_streak_days'],
			$streak['longest_streak_days'],
			$streak['last_active_date'],
			$streak['streak_freezes_available']
		);

		$updated = UserStats::from_row(
			array(
				'user_id'                  => $user_id,
				'total_xp'                 => $stats->total_xp + $xp,
				'current_streak_days'      => $streak['current_streak_days'],
				'longest_streak_days'      => $streak['longest_streak_days'],
				'last_active_date'         => $streak['last_active_date'],
				'streak_freezes_available' => $streak['streak_freezes_available'],
			)
		);
		$now = current_time( 'mysql', true );
		foreach ( BadgeCatalog::newly_earned( $updated, $this->repository->badges_for_user( $user_id ) ) as $badge_code ) {
			$this->repository->award_badge( $user_id, $badge_code, $now );
		}
	}

	/**
	 * How much of $xp this user may still earn from $source today (site
	 * time). Uncapped sources get all of it.
	 */
	private function xp_under_daily_cap( int $user_id, string $source, int $xp, string $today ): int {
		if ( $xp <= 0 ) {
			return $xp;
		}

		$caps = self::DAILY_XP_CAPS;
		if ( function_exists( 'apply_filters' ) ) {
			$caps = (array) apply_filters( 'efc_daily_xp_caps', $caps );
		}
		$cap = (int) ( $caps[ $source ] ?? 0 );
		if ( $cap <= 0 ) {
			return $xp;
		}

		$offset = self::site_offset_seconds();
		$start  = strtotime( $today . ' 00:00:00 UTC' ) - $offset;
		$earned = $this->repository->xp_for_source_between(
			$user_id,
			$source,
			gmdate( 'Y-m-d H:i:s', $start ),
			gmdate( 'Y-m-d H:i:s', $start + 86400 )
		);

		return max( 0, min( $xp, $cap - $earned ) );
	}

	/**
	 * Pro members get their streak freezes topped up to PRO_MONTHLY_FREEZES
	 * once per calendar month (site time). Never lowers a higher count.
	 * $persist: record the month (record_event), or only show it (reads).
	 */
	public function with_pro_refill( UserStats $stats, string $today, bool $persist ): UserStats {
		if ( $stats->user_id <= 0 || null === $this->is_pro || ! ( $this->is_pro )( $stats->user_id ) ) {
			return $stats;
		}

		$month = substr( $today, 0, 7 );
		if ( $month === (string) get_user_meta( $stats->user_id, self::PRO_REFILL_META, true ) ) {
			return $stats;
		}

		$target = max( 0, (int) apply_filters( 'efc_pro_monthly_streak_freezes', self::PRO_MONTHLY_FREEZES, $stats->user_id ) );
		if ( $persist ) {
			update_user_meta( $stats->user_id, self::PRO_REFILL_META, $month );
		}

		return $stats->streak_freezes_available < $target ? $stats->with_freezes( $target ) : $stats;
	}

	/** @return list<string> */
	public function badges_for_user( int $user_id ): array {
		return $this->repository->badges_for_user( $user_id );
	}
}
