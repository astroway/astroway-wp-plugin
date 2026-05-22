<?php
/**
 * Plugin Name:       AstroWay – Astrology & Horoscopes
 * Plugin URI:        https://github.com/astroway/astroway-wp-plugin
 * Description:       Astrology shortcodes & blocks: natal charts, synastry, transits, horoscope, Tarot, Numerology, Human Design API. Powered by api.astroway.info.
 * Version:           0.5.7
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            AstroWay
 * Author URI:        https://astroway.info
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       astroway
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );
define( 'ASTROWAY_WP_PLUGIN_VERSION', $astroway_plugin_data['Version'] );
unset( $astroway_plugin_data );
define( 'ASTROWAY_WP_PLUGIN_FILE', __FILE__ );
define( 'ASTROWAY_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASTROWAY_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ASTROWAY_API_BASE', 'https://api.astroway.info/v1' );

require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/lib/plugin-update-checker/load-v5p6.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-renderer-decisions.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-public-client.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-blocks.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-cache.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-api-client.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-admin.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-updater.php';
// Classes below are added in later atomic versions (v0.6.x onwards) — guard the
// require so a partial ZIP (e.g. v0.5.6 with this main file but without these
// later includes/) loads cleanly instead of fatal-erroring on a missing file.
foreach ( [ 'class-tier', 'class-addon-api', 'elementor' ] as $astroway_opt_include ) {
	$astroway_opt_path = ASTROWAY_WP_PLUGIN_DIR . 'includes/' . $astroway_opt_include . '.php';
	if ( file_exists( $astroway_opt_path ) ) {
		require_once $astroway_opt_path;
	}
}
unset( $astroway_opt_include, $astroway_opt_path );
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, [ '\\AstroWay\\WPPlugin\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\\AstroWay\\WPPlugin\\Plugin', 'deactivate' ] );

add_action( 'plugins_loaded', [ '\\AstroWay\\WPPlugin\\Plugin', 'boot' ] );

// Load bundled translations before WP 6.x just-in-time loader runs, so admin
// strings hit our /languages/ before anything else queries them.
add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'astroway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);
