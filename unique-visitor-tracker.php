<?php

/**
 * Plugin Name: Unique Visitor Tracker
 * Description: Tracks unique visitors and displays stats.
 * Version: 1.0
 * Author: M Ali Abrar Khan
 */

register_activation_hook(__FILE__, 'uvt_create_table');

function uvt_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'unique_visitors';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        post_id BIGINT(20) UNSIGNED,
        visit_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY ip_post (ip_address, post_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('wp', 'uvt_track_visitor');

function uvt_track_visitor()
{
    if (is_singular()) {
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'];
        $post_id = get_the_ID();
        $table_name = $wpdb->prefix . 'unique_visitors';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE ip_address = %s AND post_id = %d",
            $ip,
            $post_id
        ));

        if (!$exists) {
            $wpdb->insert($table_name, [
                'ip_address' => $ip,
                'post_id' => $post_id,
            ]);
        }
    }
}

// Admin menu to view visitor data
add_action('admin_menu', 'uvt_admin_menu');
function uvt_admin_menu()
{
    add_menu_page(
        'Visitor Tracker',
        'Visitor Tracker',
        'manage_options',
        'visitor-tracker',
        'uvt_admin_page',
        'dashicons-visibility',
        20
    );
}

function uvt_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'unique_visitors';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY visit_time DESC");

    echo "<div class='wrap'><h2>Unique Visitor Logs</h2><table class='widefat'>
    <thead><tr><th>ID</th><th>IP Address</th><th>Post ID</th><th>Post Title</th><th>Visit Time</th></tr></thead><tbody>";

    foreach ($results as $row) {
        echo "<tr>
            <td>{$row->id}</td>
            <td>{$row->ip_address}</td>
            <td>{$row->post_id}</td>
            <td><a href='" . get_permalink($row->post_id) . "'>" . get_the_title($row->post_id) . "</a></td>
            <td>{$row->visit_time}</td>
        </tr>";
    }

    echo "</tbody></table></div>";
}

// Shortcode to show total visitor count
add_shortcode('total_visitors', 'uvt_total_visitors');
function uvt_total_visitors()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'unique_visitors';
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    return "<p style='text-align:center;'>Total Visitors: <strong>$total</strong></p>";
}

// Auto-display visitor count in the footer
add_action('wp_footer', 'uvt_display_footer_count');
function uvt_display_footer_count()
{
    echo do_shortcode('[total_visitors]');
}


// Shortcode to show total visitor count with design
add_shortcode('total_visitors', 'uvt_total_visitors');
function uvt_total_visitors()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'unique_visitors';
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    ob_start(); ?>
    <style>
        .uvt-footer-box {
            background: #1e1e1e;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            display: inline-block;
            font-family: sans-serif;
        }

        .uvt-footer-box .uvt-icon {
            background: #c62828;
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .uvt-footer-box .uvt-icon svg {
            width: 24px;
            height: 24px;
            fill: #fff;
        }

        .uvt-footer-box .uvt-label {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .uvt-footer-box .uvt-count {
            font-size: 24px;
            font-weight: bold;
        }
    </style>

    <div class="uvt-footer-box">
        <div class="uvt-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path fill="white" d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 1.6c-3.2 0-9.6 1.6-9.6 4.9V22h19.2v-3.5c0-3.3-6.4-4.9-9.6-4.9z" />
            </svg>
        </div>
        <div class="uvt-label">Visitors</div>
        <div class="uvt-count"><?php echo esc_html($total); ?></div>
    </div>
<?php
    return ob_get_clean();
}
