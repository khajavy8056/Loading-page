<?php
/**
 * Admin: "Apple Star Loader" settings page.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the admin menu and the Settings API fields.
 *
 * The settings page is reachable in two places:
 *  - as a top-level item in the admin sidebar: "Apple Star Loader"
 *  - under Settings → Apple Star Loader
 *
 * @package Apple_Star_Loader
 */
class ASPL_Settings {

	const OPTION        = 'aspl_settings';
	const GROUP         = 'aspl_settings_group';
	const PAGE          = 'aspl-settings';
	const MENU_SLUG     = 'apple-star-loader';
	const SETTINGS_SLUG = 'apple-star-loader-settings';
	const CAPABILITY    = 'manage_options';

	/**
	 * Hook registration.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Seeds the default options on activation.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, ASPL_Defaults::get_options() );
		}
	}

	/**
	 * Returns the saved options merged over the defaults.
	 *
	 * @return array{enabled:int,target:string,code:string,timeout:int}
	 */
	public static function get_options() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( (array) $saved, ASPL_Defaults::get_options() );
	}

	/**
	 * Registers the admin menu entries.
	 *
	 * @return void
	 */
	public function register_menu() {
		// Top-level item in the admin sidebar.
		add_menu_page(
			__( 'Apple Star Loader', 'apple-star-loader' ),
			__( 'Apple Star Loader', 'apple-star-loader' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-star-filled',
			59
		);

		// Makes the top-level item point at the settings page directly.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Apple Star Loader — Settings', 'apple-star-loader' ),
			__( 'Apple Star Loader', 'apple-star-loader' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);

		// Also available under Settings → Apple Star Loader.
		add_submenu_page(
			'options-general.php',
			__( 'Apple Star Loader — Settings', 'apple-star-loader' ),
			__( 'Apple Star Loader', 'apple-star-loader' ),
			self::CAPABILITY,
			self::SETTINGS_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registers the setting, its sections and its fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
			)
		);

		// --- Sections -------------------------------------------------

		add_settings_section(
			'aspl_section_general',
			__( 'General', 'apple-star-loader' ),
			array( $this, 'render_section_general' ),
			self::PAGE
		);
		add_settings_section(
			'aspl_section_code',
			__( 'Loader Code (HTML / CSS)', 'apple-star-loader' ),
			array( $this, 'render_section_code' ),
			self::PAGE
		);
		add_settings_section(
			'aspl_section_timing',
			__( 'Timing & Fallback', 'apple-star-loader' ),
			array( $this, 'render_section_timing' ),
			self::PAGE
		);

		// --- Fields ---------------------------------------------------

		add_settings_field(
			'aspl_field_enabled',
			__( 'Enable loader', 'apple-star-loader' ),
			array( $this, 'field_enabled' ),
			self::PAGE,
			'aspl_section_general'
		);
		add_settings_field(
			'aspl_field_target',
			__( 'Display target', 'apple-star-loader' ),
			array( $this, 'field_target' ),
			self::PAGE,
			'aspl_section_general'
		);
		add_settings_field(
			'aspl_field_code',
			__( 'HTML + CSS code', 'apple-star-loader' ),
			array( $this, 'field_code' ),
			self::PAGE,
			'aspl_section_code'
		);
		add_settings_field(
			'aspl_field_timeout',
			__( 'Fallback timeout (seconds)', 'apple-star-loader' ),
			array( $this, 'field_timeout' ),
			self::PAGE,
			'aspl_section_timing'
		);
	}

	/**
	 * Sanitizes the whole options array on save.
	 *
	 * @param mixed $input Raw option value from the form.
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = array();

		// Enable / disable.
		$clean['enabled'] = empty( $input['enabled'] ) ? 0 : 1;

		// Display target.
		$target           = isset( $input['target'] ) ? sanitize_key( $input['target'] ) : 'front_page';
		$clean['target']  = in_array( $target, array( 'front_page', 'all_pages' ), true ) ? $target : 'front_page';

		// Loader code: intentionally a raw HTML/CSS field (the user's own
		// loader markup, only ever rendered in their visitors' browsers).
		// We only trim surrounding whitespace so the stored value stays
		// predictable; the content itself is preserved byte-for-byte.
		$code              = isset( $input['code'] ) ? (string) $input['code'] : '';
		$clean['code']     = trim( $code );

		// Fallback timeout in seconds (1–120).
		$timeout           = isset( $input['timeout'] ) ? absint( $input['timeout'] ) : 10;
		$clean['timeout']  = min( 120, max( 1, $timeout ) );

		return $clean;
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'apple-star-loader' ) );
		}

		$options      = self::get_options();
		$default_code = ASPL_Defaults::get_loader_code();
		?>
		<div class="wrap aspl-wrap">
			<h1 class="aspl-header">
				<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
				<span class="aspl-title"><?php esc_html_e( 'Apple Star Loader', 'apple-star-loader' ); ?></span>
				<span class="aspl-version">v<?php echo esc_html( ASPL_VERSION ); ?></span>
				<?php if ( ! empty( $options['enabled'] ) ) : ?>
					<span class="aspl-pill aspl-pill--on"><?php esc_html_e( 'Active', 'apple-star-loader' ); ?></span>
				<?php else : ?>
					<span class="aspl-pill aspl-pill--off"><?php esc_html_e( 'Disabled', 'apple-star-loader' ); ?></span>
				<?php endif; ?>
			</h1>

			<div class="aspl-card aspl-card--intro">
				<h2><?php esc_html_e( 'Custom glass preloader', 'apple-star-loader' ); ?></h2>
				<p>
					<?php esc_html_e( 'Shows the "Apple Star" loading screen while your page is getting ready, waits until every heavy asset (Elementor, fonts, images) is fully loaded, then fades out and removes itself from the DOM.', 'apple-star-loader' ); ?>
				</p>
				<ul>
					<li><?php esc_html_e( 'Injected at the very top of the page via wp_body_open', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Scroll is locked (overflow: hidden) while loading', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Waits for the window "load" event (all heavy assets)', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Smooth fade-out (opacity) + full removal from the DOM', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Fallback timeout: the site can never stay locked', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Fully responsive — mobile, tablet and desktop', 'apple-star-loader' ); ?></li>
				</ul>
			</div>

			<?php settings_errors(); ?>

			<form action="options.php" method="post" novalidate>
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button( __( 'Save Settings', 'apple-star-loader' ), 'primary', 'submit', false );
				?>
			</form>

			<div class="aspl-card aspl-card--preview">
				<h2><?php esc_html_e( 'Live preview', 'apple-star-loader' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Renders your loader code exactly as it will appear on the site (sandboxed, scripts disabled). Switch between device widths to check the responsive behavior. Note: in the preview the heartbeat/scanner animation runs, but the load/delay logic does not — the loader below is decorative.', 'apple-star-loader' ); ?>
				</p>
				<div class="aspl-preview-actions">
					<button type="button" class="button button-primary" data-aspl-preview-w="375px"><?php esc_html_e( 'Mobile 375px', 'apple-star-loader' ); ?></button>
					<button type="button" class="button" data-aspl-preview-w="768px"><?php esc_html_e( 'Tablet 768px', 'apple-star-loader' ); ?></button>
					<button type="button" class="button" data-aspl-preview-w="100%"><?php esc_html_e( 'Desktop', 'apple-star-loader' ); ?></button>
				</div>
				<div class="aspl-preview-stage">
					<iframe class="aspl-preview-frame" sandbox="" title="<?php esc_attr_e( 'Loader live preview', 'apple-star-loader' ); ?>"></iframe>
				</div>
			</div>

			<script>
				window.ASPL = {
					defaultCode: <?php echo wp_json_encode( $default_code, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>
				};
				( function () {
					'use strict';

					var ta     = document.querySelector( '.aspl-code' );
					var frame  = document.querySelector( '.aspl-preview-frame' );
					var reset  = document.getElementById( 'aspl-reset-code' );
					if ( ! ta || ! frame ) { return; }

					function updatePreview() {
						var doc = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><style>html,body{margin:0;padding:0;min-height:100vh;background:#0b0b0c;}</style></head><body>' + ta.value + '</body></html>';
						frame.srcdoc = doc;
					}

					var timer = null;
					ta.addEventListener( 'input', function () {
						clearTimeout( timer );
						timer = setTimeout( updatePreview, 350 );
					} );

					if ( reset ) {
						reset.addEventListener( 'click', function () {
							ta.value = window.ASPL.defaultCode;
							updatePreview();
						} );
					}

					document.querySelectorAll( '.aspl-preview-actions button' ).forEach( function ( btn ) {
						btn.addEventListener( 'click', function () {
							frame.style.width = btn.getAttribute( 'data-aspl-preview-w' );
							document.querySelectorAll( '.aspl-preview-actions button' ).forEach( function ( b ) {
								b.classList.remove( 'button-primary' );
							} );
							btn.classList.add( 'button-primary' );
						} );
					} );

					updatePreview();
				}() );
			</script>

			<style>
				.aspl-header { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin: 24px 0 0; }
				.aspl-header .dashicons { font-size: 26px; width: 36px; height: 36px; color: #f5f5f7; background: #1d1d1f; border-radius: 9px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.28); }
				.aspl-version { color: #787c82; font-size: 13px; font-weight: 400; }
				.aspl-pill { font-size: 11px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; padding: 3px 10px; border-radius: 999px; }
				.aspl-pill--on { background: #e3f7e9; color: #1e7a3d; }
				.aspl-pill--off { background: #f0f0f1; color: #666666; }
				.aspl-card { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 18px 20px; margin-top: 18px; max-width: 960px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04); }
				.aspl-card h2 { margin-top: 0; }
				.aspl-card--intro ul { margin: 10px 0 0; line-height: 1.9; }
				.aspl-code { font-family: Consolas, Monaco, 'Courier New', monospace; font-size: 12px; line-height: 1.5; min-height: 340px; direction: ltr; text-align: left; }
				.aspl-unit { margin: 0 6px; color: #50575e; }
				.aspl-preview-frame { width: 375px; max-width: 100%; height: 300px; border: 1px solid #dcdcde; border-radius: 8px; background: #0b0b0c; display: block; }
				.aspl-preview-stage { background: #f6f7f7; border: 1px dashed #c3c4c7; border-radius: 8px; padding: 12px; display: flex; justify-content: center; }
				.aspl-preview-actions { margin: 10px 0; }
			</style>
		</div>
		<?php
	}

	/*
	|--------------------------------------------------------------------------
	| Section descriptions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Description of the General section.
	 *
	 * @return void
	 */
	public function render_section_general() {
		echo '<p>' . esc_html__( 'Enable the loader and choose where it should appear.', 'apple-star-loader' ) . '</p>';
	}

	/**
	 * Description of the Loader Code section.
	 *
	 * @return void
	 */
	public function render_section_code() {
		echo '<p>' . esc_html__( 'The complete HTML + CSS of the loading screen. This field is fully open — replace or edit it any time with your own design, then press Save.', 'apple-star-loader' ) . '</p>';
	}

	/**
	 * Description of the Timing section.
	 *
	 * @return void
	 */
	public function render_section_timing() {
		echo '<p>' . esc_html__( 'Safety net for slow or broken pages.', 'apple-star-loader' ) . '</p>';
	}

	/*
	|--------------------------------------------------------------------------
	| Field renderers
	|--------------------------------------------------------------------------
	*/

	/**
	 * Enable / disable checkbox.
	 *
	 * @param array $args Settings field args.
	 * @return void
	 */
	public function field_enabled( $args ) {
		$options = self::get_options();
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $args['option_name'] ); ?>[enabled]" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?> />
			<?php esc_html_e( 'Show the loading screen to visitors', 'apple-star-loader' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Turn the loader on or off without touching your custom code.', 'apple-star-loader' ); ?></p>
		<?php
	}

	/**
	 * Display target radio buttons.
	 *
	 * @param array $args Settings field args.
	 * @return void
	 */
	public function field_target( $args ) {
		$options = self::get_options();
		$targets = array(
			'front_page' => __( 'Front page only (recommended for heavy pages)', 'apple-star-loader' ),
			'all_pages'  => __( 'All pages of the site', 'apple-star-loader' ),
		);
		?>
		<?php foreach ( $targets as $value => $label ) : ?>
			<label style="display:block;margin-bottom:4px;">
				<input type="radio" name="<?php echo esc_attr( $args['option_name'] ); ?>[target]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $options['target'], $value ); ?> />
				<?php echo esc_html( $label ); ?>
			</label>
		<?php endforeach; ?>
		<p class="description"><?php esc_html_e( 'Front page = the main landing page of your site (a static page or your blog index).', 'apple-star-loader' ); ?></p>
		<?php
	}

	/**
	 * Large textarea with the loader HTML + CSS code.
	 *
	 * @param array $args Settings field args.
	 * @return void
	 */
	public function field_code( $args ) {
		$options = self::get_options();
		?>
		<textarea id="aspl-code" class="aspl-code large-text code" name="<?php echo esc_attr( $args['option_name'] ); ?>[code]" rows="16" dir="ltr" spellcheck="false"><?php echo esc_textarea( $options['code'] ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Complete HTML + CSS of your loader (the built-in Apple Star design or your own). Keep it self-contained — the plugin wraps the code in one block and removes it together when the page is ready. The live preview below updates while you type.', 'apple-star-loader' ); ?>
		</p>
		<p>
			<button type="button" class="button" id="aspl-reset-code"><?php esc_html_e( 'Reset to default code', 'apple-star-loader' ); ?></button>
			<span class="description"><?php esc_html_e( 'Restores the built-in responsive "Apple Star" code (remember to press Save).', 'apple-star-loader' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Fallback timeout number input.
	 *
	 * @param array $args Settings field args.
	 * @return void
	 */
	public function field_timeout( $args ) {
		$options = self::get_options();
		?>
		<input type="number" class="small-text" name="<?php echo esc_attr( $args['option_name'] ); ?>[timeout]" value="<?php echo esc_attr( (int) $options['timeout'] ); ?>" min="1" max="120" step="1" />
		<span class="aspl-unit"><?php esc_html_e( 'seconds', 'apple-star-loader' ); ?></span>
		<p class="description"><?php esc_html_e( 'If the page does not fire the "load" event within this time (for example a stuck image or a broken request), the loader closes anyway so the site can never stay locked. Allowed range: 1–120 seconds.', 'apple-star-loader' ); ?></p>
		<?php
	}
}
