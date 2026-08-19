<?php
/**
 * Admin: professional "Apple Star Loader" settings dashboard v2.0.1.
 *
 * Tabs: General | Design | Content | Timing | Custom Code | Preview
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the admin menu and settings page.
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue color picker + media uploader assets on our page.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, self::MENU_SLUG ) && false === strpos( $hook_suffix, self::SETTINGS_SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script( 'wp-color-picker' );
		// Code editor (CodeMirror) for the custom-code tab.
		if ( function_exists( 'wp_enqueue_code_editor' ) ) {
			wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		}
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".aspl-color").wpColorPicker();});' );
	}

	/**
	 * Seeds default options on activation.
	 */
	public static function activate() {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, ASPL_Defaults::get_options() );
		} else {
			// Merge new v2 defaults into old v1 settings so nothing is lost.
			$saved   = get_option( self::OPTION, array() );
			$merged  = wp_parse_args( (array) $saved, ASPL_Defaults::get_options() );
			update_option( self::OPTION, $merged );
		}
	}

	/**
	 * Returns saved options merged with defaults.
	 *
	 * @return array
	 */
	public static function get_options() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( (array) $saved, ASPL_Defaults::get_options() );
	}

	/**
	 * Registers admin menu entries.
	 */
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
			__( 'Dashboard', 'apple-star-loader' ),
			__( 'Dashboard', 'apple-star-loader' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'apple-star-loader' ),
			__( 'Settings', 'apple-star-loader' ),
			self::CAPABILITY,
			self::MENU_SLUG . '-settings',
			array( $this, 'render_page' )
		);
		add_submenu_page(
			'options-general.php',
			__( 'Apple Star Loader', 'apple-star-loader' ),
			__( 'Apple Star Loader', 'apple-star-loader' ),
			self::CAPABILITY,
			self::SETTINGS_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registers the single setting (one options array).
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
	}

	/**
	 * Sanitizes the options array.
	 *
	 * @param mixed $input Raw.
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$clean   = array();
		$presets = array_keys( ASPL_Defaults::get_presets() );

		$clean['enabled']           = empty( $input['enabled'] ) ? 0 : 1;
		$clean['target']            = isset( $input['target'] ) ? sanitize_key( $input['target'] ) : 'all_pages';
		$targets                    = array( 'front_page', 'all_pages', 'home_posts', 'posts_only', 'pages_only', 'woocommerce' );
		if ( ! in_array( $clean['target'], $targets, true ) ) {
			$clean['target'] = 'all_pages';
		}

		$clean['preset']            = isset( $input['preset'] ) ? sanitize_key( $input['preset'] ) : 'apple_star';
		if ( ! in_array( $clean['preset'], $presets, true ) ) {
			$clean['preset'] = 'apple_star';
		}

		$clean['text']              = isset( $input['text'] ) ? sanitize_text_field( $input['text'] ) : 'LOADING';
		$clean['logo']              = isset( $input['logo'] ) ? esc_url_raw( $input['logo'] ) : '';
		$clean['show_percentage']   = ! empty( $input['show_percentage'] ) ? 1 : 0;
		$clean['show_progress_bar'] = ! empty( $input['show_progress_bar'] ) ? 1 : 0;
		$clean['show_tips']         = ! empty( $input['show_tips'] ) ? 1 : 0;

		$clean['bg_color']          = isset( $input['bg_color'] ) ? sanitize_hex_color( $input['bg_color'] ) : '#0b0b0f';
		if ( ! $clean['bg_color'] ) { $clean['bg_color'] = '#0b0b0f'; }
		$clean['bg_opacity']        = isset( $input['bg_opacity'] ) ? max( 0, min( 100, absint( $input['bg_opacity'] ) ) ) : 100;
		$clean['primary_color']     = isset( $input['primary_color'] ) ? sanitize_hex_color( $input['primary_color'] ) : '#ffffff';
		if ( ! $clean['primary_color'] ) { $clean['primary_color'] = '#ffffff'; }
		$clean['accent_color']      = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '#0071e3';
		if ( ! $clean['accent_color'] ) { $clean['accent_color'] = '#0071e3'; }
		$clean['text_color']        = isset( $input['text_color'] ) ? sanitize_hex_color( $input['text_color'] ) : '#ffffff';
		if ( ! $clean['text_color'] ) { $clean['text_color'] = '#ffffff'; }
		$clean['bar_bg_color']      = isset( $input['bar_bg_color'] ) ? sanitize_text_field( $input['bar_bg_color'] ) : 'rgba(255,255,255,0.15)';
		$clean['bar_fg_color']      = isset( $input['bar_fg_color'] ) ? sanitize_text_field( $input['bar_fg_color'] ) : '#ffffff';

		$clean['timeout']           = isset( $input['timeout'] ) ? max( 1, min( 120, absint( $input['timeout'] ) ) ) : 15;
		$clean['min_time']          = isset( $input['min_time'] ) ? max( 0, min( 10000, absint( $input['min_time'] ) ) ) : 600;
		$clean['fade_duration']     = isset( $input['fade_duration'] ) ? max( 100, min( 5000, absint( $input['fade_duration'] ) ) ) : 600;

		$clean['hide_for_logged_in'] = ! empty( $input['hide_for_logged_in'] ) ? 1 : 0;
		$clean['show_on_mobile']     = ! empty( $input['show_on_mobile'] ) ? 1 : 0;
		$clean['blur_amount']        = isset( $input['blur_amount'] ) ? max( 0, min( 50, absint( $input['blur_amount'] ) ) ) : 0;
		$clean['z_index']            = isset( $input['z_index'] ) ? max( 1000, absint( $input['z_index'] ) ) : 99999999;

		$clean['custom_css']        = isset( $input['custom_css'] ) ? (string) $input['custom_css'] : '';
		$clean['code']              = isset( $input['code'] ) ? trim( (string) $input['code'] ) : '';

		return $clean;
	}

	/**
	 * Tabs definition.
	 */
	private function get_tabs() {
		return array(
			'dashboard' => __( '🚀 Dashboard', 'apple-star-loader' ),
			'general'   => __( '⚙️ General', 'apple-star-loader' ),
			'design'    => __( '🎨 Design', 'apple-star-loader' ),
			'content'   => __( '📝 Content', 'apple-star-loader' ),
			'timing'    => __( '⏱️ Timing', 'apple-star-loader' ),
			'advanced'  => __( '🧩 Advanced', 'apple-star-loader' ),
		);
	}

	/**
	 * Convert hex to rgba for preview.
	 */
	private function hex_to_rgba( $hex, $alpha = 100 ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return 'rgba(0,0,0,' . ( $alpha / 100 ) . ')';
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . ( $alpha / 100 ) . ')';
	}

	/**
	 * Renders the entire settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'apple-star-loader' ) );
		}

		$options      = self::get_options();
		$presets      = ASPL_Defaults::get_presets();
		$current_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! array_key_exists( $current_tab, $this->get_tabs() ) ) {
			$current_tab = 'dashboard';
		}

		$preset_codes = array();
		foreach ( $presets as $slug => $label ) {
			if ( 'custom_code' === $slug ) {
				$preset_codes[ $slug ] = $options['code'];
			} else {
				$preset_codes[ $slug ] = ASPL_Defaults::get_preset_code( $slug );
			}
		}
		$bg_rgba = $this->hex_to_rgba( $options['bg_color'], $options['bg_opacity'] );
		?>
		<div class="wrap aspl-admin">
			<div class="aspl-topbar">
				<div class="aspl-topbar-left">
					<span class="aspl-logo-badge">✦</span>
					<div class="aspl-topbar-titles">
						<h1 class="aspl-title">Apple Star Loader <span class="aspl-version">v<?php echo esc_html( ASPL_VERSION ); ?></span></h1>
						<div class="aspl-subtitle"><?php esc_html_e( 'Professional animated preloader for WordPress', 'apple-star-loader' ); ?></div>
					</div>
				</div>
				<div class="aspl-topbar-right">
					<?php if ( ! empty( $options['enabled'] ) ) : ?>
						<span class="aspl-status aspl-status--on"><span class="aspl-status-dot"></span><?php esc_html_e( 'Active', 'apple-star-loader' ); ?></span>
					<?php else : ?>
						<span class="aspl-status aspl-status--off"><span class="aspl-status-dot"></span><?php esc_html_e( 'Disabled', 'apple-star-loader' ); ?></span>
					<?php endif; ?>
					<button type="button" class="button aspl-preview-quick" id="aspl-preview-quick"><?php esc_html_e( '▶ Live Preview', 'apple-star-loader' ); ?></button>
				</div>
			</div>

			<nav class="aspl-tabs">
				<?php foreach ( $this->get_tabs() as $slug => $label ) : ?>
					<a class="aspl-tab <?php echo $current_tab === $slug ? 'aspl-tab--active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" id="aspl-form" class="aspl-form">
				<?php settings_fields( self::GROUP ); ?>
				<input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>" />

				<div class="aspl-grid">
					<div class="aspl-main">
						<?php if ( 'dashboard' === $current_tab ) : ?>
							<?php $this->render_dashboard( $options ); ?>
						<?php elseif ( 'general' === $current_tab ) : ?>
							<?php $this->render_general( $options ); ?>
						<?php elseif ( 'design' === $current_tab ) : ?>
							<?php $this->render_design( $options, $presets ); ?>
						<?php elseif ( 'content' === $current_tab ) : ?>
							<?php $this->render_content( $options ); ?>
						<?php elseif ( 'timing' === $current_tab ) : ?>
							<?php $this->render_timing( $options ); ?>
						<?php elseif ( 'advanced' === $current_tab ) : ?>
							<?php $this->render_advanced( $options ); ?>
						<?php endif; ?>

						<div class="aspl-actions">
							<?php submit_button( __( '💾 Save Settings', 'apple-star-loader' ), 'primary', 'submit', false, array( 'class' => 'button button-primary button-hero aspl-save-btn' ) ); ?>
							<button type="button" class="button button-hero aspl-reset-btn" id="aspl-reset-all"><?php esc_html_e( '↺ Reset to defaults', 'apple-star-loader' ); ?></button>
						</div>
					</div>

					<aside class="aspl-side">
						<div class="aspl-card aspl-side-preview">
							<div class="aspl-card-head">
								<h3><?php esc_html_e( 'Instant Preview', 'apple-star-loader' ); ?></h3>
								<div class="aspl-device-switch">
									<button type="button" class="aspl-device-btn active" data-w="375" title="Mobile">📱</button>
									<button type="button" class="aspl-device-btn" data-w="768" title="Tablet">📄</button>
									<button type="button" class="aspl-device-btn" data-w="1200" title="Desktop">🖥️</button>
								</div>
							</div>
				<div class="aspl-preview-box">
						<iframe id="aspl-frame" class="aspl-preview-frame" title="<?php esc_attr_e( 'Loader preview', 'apple-star-loader' ); ?>" sandbox="allow-same-origin allow-scripts allow-popups"></iframe>
				</div>
							<p class="description aspl-hint"><?php esc_html_e( 'Updates live as you change settings. Tap Live Preview for fullscreen.', 'apple-star-loader' ); ?></p>
						</div>

						<div class="aspl-card">
							<h3><?php esc_html_e( 'Quick Tips', 'apple-star-loader' ); ?></h3>
							<ul class="aspl-tips-list">
								<li><?php esc_html_e( 'Progress % is estimated from loaded images and network timing.', 'apple-star-loader' ); ?></li>
								<li><?php esc_html_e( 'Set Min Display Time (≥400ms) to avoid flicker on fast loads.', 'apple-star-loader' ); ?></li>
								<li><?php esc_html_e( 'Fallback Timeout ensures your site never stays locked.', 'apple-star-loader' ); ?></li>
								<li><?php esc_html_e( 'Use the Custom CSS box for extra tweaks without touching presets.', 'apple-star-loader' ); ?></li>
							</ul>
						</div>

						<div class="aspl-card aspl-card--stats">
							<h3><?php esc_html_e( 'System Info', 'apple-star-loader' ); ?></h3>
							<div class="aspl-stat"><span><?php esc_html_e( 'Version', 'apple-star-loader' ); ?></span><b><?php echo esc_html( ASPL_VERSION ); ?></b></div>
							<div class="aspl-stat"><span><?php esc_html_e( 'Active preset', 'apple-star-loader' ); ?></span><b><?php echo esc_html( isset( $presets[ $options['preset'] ] ) ? $presets[ $options['preset'] ] : $options['preset'] ); ?></b></div>
							<div class="aspl-stat"><span><?php esc_html_e( 'Target', 'apple-star-loader' ); ?></span><b><?php echo esc_html( $options['target'] ); ?></b></div>
							<div class="aspl-stat"><span><?php esc_html_e( 'Timeout', 'apple-star-loader' ); ?></span><b><?php echo esc_html( $options['timeout'] ); ?>s</b></div>
						</div>
					</aside>
				</div>
			</form>
		</div>

		<!-- Fullscreen preview modal -->
		<div class="aspl-modal" id="aspl-modal" aria-hidden="true">
			<div class="aspl-modal-backdrop"></div>
			<div class="aspl-modal-frame">
				<button type="button" class="aspl-modal-close" id="aspl-modal-close" aria-label="<?php esc_attr_e( 'Close', 'apple-star-loader' ); ?>">✕</button>
				<iframe id="aspl-modal-frame" class="aspl-modal-iframe" sandbox="allow-same-origin allow-scripts allow-popups"></iframe>
			</div>
		</div>

		<!-- Hidden default codes for JS (used by Reset and live preview) -->
		<script>
			window.ASPL = {
				defaults: <?php echo wp_json_encode( ASPL_Defaults::get_options(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>,
				presets:  <?php echo wp_json_encode( $preset_codes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>,
				site:     <?php echo wp_json_encode( array( 'logo' => $options['logo'] ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>,
				isRtl:    <?php echo is_rtl() ? 'true' : 'false'; ?>
			};
		</script>

		<style>
			<?php echo $this->get_admin_css(); ?>
		</style>
		<script>
			<?php echo $this->get_admin_js(); ?>
		</script>
		<?php
	}

	/* -- Dashboard tab --------------------------------------------------- */
	private function render_dashboard( $options ) {
		$presets = ASPL_Defaults::get_presets();
		?>
		<div class="aspl-card aspl-hero">
			<div class="aspl-hero-msg">
				<h2><?php esc_html_e( 'Welcome to Apple Star Loader 2.0!', 'apple-star-loader' ); ?> 🎉</h2>
				<p><?php esc_html_e( 'نسخه جدید با حروف موجی، درصد شمارنده پرش‌کننده، نوار پیشرفت متحرک، ۱۰ طرح آماده، پشتیبانی کامل فارسی (حروف به‌هم نمی‌ریزند)، آپلود لوگو، رنگ‌بندی، نکات چرخشی، پیش‌نمایش زنده و ده‌ها امکانات حرفه‌ای دیگر.', 'apple-star-loader' ); ?></p>
			</div>
			<div class="aspl-hero-stats">
				<div class="aspl-hero-stat"><b>10</b><span><?php esc_html_e( 'Presets', 'apple-star-loader' ); ?></span></div>
				<div class="aspl-hero-stat"><b>∞</b><span><?php esc_html_e( 'Color combos', 'apple-star-loader' ); ?></span></div>
				<div class="aspl-hero-stat"><b>0–100</b><span><?php esc_html_e( 'Progress', 'apple-star-loader' ); ?></span></div>
				<div class="aspl-hero-stat"><b>100%</b><span><?php esc_html_e( 'Responsive', 'apple-star-loader' ); ?></span></div>
			</div>
		</div>

		<div class="aspl-card">
			<h3><?php esc_html_e( 'Quick Start', 'apple-star-loader' ); ?> ⚡</h3>
			<div class="aspl-quick-grid">
				<a class="aspl-quick" href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'general' ), admin_url( 'admin.php' ) ) ); ?>">
					<span class="aspl-quick-ico">⚙️</span>
					<span class="aspl-quick-title"><?php esc_html_e( 'Toggle on/off', 'apple-star-loader' ); ?></span>
					<span class="aspl-quick-desc"><?php esc_html_e( 'Enable the loader and pick where it should appear on your site.', 'apple-star-loader' ); ?></span>
				</a>
				<a class="aspl-quick" href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'design' ), admin_url( 'admin.php' ) ) ); ?>">
					<span class="aspl-quick-ico">🎨</span>
					<span class="aspl-quick-title"><?php esc_html_e( 'Pick a preset', 'apple-star-loader' ); ?></span>
					<span class="aspl-quick-desc"><?php esc_html_e( 'Choose one of 6 animated designs and customize its colors.', 'apple-star-loader' ); ?></span>
				</a>
				<a class="aspl-quick" href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'content' ), admin_url( 'admin.php' ) ) ); ?>">
					<span class="aspl-quick-ico">🖼️</span>
					<span class="aspl-quick-title"><?php esc_html_e( 'Add your logo', 'apple-star-loader' ); ?></span>
					<span class="aspl-quick-desc"><?php esc_html_e( 'Upload your logo and customize the loading text.', 'apple-star-loader' ); ?></span>
				</a>
				<a class="aspl-quick" href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'timing' ), admin_url( 'admin.php' ) ) ); ?>">
					<span class="aspl-quick-ico">⏱️</span>
					<span class="aspl-quick-title"><?php esc_html_e( 'Tune timing', 'apple-star-loader' ); ?></span>
					<span class="aspl-quick-desc"><?php esc_html_e( 'Adjust minimum display time, fade duration and fallback timeout.', 'apple-star-loader' ); ?></span>
				</a>
			</div>
		</div>

		<div class="aspl-card">
			<h3><?php esc_html_e( '✨ What\'s new in v2.0', 'apple-star-loader' ); ?></h3>
			<div class="aspl-feature-grid">
				<div class="aspl-feature">🌊 <b><?php esc_html_e( 'Wave-animated letters', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'Every character in the wordmark rises and falls in a smooth cascading wave.', 'apple-star-loader' ); ?></p></div>
				<div class="aspl-feature">🔢 <b><?php esc_html_e( 'Bouncing percentage counter', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'The numbers actually animate and bounce as the counter goes 0→100.', 'apple-star-loader' ); ?></p></div>
				<div class="aspl-feature">📊 <b><?php esc_html_e( 'Animated progress bar', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'Smooth-filling bars, shine effects, traveling dots — per 10% increments.', 'apple-star-loader' ); ?></p></div>
				<div class="aspl-feature">🎨 <b><?php esc_html_e( '10 professional presets', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'Apple Star ECG, Wave Letters, Spinner Pro, Progress Bar, Pulse Ring, Equalizer Bars, Dots Bounce, Neon Line, Particles & Minimal Dot.', 'apple-star-loader' ); ?></p></div>
			<div class="aspl-feature">🆎 <b><?php esc_html_e( 'RTL / Persian support', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'Persian & Arabic text is animated word-by-word so letters stay connected and never appear mirrored.', 'apple-star-loader' ); ?></p></div>
				<div class="aspl-feature">🖼️ <b><?php esc_html_e( 'Logo + text branding', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'Upload your logo, change the loading text, and match your brand colors.', 'apple-star-loader' ); ?></p></div>
				<div class="aspl-feature">💬 <b><?php esc_html_e( 'Rotating tips', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'Friendly messages cycle while visitors wait.', 'apple-star-loader' ); ?></p></div>
				<div class="aspl-feature">🎛️ <b><?php esc_html_e( 'Redesigned dashboard', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'Clean tabbed UI with live side preview and color pickers.', 'apple-star-loader' ); ?></p></div>
				<div class="aspl-feature">🧩 <b><?php esc_html_e( 'Custom CSS + code', 'apple-star-loader' ); ?></b><p><?php esc_html_e( 'Bring your own HTML/CSS if the presets aren\'t enough.', 'apple-star-loader' ); ?></p></div>
			</div>
		</div>
		<?php
	}

	/* -- General tab ---------------------------------------------------- */
	private function render_general( $options ) {
		?>
		<div class="aspl-card">
			<h3><?php esc_html_e( 'Loader status', 'apple-star-loader' ); ?></h3>
			<div class="aspl-field">
				<label class="aspl-switch">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enabled]" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?> />
					<span class="aspl-switch-slider"></span>
					<span class="aspl-switch-label"><?php esc_html_e( 'Enable the Apple Star Loader', 'apple-star-loader' ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'Turn the loader on or off for visitors. Admins can always preview.', 'apple-star-loader' ); ?></p>
			</div>
		</div>

		<div class="aspl-card">
			<h3><?php esc_html_e( 'Display target', 'apple-star-loader' ); ?></h3>
			<p class="description aspl-card-desc"><?php esc_html_e( 'Choose which pages the loader should appear on.', 'apple-star-loader' ); ?></p>
			<div class="aspl-radio-grid">
				<?php
				$targets = array(
					'all_pages'   => array( '🌐', __( 'All pages', 'apple-star-loader' ), __( 'Show the loader on every page of the site.', 'apple-star-loader' ) ),
					'front_page'  => array( '🏠', __( 'Front page only', 'apple-star-loader' ), __( 'Only on the homepage / landing page.', 'apple-star-loader' ) ),
					'home_posts'  => array( '📰', __( 'Home & blog', 'apple-star-loader' ), __( 'Front page and blog posts index.', 'apple-star-loader' ) ),
					'posts_only'  => array( '✍️', __( 'Blog posts only', 'apple-star-loader' ), __( 'Single post pages only.', 'apple-star-loader' ) ),
					'pages_only'  => array( '📄', __( 'Pages only', 'apple-star-loader' ), __( 'Standard WP pages (not posts).', 'apple-star-loader' ) ),
					'woocommerce' => array( '🛒', __( 'WooCommerce only', 'apple-star-loader' ), __( 'Shop, product, cart, checkout & account pages.', 'apple-star-loader' ) ),
				);
				foreach ( $targets as $val => $info ) :
					list( $icon, $label, $desc ) = $info;
					?>
					<label class="aspl-radio-card <?php echo checked( $options['target'], $val, false ) ? 'aspl-radio-card--checked' : ''; ?>">
						<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[target]" value="<?php echo esc_attr( $val ); ?>" <?php checked( $options['target'], $val ); ?> />
						<span class="aspl-radio-ico"><?php echo esc_html( $icon ); ?></span>
						<span class="aspl-radio-body"><b><?php echo esc_html( $label ); ?></b><span class="description"><?php echo esc_html( $desc ); ?></span></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="aspl-card">
			<h3><?php esc_html_e( 'Visibility options', 'apple-star-loader' ); ?></h3>
			<div class="aspl-field">
				<label class="aspl-switch">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_on_mobile]" value="1" <?php checked( ! empty( $options['show_on_mobile'] ) ); ?> />
					<span class="aspl-switch-slider"></span>
					<span class="aspl-switch-label"><?php esc_html_e( 'Show on mobile devices', 'apple-star-loader' ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'Disable to hide the loader on phones and tablets.', 'apple-star-loader' ); ?></p>
			</div>
			<div class="aspl-field">
				<label class="aspl-switch">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[hide_for_logged_in]" value="1" <?php checked( ! empty( $options['hide_for_logged_in'] ) ); ?> />
					<span class="aspl-switch-slider"></span>
					<span class="aspl-switch-label"><?php esc_html_e( 'Hide for logged-in users', 'apple-star-loader' ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'When enabled, only visitors who are NOT logged in see the loader.', 'apple-star-loader' ); ?></p>
			</div>
		</div>
		<?php
	}

	/* -- Design tab ----------------------------------------------------- */
	private function render_design( $options, $presets ) {
		?>
		<div class="aspl-card">
			<h3><?php esc_html_e( 'Choose a preset', 'apple-star-loader' ); ?></h3>
			<p class="description aspl-card-desc"><?php esc_html_e( 'Pick an animated design. You can customize colors and content on top of it.', 'apple-star-loader' ); ?></p>
			<div class="aspl-preset-grid">
				<?php
				$preset_icons = array(
					'apple_star'   => '✦',
					'wave_letters' => '🌊',
					'spinner_pro'  => '🎯',
					'progress_bar' => '📊',
					'pulse_ring'   => '◎',
					'bars'         => '📶',
					'dots_bounce'  => '⋯',
					'neon_line'    => '━',
					'particles'    => '✨',
					'minimal_dot'  => '●',
					'custom_code'  => '🧩',
				);
				foreach ( $presets as $slug => $label ) :
					$icon = isset( $preset_icons[ $slug ] ) ? $preset_icons[ $slug ] : '✦';
					?>
					<label class="aspl-preset-card <?php echo checked( $options['preset'], $slug, false ) ? 'aspl-preset-card--checked' : ''; ?>" data-preset="<?php echo esc_attr( $slug ); ?>">
						<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[preset]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $options['preset'], $slug ); ?> />
						<span class="aspl-preset-preview" data-preset-preview="<?php echo esc_attr( $slug ); ?>"><span class="aspl-preset-ico"><?php echo esc_html( $icon ); ?></span></span>
						<span class="aspl-preset-label"><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="aspl-card">
			<h3><?php esc_html_e( 'Colors', 'apple-star-loader' ); ?> 🎨</h3>
			<div class="aspl-color-grid">
				<div class="aspl-field">
					<label><?php esc_html_e( 'Background color', 'apple-star-loader' ); ?></label>
					<input type="text" class="aspl-color" name="<?php echo esc_attr( self::OPTION ); ?>[bg_color]" value="<?php echo esc_attr( $options['bg_color'] ); ?>" data-default-color="#0b0b0f" />
				</div>
				<div class="aspl-field aspl-range-field">
					<label><?php esc_html_e( 'Background opacity', 'apple-star-loader' ); ?> — <span class="aspl-range-val" data-target="bg_opacity"><?php echo (int) $options['bg_opacity']; ?></span>%</label>
					<input type="range" name="<?php echo esc_attr( self::OPTION ); ?>[bg_opacity]" min="0" max="100" value="<?php echo (int) $options['bg_opacity']; ?>" class="aspl-range" data-preview="bg_opacity" />
				</div>
				<div class="aspl-field">
					<label><?php esc_html_e( 'Text color', 'apple-star-loader' ); ?></label>
					<input type="text" class="aspl-color" name="<?php echo esc_attr( self::OPTION ); ?>[text_color]" value="<?php echo esc_attr( $options['text_color'] ); ?>" data-default-color="#ffffff" />
				</div>
				<div class="aspl-field">
					<label><?php esc_html_e( 'Accent color', 'apple-star-loader' ); ?></label>
					<input type="text" class="aspl-color" name="<?php echo esc_attr( self::OPTION ); ?>[accent_color]" value="<?php echo esc_attr( $options['accent_color'] ); ?>" data-default-color="#0071e3" />
				</div>
				<div class="aspl-field">
					<label><?php esc_html_e( 'Primary color', 'apple-star-loader' ); ?></label>
					<input type="text" class="aspl-color" name="<?php echo esc_attr( self::OPTION ); ?>[primary_color]" value="<?php echo esc_attr( $options['primary_color'] ); ?>" data-default-color="#ffffff" />
				</div>
				<div class="aspl-field">
					<label><?php esc_html_e( 'Progress bar track', 'apple-star-loader' ); ?></label>
					<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[bar_bg_color]" value="<?php echo esc_attr( $options['bar_bg_color'] ); ?>" class="aspl-textinput" />
					<p class="description"><?php esc_html_e( 'Any CSS color (e.g. rgba(255,255,255,0.15)).', 'apple-star-loader' ); ?></p>
				</div>
				<div class="aspl-field">
					<label><?php esc_html_e( 'Progress bar fill', 'apple-star-loader' ); ?></label>
					<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[bar_fg_color]" value="<?php echo esc_attr( $options['bar_fg_color'] ); ?>" class="aspl-textinput" />
				</div>
				<div class="aspl-field aspl-range-field">
					<label><?php esc_html_e( 'Background blur', 'apple-star-loader' ); ?> — <span class="aspl-range-val" data-target="blur_amount"><?php echo (int) $options['blur_amount']; ?></span>px</label>
					<input type="range" name="<?php echo esc_attr( self::OPTION ); ?>[blur_amount]" min="0" max="30" value="<?php echo (int) $options['blur_amount']; ?>" class="aspl-range" data-preview="blur_amount" />
				</div>
			</div>
		</div>

		<div class="aspl-card">
			<h3><?php esc_html_e( 'Elements to show', 'apple-star-loader' ); ?></h3>
			<div class="aspl-field">
				<label class="aspl-switch">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_percentage]" value="1" <?php checked( ! empty( $options['show_percentage'] ) ); ?> />
					<span class="aspl-switch-slider"></span>
					<span class="aspl-switch-label"><?php esc_html_e( 'Percentage counter (0–100%)', 'apple-star-loader' ); ?></span>
				</label>
			</div>
			<div class="aspl-field">
				<label class="aspl-switch">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_progress_bar]" value="1" <?php checked( ! empty( $options['show_progress_bar'] ) ); ?> />
					<span class="aspl-switch-slider"></span>
					<span class="aspl-switch-label"><?php esc_html_e( 'Animated progress bar', 'apple-star-loader' ); ?></span>
				</label>
			</div>
			<div class="aspl-field">
				<label class="aspl-switch">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_tips]" value="1" <?php checked( ! empty( $options['show_tips'] ) ); ?> />
					<span class="aspl-switch-slider"></span>
					<span class="aspl-switch-label"><?php esc_html_e( 'Rotating loading tips', 'apple-star-loader' ); ?></span>
				</label>
			</div>
		</div>
		<?php
	}

	/* -- Content tab ---------------------------------------------------- */
	private function render_content( $options ) {
		?>
		<div class="aspl-card">
			<h3><?php esc_html_e( 'Logo', 'apple-star-loader' ); ?></h3>
			<p class="description aspl-card-desc"><?php esc_html_e( 'Upload a logo to display above the loader (recommended: transparent PNG/SVG, up to 220px wide).', 'apple-star-loader' ); ?></p>
			<div class="aspl-logo-upload">
				<div class="aspl-logo-preview" id="aspl-logo-preview">
					<?php if ( ! empty( $options['logo'] ) ) : ?>
						<img src="<?php echo esc_url( $options['logo'] ); ?>" alt="" />
					<?php else : ?>
						<span class="aspl-logo-placeholder"><?php esc_html_e( 'No logo selected', 'apple-star-loader' ); ?></span>
					<?php endif; ?>
				</div>
				<input type="hidden" id="aspl-logo-input" name="<?php echo esc_attr( self::OPTION ); ?>[logo]" value="<?php echo esc_attr( $options['logo'] ); ?>" />
				<div class="aspl-logo-actions">
					<button type="button" class="button" id="aspl-logo-upload"><?php esc_html_e( 'Choose / Upload logo', 'apple-star-loader' ); ?></button>
					<button type="button" class="button aspl-link-danger" id="aspl-logo-remove"><?php esc_html_e( 'Remove', 'apple-star-loader' ); ?></button>
				</div>
			</div>
		</div>

		<div class="aspl-card">
			<h3><?php esc_html_e( 'Loading text', 'apple-star-loader' ); ?></h3>
			<div class="aspl-field">
				<label><?php esc_html_e( 'Main wordmark / text', 'apple-star-loader' ); ?></label>
				<input type="text" class="aspl-textinput aspl-text-lg" name="<?php echo esc_attr( self::OPTION ); ?>[text]" value="<?php echo esc_attr( $options['text'] ); ?>" maxlength="40" placeholder="LOADING" data-preview="text" />
				<p class="description"><?php esc_html_e( 'Each letter animates in a wave. Keep it short for best results.', 'apple-star-loader' ); ?></p>
			</div>
		</div>
		<?php
	}

	/* -- Timing tab ----------------------------------------------------- */
	private function render_timing( $options ) {
		?>
		<div class="aspl-card">
			<h3><?php esc_html_e( 'Timing controls', 'apple-star-loader' ); ?> ⏱️</h3>
			<div class="aspl-field aspl-range-field">
				<label><?php esc_html_e( 'Minimum display time', 'apple-star-loader' ); ?> — <span class="aspl-range-val" data-target="min_time"><?php echo (int) $options['min_time']; ?></span> ms</label>
				<input type="range" name="<?php echo esc_attr( self::OPTION ); ?>[min_time]" min="0" max="3000" step="100" value="<?php echo (int) $options['min_time']; ?>" class="aspl-range" data-preview="min_time" />
				<p class="description"><?php esc_html_e( 'Forces the loader to stay visible at least this long (prevents flicker on fast pages). Recommended: 400–800 ms.', 'apple-star-loader' ); ?></p>
			</div>
			<div class="aspl-field">
				<label><?php esc_html_e( 'Fallback timeout', 'apple-star-loader' ); ?></label>
				<input type="number" class="small-text" name="<?php echo esc_attr( self::OPTION ); ?>[timeout]" value="<?php echo (int) $options['timeout']; ?>" min="1" max="120" step="1" />
				<span class="aspl-unit"><?php esc_html_e( 'seconds', 'apple-star-loader' ); ?></span>
				<p class="description"><?php esc_html_e( 'If a resource gets stuck and the page never fires "load", the loader closes anyway so visitors can still use the site.', 'apple-star-loader' ); ?></p>
			</div>
			<div class="aspl-field aspl-range-field">
				<label><?php esc_html_e( 'Fade-out duration', 'apple-star-loader' ); ?> — <span class="aspl-range-val" data-target="fade_duration"><?php echo (int) $options['fade_duration']; ?></span> ms</label>
				<input type="range" name="<?php echo esc_attr( self::OPTION ); ?>[fade_duration]" min="100" max="2000" step="50" value="<?php echo (int) $options['fade_duration']; ?>" class="aspl-range" data-preview="fade_duration" />
			</div>
		</div>
		<?php
	}

	/* -- Advanced tab --------------------------------------------------- */
	private function render_advanced( $options ) {
		?>
		<div class="aspl-card">
			<h3><?php esc_html_e( 'Advanced styling', 'apple-star-loader' ); ?> 🧩</h3>
			<div class="aspl-field">
				<label><?php esc_html_e( 'Z-Index', 'apple-star-loader' ); ?></label>
				<input type="number" class="small-text" name="<?php echo esc_attr( self::OPTION ); ?>[z_index]" value="<?php echo (int) $options['z_index']; ?>" min="1000" step="1" />
				<p class="description"><?php esc_html_e( 'Increase if another plugin/theme element overlaps the loader.', 'apple-star-loader' ); ?></p>
			</div>
			<div class="aspl-field">
				<label><?php esc_html_e( 'Custom CSS', 'apple-star-loader' ); ?></label>
				<textarea name="<?php echo esc_attr( self::OPTION ); ?>[custom_css]" rows="8" class="aspl-codearea large-text code" spellcheck="false" dir="ltr" placeholder="/* Example: */&#10;.asp-stage { box-shadow: 0 0 50px red; }"><?php echo esc_textarea( $options['custom_css'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Add custom CSS overrides here. It will be applied together with the preset styles.', 'apple-star-loader' ); ?></p>
			</div>
		</div>

		<div class="aspl-card">
			<h3><?php esc_html_e( 'Custom loader HTML + CSS', 'apple-star-loader' ); ?></h3>
			<p class="description aspl-card-desc"><?php esc_html_e( 'Only used when you select the "Custom Code" preset. Paste any self-contained HTML/CSS (scripts allowed in the frontend but not in preview). Your markup should contain an .asp-percent element and .asp-bar-fill element if you want progress animations to work with your code.', 'apple-star-loader' ); ?></p>
			<textarea id="aspl-code" name="<?php echo esc_attr( self::OPTION ); ?>[code]" rows="18" class="aspl-codearea large-text code aspl-custom-code" spellcheck="false" dir="ltr"><?php echo esc_textarea( $options['code'] ); ?></textarea>
			<p>
				<button type="button" class="button" id="aspl-reset-code"><?php esc_html_e( 'Insert Apple Star default code', 'apple-star-loader' ); ?></button>
			</p>
		</div>
		<?php
	}

	/* -- Admin CSS ------------------------------------------------------ */
	private function get_admin_css() {
		return <<<'CSS'
:root{
  --aspl-primary:#0071e3;
  --aspl-primary-hover:#0077ed;
  --aspl-bg:#f6f8fb;
  --aspl-card:#ffffff;
  --aspl-border:#e5e7eb;
  --aspl-text:#1d2327;
  --aspl-muted:#6b7280;
  --aspl-success:#10b981;
  --aspl-danger:#ef4444;
  --aspl-shadow:0 1px 3px rgba(0,0,0,.05),0 8px 24px rgba(17,24,39,.06);
}
.aspl-admin{max-width:1400px;margin:10px 20px 0 0;color:var(--aspl-text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}
.aspl-admin h1,.aspl-admin h2,.aspl-admin h3{font-family:-apple-system,Segoe UI,sans-serif;color:var(--aspl-text);}
.aspl-admin *{box-sizing:border-box;}

/* Top bar */
.aspl-topbar{display:flex;align-items:center;justify-content:space-between;padding:22px 28px;background:linear-gradient(135deg,#1d1d1f 0%,#0071e3 100%);color:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(0,113,227,.25);margin-bottom:18px;flex-wrap:wrap;gap:14px;}
.aspl-topbar-left{display:flex;align-items:center;gap:16px;}
.aspl-logo-badge{width:52px;height:52px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.2);}
.aspl-title{font-size:22px;font-weight:700;margin:0;padding:0;color:#fff;line-height:1.2;}
.aspl-version{display:inline-block;background:rgba(255,255,255,.2);padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600;vertical-align:middle;margin-left:8px;}
.aspl-subtitle{font-size:13px;opacity:.85;margin-top:4px;}
.aspl-topbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.aspl-status{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);padding:6px 12px;border-radius:999px;font-size:12px;font-weight:600;backdrop-filter:blur(10px);}
.aspl-status--on{background:rgba(16,185,129,.3);}
.aspl-status--off{background:rgba(239,68,68,.3);}
.aspl-status-dot{width:8px;height:8px;border-radius:50%;background:#fff;box-shadow:0 0 0 3px rgba(255,255,255,.3);animation:asplPulse 2s infinite;}
@keyframes asplPulse{0%,100%{opacity:1;}50%{opacity:.4;}}
.aspl-preview-quick{background:rgba(255,255,255,.2)!important;border-color:transparent!important;color:#fff!important;backdrop-filter:blur(10px);border-radius:10px!important;font-weight:600;}
.aspl-preview-quick:hover{background:rgba(255,255,255,.3)!important;border-color:transparent!important;color:#fff!important;}

/* Tabs */
.aspl-tabs{display:flex;gap:4px;background:var(--aspl-card);padding:6px;border-radius:14px;margin-bottom:20px;box-shadow:var(--aspl-shadow);overflow-x:auto;flex-wrap:wrap;}
.aspl-tab{padding:10px 16px;border-radius:10px;color:var(--aspl-muted);text-decoration:none;font-weight:500;font-size:14px;transition:all .2s ease;white-space:nowrap;}
.aspl-tab:hover{background:#f3f4f6;color:var(--aspl-text);}
.aspl-tab--active{background:var(--aspl-primary);color:#fff!important;box-shadow:0 4px 12px rgba(0,113,227,.3);}

/* Grid layout */
.aspl-grid{display:grid;grid-template-columns:1fr 380px;gap:20px;}
.aspl-main{min-width:0;}
.aspl-side{display:flex;flex-direction:column;gap:16px;position:sticky;top:32px;align-self:start;}
@media (max-width:1200px){.aspl-grid{grid-template-columns:1fr;}.aspl-side{position:static;}}

/* Cards */
.aspl-card{background:var(--aspl-card);border:1px solid var(--aspl-border);border-radius:16px;padding:24px;box-shadow:var(--aspl-shadow);margin-bottom:18px;}
.aspl-card h3{margin:0 0 18px;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;}
.aspl-card-desc{color:var(--aspl-muted);margin:-10px 0 18px;}
.aspl-side-preview .aspl-card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.aspl-side-preview .aspl-card-head h3{margin:0;}
.aspl-hint{color:var(--aspl-muted);font-size:12px;margin:10px 0 0;text-align:center;}

/* Fields */
.aspl-field{margin-bottom:18px;}
.aspl-field:last-child{margin-bottom:0;}
.aspl-field label{display:block;font-weight:600;font-size:13px;margin-bottom:8px;color:var(--aspl-text);}
.aspl-field .description{color:var(--aspl-muted);font-size:12px;margin-top:6px;}
.aspl-textinput{width:100%;max-width:420px;padding:10px 12px;border:1px solid var(--aspl-border);border-radius:10px;font-size:14px;background:#fafbfc;transition:border-color .15s,box-shadow .15s;}
.aspl-textinput:focus{border-color:var(--aspl-primary);box-shadow:0 0 0 3px rgba(0,113,227,.15);outline:none;background:#fff;}
.aspl-text-lg{font-size:18px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;text-align:center;}
.aspl-unit{color:var(--aspl-muted);margin-left:8px;}
.aspl-codearea{font-family:ui-monospace,SF Mono,Menlo,Consolas,monospace;font-size:13px;line-height:1.6;border-radius:10px;border:1px solid var(--aspl-border);background:#fafbfc;direction:ltr;text-align:left;tab-size:2;}
.aspl-codearea:focus{border-color:var(--aspl-primary);box-shadow:0 0 0 3px rgba(0,113,227,.15);outline:none;}

/* Switch */
.aspl-switch{display:flex;align-items:center;gap:12px;cursor:pointer;}
.aspl-switch input{position:absolute;opacity:0;pointer-events:none;}
.aspl-switch-slider{position:relative;width:44px;height:24px;background:#d1d5db;border-radius:999px;transition:background .2s;flex-shrink:0;}
.aspl-switch-slider::before{content:"";position:absolute;top:2px;left:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 2px 4px rgba(0,0,0,.2);}
.aspl-switch input:checked + .aspl-switch-slider{background:var(--aspl-primary);}
.aspl-switch input:checked + .aspl-switch-slider::before{transform:translateX(20px);}
.aspl-switch-label{font-size:14px;font-weight:500;}

/* Radio cards */
.aspl-radio-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;}
.aspl-radio-card{display:flex;align-items:flex-start;gap:12px;padding:14px;border:2px solid var(--aspl-border);border-radius:12px;cursor:pointer;transition:all .15s;background:#fafbfc;}
.aspl-radio-card input{position:absolute;opacity:0;pointer-events:none;}
.aspl-radio-card:hover{border-color:#c7d2fe;background:#fff;}
.aspl-radio-card--checked{border-color:var(--aspl-primary);background:rgba(0,113,227,.05);box-shadow:0 0 0 3px rgba(0,113,227,.1);}
.aspl-radio-ico{font-size:24px;line-height:1;flex-shrink:0;}
.aspl-radio-body{display:flex;flex-direction:column;gap:3px;}
.aspl-radio-body b{font-size:14px;}
.aspl-radio-body .description{font-size:12px;color:var(--aspl-muted);}

/* Preset grid */
.aspl-preset-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;}
.aspl-preset-card{cursor:pointer;border:2px solid var(--aspl-border);border-radius:14px;padding:8px;background:#fafbfc;transition:all .15s;text-align:center;display:block;}
.aspl-preset-card input{position:absolute;opacity:0;pointer-events:none;}
.aspl-preset-card:hover{border-color:#c7d2fe;transform:translateY(-2px);box-shadow:var(--aspl-shadow);}
.aspl-preset-card--checked{border-color:var(--aspl-primary);background:rgba(0,113,227,.05);box-shadow:0 0 0 3px rgba(0,113,227,.12);}
.aspl-preset-preview{display:flex;align-items:center;justify-content:center;height:80px;background:linear-gradient(135deg,#0b0b0f,#1d1d1f);border-radius:10px;margin-bottom:8px;font-size:30px;color:#fff;position:relative;overflow:hidden;}
.aspl-preset-preview::after{content:"";position:absolute;inset:0;background:radial-gradient(circle at 30% 30%,rgba(0,113,227,.3),transparent 60%);}
.aspl-preset-ico{position:relative;z-index:1;}
.aspl-preset-label{display:block;font-size:12px;font-weight:600;padding-bottom:4px;}

/* Color grid */
.aspl-color-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:18px;}
.aspl-color{max-width:200px;}
.wp-picker-container{display:inline-block;}
.wp-picker-container .wp-color-result.button{border-radius:8px;border-color:var(--aspl-border);box-shadow:none;}
.wp-picker-input-wrap .button{border-radius:6px;}

/* Range */
.aspl-range{width:100%;accent-color:var(--aspl-primary);}
.aspl-range-field .aspl-range-val{color:var(--aspl-primary);font-weight:700;}

/* Logo upload */
.aspl-logo-upload{display:flex;gap:16px;align-items:center;flex-wrap:wrap;}
.aspl-logo-preview{width:200px;height:100px;border:2px dashed var(--aspl-border);border-radius:12px;display:flex;align-items:center;justify-content:center;background:#fafbfc;overflow:hidden;padding:8px;}
.aspl-logo-preview img{max-width:100%;max-height:100%;object-fit:contain;}
.aspl-logo-placeholder{color:var(--aspl-muted);font-size:13px;}
.aspl-logo-actions{display:flex;flex-direction:column;gap:8px;}
.aspl-link-danger{color:var(--aspl-danger)!important;border-color:transparent!important;background:transparent!important;text-decoration:underline;}
.aspl-link-danger:hover{color:#c53030!important;}

/* Preview */
.aspl-preview-box{background:#0b0b0f;border-radius:12px;padding:8px;display:flex;justify-content:center;align-items:center;}
.aspl-preview-frame{width:100%;height:320px;border:0;border-radius:8px;background:#0b0b0f;transition:width .25s;max-width:100%;}
.aspl-device-switch{display:flex;gap:4px;background:#f3f4f6;padding:4px;border-radius:10px;}
.aspl-device-btn{width:32px;height:28px;border:0;background:transparent;border-radius:6px;cursor:pointer;font-size:14px;transition:all .15s;}
.aspl-device-btn:hover{background:#e5e7eb;}
.aspl-device-btn.active{background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.1);}

/* Stats / tips */
.aspl-stat{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--aspl-border);font-size:13px;}
.aspl-stat:last-child{border-bottom:0;}
.aspl-stat span{color:var(--aspl-muted);}
.aspl-stat b{font-weight:600;}
.aspl-tips-list{margin:0;padding:0 0 0 18px;line-height:1.9;color:var(--aspl-muted);font-size:13px;}
.aspl-tips-list li{margin-bottom:2px;}

/* Actions bar */
.aspl-actions{display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;}
.aspl-save-btn{border-radius:12px!important;font-size:15px!important;padding:8px 28px!important;box-shadow:0 6px 20px rgba(0,113,227,.3)!important;}
.aspl-reset-btn{border-radius:12px!important;}

/* Hero */
.aspl-hero{background:linear-gradient(135deg,#0071e3 0%,#6d28d9 100%);color:#fff;border:none;}
.aspl-hero h2{color:#fff;margin:0 0 8px;}
.aspl-hero p{margin:0;opacity:.9;line-height:1.7;}
.aspl-hero-msg{margin-bottom:20px;}
.aspl-hero-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;}
.aspl-hero-stat{background:rgba(255,255,255,.15);backdrop-filter:blur(10px);padding:14px;border-radius:12px;text-align:center;border:1px solid rgba(255,255,255,.2);}
.aspl-hero-stat b{display:block;font-size:22px;font-weight:800;}
.aspl-hero-stat span{font-size:12px;opacity:.85;}

/* Quick start */
.aspl-quick-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;}
.aspl-quick{display:block;padding:18px;border:2px solid var(--aspl-border);border-radius:14px;text-decoration:none;color:var(--aspl-text);transition:all .15s;background:#fafbfc;}
.aspl-quick:hover{border-color:var(--aspl-primary);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,113,227,.12);color:var(--aspl-text);}
.aspl-quick-ico{font-size:28px;display:block;margin-bottom:8px;}
.aspl-quick-title{display:block;font-weight:700;margin-bottom:4px;}
.aspl-quick-desc{font-size:12px;color:var(--aspl-muted);line-height:1.5;}

/* Features */
.aspl-feature-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;}
.aspl-feature{padding:16px;border:1px solid var(--aspl-border);border-radius:12px;background:#fafbfc;font-size:14px;line-height:1.5;}
.aspl-feature b{display:block;margin-bottom:6px;}
.aspl-feature p{margin:0;color:var(--aspl-muted);font-size:13px;line-height:1.6;}

/* Modal */
.aspl-modal{position:fixed;inset:0;z-index:1000000;display:none;align-items:center;justify-content:center;}
.aspl-modal.is-open{display:flex;}
.aspl-modal-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.85);backdrop-filter:blur(10px);}
.aspl-modal-frame{position:relative;width:100%;height:100%;}
.aspl-modal-iframe{width:100%;height:100%;border:0;background:#000;}
.aspl-modal-close{position:absolute;top:20px;right:20px;width:44px;height:44px;border-radius:50%;border:0;background:rgba(255,255,255,.1);color:#fff;font-size:18px;cursor:pointer;z-index:10;backdrop-filter:blur(10px);}
.aspl-modal-close:hover{background:rgba(255,255,255,.2);}

/* Mobile / small screen: stack columns and tighten cards */
@media (max-width:782px){
  .aspl-admin{margin:6px 10px 0 0;}
  .aspl-topbar{padding:16px 18px;border-radius:14px;gap:10px;}
  .aspl-logo-badge{width:40px;height:40px;font-size:20px;border-radius:10px;}
  .aspl-title{font-size:18px;}
  .aspl-tabs{padding:4px;gap:2px;}
  .aspl-tab{padding:8px 10px;font-size:12px;}
  .aspl-radio-grid{grid-template-columns:1fr;}
  .aspl-preset-grid{grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;}
  .aspl-preset-card{padding:6px;}
  .aspl-preset-preview{height:60px;font-size:22px;}
  .aspl-color-grid{grid-template-columns:1fr;gap:12px;}
  .aspl-card{padding:18px 16px;border-radius:14px;}
  .aspl-quick-grid{grid-template-columns:1fr;}
  .aspl-feature-grid{grid-template-columns:1fr;}
  .aspl-actions .button{width:100%;margin:0;}
  .aspl-logo-upload{flex-direction:column;align-items:flex-start;}
  .aspl-preview-frame{height:260px;}
  .aspl-device-switch{margin-top:8px;}
  .aspl-side-preview .aspl-card-head{flex-direction:column;align-items:flex-start;gap:8px;}
  .aspl-hero-stats{grid-template-columns:repeat(2,1fr);gap:8px;}
  .aspl-hero-stat{padding:10px;border-radius:10px;}
  .aspl-hero-stat b{font-size:18px;}
  .wp-picker-container .wp-color-result.button{margin-bottom:6px;}
  .wp-picker-holder{position:relative!important;}
  .aspl-textinput{max-width:100%;}
}

/* WordPress admin tweaks inside our page */
.aspl-admin .wp-picker-holder{position:absolute;z-index:10;}
CSS;
	}

	/* -- Admin JS ------------------------------------------------------- */
	private function get_admin_js() {
		return <<<'JS'
( function ( $ ) {
	'use strict';
	if ( ! window.ASPL ) { return; }
	var defaults = window.ASPL.defaults;
	var presets  = window.ASPL.presets;
	var opts     = collectOpts();

	var $form     = $('#aspl-form');
	var $frame    = $('#aspl-frame');
	var $mframe   = $('#aspl-modal-frame');
	var $modal    = $('#aspl-modal');
	var $rangeVals= $('.aspl-range-val');
	var $logoPrev = $('#aspl-logo-preview');
	var $logoInp  = $('#aspl-logo-input');

	function collectOpts() {
		var o = {};
		$form.find(':input[name]').each(function(){
			var $el = $(this);
			var name = $el.attr('name').replace(/^aspl_settings\[(\w+)\]$/, '$1');
			if ( name === $el.attr('name') ) { return; }
			if ( $el.is(':checkbox') ) { o[name] = $el.is(':checked') ? 1 : 0; return; }
			if ( $el.is(':radio') ) { if ( $el.is(':checked') ) { o[name] = $el.val(); } return; }
			o[name] = $el.val();
		});
		// Ensure numeric types
		['bg_opacity','timeout','min_time','fade_duration','blur_amount','z_index'].forEach(function(k){ if(o[k]!=null) o[k]=parseInt(o[k],10); });
		['show_percentage','show_progress_bar','show_tips','show_on_mobile','hide_for_logged_in','enabled'].forEach(function(k){ o[k] = o[k]?1:0; });
		return o;
	}

	function hexToRgba(hex, a){
		hex = (hex||'#000').replace('#','');
		if(hex.length===3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
		if(hex.length!==6) return 'rgba(0,0,0,'+a/100+')';
		var r=parseInt(hex.substring(0,2),16),g=parseInt(hex.substring(2,4),16),b=parseInt(hex.substring(4,6),16);
		return 'rgba('+r+','+g+','+b+','+a/100+')';
	}

	var __aspInlineScript = "(function(){\nfunction isRTL(s){return /[\u0600-\u06ff\u0750-\u077f\u08a0-\u08ff\ufb50-\ufdff\ufe70-\ufeff\u0590-\u05ff]/.test(s);}\nfunction wave(el,t){\n  if(!el)return; if(el.dataset.filled)return; el.dataset.filled=1; el.innerHTML=\"\";\n  var rtl=isRTL(t);\n  if(rtl){\n    var parts=String(t||\"\").split(/(\\s+)/), i2=0;\n    for(var k=0;k<parts.length;k++){\n      var part=parts[k];\n      if(/^\\s+$/.test(part)){ el.appendChild(document.createTextNode(part)); continue; }\n      if(!part)continue;\n      var w=document.createElement(\"span\"); w.className=\"asp-wave-word\"; w.textContent=part;\n      w.style.setProperty(\"--w\",i2); el.appendChild(w); i2++;\n    }\n    el.setAttribute(\"dir\",\"rtl\");\n  }else{\n    var chars=String(t||\"\").split(\"\"), i=0;\n    for(var m=0;m<chars.length;m++){\n      var ch=chars[m];\n      if(ch===\" \"){ var sp=document.createElement(\"span\"); sp.className=\"asp-wave-letter asp-space\"; sp.innerHTML=\"&nbsp;\"; sp.style.setProperty(\"--w\",i); el.appendChild(sp); i++; continue; }\n      var s=document.createElement(\"span\"); s.className=\"asp-wave-letter\"; s.textContent=ch; s.style.setProperty(\"--w\",i); el.appendChild(s); i++;\n    }\n    el.setAttribute(\"dir\",\"ltr\");\n  }\n}\ndocument.querySelectorAll(\"#asp-loader-root .asp-wave-text,#asp-loader-root .asp-sub,#asp-loader-root .asp-sub-text,#asp-loader-root .asp-sub-top\").forEach(function(e){wave(e,window.__aspText);});\nvar ww=document.querySelector(\"#asp-loader-root .asp-stage .asp-wave-word\"); if(ww)wave(ww,window.__aspText);\nif(window.__aspLogo){var st=document.querySelector(\"#asp-loader-root .asp-stage\");if(st){var wd=document.createElement(\"div\");wd.className=\"asp-logo-wrap\";var im=document.createElement(\"img\");im.src=window.__aspLogo;wd.appendChild(im);st.insertBefore(wd,st.firstChild);}}\n(function(){var pEl=document.querySelector(\"#asp-loader-root .asp-percent\"), bEl=document.querySelector(\"#asp-loader-root .asp-bar-fill\"), bD=document.querySelector(\"#asp-loader-root .asp-bar-dot\"), p=0;\nfunction tick(){p+=Math.random()*6+1.5; if(p>100)p=0; var r=Math.round(p);\n  if(pEl){var old=parseInt(pEl.textContent,10)||0; pEl.textContent=r; if(r!==old){pEl.classList.remove(\"asp-digit-bounce\"); void pEl.offsetWidth; pEl.classList.add(\"asp-digit-bounce\");}}\n  if(bEl)bEl.style.width=r+\"%\"; if(bD)bD.style.left=r+\"%\"; setTimeout(tick,420);}\nsetTimeout(tick,400);})();\n})();";
	function buildPreviewHTML() {
		var o = collectOpts();
		var preset = o.preset || 'apple_star';
		var code = (preset==='custom_code') ? (o.code||'') : (presets[preset]||presets.apple_star);
		var bg = hexToRgba(o.bg_color||'#0b0b0f', o.bg_opacity!=null?o.bg_opacity:100);
		var vars = [
			'--asp-bg:'+bg,
			'--asp-text:'+(o.text_color||'#fff'),
			'--asp-accent:'+(o.accent_color||'#0071e3'),
			'--asp-primary:'+(o.primary_color||'#fff'),
			'--asp-bar-bg:'+(o.bar_bg_color||'rgba(255,255,255,0.15)'),
			'--asp-bar-fg:'+(o.bar_fg_color||'#fff'),
			'--asp-blur:'+(o.blur_amount||0)+'px'
		].join(';');
		var logoHTML = '';
		var text = (o.text||'LOADING').replace(/[<>&]/g,function(c){return {'<':'&lt;','>':'&gt;','&':'&amp;'}[c];});
		var hideCSS = '<style>';
		if(!o.show_percentage) hideCSS += '.asp-percent,.asp-percent-sign,.asp-percent-row,.asp-percent-big,.asp-percent-wrap,.asp-ring-core{display:none!important;}';
		if(!o.show_progress_bar) hideCSS += '.asp-bar,.asp-bar-wrap{display:none!important;}';
		if(!o.show_tips) hideCSS += '.asp-tip{display:none!important;}';
		hideCSS += '#asp-loader-root{z-index:1!important;position:absolute!important;}';
		hideCSS += '<\/style>';
		var bootstrap = '<script>window.__aspText='+JSON.stringify(text)+';window.__aspLogo='+JSON.stringify(o.logo||'')+';<\/script>';
		var script = '<script>'+__aspInlineScript.replace(/<\//g,'<\\/')+'<\/script>';
		var doc = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>html,body{margin:0;padding:0;height:100%;background:#0b0b0c;overflow:hidden;}#asp-loader-root{'+vars+'}</style>'+hideCSS+'</head><body>'+bootstrap+code+script+'</body></html>';
		return doc;
	}

	var previewTimer = null;
	function schedulePreview(){
		clearTimeout(previewTimer);
		previewTimer = setTimeout(updatePreview, 200);
	}
	function updatePreview(){
		var doc = buildPreviewHTML();
		$frame[0].srcdoc = doc;
		if ( $mframe.length ) { $mframe[0].srcdoc = doc; }
	}

	// Initialize color pickers (already enqueued; wp-color-picker alpha not bundled).
	$('.aspl-color').each(function(){
		$(this).wpColorPicker({ change: schedulePreview, clear: schedulePreview });
	});

	// Bind inputs
	$form.on('input change', ':input', function(){
		// Update range labels
		if ( $(this).hasClass('aspl-range') ) {
			var tgt = $(this).attr('data-preview');
			$('.aspl-range-val[data-target="'+tgt+'"]').text($(this).val());
		}
		// Preset card visuals
		if ( $(this).is(':radio[name="aspl_settings[preset]"]') ) {
			$('.aspl-preset-card').removeClass('aspl-preset-card--checked');
			$('.aspl-preset-card input[value="'+$(this).val()+'"]').closest('.aspl-preset-card').addClass('aspl-preset-card--checked');
		}
		if ( $(this).is(':radio[name="aspl_settings[target]"]') ) {
			$('.aspl-radio-card').removeClass('aspl-radio-card--checked');
			$(this).closest('.aspl-radio-card').addClass('aspl-radio-card--checked');
		}
		schedulePreview();
	});

	// Device switch
	$('.aspl-device-btn').on('click', function(){
		$('.aspl-device-btn').removeClass('active');
		$(this).addClass('active');
		var w = $(this).data('w')+'';
		$frame.css('width', w==='1200'?'100%':w+'px');
	});

	// Fullscreen modal
	$('#aspl-preview-quick').on('click', function(){
		$modal.addClass('is-open').attr('aria-hidden','false');
		updatePreview();
	});
	$('#aspl-modal-close,.aspl-modal-backdrop').on('click', function(){
		$modal.removeClass('is-open').attr('aria-hidden','true');
	});
	$(document).on('keydown', function(e){ if(e.key==='Escape') $modal.removeClass('is-open'); });

	// Logo upload
	var mediaFrame = null;
	$('#aspl-logo-upload').on('click', function(e){
		e.preventDefault();
		if ( mediaFrame ) { mediaFrame.open(); return; }
		mediaFrame = wp.media({ title:'Select logo', button:{text:'Use this logo'}, library:{type:'image'}, multiple:false });
		mediaFrame.on('select', function(){
			var att = mediaFrame.state().get('selection').first().toJSON();
			var url = att.url || (att.sizes&&att.sizes.medium&&att.sizes.medium.url) || '';
			$logoInp.val(url);
			$logoPrev.html('<img src="'+url+'" alt="" />');
			schedulePreview();
		});
		mediaFrame.open();
	});
	$('#aspl-logo-remove').on('click', function(){
		$logoInp.val('');
		$logoPrev.html('<span class="aspl-logo-placeholder">No logo selected</span>');
		schedulePreview();
	});

	// Reset all to defaults
	$('#aspl-reset-all').on('click', function(e){
		if ( ! confirm('Reset all settings to defaults? Your custom code will be cleared.') ) { return; }
		e.preventDefault();
		$form.find(':input[name^="aspl_settings["]').each(function(){
			var key = this.name.match(/\[(\w+)\]/); if(!key)return; key=key[1];
			var v = defaults[key];
			if ( $(this).is(':checkbox') ) { $(this).prop('checked', !!v); return; }
			if ( $(this).is(':radio') ) { $(this).prop('checked', ($(this).val()==String(v)) ); return; }
			if ( typeof v !== 'undefined' ) { $(this).val(v); }
		});
		$('.aspl-range').each(function(){ var tgt=$(this).attr('data-preview'); $('.aspl-range-val[data-target="'+tgt+'"]').text($(this).val()); });
		if ( $logoInp.length ) {
			if ( defaults.logo ) { $logoInp.val(defaults.logo); $logoPrev.html('<img src="'+defaults.logo+'" alt="" />'); }
			else { $logoInp.val(''); $logoPrev.html('<span class="aspl-logo-placeholder">No logo selected</span>'); }
		}
		schedulePreview();
	});

	// Insert default into custom code
	$('#aspl-reset-code').on('click', function(){
		var ta = document.getElementById('aspl-code');
		if ( ! ta ) return;
		ta.value = presets.apple_star || '';
		schedulePreview();
	});

	// First render
	updatePreview();
} )( jQuery );
JS;
	}
}
