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

	/**
	 * Default option values (used on activation and as fallbacks).
	 *
	 * @return array{enabled:int,target:string,code:string,timeout:int}
	 */
	public static function get_options() {
		return array(
			'enabled' => 1,
			'target'  => 'front_page',
			'code'    => self::get_loader_code(),
			'timeout' => 10,
		);
	}

	/**
	 * The default "Apple Star" loader code (HTML + CSS).
	 *
	 * The built-in design is fully responsive: the wordmark scales with the
	 * viewport (clamp + media queries) and honors prefers-reduced-motion.
	 *
	 * @return string
	 */
	public static function get_loader_code() {
		$file = ASPL_PLUGIN_DIR . 'assets/default-loader-code.html';
		$code = @file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $code ) {
			return '';
		}
		return rtrim( $code ) . "\n";
	}
}
