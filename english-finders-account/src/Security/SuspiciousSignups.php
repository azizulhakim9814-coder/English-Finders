<?php
/**
 * Users -> Suspicious sign-ups (0.16.0): accounts that look like spam, for an
 * admin to review. Nothing here deletes anything -- the selection is handed
 * to WordPress's own "Delete Users" confirmation screen, where the admin
 * decides.
 *
 * Listed only with hard evidence, never on a hunch:
 *  - links in their profile (Tutor bio / job title / social fields, the WP
 *    biography, or the website field), or
 *  - a pending Tutor instructor application.
 * And never anyone showing real use: staff, approved instructors, accounts
 * with any XP, a course enrolment or an approved comment, or accounts made
 * through /sign-up/ or Google (which have their own spam checks).
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SuspiciousSignups {
	public const PAGE = 'efa-suspicious-signups';

	public const PER_PAGE = 100;

	/** Profile fields spam accounts fill with links. */
	public const LINK_META_KEYS = array( '_tutor_profile_bio', '_tutor_profile_job_title', '_tutor_profile_website', 'description', 'facebook', 'twitter', 'linkedin', 'website', 'github', 'additional_profile_urls' );

	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	public function menu(): void {
		add_users_page(
			__( 'Suspicious sign-ups', 'english-finders-account' ),
			__( 'Suspicious sign-ups', 'english-finders-account' ),
			'delete_users',
			self::PAGE,
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'delete_users' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to view this page.', 'english-finders-account' ) );
		}

		$rows  = $this->find();
		$total = count( $rows );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only paging.
		$paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		$pages = max( 1, (int) ceil( $total / self::PER_PAGE ) );
		$paged = min( $paged, $pages );
		$rows  = array_slice( $rows, ( $paged - 1 ) * self::PER_PAGE, self::PER_PAGE );

		include EFA_PATH . 'templates/admin/suspicious-signups.php';
	}

	/**
	 * Every listed account, newest first.
	 *
	 * @return list<array{id:int,login:string,registered:string,domain:string,reasons:list<string>}>
	 */
	public function find(): array {
		$out = array();
		foreach ( $this->candidate_ids() as $id ) {
			$row = $this->assess( $id );
			if ( null !== $row ) {
				$out[] = $row;
			}
		}
		usort( $out, static fn ( array $a, array $b ): int => strcmp( $b['registered'], $a['registered'] ) ?: $b['id'] <=> $a['id'] );

		return $out;
	}

	/**
	 * Why this account is listed, or null when it isn't.
	 *
	 * @return array{id:int,login:string,registered:string,domain:string,reasons:list<string>}|null
	 */
	public function assess( int $user_id ): ?array {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User || $this->shows_real_use( $user ) ) {
			return null;
		}

		$reasons = array();
		$linked  = $this->linked_fields( $user );
		if ( array() !== $linked ) {
			/* translators: %s: profile field names */
			$reasons[] = sprintf( __( 'Links in profile: %s', 'english-finders-account' ), implode( ', ', $linked ) );
		}
		if ( 'pending' === get_user_meta( $user_id, '_tutor_instructor_status', true ) ) {
			$reasons[] = __( 'Pending instructor application', 'english-finders-account' );
		}
		if ( array() === $reasons ) {
			return null;
		}
		if ( self::looks_generated( (string) $user->user_login ) ) {
			$reasons[] = __( 'Generated-looking username', 'english-finders-account' );
		}
		$reasons[] = __( 'No learning activity, enrolments or comments', 'english-finders-account' );

		$at = strrchr( (string) $user->user_email, '@' );

		return array(
			'id'         => $user_id,
			'login'      => (string) $user->user_login,
			'registered' => (string) $user->user_registered,
			'domain'     => false !== $at ? strtolower( substr( $at, 1 ) ) : '',
			'reasons'    => $reasons,
		);
	}

	/** e.g. "vnhmdavid19", "user48213", "k3j9x0q2m7w1z8" -- a hint, never a reason on its own. */
	public static function looks_generated( string $login ): bool {
		return 1 === preg_match( '/^[a-z]{3,}\d{2,}$|\d{5,}|^[a-z0-9]{12,}$/i', $login );
	}

	/** Staff, approved instructors, learners with XP / enrolments / comments, and /sign-up/ or Google accounts. */
	private function shows_real_use( \WP_User $user ): bool {
		global $wpdb;
		$id = (int) $user->ID;

		if ( user_can( $user, 'edit_posts' ) || 'approved' === get_user_meta( $id, '_tutor_instructor_status', true ) ) {
			return true;
		}
		if ( '' !== (string) get_user_meta( $id, 'efa_account_type', true ) || '' !== (string) get_user_meta( $id, 'efa_google_sub', true ) ) {
			return true;
		}

		$events = $wpdb->prefix . 'efc_activity_events';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- admin-only review screen; one existence check per listed account.
		$has_xp = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $events ) ) === $events
			&& null !== $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$events} WHERE user_id = %d LIMIT 1", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
		$enrolled = null !== $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->posts} WHERE post_type = 'tutor_enrolled' AND post_author = %d LIMIT 1", $id ) );
		$comments = null !== $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->comments} WHERE user_id = %d AND comment_approved = '1' LIMIT 1", $id ) );
		// phpcs:enable

		return $has_xp || $enrolled || $comments;
	}

	/** @return list<string> the profile fields holding a link */
	private function linked_fields( \WP_User $user ): array {
		$out = array();
		foreach ( self::LINK_META_KEYS as $key ) {
			if ( self::has_link( (string) get_user_meta( $user->ID, $key, true ) ) ) {
				$out[] = $key;
			}
		}
		if ( '' !== trim( (string) $user->user_url ) ) {
			$out[] = 'user_url';
		}

		return $out;
	}

	public static function has_link( string $value ): bool {
		return 1 === preg_match( '#https?://|www\.|\b[a-z0-9-]+\.(com|net|org|info|xyz|site|shop|online|top|io|de|ru|pl|in)\b#i', $value );
	}

	/** Accounts with a link-like profile field, a website, or a pending instructor application. @return list<int> */
	private function candidate_ids(): array {
		global $wpdb;
		$keys = implode( ',', array_fill( 0, count( self::LINK_META_KEYS ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- admin-only; placeholders built above.
		$linked  = (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ({$keys}) AND (meta_value LIKE %s OR meta_value LIKE %s OR meta_value LIKE %s)", array_merge( self::LINK_META_KEYS, array( '%http%', '%www.%', '%.%' ) ) ) );
		$website = (array) $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE user_url <> ''" );
		$pending = (array) $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s", '_tutor_instructor_status', 'pending' ) );
		// phpcs:enable

		return array_values( array_unique( array_map( 'intval', array_merge( $linked, $website, $pending ) ) ) );
	}
}
