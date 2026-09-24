<?php
/**
 * Plugin Name:       Simple User Avatar
 * Plugin URI:        https://wordpress.org/plugins/simple-user-avatar/
 * Description:       Add a <strong>user avatar</strong> using images from your Media Library.
 * Version:           5.2
 * Requires at least: 4.0
 * Requires PHP:      8.0
 * Author:            Matteo Manna
 * Author URI:        https://matteomanna.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-user-avatar
 */

// Injection prevention
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Global defines
 *
 * @since 2.8
 */
if (!defined('SUA_PLUGIN_VERSION')) {
  define('SUA_PLUGIN_VERSION', 5.2);
}

if (!defined('SUA_USER_META_KEY')) {
  define('SUA_USER_META_KEY', 'mm_sua_attachment_id');
}

if (!defined('SUA_TRANSIENT_NAME')) {
  define('SUA_TRANSIENT_NAME', 'sua_notice_is_expired');
}

/**
 * Require classes
 *
 * @since 2.5
 */
require_once plugin_dir_path(__FILE__) . 'public/class-sua-public.php';

if (is_admin()) {
  require_once plugin_dir_path(__FILE__) . 'admin/class-sua-admin.php';
}