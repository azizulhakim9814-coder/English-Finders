<?php
/**
 * The weekly board as a small static JSON file, for visitors who aren't
 * signed in (0.15.0).
 *
 * Why a file: pages seen by logged-out visitors are cached by LiteSpeed for
 * up to 7 days, so numbers printed into them go stale; and this host's CPU
 * limit is mostly spent on PHP page loads, so an endpoint hit on every guest
 * page view would be expensive. The web server hands this file out with no
 * PHP at all, and the [efa_weekly_top] widget fetches it on load.
 *
 * What's in it: LeaderboardController::public_snapshot() -- ranks and XP
 * only. No names, photos or user ids, so it is safe to be public.
 *
 * When it's rewritten: XP is only ever earned by signed-in learners, whose
 * requests always run PHP, so at the end of a signed-in request the file is
 * refreshed if it is older than THROTTLE seconds; joining or leaving the
 * board refreshes it straight away. At most one ranking query every two
 * minutes, however busy the site is.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Leaderboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PublicBoardFile {
	public const DIR  = 'efa-public';
	public const FILE = 'weekly-top.json';

	/** Seconds between refreshes. */
	public const THROTTLE = 120;

	public function register_hooks(): void {
		add_action( 'shutdown', array( $this, 'maybe_refresh' ) );
	}

	/** End of a signed-in request: refresh if the file is missing or older than THROTTLE. */
	public function maybe_refresh(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$age = self::age();
		if ( null !== $age && $age < self::THROTTLE ) {
			return;
		}

		self::refresh();
	}

	/** Writes the file now. False when Core's board isn't available or the write fails. */
	public static function refresh(): bool {
		$data = ( new LeaderboardController() )->public_snapshot( LeaderboardController::TOP );
		if ( null === $data ) {
			return false;
		}

		$dir = self::dir();
		if ( '' === $dir || ! wp_mkdir_p( $dir ) ) {
			return false;
		}
		if ( ! is_file( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- no directory listing.
		}

		$json = wp_json_encode( $data );
		if ( ! is_string( $json ) ) {
			return false;
		}

		// Write then rename, so a visitor never fetches a half-written file.
		$tmp = $dir . '/.' . self::FILE . '.' . wp_generate_password( 8, false ) . '.tmp';
		if ( false === file_put_contents( $tmp, $json, LOCK_EX ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- small public cache file in uploads.
			return false;
		}
		if ( ! @rename( $tmp, self::path() ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename -- a failed rename just leaves the previous file.
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			return false;
		}

		return true;
	}

	/**
	 * The current snapshot for a server-side render: from the file when it is
	 * fresh, otherwise rebuilt (and saved) now.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function read(): ?array {
		$age = self::age();
		if ( null === $age || $age >= self::THROTTLE ) {
			self::refresh();
		}

		$raw  = is_file( self::path() ) ? (string) file_get_contents( self::path() ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		$data = '' !== $raw ? json_decode( $raw, true ) : null;

		return is_array( $data ) && isset( $data['rows'], $data['week'] ) ? $data : ( new LeaderboardController() )->public_snapshot( LeaderboardController::TOP );
	}

	/** Public URL of the file (scheme matches the page). */
	public static function url(): string {
		$uploads = wp_upload_dir( null, false );

		return set_url_scheme( trailingslashit( (string) $uploads['baseurl'] ) . self::DIR . '/' . self::FILE );
	}

	public static function path(): string {
		return self::dir() . '/' . self::FILE;
	}

	/** Seconds since the file was written, or null when there is none. */
	private static function age(): ?int {
		clearstatcache( true, self::path() );
		$mtime = is_file( self::path() ) ? filemtime( self::path() ) : false;

		return false === $mtime ? null : max( 0, time() - (int) $mtime );
	}

	private static function dir(): string {
		$uploads = wp_upload_dir( null, false );

		return empty( $uploads['basedir'] ) ? '' : untrailingslashit( (string) $uploads['basedir'] ) . '/' . self::DIR;
	}
}
