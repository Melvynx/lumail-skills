<?php
/**
 * Plugin Name: Lumail
 * Plugin URI: https://github.com/Melvynx/lumail-opensource
 * Description: Subscribe forms for Lumail. Shortcode [lumail_form].
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author: Lumail
 * Author URI: https://lumail.io
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: lumail
 *
 * @package Lumail
 */

if (! defined('ABSPATH')) {
    exit;
}

define('LUMAIL_VERSION', '0.1.0');
define('LUMAIL_PLUGIN_FILE', __FILE__);
define('LUMAIL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LUMAIL_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once LUMAIL_PLUGIN_DIR . 'includes/class-lumail-client.php';
require_once LUMAIL_PLUGIN_DIR . 'includes/class-lumail-form.php';
require_once LUMAIL_PLUGIN_DIR . 'includes/class-lumail-settings.php';
require_once LUMAIL_PLUGIN_DIR . 'includes/class-lumail-ajax.php';
require_once LUMAIL_PLUGIN_DIR . 'includes/class-lumail.php';

Lumail_Plugin::instance()->boot();
