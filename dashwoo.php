<?php
/**
 * Plugin Name:       DashWoo
 * Plugin URI:        https://dashwoo.dev/
 * Description:       Full UI platform for WooCommerce x Elementor: design tokens, local fonts, icons and assets. No CDN.
 * Version:           1.4.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            DashWoo
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dashwoo
 * Domain Path:       /languages
 * WC requires at least: 8.5
 * WC tested up to:   9.9
 *
 * @package DashWoo
 */

defined( 'ABSPATH' ) || exit;

define( 'DASHWOO_VERSION', '1.4.0' );
define( 'DASHWOO_FILE', __FILE__ );
define( 'DASHWOO_DIR', plugin_dir_path( __FILE__ ) );
define( 'DASHWOO_INCLUDES', DASHWOO_DIR . 'includes/' );
define( 'DASHWOO_URL', plugin_dir_url( __FILE__ ) );
define( 'DASHWOO_BASENAME', plugin_basename( __FILE__ ) );
define( 'DASHWOO_DB_VERSION', '1' );
define( 'DASHWOO_TEXTDOMAIN', 'dashwoo' );

require_once DASHWOO_INCLUDES . 'class-autoloader.php';

\DashWoo\Autoloader::register();

require_once DASHWOO_INCLUDES . 'functions.php';

register_activation_hook( __FILE__, array( '\DashWoo\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\DashWoo\Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', array( '\DashWoo\Plugin', 'boot' ), 5 );
