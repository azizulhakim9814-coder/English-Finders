<?php
/**
 * Uninstall handler.
 *
 * Runs only when the user explicitly deletes the plugin — not on deactivation.
 * This is the one place Core destroys data, and it is guarded by an opt-in
 * setting so deleting the plugin does not silently discard a dictionary that
 * may have taken hours to import.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$efs_settings = get_option( 'efs_settings', array() );
$efs_purge    = is_array( $efs_settings ) && ! empty( $efs_settings['delete_data_on_uninstall'] );

if ( ! $efs_purge ) {
	return;
}

global $wpdb;

// Table names are prefix-derived constants, never user input.
$efs_tables = array(
	$wpdb->prefix . 'efs_migrations',
);

foreach ( $efs_tables as $efs_table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$efs_table}" );
}

delete_option( 'efs_settings' );
delete_option( 'efs_db_version' );
