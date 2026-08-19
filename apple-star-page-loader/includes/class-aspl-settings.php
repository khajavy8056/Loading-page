<?php
/**
 * Admin settings page — v3.0.
 *
 * Single-page, tab-free, mobile responsive. Sections:
 *   • Status & display (enable switch + target)
 *   • Preset picker (11 visual cards)
 *   • Text & logo
 *   • Maintenance mode (enable, H/M/S, message)
 *   • Colors
 *   • Timing
 *   • Custom CSS
 * Plus a live JS-running preview iframe (with fullscreen modal).
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Settings {

	const OPTION        = 'aspl_settings';
	const GROUP         = 'aspl_settings_group';
	const MENU_SLUG     = 'apple-star-loader';
	const CAPABILITY    = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, self::MENU_SLUG ) === false ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){$(".aspl-color").wpColorPicker({change:function(){setTimeout(function(){window.__asplUpdate&&window.__asplUpdate();},50);},clear:function(){setTimeout(function(){window.__asplUpdate&&window.__asplUpdate();},50);}});});'
		);
	}

	public static function activate() {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, ASPL_Defaults::get_options() );
		} else {
			$saved  = get_option( self::OPTION, array() );
			$merged = wp_parse_args( (array) $saved, ASPL_Defaults::get_options() );
			update_option( self::OPTION, $merged );
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
	}

	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = array();

		// Switches.
		$clean['enabled']            = ! empty( $input['enabled'] ) ? 1 : 0;
		$clean['use_site_title']     = ! empty( $input['use_site_title'] ) ? 1 : 0;
		$clean['wait_images']        = ! empty( $input['wait_images'] ) ? 1 : 1;
		$clean['show_on_mobile']     = ! empty( $input['show_on_mobile'] ) ? 1 : 0;
		$clean['hide_for_logged_in'] = ! empty( $input['hide_for_logged_in'] ) ? 1 : 0;
		$clean['maintenance_mode']   = ! empty( $input['maintenance_mode'] ) ? 1 : 0;

		// Target.
		$target = isset( $input['target'] ) ? sanitize_key( $input['target'] ) : 'front_page';
		$clean['target'] = in_array( $target, array( 'front_page', 'all_pages', 'home_posts', 'posts_only', 'pages_only', 'woocommerce' ), true ) ? $target : 'front_page';

		// Preset — whitelist against known slugs.
		$presets = array_keys( ASPL_Defaults::get_presets() );
		$preset  = isset( $input['preset'] ) ? sanitize_key( $input['preset'] ) : 'apple_star';
		$clean['preset'] = in_array( $preset, $presets, true ) ? $preset : 'apple_star';

		// Text / logo.
		$clean['text'] = isset( $input['text'] ) ? sanitize_text_field( $input['text'] ) : 'APPLE STAR';
		$clean['logo'] = isset( $input['logo'] ) ? esc_url_raw( $input['logo'] ) : '';

		// Colors.
		foreach ( array(
			'bg_color'      => '#0a0a0f',
			'text_color'    => '#ffffff',
			'accent_color'  => '#00c3ff',
		) as $k => $def ) {
			$clean[ $k ] = isset( $input[ $k ] ) && sanitize_hex_color( $input[ $k ] ) ? sanitize_hex_color( $input[ $k ] ) : $def;
		}

		// Numeric ranges.
		$clean['bg_opacity']         = isset( $input['bg_opacity'] ) ? max( 0, min( 100, absint( $input['bg_opacity'] ) ) ) : 85;
		$clean['blur_amount']        = isset( $input['blur_amount'] ) ? max( 0, min( 50, absint( $input['blur_amount'] ) ) ) : 16;
		$clean['timeout']            = isset( $input['timeout'] ) ? max( 1, min( 600, absint( $input['timeout'] ) ) ) : 20;
		$clean['min_time']           = isset( $input['min_time'] ) ? max( 0, min( 10000, absint( $input['min_time'] ) ) ) : 800;
		$clean['fade_duration']      = isset( $input['fade_duration'] ) ? max( 100, min( 5000, absint( $input['fade_duration'] ) ) ) : 700;
		$clean['z_index']            = isset( $input['z_index'] ) ? max( 1000, absint( $input['z_index'] ) ) : 99999999;

		// Maintenance timer.
		$clean['maintenance_hours']   = isset( $input['maintenance_hours'] ) ? max( 0, min( 999, absint( $input['maintenance_hours'] ) ) ) : 0;
		$clean['maintenance_minutes'] = isset( $input['maintenance_minutes'] ) ? max( 0, min( 59, absint( $input['maintenance_minutes'] ) ) ) : 30;
		$clean['maintenance_seconds'] = isset( $input['maintenance_seconds'] ) ? max( 0, min( 59, absint( $input['maintenance_seconds'] ) ) ) : 0;
		$clean['maintenance_msg']     = isset( $input['maintenance_msg'] ) ? sanitize_textarea_field( $input['maintenance_msg'] ) : 'ما در حال بروز رسانی هستیم. بزودی با ارائه خدمات بهتر در خدمت شما هستیم.';

		// Custom CSS (allow CSS — we escape on output).
		$clean['custom_css'] = isset( $input['custom_css'] ) ? (string) $input['custom_css'] : '';

		return $clean;
	}

	/**
	 * Helper: hex to rgba for the preview CSS.
	 */
	private function hex2rgba( $hex, $a = 100 ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( strlen( $hex ) !== 6 ) {
			return 'rgba(0,0,0,' . ( $a / 100 ) . ')';
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . ( $a / 100 ) . ')';
	}

	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'apple-star-loader' ) );
		}
		$opts    = self::get_options();
		$presets = ASPL_Defaults::get_presets();
		?>
