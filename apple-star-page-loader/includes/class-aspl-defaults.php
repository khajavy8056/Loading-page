<?php
/**
 * Default options & preset registry.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Defaults {

	/**
	 * Available presets — every slug must have a matching file in
	 * assets/presets/{slug}.html
	 *
	 * @return array  slug => label
	 */
	public static function get_presets() {
		return array(
			'apple_star'      => __( 'Apple Star Pulse (ECG)', 'apple-star-loader' ),
			'pulse_classic'   => __( 'Classic Pulse', 'apple-star-loader' ),
			'freq_bars'       => __( 'Equalizer Bars', 'apple-star-loader' ),
			'sine_wave'       => __( 'Sine Wave Dots', 'apple-star-loader' ),
			'ecg_heartbeat'   => __( 'ECG Heartbeat', 'apple-star-loader' ),
			'siri_orbit'      => __( 'Siri Orbit', 'apple-star-loader' ),
			'radar_sweep'     => __( 'Concentric Radar', 'apple-star-loader' ),
			'breathing_core'  => __( 'Breathing Core', 'apple-star-loader' ),
			'quantum_spin'    => __( 'Quantum Spin', 'apple-star-loader' ),
			'wave_morph'      => __( 'Wave Morph', 'apple-star-loader' ),
			'dot_rhythm'      => __( 'Dot Rhythm', 'apple-star-loader' ),
		);
	}

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function get_options() {
		return array(
			// General.
			'enabled'            => 1,
			'target'             => 'front_page',
			'preset'             => 'apple_star',
			'show_on_mobile'     => 1,
			'hide_for_logged_in' => 0,

			// Branding.
			'text'               => 'APPLE STAR',
			'use_site_title'     => 1,
			'logo'               => '',

			// Colors.
			'bg_color'           => '#0a0a0f',
			'bg_opacity'         => 85,
			'text_color'         => '#ffffff',
			'accent_color'       => '#00c3ff',
			'blur_amount'        => 16,

			// Timing.
			'wait_images'        => 1,
			'timeout'            => 20,
			'min_time'           => 800,
			'fade_duration'      => 700,
			'z_index'            => 99999999,

			// Maintenance mode.
			'maintenance_mode'   => 0,
			'maintenance_hours'  => 0,
			'maintenance_minutes'=> 30,
			'maintenance_seconds'=> 0,
			'maintenance_msg'    => 'ما در حال بروز رسانی هستیم. بزودی با ارائه خدمات بهتر در خدمت شما هستیم.',

			// Advanced.
			'custom_css'         => '',
		);
	}

	/**
	 * Read a preset file from assets/presets/.
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
	 * The default loader code used by the "reset" button (defaults to apple_star).
	 *
	 * @return string
	 */
	public static function get_loader_code() {
		return self::get_preset_code( 'apple_star' );
	}
}
