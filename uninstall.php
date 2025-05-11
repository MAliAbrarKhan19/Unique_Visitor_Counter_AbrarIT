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

// delete_option('uvt_some_option'); // Uncomment and update if needed


// Log result
if ($result === false) {
    error_log("Unique_Visitor_Counter_AbrarIT: Failed to drop table $table. Error: " . $wpdb->last_error);
} else {
    error_log("Unique_Visitor_Counter_AbrarIT: Successfully dropped table $table.");
}