<div class="wrap aspl-admin">
	<div class="aspl-head">
		<div class="aspl-head-left">
			<span class="aspl-logo">✦</span>
			<div>
				<h1>Apple Star Loader <small>v<?php echo esc_html( ASPL_VERSION ); ?></small></h1>
				<p><?php esc_html_e( 'لودینگ‌های اپل‌استایل برای وردپرس — با حالت بروزرسانی، پیش‌نمایش زنده و پشتیبانی فارسی.', 'apple-star-loader' ); ?></p>
			</div>
		</div>
		<div class="aspl-head-right">
			<span class="aspl-pill <?php echo $opts['enabled'] ? 'on' : 'off'; ?>">
				<span class="dot"></span>
				<?php echo $opts['enabled'] ? esc_html__( 'روشن', 'apple-star-loader' ) : esc_html__( 'خاموش', 'apple-star-loader' ); ?>
			</span>
			<?php if ( ! empty( $opts['maintenance_mode'] ) ) : ?>
				<span class="aspl-pill maint"><span class="dot"></span><?php esc_html_e( 'بروزرسانی', 'apple-star-loader' ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<div class="aspl-grid">
		<form method="post" action="options.php" id="aspl-form" class="aspl-card-col">
			<?php settings_fields( self::GROUP ); ?>

			<!-- Status -->
			<section class="aspl-card">
				<h2>🔌 <?php esc_html_e( 'وضعیت و نمایش', 'apple-star-loader' ); ?></h2>

				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enabled]" value="1" <?php checked( $opts['enabled'] ); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e( 'فعال‌بودن لودینگ', 'apple-star-loader' ); ?></span>
					</label>
				</div>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_on_mobile]" value="1" <?php checked( $opts['show_on_mobile'] ); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e( 'نمایش روی موبایل', 'apple-star-loader' ); ?></span>
					</label>
				</div>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[hide_for_logged_in]" value="1" <?php checked( $opts['hide_for_logged_in'] ); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e( 'مخفی برای کاربران واردشده', 'apple-star-loader' ); ?></span>
					</label>
				</div>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[wait_images]" value="1" <?php checked( $opts['wait_images'] ); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e( 'صبر تا لود آخرین عکس (صفحه کامل باز شود)', 'apple-star-loader' ); ?></span>
					</label>
				</div>

				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e( 'نمایش در کدام صفحات؟', 'apple-star-loader' ); ?></label>
					<select name="<?php echo esc_attr( self::OPTION ); ?>[target]" class="aspl-select">
						<option value="front_page"  <?php selected( $opts['target'], 'front_page' ); ?>><?php esc_html_e( 'فقط صفحه اصلی', 'apple-star-loader' ); ?></option>
						<option value="all_pages"   <?php selected( $opts['target'], 'all_pages' ); ?>><?php esc_html_e( 'همه صفحات', 'apple-star-loader' ); ?></option>
						<option value="home_posts"  <?php selected( $opts['target'], 'home_posts' ); ?>><?php esc_html_e( 'صفحه اصلی و وبلاگ', 'apple-star-loader' ); ?></option>
						<option value="posts_only"  <?php selected( $opts['target'], 'posts_only' ); ?>><?php esc_html_e( 'فقط نوشته‌ها', 'apple-star-loader' ); ?></option>
						<option value="pages_only"  <?php selected( $opts['target'], 'pages_only' ); ?>><?php esc_html_e( 'فقط برگه‌ها', 'apple-star-loader' ); ?></option>
						<option value="woocommerce" <?php selected( $opts['target'], 'woocommerce' ); ?>><?php esc_html_e( 'فقط فروشگاه (WooCommerce)', 'apple-star-loader' ); ?></option>
					</select>
					<small class="aspl-hint"><?php esc_html_e( 'در حالت بروزرسانی، لودینگ در همه صفحات نمایش داده می‌شود.', 'apple-star-loader' ); ?></small>
				</div>
			</section>

			<!-- Presets -->
			<section class="aspl-card">
				<h2>✨ <?php esc_html_e( 'انتخاب مدل لودینگ', 'apple-star-loader' ); ?></h2>
				<p class="aspl-section-hint"><?php esc_html_e( 'یکی از مدل‌های زیر را انتخاب کنید. پیش‌نمایش زنده سمت راست بلافاصله به‌روز می‌شود.', 'apple-star-loader' ); ?></p>
				<div class="aspl-preset-grid">
					<?php foreach ( $presets as $slug => $label ) : ?>
						<?php
						$svg_icon = $this->preset_icon( $slug );
						?>
						<label class="aspl-preset-card <?php echo $opts['preset'] === $slug ? 'selected' : ''; ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
							<input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[preset]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $opts['preset'], $slug ); ?>>
							<span class="aspl-preset-thumb"><?php echo $svg_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="aspl-preset-name"><?php echo esc_html( $label ); ?></span>
							<span class="aspl-preset-check">✓</span>
						</label>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Text / Logo -->
			<section class="aspl-card">
				<h2>🔤 <?php esc_html_e( 'متن و لوگو', 'apple-star-loader' ); ?></h2>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[use_site_title]" value="1" <?php checked( $opts['use_site_title'] ); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e( 'استفاده از عنوان سایت', 'apple-star-loader' ); ?></span>
					</label>
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e( 'متن لودینگ', 'apple-star-loader' ); ?></label>
					<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[text]" value="<?php echo esc_attr( $opts['text'] ); ?>" class="aspl-text" maxlength="40" data-k="text">
					<small class="aspl-hint"><?php esc_html_e( 'فارسی یا انگلیسی — در مدل Apple Star Pulse حروف یکی‌یکی موج (نبض) می‌خورند.', 'apple-star-loader' ); ?></small>
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e( 'لوگو (اختیاری)', 'apple-star-loader' ); ?></label>
					<div class="aspl-logo-row">
						<div class="aspl-logo-prev" id="logoPrev">
							<?php if ( $opts['logo'] ) : ?>
								<img src="<?php echo esc_url( $opts['logo'] ); ?>" alt="">
							<?php else : ?>
								<span><?php esc_html_e( 'بدون لوگو', 'apple-star-loader' ); ?></span>
							<?php endif; ?>
						</div>
						<input type="hidden" id="logoIn" name="<?php echo esc_attr( self::OPTION ); ?>[logo]" value="<?php echo esc_attr( $opts['logo'] ); ?>">
						<div class="aspl-logo-btns">
							<button type="button" class="button" id="logoUp"><?php esc_html_e( 'انتخاب لوگو', 'apple-star-loader' ); ?></button>
							<button type="button" class="button-link-delete" id="logoRm"><?php esc_html_e( 'حذف', 'apple-star-loader' ); ?></button>
						</div>
					</div>
				</div>
			</section>

			<!-- Maintenance Mode -->
			<section class="aspl-card aspl-maint-card">
				<h2>🛠️ <?php esc_html_e( 'حالت بروزرسانی', 'apple-star-loader' ); ?></h2>
				<p class="aspl-section-hint"><?php esc_html_e( 'وقتی روشن باشد، سایت برای بازدیدکنندگان با صفحه لودینگ + شمارنده معکوس قفل می‌ماند تا تایمر صفر شود. مناسب زمان به‌روزرسانی یا تعمیرات.', 'apple-star-loader' ); ?></p>

				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[maintenance_mode]" value="1" id="maintMode" <?php checked( $opts['maintenance_mode'] ); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e( 'فعال‌کردن حالت بروزرسانی (پیش‌فرض خاموش)', 'apple-star-loader' ); ?></span>
					</label>
				</div>

				<div class="aspl-maint-body" id="maintBody" style="<?php echo $opts['maintenance_mode'] ? '' : 'display:none;'; ?>">
					<div class="aspl-row aspl-timer-row">
						<label class="aspl-lbl"><?php esc_html_e( 'مدت زمان بروزرسانی', 'apple-star-loader' ); ?></label>
						<div class="aspl-timer-inputs">
							<label><input type="number" min="0" max="999" value="<?php echo (int) $opts['maintenance_hours']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[maintenance_hours]" class="small-text" data-k="maintenance_hours"> <span><?php esc_html_e( 'ساعت', 'apple-star-loader' ); ?></span></label>
							<label><input type="number" min="0" max="59" value="<?php echo (int) $opts['maintenance_minutes']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[maintenance_minutes]" class="small-text" data-k="maintenance_minutes"> <span><?php esc_html_e( 'دقیقه', 'apple-star-loader' ); ?></span></label>
							<label><input type="number" min="0" max="59" value="<?php echo (int) $opts['maintenance_seconds']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[maintenance_seconds]" class="small-text" data-k="maintenance_seconds"> <span><?php esc_html_e( 'ثانیه', 'apple-star-loader' ); ?></span></label>
						</div>
						<small class="aspl-hint"><?php esc_html_e( 'لودینگ تا پایان این شمارنده روی سایت می‌ماند و بعد محو می‌شود.', 'apple-star-loader' ); ?></small>
					</div>
					<div class="aspl-row">
						<label class="aspl-lbl"><?php esc_html_e( 'متن زیر شمارنده', 'apple-star-loader' ); ?></label>
						<textarea name="<?php echo esc_attr( self::OPTION ); ?>[maintenance_msg]" rows="2" class="aspl-textarea" data-k="maintenance_msg"><?php echo esc_textarea( $opts['maintenance_msg'] ); ?></textarea>
					</div>
				</div>
			</section>

			<!-- Colors -->
			<section class="aspl-card">
				<h2>🎨 <?php esc_html_e( 'رنگ‌ها', 'apple-star-loader' ); ?></h2>
				<div class="aspl-row aspl-color-grid">
					<div class="aspl-color-cell">
						<label class="aspl-lbl"><?php esc_html_e( 'پس‌زمینه', 'apple-star-loader' ); ?></label>
						<input type="text" class="aspl-color" name="<?php echo esc_attr( self::OPTION ); ?>[bg_color]" value="<?php echo esc_attr( $opts['bg_color'] ); ?>" data-default-color="#0a0a0f" data-k="bg_color">
					</div>
					<div class="aspl-color-cell">
						<label class="aspl-lbl"><?php esc_html_e( 'متن', 'apple-star-loader' ); ?></label>
						<input type="text" class="aspl-color" name="<?php echo esc_attr( self::OPTION ); ?>[text_color]" value="<?php echo esc_attr( $opts['text_color'] ); ?>" data-default-color="#ffffff" data-k="text_color">
					</div>
					<div class="aspl-color-cell">
						<label class="aspl-lbl"><?php esc_html_e( 'رنگ اصلی (هایلایت/اکسنت)', 'apple-star-loader' ); ?></label>
						<input type="text" class="aspl-color" name="<?php echo esc_attr( self::OPTION ); ?>[accent_color]" value="<?php echo esc_attr( $opts['accent_color'] ); ?>" data-default-color="#00c3ff" data-k="accent_color">
					</div>
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl small"><?php esc_html_e( 'شفافیت پس‌زمینه', 'apple-star-loader' ); ?> — <b id="bgOpLbl"><?php echo (int) $opts['bg_opacity']; ?>%</b></label>
					<input type="range" min="0" max="100" value="<?php echo (int) $opts['bg_opacity']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[bg_opacity]" class="aspl-range" data-k="bg_opacity">
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl small"><?php esc_html_e( 'میزان محو (Blur) پس‌زمینه', 'apple-star-loader' ); ?> — <b id="blurLbl"><?php echo (int) $opts['blur_amount']; ?>px</b></label>
					<input type="range" min="0" max="40" value="<?php echo (int) $opts['blur_amount']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[blur_amount]" class="aspl-range" data-k="blur_amount">
				</div>
			</section>

			<!-- Timing -->
			<section class="aspl-card">
				<h2>⏱️ <?php esc_html_e( 'زمان‌بندی', 'apple-star-loader' ); ?></h2>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e( 'حداقل زمان نمایش', 'apple-star-loader' ); ?> — <b id="minLbl"><?php echo (int) $opts['min_time']; ?></b> ms</label>
					<input type="range" min="0" max="3000" step="100" value="<?php echo (int) $opts['min_time']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[min_time]" class="aspl-range" data-k="min_time">
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e( 'زمان محو شدن', 'apple-star-loader' ); ?> — <b id="fadeLbl"><?php echo (int) $opts['fade_duration']; ?></b> ms</label>
					<input type="range" min="100" max="2000" step="50" value="<?php echo (int) $opts['fade_duration']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[fade_duration]" class="aspl-range" data-k="fade_duration">
				</div>
				<div class="aspl-row aspl-two-col">
					<div>
						<label class="aspl-lbl"><?php esc_html_e( 'حداکثر انتظار (Timeout)', 'apple-star-loader' ); ?></label>
						<input type="number" min="1" max="600" value="<?php echo (int) $opts['timeout']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[timeout]" class="small-text"> <span><?php esc_html_e( 'ثانیه', 'apple-star-loader' ); ?></span>
					</div>
					<div>
						<label class="aspl-lbl">Z-Index</label>
						<input type="number" min="1000" value="<?php echo (int) $opts['z_index']; ?>" name="<?php echo esc_attr( self::OPTION ); ?>[z_index]" class="small-text">
					</div>
				</div>
			</section>

			<!-- Custom CSS -->
			<section class="aspl-card">
				<h2>🧩 <?php esc_html_e( 'CSS سفارشی', 'apple-star-loader' ); ?></h2>
				<textarea name="<?php echo esc_attr( self::OPTION ); ?>[custom_css]" rows="5" class="aspl-code" spellcheck="false" dir="ltr" placeholder="/* مثال: */&#10;#asp-loader-root .asp-stage { transform: scale(0.95); }"><?php echo esc_textarea( $opts['custom_css'] ); ?></textarea>
			</section>

			<div class="aspl-savebar">
				<?php submit_button( __( '💾 ذخیره تنظیمات', 'apple-star-loader' ), 'primary', 'submit', false, array( 'class' => 'button button-primary button-hero' ) ); ?>
			</div>
		</form>

		<!-- Live preview sidebar -->
		<aside class="aspl-side">
			<section class="aspl-card aspl-preview-card">
				<h2>👀 <?php esc_html_e( 'پیش‌نمایش زنده', 'apple-star-loader' ); ?></h2>
				<div class="aspl-framebox">
					<iframe id="asplFrame" class="aspl-frame" title="preview" sandbox="allow-scripts allow-same-origin" srcdoc=""></iframe>
				</div>
				<div class="aspl-preview-actions">
					<button type="button" class="button" id="asplFs"><?php esc_html_e( '🔍 تمام صفحه', 'apple-star-loader' ); ?></button>
					<button type="button" class="button" id="asplRefresh"><?php esc_html_e( '🔄 رفرش', 'apple-star-loader' ); ?></button>
				</div>
				<small class="aspl-hint"><?php esc_html_e( 'پیش‌نمایش با تغییر هر تنظیم به‌روز می‌شود. انیمیشن‌ها واقعی اجرا می‌شوند.', 'apple-star-loader' ); ?></small>
			</section>

			<section class="aspl-card">
				<h2>ℹ️ <?php esc_html_e( 'راهنما', 'apple-star-loader' ); ?></h2>
				<ul class="aspl-tips">
					<li><?php esc_html_e( 'لودینگ تا بارگذاری کامل صفحه (شامل آخرین عکس) می‌ماند و سپس محو می‌شود.', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'حالت بروزرسانی: شمارنده معکوس تا صفر شدن فعال می‌ماند.', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'متن فارسی به‌صورت کلمه‌به‌کلمه موج می‌خورد تا اتصال حروف به‌هم نریزد.', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'برای جلوگیری از فلیکر، «حداقل زمان نمایش» را روی ۴۰۰ تا ۸۰۰ میلی‌ثانیه بگذارید.', 'apple-star-loader' ); ?></li>
					<li><?php esc_html_e( 'Timeout تضمین می‌کند سایت هیچ‌گاه پشت لودینگ قفل نمی‌شود.', 'apple-star-loader' ); ?></li>
				</ul>
			</section>
		</aside>
	</div>

	<!-- Fullscreen preview modal -->
	<div class="aspl-modal" id="asplModal">
		<div class="aspl-modal-bg"></div>
		<button type="button" class="aspl-modal-x" id="asplModalX">✕</button>
		<iframe id="asplModalFrame" class="aspl-modal-frame" sandbox="allow-scripts allow-same-origin"></iframe>
	</div>
