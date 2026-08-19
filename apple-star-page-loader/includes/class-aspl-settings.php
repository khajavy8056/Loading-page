<?php
/**
 * Admin: "Apple Star Loader" settings page.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Settings {

	const OPTION        = 'aspl_settings';
	const GROUP         = 'aspl_settings_group';
	const PAGE          = 'aspl-settings';
	const MENU_SLUG     = 'apple-star-loader';
	const SETTINGS_SLUG = 'apple-star-loader-settings';
	const CAPABILITY    = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public static function activate() {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, ASPL_Defaults::get_options() );
		}
	}

	public static function get_options() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( (array) $saved, ASPL_Defaults::get_options() );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Apple Star Loader', 'apple-star-loader' ),
			__( 'Apple Star Loader', 'apple-star-loader' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-star-filled',
			59
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Apple Star Loader — Settings', 'apple-star-loader' ),
			__( 'Apple Star Loader', 'apple-star-loader' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
		add_submenu_page(
			'options-general.php',
			__( 'Apple Star Loader — Settings', 'apple-star-loader' ),
			__( 'Apple Star Loader', 'apple-star-loader' ),
			self::CAPABILITY,
			self::SETTINGS_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
			)
		);

		add_settings_section( 'aspl_section_general', __( 'General', 'apple-star-loader' ), array( $this, 'render_section_general' ), self::PAGE );
		add_settings_section( 'aspl_section_design', __( 'Design', 'apple-star-loader' ), array( $this, 'render_section_design' ), self::PAGE );
		add_settings_section( 'aspl_section_code', __( 'Loader Code (HTML / CSS)', 'apple-star-loader' ), array( $this, 'render_section_code' ), self::PAGE );
		add_settings_section( 'aspl_section_timing', __( 'Timing & Fallback', 'apple-star-loader' ), array( $this, 'render_section_timing' ), self::PAGE );
		add_settings_section( 'aspl_section_maintenance', __( 'Maintenance / Coming Soon', 'apple-star-loader' ), array( $this, 'render_section_maintenance' ), self::PAGE );

		add_settings_field( 'aspl_field_enabled', __( 'Enable loader', 'apple-star-loader' ), array( $this, 'field_enabled' ), self::PAGE, 'aspl_section_general' );
		add_settings_field( 'aspl_field_target', __( 'Display target', 'apple-star-loader' ), array( $this, 'field_target' ), self::PAGE, 'aspl_section_general' );
		add_settings_field( 'aspl_field_model', __( 'Loader design', 'apple-star-loader' ), array( $this, 'field_model' ), self::PAGE, 'aspl_section_design' );
		add_settings_field( 'aspl_field_code', __( 'HTML + CSS code', 'apple-star-loader' ), array( $this, 'field_code' ), self::PAGE, 'aspl_section_code' );
		add_settings_field( 'aspl_field_timeout', __( 'Fallback timeout (seconds)', 'apple-star-loader' ), array( $this, 'field_timeout' ), self::PAGE, 'aspl_section_timing' );
		add_settings_field( 'aspl_field_maintenance_enabled', __( 'Maintenance mode', 'apple-star-loader' ), array( $this, 'field_maintenance_enabled' ), self::PAGE, 'aspl_section_maintenance' );
		add_settings_field( 'aspl_field_maintenance_message', __( 'Maintenance message', 'apple-star-loader' ), array( $this, 'field_maintenance_message' ), self::PAGE, 'aspl_section_maintenance' );
		add_settings_field( 'aspl_field_countdown', __( 'Countdown', 'apple-star-loader' ), array( $this, 'field_countdown' ), self::PAGE, 'aspl_section_maintenance' );
	}

	public function sanitize_options( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = array();
		$clean['enabled'] = empty( $input['enabled'] ) ? 0 : 1;
		$target          = isset( $input['target'] ) ? sanitize_key( $input['target'] ) : 'front_page';
		$clean['target'] = in_array( $target, array( 'front_page', 'all_pages' ), true ) ? $target : 'front_page';

		// Model
		$model = isset( $input['model'] ) ? sanitize_key( $input['model'] ) : ASPL_Defaults::DEFAULT_DESIGN;
		if ( 'custom' !== $model && ! ASPL_Designs::is_valid( $model ) ) {
			$model = ASPL_Defaults::DEFAULT_DESIGN;
		}
		$clean['model'] = $model;

		// Code raw trim only
		$code          = isset( $input['code'] ) ? (string) $input['code'] : '';
		$clean['code'] = trim( $code );

		$timeout          = isset( $input['timeout'] ) ? absint( $input['timeout'] ) : 10;
		$clean['timeout'] = min( 120, max( 1, $timeout ) );

		// Maintenance
		$clean['maintenance_enabled'] = empty( $input['maintenance_enabled'] ) ? 0 : 1;
		$msg = isset( $input['maintenance_message'] ) ? wp_strip_all_tags( (string) $input['maintenance_message'] ) : '';
		$msg = trim( $msg );
		if ( '' === $msg ) {
			$msg = ASPL_Defaults::DEFAULT_MAINTENANCE_MESSAGE;
		}
		$clean['maintenance_message'] = $msg;

		$type = isset( $input['countdown_type'] ) ? sanitize_key( $input['countdown_type'] ) : 'hours';
		if ( ! in_array( $type, array( 'off', 'hours', 'datetime' ), true ) ) {
			$type = 'hours';
		}
		$clean['countdown_type'] = $type;

		$hours = isset( $input['countdown_hours'] ) ? absint( $input['countdown_hours'] ) : 48;
		$hours = min( 240, max( 1, $hours ) );
		$clean['countdown_hours'] = $hours;

		// Compute countdown_end
		$end = 0;
		if ( 'hours' === $type ) {
			$end = time() + $hours * 3600;
		} elseif ( 'datetime' === $type ) {
			$raw = isset( $input['countdown_end_raw'] ) ? (string) $input['countdown_end_raw'] : '';
			$raw = trim( $raw );
			if ( '' !== $raw ) {
				$ts = strtotime( $raw );
				if ( false !== $ts && $ts > 0 ) {
					$end = (int) $ts;
				}
			}
		} else { // off
			$end = 0;
		}
		// If maintenance disabled, still store end but cron cleared below
		$clean['countdown_end'] = $end;

		// Cron scheduling
		if ( ! empty( $clean['maintenance_enabled'] ) && $clean['countdown_end'] > time() ) {
			// Clear previous schedule
			$existing = wp_next_scheduled( 'aspl_maintenance_end' );
			if ( $existing ) {
				wp_unschedule_event( $existing, 'aspl_maintenance_end' );
			}
			wp_clear_scheduled_hook( 'aspl_maintenance_end' );
			wp_schedule_single_event( $clean['countdown_end'], 'aspl_maintenance_end' );
		} else {
			wp_clear_scheduled_hook( 'aspl_maintenance_end' );
			$next = wp_next_scheduled( 'aspl_maintenance_end' );
			if ( $next ) {
				wp_unschedule_event( $next, 'aspl_maintenance_end' );
			}
		}

		return $clean;
	}

	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'apple-star-loader' ) );
		}
		$options      = self::get_options();
		$default_code = ASPL_Designs::get_code( 'apple-star' );
		$designs      = ASPL_Designs::get_designs();
		$maint_live   = ! empty( $options['maintenance_enabled'] ) && ( 0 === (int) $options['countdown_end'] || (int) $options['countdown_end'] > time() );
		?>
		<div class="wrap aspl-wrap">
			<h1 class="aspl-header">
				<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
				<span class="aspl-title"><?php esc_html_e( 'Apple Star Loader', 'apple-star-loader' ); ?></span>
				<span class="aspl-version">v<?php echo esc_html( ASPL_VERSION ); ?></span>
				<?php if ( ! empty( $options['enabled'] ) ) : ?>
					<span class="aspl-pill aspl-pill--on" data-on><?php esc_html_e( 'Active', 'apple-star-loader' ); ?></span>
					<span class="aspl-pill aspl-pill--off" data-off style="display:none"><?php esc_html_e( 'Disabled', 'apple-star-loader' ); ?></span>
				<?php else : ?>
					<span class="aspl-pill aspl-pill--on" data-on style="display:none"><?php esc_html_e( 'Active', 'apple-star-loader' ); ?></span>
					<span class="aspl-pill aspl-pill--off" data-off><?php esc_html_e( 'Disabled', 'apple-star-loader' ); ?></span>
				<?php endif; ?>
				<?php if ( $maint_live ) : ?>
					<span class="aspl-pill aspl-pill--maint"><?php esc_html_e( 'Maintenance ON', 'apple-star-loader' ); ?></span>
				<?php endif; ?>
			</h1>

			<div class="aspl-card aspl-card--intro">
				<h2><?php esc_html_e( 'Custom glass preloader', 'apple-star-loader' ); ?></h2>
				<p><?php esc_html_e( 'Shows the "Apple Star" loading screen while your page is getting ready, waits until every heavy asset (Elementor, fonts, images) is fully loaded, then fades out and removes itself from the DOM.', 'apple-star-loader' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Injected at the very top of the page via wp_body_open', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Scroll is locked (overflow: hidden) while loading', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Waits for the window "load" event (all heavy assets)', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Smooth fade-out (opacity) + full removal from the DOM', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Fallback timeout: the site can never stay locked', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Fully responsive — mobile, tablet and desktop', 'apple-star-loader' ); ?></li>
				</ul>
			</div>

			<div class="aspl-card" id="aspl-status-card" style="<?php echo ! empty( $options['enabled'] ) ? '' : 'display:none'; ?>">
				<p><strong><?php esc_html_e( 'Loader is ON — visitors will see the loading screen.', 'apple-star-loader' ); ?></strong></p>
			</div>
			<div class="aspl-card" id="aspl-status-card-off" style="<?php echo empty( $options['enabled'] ) ? '' : 'display:none'; ?>">
				<p><?php esc_html_e( 'Loader is OFF — no loading screen will be shown.', 'apple-star-loader' ); ?></p>
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
				<p class="description"><?php esc_html_e( 'Renders your loader code exactly as it will appear on the site (sandboxed, scripts disabled). Switch between device widths to check the responsive behavior.', 'apple-star-loader' ); ?></p>
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
					designs: <?php echo wp_json_encode( ASPL_Designs::get_all_codes(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>,
					enabled: <?php echo ! empty( $options['enabled'] ) ? 'true' : 'false'; ?>,
					defaultCode: <?php echo wp_json_encode( $default_code, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>
				};
				( function () {
					'use strict';
					var ta    = document.querySelector( '.aspl-code' );
					var frame = document.querySelector( '.aspl-preview-frame' );
					var reset = document.getElementById( 'aspl-reset-code' );
					if ( ! ta || ! frame ) { return; }
					function updatePreview() {
						var override='<style>.asld{position:absolute!important;}</style>';
						var doc = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>html,body{margin:0;padding:0;min-height:100vh;background:#0b0b0c;overflow:hidden;} body{position:relative;}</style></head><body dir="ltr">' + ta.value + override + '</body></html>';
						frame.srcdoc = doc;
						// fallback for browsers without srcdoc (rare)
						try{ frame.contentDocument && (frame.contentDocument.documentElement.innerHTML=doc);}catch(e){}
					}
					// mini thumbs — live scaled previews
					function initThumbs(){
						document.querySelectorAll( '.aspl-model-thumb iframe[data-thumb]' ).forEach(function(f){
							var key=f.getAttribute('data-thumb');
							var code=window.ASPL.designs[key];
							if(!code || !code.trim()){ f.style.background='#111'; return; }
							var override='<style>.asld{position:absolute!important;}</style>';
							var doc='<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>html,body{margin:0;padding:0;overflow:hidden;background:#0b0b0c;} body{position:relative;min-height:200px;}</style></head><body dir="ltr">'+code+override+'</body></html>';
							f.srcdoc=doc;
							// retry once if still blank (some Android WebView needs delay)
							setTimeout(function(){ try{ if(!f.contentDocument || !f.contentDocument.body || !f.contentDocument.body.innerHTML.trim()) f.srcdoc=doc; }catch(e){}}, 400);
						});
					}
					initThumbs();
					var timer = null;
					ta.addEventListener( 'input', function () {
						clearTimeout( timer );
						timer = setTimeout( updatePreview, 350 );
					} );
					if ( reset ) {
						reset.addEventListener( 'click', function () {
							ta.value = window.ASPL.defaultCode;
							var r = document.querySelector( 'input[name="aspl_settings[model]"][value="apple-star"]' );
							if ( r ) { r.checked = true; document.querySelectorAll('.aspl-model-card').forEach(function(c){c.classList.remove('is-selected')}); var card=document.querySelector('.aspl-model-card[data-model="apple-star"]'); if(card) card.classList.add('is-selected'); }
							updatePreview();
						} );
					}
					// Model switching — cards + radios
					document.querySelectorAll( '.aspl-model-card' ).forEach(function(card){
						card.addEventListener('click', function(){
							var radio=card.querySelector('input[type=radio]');
							if(!radio) return;
							radio.checked=true;
							radio.dispatchEvent(new Event('change',{bubbles:true}));
							document.querySelectorAll('.aspl-model-card').forEach(function(c){c.classList.remove('is-selected')});
							card.classList.add('is-selected');
						});
					});
					document.querySelectorAll( 'input[name="aspl_settings[model]"]' ).forEach( function ( radio ) {
						radio.addEventListener( 'change', function () {
							if ( radio.value === 'custom' ) { updatePreview(); return; }
							var code = window.ASPL.designs[ radio.value ];
							if ( typeof code !== 'undefined' ) { ta.value = code; updatePreview(); }
						} );
					} );
					document.querySelectorAll( '.aspl-preview-actions button' ).forEach( function ( btn ) {
						btn.addEventListener( 'click', function () {
							frame.style.width = btn.getAttribute( 'data-aspl-preview-w' );
							document.querySelectorAll( '.aspl-preview-actions button' ).forEach( function ( b ) { b.classList.remove( 'button-primary' ); } );
							btn.classList.add( 'button-primary' );
						} );
					} );
					// Enable/disable switch toggle pills/banners
					var enabledCb = document.querySelector( 'input[name="aspl_settings[enabled]"]' );
					if ( enabledCb ) {
						enabledCb.addEventListener( 'change', function () {
							var on = enabledCb.checked;
							var pillOn = document.querySelector( '[data-on]' );
							var pillOff = document.querySelector( '[data-off]' );
							var cardOn = document.getElementById( 'aspl-status-card' );
							var cardOff = document.getElementById( 'aspl-status-card-off' );
							if ( pillOn ) pillOn.style.display = on ? '' : 'none';
							if ( pillOff ) pillOff.style.display = on ? 'none' : '';
							if ( cardOn ) cardOn.style.display = on ? '' : 'none';
							if ( cardOff ) cardOff.style.display = on ? 'none' : '';
						} );
					}
					// Maintenance card toggle
					var maintCb = document.getElementById( 'aspl-maintenance-enabled' );
					var maintCard = document.getElementById( 'aspl-maintenance-status' );
					function toggleMaint() {
						if ( ! maintCb || ! maintCard ) return;
						maintCard.style.display = maintCb.checked ? '' : 'none';
					}
					if ( maintCb ) { maintCb.addEventListener( 'change', toggleMaint ); toggleMaint(); }
					// Countdown type show/hide
					var countRadios = document.querySelectorAll( 'input[name="aspl_settings[countdown_type]"]' );
					var hoursBox = document.getElementById( 'aspl-count-hours-box' );
					var dtBox = document.getElementById( 'aspl-count-dt-box' );
					function toggleCount() {
						var val = document.querySelector( 'input[name="aspl_settings[countdown_type]"]:checked' );
						var v = val ? val.value : 'off';
						if ( hoursBox ) hoursBox.style.display = v === 'hours' ? '' : 'none';
						if ( dtBox ) dtBox.style.display = v === 'datetime' ? '' : 'none';
					}
					countRadios.forEach( function ( r ) { r.addEventListener( 'change', toggleCount ); } );
					toggleCount();
					updatePreview();
				}() );
			</script>

			<style>
				.aspl-wrap{max-width:1020px}
				.aspl-header { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin: 24px 0 12px; }
				.aspl-header .dashicons { font-size: 26px; width: 36px; height: 36px; color: #f5f5f7; background: linear-gradient(135deg,#1d1d1f 0%,#2c2c2e 100%); border-radius: 9px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.28); display:flex; align-items:center; justify-content:center; }
				.aspl-header .aspl-title{font-size:20px; font-weight:700; letter-spacing:-0.02em}
				.aspl-version { color: #787c82; font-size: 13px; font-weight: 400; background:#f6f7f7; padding:2px 8px; border-radius:999px; border:1px solid #dcdcde }
				.aspl-pill { font-size: 11px; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; padding: 4px 11px; border-radius: 999px; border:1px solid transparent }
				.aspl-pill--on { background: #e3f7e9; color: #1e7a3d; border-color:#b7e8c4 }
				.aspl-pill--off { background: #f0f0f1; color: #666666; border-color:#dcdcde }
				.aspl-pill--maint { background: #fde8e8; color: #b91c1c; border-color:#f0a0a0 }
				.aspl-card { background: #fff; border: 1px solid #dcdcde; border-radius: 12px; padding: 20px 22px; margin-top: 16px; max-width: 960px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
				.aspl-card h2 { margin: 0 0 8px; font-size:15px; font-weight:700; letter-spacing:-0.01em }
				.aspl-card--intro{ background: linear-gradient(180deg,#fff 0%,#fafafa 100%); }
				.aspl-card--intro ul { margin: 10px 0 0; line-height: 1.9; padding-left:18px }
				.aspl-card--intro li::marker{color:#2271b1}
				.form-table th{font-weight:600; color:#1d1d1f}
				.aspl-code { font-family: Consolas, Monaco, 'Courier New', monospace; font-size: 12px; line-height: 1.6; min-height: 360px; direction: ltr; text-align: left; background:#0b0b0c; color:#e8e8ed; border-radius:8px; border:1px solid #2c2c2e; padding:12px }
				.aspl-unit { margin: 0 6px; color: #50575e; }
				.aspl-preview-frame { width: 375px; max-width: 100%; height: 420px; border: 1px solid #dcdcde; border-radius: 12px; background: #0b0b0c; display: block; box-shadow: inset 0 2px 10px rgba(0,0,0,0.12)}
				.aspl-preview-stage { background: #f6f7f7; border: 1px dashed #c3c4c7; border-radius: 12px; padding: 14px; display: flex; justify-content: center; }
				.aspl-preview-actions { margin: 12px 0; display:flex; gap:8px; flex-wrap:wrap }
				.aspl-preview-actions .button{border-radius:999px; padding:0 16px; height:32px}
				.aspl-models-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(152px,1fr));gap:14px;margin:14px 0}
				.aspl-model-card{border:2px solid #e5e5e7;border-radius:14px;overflow:hidden;cursor:pointer;background:#fff;transition:all .2s;display:flex;flex-direction:column;align-items:center;padding:0 0 10px; box-shadow:0 1px 2px rgba(0,0,0,0.04)}
				.aspl-model-card:hover{border-color:#a7c7e7; transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,0.08)}
				.aspl-model-card.is-selected{border-color:#2271b1;box-shadow:0 0 0 3px rgba(34,113,177,.18); background:#f0f6ff}
				.aspl-model-thumb{width:100%;height:96px;background:#0b0b0c;overflow:hidden;position:relative; border-bottom:1px solid #e5e5e7}
				.aspl-model-thumb iframe{width:320px;height:205px;border:0;transform:scale(0.475);transform-origin:top left;pointer-events:none;background:#0b0b0c; display:block}
				.aspl-model-thumb--custom{display:flex;align-items:center;justify-content:center;font-size:28px;background:linear-gradient(135deg,#f6f7f7 0%,#eef2f7 100%)}
				.aspl-model-name{font-weight:700;font-size:12px;margin-top:8px; color:#1d1d1f}
				.aspl-model-key{font-size:10px;color:#787c82; background:#f6f7f7; padding:1px 6px; border-radius:999px; margin-top:3px; font-family:monospace}
				.aspl-switch { position: relative; display: inline-block; width: 44px; height: 24px; vertical-align: middle; }
				.aspl-switch input { opacity: 0; width: 0; height: 0; }
				.aspl-switch .aspl-track { position: absolute; inset: 0; background: #dcdcde; border-radius: 999px; transition: background 0.2s; }
				.aspl-switch .aspl-knob { position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.2s; }
				.aspl-switch input:checked + .aspl-track { background: #2271b1; }
				.aspl-switch input:checked + .aspl-track .aspl-knob { transform: translateX(20px); }
				.aspl-switch--red input:checked + .aspl-track { background: #d63638; }
				.aspl-card--maint { background: #fff8f8; border-color: #f0c0c0; }
			</style>
		</div>
		<?php
	}

	public function render_section_general() { echo '<p>' . esc_html__( 'Enable the loader and choose where it should appear.', 'apple-star-loader' ) . '</p>'; }
	public function render_section_design() { echo '<p>' . esc_html__( 'Choose one of the 10 built-in designs or use Custom to write your own code.', 'apple-star-loader' ) . '</p>'; }
	public function render_section_code() { echo '<p>' . esc_html__( 'The complete HTML + CSS of the loading screen. This field is fully open — replace or edit it any time with your own design, then press Save.', 'apple-star-loader' ) . '</p>'; }
	public function render_section_timing() { echo '<p>' . esc_html__( 'Safety net for slow or broken pages.', 'apple-star-loader' ) . '</p>'; }
	public function render_section_maintenance() { echo '<p>' . esc_html__( 'Show a full-screen maintenance page to visitors while you work. Admins always bypass it.', 'apple-star-loader' ) . '</p>'; }

	public function field_enabled( $args ) {
		$options = self::get_options();
		?>
		<label class="aspl-switch">
			<input type="checkbox" name="<?php echo esc_attr( $args['option_name'] ); ?>[enabled]" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?> />
			<span class="aspl-track"><span class="aspl-knob"></span></span>
		</label>
		<span style="margin-left:8px;<?php echo ! empty( $options['enabled'] ) ? 'color:#1e7a3d;' : 'color:#666;'; ?>"><?php echo ! empty( $options['enabled'] ) ? esc_html__( 'ON', 'apple-star-loader' ) : esc_html__( 'OFF', 'apple-star-loader' ); ?></span>
		<p class="description"><?php esc_html_e( 'Turn the loader on or off without touching your custom code.', 'apple-star-loader' ); ?></p>
		<?php
	}

	public function field_target( $args ) {
		$options = self::get_options();
		$targets = array(
			'front_page' => __( 'Front page only (recommended for heavy pages)', 'apple-star-loader' ),
			'all_pages'  => __( 'All pages of the site', 'apple-star-loader' ),
		);
		foreach ( $targets as $value => $label ) : ?>
			<label style="display:block;margin-bottom:4px;">
				<input type="radio" name="<?php echo esc_attr( $args['option_name'] ); ?>[target]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $options['target'], $value ); ?> />
				<?php echo esc_html( $label ); ?>
			</label>
		<?php endforeach;
		echo '<p class="description">' . esc_html__( 'Front page = the main landing page of your site (a static page or your blog index).', 'apple-star-loader' ) . '</p>';
	}

	public function field_model( $args ) {
		$options = self::get_options();
		$current = isset( $options['model'] ) ? $options['model'] : ASPL_Defaults::DEFAULT_DESIGN;
		$designs = ASPL_Designs::get_designs();
		?>
		<div class="aspl-models-grid">
		<?php foreach ( $designs as $key => $info ) : ?>
			<label class="aspl-model-card <?php echo $current === $key ? 'is-selected' : ''; ?>" data-model="<?php echo esc_attr( $key ); ?>">
				<input type="radio" name="<?php echo esc_attr( $args['option_name'] ); ?>[model]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current, $key ); ?> hidden />
				<div class="aspl-model-thumb"><iframe sandbox="" title="<?php echo esc_attr( $info['name'] ); ?>" data-thumb="<?php echo esc_attr( $key ); ?>"></iframe></div>
				<span class="aspl-model-name"><?php echo esc_html( $info['name'] ); ?></span>
				<span class="aspl-model-key"><?php echo esc_html( $key ); ?></span>
			</label>
		<?php endforeach; ?>
			<label class="aspl-model-card aspl-model-card--custom <?php echo 'custom' === $current ? 'is-selected' : ''; ?>" data-model="custom">
				<input type="radio" name="<?php echo esc_attr( $args['option_name'] ); ?>[model]" value="custom" <?php checked( $current, 'custom' ); ?> hidden />
				<div class="aspl-model-thumb aspl-model-thumb--custom"><span>✏️</span></div>
				<span class="aspl-model-name"><?php esc_html_e( 'Custom', 'apple-star-loader' ); ?></span>
				<span class="aspl-model-key">custom</span>
			</label>
		</div>
		<p class="description"><?php esc_html_e( 'Click a design to preview it instantly below and load its code into the textarea. Custom leaves your code untouched.', 'apple-star-loader' ); ?></p>
		<?php
	}

	public function field_code( $args ) {
		$options = self::get_options();
		?>
		<textarea id="aspl-code" class="aspl-code large-text code" name="<?php echo esc_attr( $args['option_name'] ); ?>[code]" rows="16" dir="ltr" spellcheck="false"><?php echo esc_textarea( $options['code'] ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Complete HTML + CSS of your loader (the built-in design or your own). Keep it self-contained — the plugin wraps the code in one block and removes it together when the page is ready. The live preview below updates while you type.', 'apple-star-loader' ); ?></p>
		<p><button type="button" class="button" id="aspl-reset-code"><?php esc_html_e( 'Reset to default code', 'apple-star-loader' ); ?></button> <span class="description"><?php esc_html_e( 'Restores the built-in responsive "Apple Star" code (remember to press Save).', 'apple-star-loader' ); ?></span></p>
		<?php
	}

	public function field_timeout( $args ) {
		$options = self::get_options();
		?>
		<input type="number" class="small-text" name="<?php echo esc_attr( $args['option_name'] ); ?>[timeout]" value="<?php echo esc_attr( (int) $options['timeout'] ); ?>" min="1" max="120" step="1" />
		<span class="aspl-unit"><?php esc_html_e( 'seconds', 'apple-star-loader' ); ?></span>
		<p class="description"><?php esc_html_e( 'If the page does not fire the "load" event within this time, the loader closes anyway so the site can never stay locked. Allowed range: 1–120 seconds.', 'apple-star-loader' ); ?></p>
		<?php
	}

	public function field_maintenance_enabled( $args ) {
		$options = self::get_options();
		$enabled = ! empty( $options['maintenance_enabled'] );
		$end     = isset( $options['countdown_end'] ) ? (int) $options['countdown_end'] : 0;
		?>
		<label class="aspl-switch aspl-switch--red">
			<input type="checkbox" id="aspl-maintenance-enabled" name="<?php echo esc_attr( $args['option_name'] ); ?>[maintenance_enabled]" value="1" <?php checked( $enabled ); ?> />
			<span class="aspl-track"><span class="aspl-knob"></span></span>
		</label>
		<span style="margin-left:8px;<?php echo $enabled ? 'color:#b91c1c;' : 'color:#666;'; ?>"><?php echo $enabled ? esc_html__( 'ON', 'apple-star-loader' ) : esc_html__( 'OFF', 'apple-star-loader' ); ?></span>
		<?php
		$status_text = '';
		if ( $enabled ) {
			if ( $end > time() ) {
				$date = wp_date( 'Y-m-d H:i', $end );
				$left = human_time_diff( time(), $end );
				$status_text = sprintf( __( 'Countdown ends at %s (about %s left). The site opens automatically when the timer reaches zero.', 'apple-star-loader' ), $date, $left );
			} else if ( 0 === $end ) {
				$status_text = __( 'Maintenance is ON without a countdown. Turn it off manually.', 'apple-star-loader' );
			} else {
				$status_text = __( 'Countdown has expired — maintenance will turn off on next visit.', 'apple-star-loader' );
			}
		}
		?>
		<div id="aspl-maintenance-status" class="aspl-card aspl-card--maint" style="<?php echo $enabled ? '' : 'display:none'; ?>;margin-top:10px;">
			<p><?php echo esc_html( $status_text ); ?></p>
		</div>
		<p class="description"><?php esc_html_e( 'When ON, visitors see the full-screen maintenance page. Admins bypass it and see a top banner.', 'apple-star-loader' ); ?></p>
		<?php
	}

	public function field_maintenance_message( $args ) {
		$options = self::get_options();
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( $args['option_name'] ); ?>[maintenance_message]" value="<?php echo esc_attr( $options['maintenance_message'] ); ?>" placeholder="<?php esc_attr_e( "We'll be back soon", 'apple-star-loader' ); ?>" />
		<p class="description"><?php esc_html_e( 'Message shown on the maintenance page.', 'apple-star-loader' ); ?></p>
		<?php
	}

	public function field_countdown( $args ) {
		$options = self::get_options();
		$type = $options['countdown_type'];
		$hours = (int) $options['countdown_hours'];
		$end   = (int) $options['countdown_end'];
		$dtVal = '';
		if ( $end > time() && 'datetime' === $type ) {
			$dtVal = wp_date( 'Y-m-d\TH:i', $end );
		}
		?>
		<label style="display:block;margin-bottom:4px;"><input type="radio" name="<?php echo esc_attr( $args['option_name'] ); ?>[countdown_type]" value="off" <?php checked( $type, 'off' ); ?> /> <?php esc_html_e( 'No countdown', 'apple-star-loader' ); ?></label>
		<label style="display:block;margin-bottom:4px;"><input type="radio" name="<?php echo esc_attr( $args['option_name'] ); ?>[countdown_type]" value="hours" <?php checked( $type, 'hours' ); ?> /> <?php esc_html_e( 'Count down from now', 'apple-star-loader' ); ?></label>
		<label style="display:block;margin-bottom:4px;"><input type="radio" name="<?php echo esc_attr( $args['option_name'] ); ?>[countdown_type]" value="datetime" <?php checked( $type, 'datetime' ); ?> /> <?php esc_html_e( 'Until a specific date & time', 'apple-star-loader' ); ?></label>

		<div id="aspl-count-hours-box" style="<?php echo 'hours' === $type ? '' : 'display:none'; ?>;margin-top:8px;">
			<input type="number" class="small-text" name="<?php echo esc_attr( $args['option_name'] ); ?>[countdown_hours]" value="<?php echo esc_attr( $hours ); ?>" min="1" max="240" step="1" />
			<span class="aspl-unit"><?php esc_html_e( 'hours', 'apple-star-loader' ); ?></span>
		</div>
		<div id="aspl-count-dt-box" style="<?php echo 'datetime' === $type ? '' : 'display:none'; ?>;margin-top:8px;">
			<input type="datetime-local" name="<?php echo esc_attr( $args['option_name'] ); ?>[countdown_end_raw]" value="<?php echo esc_attr( $dtVal ); ?>" />
		</div>
		<p class="description"><?php esc_html_e( 'Default is 48 hours. When the timer reaches zero, maintenance turns off automatically.', 'apple-star-loader' ); ?></p>
		<?php
	}
}
