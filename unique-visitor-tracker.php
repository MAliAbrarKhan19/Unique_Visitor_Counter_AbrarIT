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
            margin-left: auto;
            margin-right: auto;

            /* background: #1F2125; */
            color: #23BE2A;
            padding: 20px;
            text-align: center;
            border: 4px #BB1919;
            border-radius: 10px;
            display: block;
            font-family: sans-serif;
        }

        .uvt-footer-box .uvt-icon {
            background: #BB1919;
            width: 80px;
            height: 80px;
            margin: 0 auto 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .uvt-footer-box .uvt-icon svg {
            width: 60px;
            height: 60px;
            fill: #BB1919;
        }

        .uvt-footer-box .uvt-label {
            font-size: 38px;
            font-weight: 400;
            margin-bottom: 2px;
        }

        .uvt-footer-box .uvt-count {
            width: 100%;
            font-size: 46px;
            font-weight: bolder;
        }
    </style>
    <div class="uvt-footer-box">
        <div class="uvt-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                <path fill="white" d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
            </svg>
        </div>
        <div class="uvt-label">Visitors</div>
        <div class="uvt-count"><?php //$total = $total + 3000;
                                echo esc_html($total); ?></div>
    </div>
<?php
    return ob_get_clean();
}

add_action('get_footer', 'uvt_display_footer_count', 1);
function uvt_display_footer_count()
{
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


