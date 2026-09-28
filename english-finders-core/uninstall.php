<?php
/**
 * Uninstall handler.
 *
 * Runs only when the user explicitly deletes the plugin — not on deactivation.
 * This is the one place Core destroys data, and it is guarded by an opt-in
 * setting so deleting the plugin does not silently discard a dictionary that
 * may have taken hours to import.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$efc_settings = get_option( 'efc_settings', array() );
$efc_purge    = is_array( $efc_settings ) && ! empty( $efc_settings['delete_data_on_uninstall'] );

if ( ! $efc_purge ) {
	return;
}

global $wpdb;

// Table names are prefix-derived constants, never user input.
$efc_tables = array(
	$wpdb->prefix . 'efc_migrations',
);

foreach ( $efc_tables as $efc_table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$efc_table}" );
}

delete_option( 'efc_settings' );
delete_option( 'efc_db_version' );
