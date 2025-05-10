# Unique_Visitor_Counter_AbrarIT

=== Unique Visitor Tracker ===
Contributors: abrarwebdev
Donate link:  
Tags: visitor, analytics, tracker, post visits, unique ip, graph, footer widget
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track unique visitors by IP, record visits per post, display stats in the admin panel, and show total visitors publicly in your footer with a shortcode.

== Description ==

**Unique Visitor Tracker** is a lightweight and privacy-aware plugin that helps you track **unique visitors** to your WordPress site based on IP addresses.

### 🔧 Features:

- Automatically creates a visitor tracking table on activation
- Tracks unique visitors using IP addresses
- Records visits **per post or page**
- Prevents duplicate entries for the same user/IP and post
- Adds a new admin menu for viewing all visitor logs
- Includes a shortcode `[total_visitors]` to display total visits
- Automatically displays total visitor count in the **site footer**
- Fully compatible with any theme
- No cookies or external tracking required

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory or install via the Plugins menu in WordPress.
2. Activate the plugin through the "Plugins" menu.
3. The plugin will automatically create the necessary database table.
4. Visit **Admin > Visitor Tracker** to see logs.
5. Use the shortcode `[total_visitors]` to display total count anywhere.
6. Total visitor count will also appear automatically in the page footer.

== Shortcodes ==

- `[total_visitors]`  
  Displays total unique visitor count. Can be added in posts, pages, or widgets.

== Screenshots ==

1. Admin panel showing visitor logs
2. Frontend footer showing total visitors

== Frequently Asked Questions ==

= Does it track repeat visits? =  
No. Each visitor is recorded once per post based on their IP address.

= Does this plugin use cookies or external scripts? =  
No. It is 100% self-contained and privacy-friendly.

= How is a "unique" visitor defined? =  
A unique visitor is identified by their IP address and the post they visit.

= Can I remove the auto footer output? =  
Yes. Simply comment out or remove the `add_action('wp_footer', ...)` line in the plugin file.

== Changelog ==

= 1.0 =

- Initial release with post-wise visitor tracking and shortcode support

== Upgrade Notice ==

= 1.0 =
Initial stable version

== Author ==

Developed by **M Ali Abrar Khan**
