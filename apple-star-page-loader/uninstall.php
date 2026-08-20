<?php
/**
 * Fired when the plugin is uninstalled.
 * Cleans every option the plugin created.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'aspl_settings' );

