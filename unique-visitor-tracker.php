<?php
/*
 * Plugin Name:       Unique_Visitor_Counter_AbrarIT
 * Plugin URI:        https://github.com/MAliAbrarKhan19/Unique_Visitor_Counter_AbrarIT
 * Description:       Tracks unique visitors, logs per post, and displays data with chart, footer count, and post readers.
 * Version:           1.4
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            M Ali Abrar Khan, Abrar IT
 * Author URI:        https://www.facebook.com/mkabrar1991
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       Unique_Visitor_Counter
 */

defined('ABSPATH') or die("No script kiddies please!");

// Create table on activation
register_activation_hook(__FILE__, 'uvt_create_table');
function uvt_create_table()
{
    global $wpdb;
    $table = $wpdb->prefix . 'unique_visitors';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        post_id BIGINT UNSIGNED,
        visit_date DATE DEFAULT CURRENT_DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Track unique visitors
add_action('wp', 'uvt_track_visitor');
function uvt_track_visitor()
{
    if (is_admin() || is_user_logged_in()) return;

    if (is_singular()) {
        global $wpdb, $post;
        $ip = $_SERVER['REMOTE_ADDR'];
        $post_id = isset($post->ID) ? $post->ID : 0;
        $table = $wpdb->prefix . 'unique_visitors';

        if ($post_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE ip_address = %s AND post_id = %d",
                $ip,
                $post_id
            ));

            if (!$exists) {
                $inserted = $wpdb->insert($table, [
                    'ip_address' => $ip,
                    'post_id' => $post_id,
                    'visit_date' => current_time('Y-m-d')
                ]);

                if ($inserted === false) {
                    error_log('Visitor insert failed: ' . $wpdb->last_error);
                }
            }
        }
    }
}

// Shortcode to show total visitor count with style
add_shortcode('total_visitors', 'uvt_total_visitors');
function uvt_total_visitors()
{
    global $wpdb;
    $table = $wpdb->prefix . 'unique_visitors';
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");

    ob_start(); ?>
    <style>
        .uvt-footer-box {
            background: rgb(241, 213, 213);
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            display: inline-block;
            font-family: sans-serif;
        }

        .uvt-footer-box .uvt-icon {
            background: #c62828;
            width: 80px;
            height: 80px;
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
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .uvt-footer-box .uvt-count {
            width: 100%;
            font-size: 26px;
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
        <div class="uvt-count"><?php $total=$total+3000 echo esc_html($total); ?></div>
    </div>
<?php
    return ob_get_clean();
}

add_action('get_footer', 'uvt_display_footer_count', 1);
function uvt_display_footer_count() {
    echo do_shortcode('[total_visitors]');
}

add_filter('the_content', 'uvt_show_post_readers');
function uvt_show_post_readers($content)
{
    if (is_singular('post') && in_the_loop() && is_main_query()) {
        global $wpdb;
        $post_id = get_the_ID();
        $table = $wpdb->prefix . 'unique_visitors';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ip_address) FROM $table WHERE post_id = %d",
            $post_id
        ));

        $readers_info = '<p style="font-weight: bold; color: #c62828;">Post readers: ' . esc_html($count) . '</p>';
        return $readers_info . $content;
    }
    return $content;
}

add_action('admin_menu', 'uvt_admin_menu');
function uvt_admin_menu()
{
    add_menu_page('Visitor Tracker', 'Visitor Tracker', 'manage_options', 'uvt_dashboard', 'uvt_admin_page', 'dashicons-chart-line', 20);
    add_submenu_page('uvt_dashboard', 'Visitor Logs', 'All Visitors', 'manage_options', 'uvt_dashboard', 'uvt_admin_page');
    add_submenu_page('uvt_dashboard', 'Daily Visitors', 'Daily Summary', 'manage_options', 'uvt_daily_summary', 'uvt_daily_page');
}

function uvt_admin_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'unique_visitors';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
    echo '<div class="wrap"><h2>Unique Visitor Logs</h2><table class="widefat striped"><thead><tr><th>ID</th><th>IP Address</th><th>Post ID</th><th>Visit Date</th><th>Created At</th></tr></thead><tbody>';
    foreach ($results as $row) {
        echo '<tr><td>' . esc_html($row->id) . '</td><td>' . esc_html($row->ip_address) . '</td><td>' . esc_html($row->post_id) . '</td><td>' . esc_html($row->visit_date) . '</td><td>' . esc_html($row->created_at) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function uvt_daily_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'unique_visitors';
    $results = $wpdb->get_results("SELECT visit_date, COUNT(DISTINCT ip_address) as visitors FROM $table GROUP BY visit_date ORDER BY visit_date DESC LIMIT 30");
    $dates = array_column($results, 'visit_date');
    $counts = array_column($results, 'visitors');
    echo '<div class="wrap"><h2>Daily Unique Visitors</h2><canvas id="uvtGraph" width="600" height="300"></canvas></div>';
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>const ctx=document.getElementById("uvtGraph").getContext("2d");new Chart(ctx,{type:"line",data:{labels:' . json_encode($dates) . ',datasets:[{label:"Daily Unique Visitors",data:' . json_encode($counts) . ',backgroundColor:"rgba(198,40,40,0.2)",borderColor:"rgba(198,40,40,1)",borderWidth:2,tension:0.3,fill:true,pointRadius:3}]},options:{responsive:true,scales:{y:{beginAtZero:true}}}});</script>';
}

class UVT_Visitors_Widget extends WP_Widget
{
    function __construct()
    {
        parent::__construct('uvt_visitors_widget', __('Total Visitors', 'uvt'), ['description' => __('Displays the total unique visitors.', 'uvt')]);
    }
    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        echo do_shortcode('[total_visitors]');
        echo $args['after_widget'];
    }
}
add_action('widgets_init', function () {
    register_widget('UVT_Visitors_Widget');
});
