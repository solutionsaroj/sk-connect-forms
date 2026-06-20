<?php
/**
 * Uninstallation cleanup script.
 * Runs only when user clicks 'Delete' in the WordPress Plugins screen.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * @package SKConnectForms
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 1. Drop Custom Tables
global $wpdb;
$sk_connect_submissions_table = esc_sql( $wpdb->prefix . 'sk_connect_submissions' );
$sk_connect_forms_table       = esc_sql( $wpdb->prefix . 'sk_connect_forms' );

// phpcs:disable WordPress.DB.DirectDatabaseQuery
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query("DROP TABLE IF EXISTS {$sk_connect_submissions_table}");
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query("DROP TABLE IF EXISTS {$sk_connect_forms_table}");
// phpcs:enable WordPress.DB.DirectDatabaseQuery

// 2. Delete Registered Options
delete_option('sk_connect_forms_db_version');
delete_option('sk_connect_forms_recipient_email');
delete_option('sk_connect_forms_success_message');
delete_option('sk_connect_forms_primary_color');
