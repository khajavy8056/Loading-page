<?php
/**
 * Plugin Name:       Apple Star Page Loader
 * Plugin URI:        https://github.com/khajavy8056/Loading-page
 * Description:       Production-ready, fully responsive "Apple Star" glass preloader for WordPress & WooCommerce. Injects a 100% customizable loading screen at the very top of the page (wp_body_open), locks scrolling, waits for the real window "load" event (heavy Elementor builds, web fonts and images) and then fades out smoothly before removing itself from the DOM. Full control from the admin panel: enable/disable, front-page or all-pages target, 10 one-click loader designs + custom code, Maintenance / Coming Soon mode with editable message, live countdown, auto-shutdown and 503 handling, editable HTML/CSS code with live preview, and a fallback timeout so your site can never stay locked.
 * Version:           1.3.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Khajavy
 * Author URI:        https://github.com/khajavy8056
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       apple-star-loader
 * Domain Path:       /languages
 *
 * @package Apple_Star_Loader
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version.
 */
define( 'ASPL_VERSION', '1.3.2' );

/**
 * Absolute path to the main plugin file.
 */
define( 'ASPL_BASEFILE', __FILE__ );

/**
 * Absolute path to the plugin directory (with trailing slash).
 */
define( 'ASPL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * URL of the plugin directory (with trailing slash).
 */
define( 'ASPL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Option name used to store all plugin settings.
 */
define( 'ASPL_OPTION', 'aspl_settings' );

require_once ASPL_PLUGIN_DIR . 'includes/class-aspl-defaults.php';
require_once ASPL_PLUGIN_DIR . 'includes/class-aspl-designs.php';
require_once ASPL_PLUGIN_DIR . 'includes/class-aspl-settings.php';
require_once ASPL_PLUGIN_DIR . 'includes/class-aspl-frontend.php';

/**
 * Runs once on plugin activation: seeds the default options.
 *
 * @return void
 */
function aspl_activate() {
	ASPL_Settings::activate();
}
register_activation_hook( __FILE__, 'aspl_activate' );

/**
 * Automatically disables maintenance mode when the countdown ends.
 *
 * @return void
 */
function aspl_maintenance_auto_disable() {
	$options = ASPL_Settings::get_options();
	if ( ! empty( $options['maintenance_enabled'] ) ) {
		$options['maintenance_enabled'] = 0;
		update_option( ASPL_OPTION, $options );
	}
	wp_clear_scheduled_hook( 'aspl_maintenance_end' );
}
add_action( 'aspl_maintenance_end', 'aspl_maintenance_auto_disable' );

/**
 * Returns the main plugin instance.
 *
 * @return ASPL
 */
function aspl() {
	return ASPL::instance();
}

/**
 * Main plugin class (singleton).
 *
 * @package Apple_Star_Loader
 */
final class ASPL {

	/**
	 * Singleton instance.
	 *
	 * @var ASPL|null
	 */
	private static $instance = null;

	/**
	 * Admin settings component.
	 *
	 * @var ASPL_Settings
	 */
	public $settings;

	/**
	 * Frontend loader component.
	 *
	 * @var ASPL_Frontend
	 */
	public $frontend;

	/**
	 * Returns the singleton instance.
	 *
	 * @return ASPL
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use ASPL::instance() / aspl().
	 *
	 * @return void
	 */
	private function __construct() {
		$this->settings = new ASPL_Settings();
		$this->frontend = new ASPL_Frontend();
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @throws \RuntimeException Always.
	 */
	public function __wakeup() {
		throw new \RuntimeException( 'Cannot unserialize a singleton.' );
	}
}

// Boot the plugin.
aspl();
