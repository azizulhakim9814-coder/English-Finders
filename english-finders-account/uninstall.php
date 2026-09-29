<?php
/**
 * Uninstall handler.
 *
 * Runs only when the user explicitly deletes the plugin — not on
 * deactivation. Profile usermeta is only removed if the (currently
 * settings-screen-less, but future-proofed) delete_data_on_uninstall
 * option has been explicitly set — the safe default is to keep every
 * user's profile data even if this plugin is removed, since deleting it
 * would otherwise happen without anyone having chosen to.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$efa_settings = get_option( 'efa_settings', array() );
$efa_purge    = is_array( $efa_settings ) && ! empty( $efa_settings['delete_data_on_uninstall'] );

delete_option( 'efa_settings' );

if ( ! $efa_purge ) {
	return;
}

global $wpdb;

// One query across every user rather than a per-user loop -- efficient
// regardless of how many accounts exist.
$efa_meta_keys = array(
	'efa_native_language',
	'efa_daily_goal_minutes',
	'efa_daily_goal',
	'efa_public_profile',
	'efa_leaderboard_optin',
	'efa_notifications_enabled',
);

$efa_placeholders = implode( ',', array_fill( 0, count( $efa_meta_keys ), '%s' ) );

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders are built above and passed through prepare() immediately below.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ({$efa_placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$efa_meta_keys
	)
);
