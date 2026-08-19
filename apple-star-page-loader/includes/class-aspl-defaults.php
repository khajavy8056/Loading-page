<?php
/**
 * Default options and the default loader code.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides plugin defaults.
 *
 * @package Apple_Star_Loader
 */
class ASPL_Defaults {

	const DEFAULT_DESIGN = 'apple-star';
	const DEFAULT_MAINTENANCE_MESSAGE = "We'll be back soon";

	/**
	 * Default option values (used on activation and as fallbacks).
	 *
	 * @return array
	 */
	public static function get_options() {
		return array(
			'enabled'             => 1,
			'target'              => 'all_pages',
			'model'               => self::DEFAULT_DESIGN,
			'code'                => self::get_loader_code(),
			'timeout'             => 10,
			'maintenance_enabled' => 0,
			'maintenance_message' => self::DEFAULT_MAINTENANCE_MESSAGE,
			'countdown_type'      => 'hours',
			'countdown_hours'     => 48,
			'countdown_end'       => 0,
		);
	}

	/**
	 * The default "Apple Star" loader code (HTML + CSS).
	 *
	 * @return string
	 */
	public static function get_loader_code() {
		if ( class_exists( 'ASPL_Designs' ) ) {
			$code = ASPL_Designs::get_code( self::DEFAULT_DESIGN );
			if ( '' !== $code ) {
				return $code;
			}
		}
		$file = ASPL_PLUGIN_DIR . 'assets/designs/01-apple-star.html';
		if ( file_exists( $file ) ) {
			$code = @file_get_contents( $file ); // phpcs:ignore
			if ( false !== $code ) {
				return rtrim( $code ) . "\n";
			}
		}
		// Fallback to legacy file for backwards-compat.
		$legacy = ASPL_PLUGIN_DIR . 'assets/default-loader-code.html';
		if ( file_exists( $legacy ) ) {
			$code = @file_get_contents( $legacy ); // phpcs:ignore
			if ( false !== $code ) {
				return rtrim( $code ) . "\n";
			}
		}
		return '';
	}
}
