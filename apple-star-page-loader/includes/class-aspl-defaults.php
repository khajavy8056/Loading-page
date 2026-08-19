<?php
/**
 * Default options for v2.0.1 — multiple presets, colors, logo, progress, branding.
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
			'apple_star'   => __( 'Apple Star (ECG / نبض قلب)', 'apple-star-loader' ),
			'wave_letters' => __( 'Wave Letters / حروف موجی', 'apple-star-loader' ),
			'spinner_pro'  => __( 'Spinner Pro / حلقه دوگانه', 'apple-star-loader' ),
			'progress_bar' => __( 'Progress Bar / نوار بزرگ', 'apple-star-loader' ),
			'pulse_ring'   => __( 'Pulse Ring / حلقه نبض', 'apple-star-loader' ),
			'bars'         => __( 'Equalizer Bars / اکولایزر', 'apple-star-loader' ),
			'dots_bounce'  => __( 'Dots Bounce / سه نقطه', 'apple-star-loader' ),
			'neon_line'    => __( 'Neon Line / خط نئون', 'apple-star-loader' ),
			'particles'    => __( 'Particles Glow / ذرات', 'apple-star-loader' ),
			'minimal_dot'  => __( 'Minimal Dot / نقطه ساده', 'apple-star-loader' ),
			'custom_code'  => __( 'Custom Code / کد سفارشی', 'apple-star-loader' ),
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
			'target'            => 'all_pages',
			'preset'            => 'apple_star',
			'text'              => is_rtl() ? 'در حال بارگذاری' : 'LOADING',
			'logo'              => '',
			'show_percentage'   => 1,
			'show_progress_bar' => 1,
			'show_tips'         => 1,
			'bg_color'          => '#0b0b0f',
			'bg_opacity'        => 100,
			'primary_color'     => '#ffffff',
			'accent_color'      => '#0071e3',
			'text_color'        => '#ffffff',
			'bar_bg_color'      => 'rgba(255,255,255,0.15)',
			'bar_fg_color'      => '#ffffff',
			'timeout'           => 15,
			'min_time'          => 600,
			'fade_duration'     => 600,
			'hide_for_logged_in'=> 0,
			'show_on_mobile'    => 1,
			'blur_amount'       => 0,
			'z_index'           => 99999999,
			'custom_css'        => '',
			'code'              => '',
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
	 * The default loader code (used by "Reset to default" button).
	 *
	 * @return string
	 */
	public static function get_loader_code() {
		return self::get_preset_code( 'apple_star' );
	}
}
