<?php
/**
 * Registry of 10 loader designs.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Designs {

	/**
	 * Returns ordered list of designs: key => [name,file].
	 *
	 * @return array
	 */
	public static function get_designs() {
		return array(
			'apple-star'  => array( 'name' => 'Apple Star',  'file' => '01-apple-star.html' ),
			'star-frost'  => array( 'name' => 'Star Frost',  'file' => '02-star-frost.html' ),
			'dots'        => array( 'name' => 'Dots',        'file' => '03-dots.html' ),
			'spinner'     => array( 'name' => 'Spinner',     'file' => '04-spinner.html' ),
			'progress-bar'=> array( 'name' => 'Progress Bar','file' => '05-progress-bar.html' ),
			'pulse-ring'  => array( 'name' => 'Pulse Ring',  'file' => '06-pulse-ring.html' ),
			'orbit'       => array( 'name' => 'Orbit',       'file' => '07-orbit.html' ),
			'typing'      => array( 'name' => 'Typing',      'file' => '08-typing.html' ),
			'neon'        => array( 'name' => 'Neon',        'file' => '09-neon.html' ),
			'wave'        => array( 'name' => 'Wave',        'file' => '10-wave.html' ),
		);
	}

	/**
	 * Whether a design key is valid.
	 *
	 * @param string $id Design key.
	 * @return bool
	 */
	public static function is_valid( $id ) {
		$designs = self::get_designs();
		return isset( $designs[ $id ] );
	}

	/**
	 * Get code for a design (rtrimmed).
	 *
	 * @param string $id Design key.
	 * @return string
	 */
	public static function get_code( $id ) {
		$designs = self::get_designs();
		if ( ! isset( $designs[ $id ] ) ) {
			return '';
		}
		$file = ASPL_PLUGIN_DIR . 'assets/designs/' . $designs[ $id ]['file'];
		if ( ! file_exists( $file ) ) {
			return '';
		}
		$code = @file_get_contents( $file ); // phpcs:ignore
		if ( false === $code ) {
			return '';
		}
		return rtrim( $code );
	}

	/**
	 * Get all designs codes map: key => code.
	 *
	 * @return array
	 */
	public static function get_all_codes() {
		$all = array();
		foreach ( self::get_designs() as $key => $info ) {
			$all[ $key ] = self::get_code( $key );
		}
		return $all;
	}
}
