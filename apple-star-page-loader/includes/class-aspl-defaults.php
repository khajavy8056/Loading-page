<?php
/**
 * Default options for v2.0.0 — multiple presets, colors, logo, progress, branding.
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
	 * List of built-in loader presets.
	 *
	 * @return array
	 */
	public static function get_presets() {
		return array(
			'apple_star'    => __( 'Apple Star (Glass)', 'apple-star-loader' ),
			'wave_letters'  => __( 'Wave Letters', 'apple-star-loader' ),
			'spinner_pro'   => __( 'Spinner Pro', 'apple-star-loader' ),
			'progress_bar'  => __( 'Progress Bar', 'apple-star-loader' ),
			'particles'     => __( 'Particles Glow', 'apple-star-loader' ),
			'minimal_dot'   => __( 'Minimal Dot', 'apple-star-loader' ),
			'custom_code'   => __( 'Custom Code', 'apple-star-loader' ),
		);
	}

	/**
	 * Default option values (used on activation and as fallbacks).
	 *
	 * @return array
	 */
	public static function get_options() {
		return array(
			// General.
			'enabled'           => 1,
			'target'            => 'all_pages',
			'preset'            => 'apple_star',

			// Content.
			'text'              => 'LOADING',
			'logo'              => '',
			'show_percentage'   => 1,
			'show_progress_bar' => 1,
			'show_tips'         => 1,

			// Colors.
			'bg_color'          => '#0b0b0f',
			'bg_opacity'        => 100,
			'primary_color'     => '#ffffff',
			'accent_color'      => '#0071e3',
			'text_color'        => '#ffffff',
			'bar_bg_color'      => 'rgba(255,255,255,0.15)',
			'bar_fg_color'      => '#ffffff',

			// Timing.
			'timeout'           => 15,
			'min_time'          => 600,
			'fade_duration'     => 600,

			// Extras.
			'hide_for_logged_in'=> 0,
			'show_on_mobile'    => 1,
			'blur_amount'       => 0,
			'z_index'           => 99999999,

			// Advanced.
			'custom_css'        => '',
			'code'              => '', // custom code when preset === 'custom_code'
		);
	}

	/**
	 * Read a preset file from assets/presets/{slug}.html.
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
	 * The default loader HTML/CSS used when the user has no custom code saved.
	 *
	 * @return string
	 */
	public static function get_loader_code() {
		return self::get_preset_code( 'apple_star' );
	}
}
