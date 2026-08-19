<?php
/**
 * Default options.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Defaults {

	/**
	 * One simple preset list. Only one real visual style ships in v2.1: the
	 * wave-letter wordmark (which auto-adapts to Latin / Persian). The list
	 * is kept extensible so future presets slot in cleanly.
	 *
	 * @return array
	 */
	public static function get_presets() {
		return array(
			'apple_star' => __( 'Apple Star (Wave Letters)', 'apple-star-loader' ),
		);
	}

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function get_options() {
		return array(
			'enabled'           => 1,
			'target'            => 'front_page',
			'preset'            => 'apple_star',
			'text'              => 'APPLE STAR',
			'use_site_title'    => 1,
			'logo'              => '',
			'show_on_mobile'    => 1,
			'hide_for_logged_in'=> 0,
			'bg_color'          => '#0a0a0f',
			'bg_opacity'        => 100,
			'text_color'        => '#ffffff',
			'accent_color'      => '#0071e3',
			'blur_amount'       => 0,
			'wait_images'       => 1,
			'timeout'           => 15,
			'min_time'          => 800,
			'fade_duration'     => 700,
			'z_index'           => 99999999,
			'custom_css'        => '',
			'code'              => '',
		);
	}

	/**
	 * Read preset file from assets/presets/.
	 *
	 * @param string $slug Preset slug.
	 * @return string
	 */
	public static function get_preset_code( $slug ) {
		$file = ASPL_PLUGIN_DIR . 'assets/presets/' . sanitize_file_name( (string) $slug ) . '.html';
		if ( file_exists( $file ) ) {
			$code = @file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false !== $code ) {
				return rtrim( $code ) . "\n";
			}
		}
		return '';
	}

	/**
	 * The default loader code used by the "reset" button.
	 *
	 * @return string
	 */
	public static function get_loader_code() {
		return self::get_preset_code( 'apple_star' );
	}
}
