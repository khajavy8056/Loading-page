<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes every option the plugin created, leaving the site clean.
 *
 * @package Apple_Star_Loader
 */

// If uninstall was not called by WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'aspl_settings' );