</div>

<style><?php echo $this->adminCss(); ?></style>
<script>
	window.__asplSiteTitle = <?php echo wp_json_encode( get_bloginfo( 'name' ) ); ?>;
	window.__asplPresets = <?php
		$codes = array();
		foreach ( array_keys( $presets ) as $slug ) {
			$codes[ $slug ] = ASPL_Defaults::get_preset_code( $slug );
		}
		echo wp_json_encode( $codes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
	?>;
	window.__asplDefaults = <?php echo wp_json_encode( ASPL_Defaults::get_options(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>;
</script>
<script><?php echo $this->adminJs(); ?></script>
		<?php
	}

	/**
	 * Inline SVG thumbnails for preset cards. These are decorative, small,
	 * monochrome — they match the overall loader shape at a glance.
	 */
	private function preset_icon( $slug ) {
		$c = 'currentColor';
		switch ( $slug ) {
			case 'apple_star':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><line x1="4" y1="20" x2="76" y2="20" stroke="' . $c . '" stroke-opacity=".25"/><circle cx="40" cy="20" r="3" fill="' . $c . '"><animate attributeName="cx" values="4;76;4" dur="2.4s" repeatCount="indefinite"/></circle><text x="14" y="34" font-family="sans-serif" font-weight="800" font-size="11" fill="' . $c . '" fill-opacity=".55" letter-spacing="2">APPLE STAR</text></svg>';
			case 'pulse_classic':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><circle cx="40" cy="22" r="6" fill="' . $c . '"><animate attributeName="r" values="5;11;5" dur="1.5s" repeatCount="indefinite"/></circle><circle cx="40" cy="22" r="6" fill="none" stroke="' . $c . '" stroke-opacity=".5"><animate attributeName="r" values="5;22;5" dur="1.5s" repeatCount="indefinite"/><animate attributeName="opacity" values=".7;0;.7" dur="1.5s" repeatCount="indefinite"/></circle></svg>';
			case 'freq_bars':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg">'
					. '<rect x="22" y="16" width="5" height="12" rx="2" fill="' . $c . '"><animate attributeName="height" values="6;18;6" dur="1s" begin="-0.4s" repeatCount="indefinite"/><animate attributeName="y" values="23;17;23" dur="1s" begin="-0.4s" repeatCount="indefinite"/></rect>'
					. '<rect x="32" y="14" width="5" height="16" rx="2" fill="' . $c . '"><animate attributeName="height" values="6;20;6" dur="1s" begin="-0.2s" repeatCount="indefinite"/><animate attributeName="y" values="23;14;23" dur="1s" begin="-0.2s" repeatCount="indefinite"/></rect>'
					. '<rect x="42" y="10" width="6" height="22" rx="2" fill="' . $c . '" fill-opacity=".9"><animate attributeName="height" values="10;24;10" dur="1s" repeatCount="indefinite"/><animate attributeName="y" values="23;10;23" dur="1s" repeatCount="indefinite"/></rect>'
					. '<rect x="52" y="14" width="5" height="16" rx="2" fill="' . $c . '"><animate attributeName="height" values="6;20;6" dur="1s" begin="-0.2s" repeatCount="indefinite"/><animate attributeName="y" values="23;14;23" dur="1s" begin="-0.2s" repeatCount="indefinite"/></rect>'
					. '<rect x="62" y="16" width="5" height="12" rx="2" fill="' . $c . '"><animate attributeName="height" values="6;18;6" dur="1s" begin="-0.4s" repeatCount="indefinite"/><animate attributeName="y" values="23;17;23" dur="1s" begin="-0.4s" repeatCount="indefinite"/></rect>'
					. '</svg>';
			case 'sine_wave':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg">'
					. '<circle cx="22" cy="22" r="3" fill="' . $c . '"><animate attributeName="cy" values="28;12;28" dur="1.2s" repeatCount="indefinite"/></circle>'
					. '<circle cx="34" cy="22" r="3" fill="' . $c . '"><animate attributeName="cy" values="28;12;28" dur="1.2s" begin="0.15s" repeatCount="indefinite"/></circle>'
					. '<circle cx="46" cy="22" r="3" fill="' . $c . '"><animate attributeName="cy" values="28;12;28" dur="1.2s" begin="0.3s" repeatCount="indefinite"/></circle>'
					. '<circle cx="58" cy="22" r="3" fill="' . $c . '"><animate attributeName="cy" values="28;12;28" dur="1.2s" begin="0.45s" repeatCount="indefinite"/></circle>'
					. '</svg>';
			case 'ecg_heartbeat':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><polyline points="4,22 30,22 34,8 38,32 42,16 46,22 76,22" fill="none" stroke="' . $c . '" stroke-width="1.5" stroke-linejoin="round"/><circle cx="40" cy="22" r="3" fill="' . $c . '"><animate attributeName="cx" values="4;76;4" dur="2s" repeatCount="indefinite"/></circle></svg>';
			case 'siri_orbit':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><g transform="translate(40,22)"><circle r="10" fill="none" stroke="' . $c . '" stroke-width="2" stroke-dasharray="30 25" transform="rotate(0)"><animateTransform attributeName="transform" type="rotate" from="0" to="360" dur="1s" repeatCount="indefinite"/></circle><circle r="14" fill="none" stroke="' . $c . '" stroke-width="2" stroke-opacity=".6" stroke-dasharray="30 55" transform="rotate(120)"><animateTransform attributeName="transform" type="rotate" from="360" to="0" dur="1.5s" repeatCount="indefinite"/></circle><circle r="18" fill="none" stroke="' . $c . '" stroke-width="2" stroke-opacity=".3" stroke-dasharray="20 90"><animateTransform attributeName="transform" type="rotate" from="0" to="360" dur="2s" repeatCount="indefinite"/></circle></g></svg>';
			case 'radar_sweep':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g transform="translate(40,22)"><circle r="4" fill="' . $c . '"/><circle r="4" fill="none" stroke="' . $c . '" stroke-width="1.5"><animate attributeName="r" values="4;20;4" dur="2s" repeatCount="indefinite"/><animate attributeName="opacity" values="1;0;1" dur="2s" repeatCount="indefinite"/></circle><circle r="4" fill="none" stroke="' . $c . '" stroke-width="1.5"><animate attributeName="r" values="4;20;4" dur="2s" begin="-0.65s" repeatCount="indefinite"/><animate attributeName="opacity" values="1;0;1" dur="2s" begin="-0.65s" repeatCount="indefinite"/></circle><circle r="4" fill="none" stroke="' . $c . '" stroke-width="1.5"><animate attributeName="r" values="4;20;4" dur="2s" begin="-1.3s" repeatCount="indefinite"/><animate attributeName="opacity" values="1;0;1" dur="2s" begin="-1.3s" repeatCount="indefinite"/></circle></g></svg>';
			case 'breathing_core':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="br" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="' . $c . '" stop-opacity="1"/><stop offset="70%" stop-color="' . $c . '" stop-opacity="0"/></radialGradient></defs><circle cx="40" cy="22" r="10" fill="url(#br)"><animate attributeName="r" values="8;16;8" dur="3s" repeatCount="indefinite"/><animate attributeName="opacity" values=".6;1;.6" dur="3s" repeatCount="indefinite"/></circle></svg>';
			case 'quantum_spin':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><g transform="translate(40,22)"><ellipse rx="14" ry="5" fill="none" stroke="' . $c . '" stroke-width="1.5"/><ellipse rx="14" ry="5" fill="none" stroke="' . $c . '" stroke-width="1.5" stroke-opacity=".6" transform="rotate(60)"><animateTransform attributeName="transform" type="rotate" from="0 0 0" to="360 0 0" dur="3s" repeatCount="indefinite"/></ellipse><ellipse rx="14" ry="5" fill="none" stroke="' . $c . '" stroke-width="1.5" stroke-opacity=".35" transform="rotate(120)"/></g></svg>';
			case 'wave_morph':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><rect x="20" y="19" width="40" height="4" rx="2" fill="' . $c . '"><animate attributeName="width" values="40;10;40" dur="2s" repeatCount="indefinite"/><animate attributeName="x" values="20;35;20" dur="2s" repeatCount="indefinite"/><animate attributeName="height" values="4;22;4" dur="2s" repeatCount="indefinite"/><animate attributeName="y" values="19;9;19" dur="2s" repeatCount="indefinite"/><animate attributeName="rx" values="2;11;2" dur="2s" repeatCount="indefinite"/></rect></svg>';
			case 'dot_rhythm':
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><circle cx="18" cy="22" r="3" fill="' . $c . '"><animate attributeName="cx" values="18;60;18" dur="1.5s" repeatCount="indefinite"/><animate attributeName="opacity" values="1;0;1" dur="1.5s" repeatCount="indefinite"/></circle><circle cx="18" cy="22" r="3" fill="' . $c . '" fill-opacity=".8"><animate attributeName="cx" values="18;60;18" dur="1.5s" begin="0.2s" repeatCount="indefinite"/><animate attributeName="opacity" values="1;0;1" dur="1.5s" begin="0.2s" repeatCount="indefinite"/></circle><circle cx="18" cy="22" r="3" fill="' . $c . '" fill-opacity=".5"><animate attributeName="cx" values="18;60;18" dur="1.5s" begin="0.4s" repeatCount="indefinite"/><animate attributeName="opacity" values="1;0;1" dur="1.5s" begin="0.4s" repeatCount="indefinite"/></circle></svg>';
			default:
				return '<svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><circle cx="40" cy="22" r="8" fill="' . $c . '"/></svg>';
		}
	}

	/* ============================================================
	   ADMIN CSS
	   ============================================================ */
	private function adminCss() {
		return <<<'CSS'
:root{--p:#0071e3;--p2:#00c3ff;--ok:#16a34a;--bad:#dc2626;--bd:#e5e7eb;--bg:#f6f8fb;--tx:#1d2327;--mut:#6b7280;--sh:0 1px 3px rgba(0,0,0,.05),0 8px 24px rgba(17,24,39,.06);}
.aspl-admin{margin:10px 20px 0 0;color:var(--tx);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Vazirmatn",Tahoma,sans-serif;max-width:1360px;direction:rtl;}
.aspl-admin *{box-sizing:border-box;}
.aspl-admin h1,.aspl-admin h2{color:var(--tx);font-family:inherit;}
.aspl-admin input,.aspl-admin select,.aspl-admin textarea,.aspl-admin button{font-family:inherit;}

/* Header */
.aspl-head{display:flex;align-items:center;justify-content:space-between;padding:22px 28px;background:linear-gradient(135deg,#1d1d1f 0%,#0071e3 60%,#00c3ff 100%);color:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,113,227,.25);margin-bottom:18px;gap:14px;flex-wrap:wrap;}
.aspl-head-left{display:flex;align-items:center;gap:14px;}
.aspl-logo{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:26px;border:1px solid rgba(255,255,255,.22);}
.aspl-head h1{color:#fff;margin:0;font-size:20px;line-height:1.2;}
.aspl-head h1 small{font-size:11px;background:rgba(255,255,255,.2);padding:2px 8px;border-radius:8px;margin-right:8px;font-weight:600;}
.aspl-head p{margin:4px 0 0;opacity:.88;font-size:12.5px;}
.aspl-head-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.aspl-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);padding:6px 12px;border-radius:999px;font-size:12px;font-weight:700;}
.aspl-pill.on{background:rgba(22,163,74,.7);}
.aspl-pill.off{background:rgba(220,38,38,.65);}
.aspl-pill.maint{background:rgba(234,179,8,.7);}
.aspl-pill .dot{width:8px;height:8px;border-radius:50%;background:#fff;animation:asplp 1.8s infinite;}
@keyframes asplp{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.4;transform:scale(.7);}}

/* Layout */
.aspl-grid{display:grid;grid-template-columns:1fr 380px;gap:18px;}
@media (max-width:1080px){.aspl-grid{grid-template-columns:1fr;}}
.aspl-card-col{min-width:0;display:flex;flex-direction:column;gap:18px;}
.aspl-side{display:flex;flex-direction:column;gap:18px;align-self:start;position:sticky;top:30px;}
@media (max-width:1080px){.aspl-side{position:static;}}
.aspl-card{background:#fff;border:1px solid var(--bd);border-radius:14px;padding:22px;box-shadow:var(--sh);}
.aspl-card h2{margin:0 0 8px;font-size:15px;font-weight:800;letter-spacing:.01em;}
.aspl-section-hint{color:var(--mut);font-size:12.5px;margin:0 0 14px;line-height:1.7;}
.aspl-row{margin-bottom:16px;}
.aspl-row:last-child{margin-bottom:0;}
.aspl-lbl{display:block;font-weight:600;font-size:13px;margin-bottom:6px;}
.aspl-lbl.small{font-weight:500;font-size:12px;color:var(--mut);}
.aspl-hint{display:block;color:var(--mut);font-size:12px;margin-top:6px;line-height:1.6;}
.aspl-two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
@media (max-width:600px){.aspl-two-col{grid-template-columns:1fr;}}

/* Inputs */
.aspl-text,.aspl-textarea{width:100%;max-width:520px;padding:10px 12px;border:1px solid var(--bd);border-radius:10px;font-size:14px;background:#fafbfc;transition:.15s;}
.aspl-textarea{max-width:100%;resize:vertical;font-family:inherit;line-height:1.7;}
.aspl-text:focus,.aspl-textarea:focus{outline:none;border-color:var(--p);box-shadow:0 0 0 3px rgba(0,113,227,.12);background:#fff;}
.aspl-select{padding:8px 12px;border:1px solid var(--bd);border-radius:8px;background:#fff;min-width:260px;max-width:100%;}
.aspl-range{width:100%;accent-color:var(--p);}
.aspl-code{width:100%;font-family:ui-monospace,SF Mono,Menlo,Consolas,monospace;font-size:13px;line-height:1.6;border:1px solid var(--bd);border-radius:10px;background:#fafbfc;padding:12px;direction:ltr;text-align:left;}
.aspl-code:focus{outline:none;border-color:var(--p);box-shadow:0 0 0 3px rgba(0,113,227,.12);}

/* iOS-style switch */
.aspl-switch{display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none;}
.aspl-switch input{position:absolute;opacity:0;pointer-events:none;}
.aspl-switch .slider{position:relative;width:46px;height:26px;background:#d1d5db;border-radius:999px;transition:.2s;flex-shrink:0;}
.aspl-switch .slider::before{content:"";position:absolute;top:3px;inset-inline-end:calc(100% - 23px);width:20px;height:20px;background:#fff;border-radius:50%;transition:.25s cubic-bezier(.4,.0,.2,1);box-shadow:0 2px 5px rgba(0,0,0,.25);}
.aspl-switch input:checked + .slider{background:var(--ok);}
.aspl-switch input:checked + .slider::before{inset-inline-end:3px;}
.aspl-switch .lbl{font-size:14px;font-weight:500;}
.aspl-switch input:focus-visible + .slider{box-shadow:0 0 0 3px rgba(0,113,227,.25);}

/* Logo row */
.aspl-logo-row{display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;}
.aspl-logo-prev{width:200px;height:100px;border:2px dashed var(--bd);border-radius:10px;display:flex;align-items:center;justify-content:center;background:#fafbfc;padding:6px;overflow:hidden;}
.aspl-logo-prev img{max-width:100%;max-height:100%;object-fit:contain;}
.aspl-logo-prev span{color:var(--mut);font-size:12px;}
.aspl-logo-btns{display:flex;flex-direction:column;gap:6px;}

/* Preset cards */
.aspl-preset-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;}
.aspl-preset-card{position:relative;display:flex;flex-direction:column;align-items:center;gap:8px;border:2px solid var(--bd);border-radius:12px;padding:14px 10px 10px;cursor:pointer;background:#fafbfc;transition:.18s;text-align:center;}
.aspl-preset-card:hover{border-color:#93c5fd;background:#fff;transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,113,227,.1);}
.aspl-preset-card input{position:absolute;opacity:0;pointer-events:none;}
.aspl-preset-card.selected{border-color:var(--p);background:#eff6ff;box-shadow:0 0 0 3px rgba(0,113,227,.12);}
.aspl-preset-thumb{width:100%;height:56px;display:flex;align-items:center;justify-content:center;color:#374151;background:#fff;border-radius:8px;border:1px solid var(--bd);overflow:hidden;}
.aspl-preset-thumb svg{width:100%;height:100%;display:block;}
.aspl-preset-name{font-size:11.5px;font-weight:600;color:var(--tx);line-height:1.3;}
.aspl-preset-check{position:absolute;top:8px;inset-inline-start:8px;width:20px;height:20px;border-radius:50%;background:var(--ok);color:#fff;font-size:11px;display:none;align-items:center;justify-content:center;font-weight:800;}
.aspl-preset-card.selected .aspl-preset-check{display:flex;}

/* Maintenance body */
.aspl-maint-body{margin-top:10px;padding:14px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;}
.aspl-timer-inputs{display:flex;gap:14px;flex-wrap:wrap;}
.aspl-timer-inputs label{display:inline-flex;align-items:center;gap:6px;background:#fff;padding:8px 12px;border-radius:8px;border:1px solid #fde68a;}
.aspl-timer-inputs input{width:60px;text-align:center;}
.aspl-timer-inputs span{font-size:12.5px;color:var(--mut);}

/* Colors grid */
.aspl-color-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;}
.aspl-color-cell .wp-picker-container{display:block;}
.aspl-color-cell .wp-picker-container .wp-color-result.button{margin:0;}

/* Preview */
.aspl-framebox{background:#0a0a0f;border-radius:12px;padding:8px;border:1px solid #1f2937;}
.aspl-frame{width:100%;height:300px;border:0;border-radius:8px;background:#0a0a0f;display:block;}
.aspl-preview-actions{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;}
.aspl-modal{position:fixed;inset:0;z-index:1000000;display:none;align-items:center;justify-content:center;padding:30px;}
.aspl-modal.open{display:flex;}
.aspl-modal-bg{position:absolute;inset:0;background:rgba(0,0,0,.92);backdrop-filter:blur(10px);}
.aspl-modal-x{position:absolute;top:18px;inset-inline-end:18px;width:44px;height:44px;border-radius:50%;border:0;background:rgba(255,255,255,.12);color:#fff;font-size:18px;cursor:pointer;z-index:5;}
.aspl-modal-x:hover{background:rgba(255,255,255,.2);}
.aspl-modal-frame{position:relative;width:100%;height:100%;border:0;background:#000;border-radius:10px;}
.aspl-tips{margin:0;padding:0 0 0 20px;color:var(--mut);font-size:13px;line-height:1.9;}
.aspl-tips li{margin:0;}
.aspl-savebar{display:flex;justify-content:flex-start;}
.aspl-savebar .button{border-radius:12px!important;padding:8px 28px!important;font-size:15px!important;box-shadow:0 6px 20px rgba(0,113,227,.3)!important;}
.wp-picker-holder{position:absolute;z-index:20;}

@media (max-width:600px){
	.aspl-admin{margin:8px 10px 0 0;}
	.aspl-head{padding:18px 18px;}
	.aspl-card{padding:16px;}
	.aspl-preset-grid{grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;}
	.aspl-logo-prev{width:100%;max-width:260px;}
}
CSS;
	}

	/* ============================================================
	   ADMIN JAVASCRIPT
	   Builds a fully running preview (srcdoc) from current form state
	   for ANY selected preset. Supports logo, text, colors, timer etc.
	   ============================================================ */
	private function adminJs() {
		return <<<'JS'
(function($){
	var $f = $('#asplFrame'), $mf = $('#asplModalFrame'), $form = $('#aspl-form'), $modal=$('#asplModal');
	if(!$f.length) return;

	function isRTLText(s){return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF\u0590-\u05FF]/.test(s);}
	function hex2rgba(hex,a){hex=(hex||'#000').replace('#','');if(hex.length===3)hex=hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];if(hex.length!==6)return 'rgba(0,0,0,'+(a||100)/100+')';var r=parseInt(hex.substring(0,2),16),g=parseInt(hex.substring(2,4),16),b=parseInt(hex.substring(4,6),16);return 'rgba('+r+','+g+','+b+','+((a||100)/100)+')';}
	function val(name){
		var sel='[name$="['+name+']"]';
		var $el=$form.find(sel);
		if(!$el.length) return '';
		if($el.is(':checkbox')) return $el.is(':checked')?1:0;
		if($el.is(':radio')) return $form.find(sel+':checked').val()||'';
		return $el.val();
	}
	function num(name,def){var v=parseFloat(val(name));return isNaN(v)?def:v;}

	function buildSpans(text){
		var rtl=isRTLText(text);
		var spans='';
		if(rtl){
			var words=String(text).split(/(\s+)/); var idx=0;
			for(var i=0;i<words.length;i++){
				var c=words[i];
				if(/^\s+$/.test(c)){spans+='<span class="sp">'+c+'</span>';continue;}
				if(!c)continue;
				spans+='<span class="rtl" style="--w:'+idx+'">'+c.replace(/</g,'&lt;')+'</span>';idx++;
			}
		}else{
			var ch=String(text).split('');
			for(var k=0;k<ch.length;k++){
				if(ch[k]===' '){spans+='<span class="sp" style="--w:'+k+'">&nbsp;</span>';}
				else spans+='<span class="ltr" style="--w:'+k+'">'+ch[k].replace(/</g,'&lt;')+'</span>';
			}
		}
		return {html:spans, rtl:rtl};
	}

	function pad(n){n=n|0;return n<10?'0'+n:''+n;}

	function build(){
		var st = window.__asplSiteTitle || '';
		var useTitle = $form.find('[name$="[use_site_title]"]').is(':checked');
		var text = (useTitle && st) ? st : (val('text')||'APPLE STAR');
		var preset = val('preset') || 'apple_star';
		var code = (window.__asplPresets && window.__asplPresets[preset]) ? window.__asplPresets[preset] : '';
		if(!code) code = '';

		var bg=hex2rgba(val('bg_color'), num('bg_opacity',85));
		var textCol = val('text_color')||'#ffffff';
		var accent = val('accent_color')||'#00c3ff';
		var blur = num('blur_amount',16);
		var vars = '--asp-bg:'+bg+';--asp-text:'+textCol+';--asp-accent:'+accent+';--asp-blur:'+blur+'px;';
		var cssVars = 'html,body{margin:0;padding:0;height:100%;background:#0a0a0f;overflow:hidden;}#asp-loader-root{position:fixed;inset:0;'+vars+'z-index:1;}';

		// Logo
		var logoUrl = val('logo')||'';
		var logoHtml = '';
		if(logoUrl){
			logoHtml = '<style>.asp-logo-wrap img{max-height:70px;max-width:220px;object-fit:contain;filter:drop-shadow(0 6px 18px rgba(0,0,0,.5));}</style><div class="asp-logo-wrap" id="asp-logo-slot"><img src="'+logoUrl.replace(/"/g,'&quot;')+'"></div>';
		}

		// Spans for word
		var sp = buildSpans(text);
		var dirAttr = sp.rtl?'dir="rtl"':'dir="ltr"';

		// Inject logo + spans into preset markup
		var presetHtml = code;
		// Replace #asp-word contents with our built spans
		presetHtml = presetHtml.replace(/(<[^>]*id=["']asp-word["'][^>]*>)[\s\S]*?(<\/[^>]+>)/, '$1'+sp.html+'$2');
		// Also handle generic .asp-wave-word if present without id
		if(presetHtml.indexOf('id="asp-word"')===-1){
			presetHtml = presetHtml.replace(/(<[^>]*class=["'][^"']*asp-wave-word[^"']*["'][^>]*>)[\s\S]*?(<\/[^>]+>)/, '$1'+sp.html+'$2');
		}
		// Set dir on word element
		presetHtml = presetHtml.replace(/id=["']asp-word["']/, 'id="asp-word" '+dirAttr);

		// Inject logo into stage
		if(logoHtml){
			// If there's a logo slot placeholder
			if(presetHtml.indexOf('id="asp-logo-slot"')!==-1){
				presetHtml = presetHtml.replace(/<div class="asp-logo-wrap" id="asp-logo-slot"><\/div>/, logoHtml);
			} else {
				presetHtml = presetHtml.replace(/(<div class="asp-stage">)/, '$1'+logoHtml);
			}
		} else {
			// Remove empty logo slot
			presetHtml = presetHtml.replace(/<div class="asp-logo-wrap" id="asp-logo-slot"><\/div>/g, '');
		}

		// Maintenance block (preview shows a 30-second fake countdown so the user sees what it looks like)
		var maintOn = $form.find('[name$="[maintenance_mode]"]').is(':checked');
		var maintMsg = val('maintenance_msg')||'ما در حال بروز رسانی هستیم.';
		var maintH = num('maintenance_hours',0), maintM = num('maintenance_minutes',30), maintS = num('maintenance_seconds',0);
		var maintHtml = '';
		if(maintOn){
			var totalSec = (maintH*3600)+(maintM*60)+maintS;
			if(totalSec<=0) totalSec = 30; // preview default
			// Cap preview at 59:59 for nicer display when user sets huge hours
			var previewSec = Math.min(totalSec, 99*3600+59*60+59);
			maintHtml = '<div class="asp-maint" dir="rtl" style="margin-top:24px;text-align:center;color:rgba(255,255,255,.9);max-width:560px;padding:0 20px;">'
				+ '<div class="asp-maint-lbl" style="margin-bottom:6px;font-size:11px;letter-spacing:.18em;text-transform:uppercase;opacity:.55;">در حال بروز رسانی</div>'
				+ '<div class="asp-maint-timer" id="asp-maint-timer" style="font-family:ui-monospace,SF Mono,Menlo,Consolas,monospace;font-size:clamp(1.1rem,3vw,1.8rem);font-weight:700;letter-spacing:.08em;color:#fff;text-shadow:0 0 20px rgba(255,255,255,.4);direction:ltr;unicode-bidi:embed;">'
				+ pad(maintH)+':<span class="asp-sep" style="opacity:.6;animation:asp-blink 1s steps(2) infinite;margin:0 2px;">:</span>'+pad(maintM)+':<span class="asp-sep" style="opacity:.6;animation:asp-blink 1s steps(2) infinite;margin:0 2px;">:</span>'+pad(maintS)
				+ '</div>'
				+ '<div class="asp-maint-msg" style="margin-top:12px;font-size:clamp(.85rem,1.8vw,1rem);line-height:1.7;opacity:.8;">'+maintMsg.replace(/</g,'&lt;')+'</div>'
				+ '</div>';
			// Inject small countdown script for preview only
			maintHtml += '<script>(function(){var r='+previewSec+';var e=document.getElementById("asp-maint-timer");if(!e)return;function p(n){n=n|0;return n<10?"0"+n:""+n;}setInterval(function(){if(r<=0){r='+previewSec+';}r--;var h=Math.floor(r/3600),m=Math.floor((r%3600)/60),s=r%60;e.innerHTML=p(h)+":<span class=\"asp-sep\" style=\"opacity:.6;animation:asp-blink 1s steps(2) infinite;margin:0 2px;\">:</span>"+p(m)+":<span class=\"asp-sep\" style=\"opacity:.6;animation:asp-blink 1s steps(2) infinite;margin:0 2px;\">:</span>"+p(s);},1000);})();<\/script>';
		}
		if(maintHtml){
			// Insert maintenance block at end of .asp-stage (just before the
			// </div> that closes .asp-stage, which is always the </div>
			// immediately preceding the <style> tag in our presets).
			presetHtml = presetHtml.replace(/(<\/div>\s*<style>)/, maintHtml+'$1');
		}

		var doc = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'+cssVars+'@@keyframes asp-blink{50%{opacity:.15;}}</style></head><body>'+presetHtml+'</body></html>';
		doc = doc.replace('@@', '@'); // escape trick
		return doc;
	}

	var t=null;
	function update(){clearTimeout(t);t=setTimeout(function(){var d=build();$f[0].srcdoc=d;$mf[0].srcdoc=d;},60);}

	$form.on('input change',':input',function(){
		var k = ($(this).attr('name')||'').replace(/^.+\[(\w+)\]$/,'$1');
		// Range labels
		if($(this).hasClass('aspl-range')){
			var unit = (k==='blur_amount')?'px':(k==='bg_opacity'?'%':'ms');
			var lblId = k+'Lbl';
			if(k==='bg_opacity') lblId='bgOpLbl';
			var lbl = document.getElementById(lblId);
			if(lbl) lbl.textContent = $(this).val()+unit;
		}
		// Maintenance body toggle
		if(k==='maintenance_mode'){
			$('#maintBody').toggle($(this).is(':checked'));
		}
		update();
	});

	// Preset card visual selection
	$('.aspl-preset-card').on('click',function(e){
		if($(e.target).is('input')) return;
		$(this).find('input').prop('checked',true).trigger('change');
	});
	$form.on('change','[name$="[preset]"]',function(){
		$('.aspl-preset-card').removeClass('selected');
		$('.aspl-preset-card[data-slug="'+$(this).val()+'"]').addClass('selected');
	});

	// Logo picker
	var media=null;
	$('#logoUp').on('click',function(e){
		e.preventDefault();
		if(media){media.open();return;}
		media=wp.media({title:'انتخاب لوگو',button:{text:'استفاده از این تصویر'},library:{type:'image'},multiple:false});
		media.on('select',function(){
			var a=media.state().get('selection').first().toJSON();
			var url=a.url||(a.sizes&&a.sizes.medium&&a.sizes.medium.url)||'';
			$('#logoIn').val(url);$('#logoPrev').html('<img src="'+url+'">');
			update();
		});
		media.open();
	});
	$('#logoRm').on('click',function(e){e.preventDefault();$('#logoIn').val('');$('#logoPrev').html('<span>بدون لوگو</span>');update();});

	// Fullscreen
	$('#asplFs').on('click',function(){$modal.addClass('open');});
	$('#asplRefresh').on('click',function(){update();});
	$('#asplModalX,.aspl-modal-bg').on('click',function(){$modal.removeClass('open');});
	$(document).on('keydown',function(e){if(e.key==='Escape')$modal.removeClass('open');});

	// Trigger initial preview (wait for color pickers to init)
	setTimeout(update, 150);
})(jQuery);
JS;
	}
}
