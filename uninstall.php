<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package Unique_Visitor_Counter_AbrarIT
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$table = $wpdb->prefix . 'unique_visitors';

// Delete the plugin's custom database table
$wpdb->query("DROP TABLE IF EXISTS $table");

// Optionally, delete other plugin options if any were added
// delete_option('uvt_some_option'); // Uncomment and update if needed
