<?php
/**
 * Admin: clean Apple Star Loader settings page.
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
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".aspl-color").wpColorPicker({change:function(){setTimeout(function(){window.__asplUpdate&&window.__asplUpdate();},50);}});});' );
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
		add_submenu_page(
			'options-general.php',
			__( 'Apple Star Loader', 'apple-star-loader' ),
			__( 'Apple Star Loader', 'apple-star-loader' ),
			self::CAPABILITY,
			'aspl-settings',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting( self::GROUP, self::OPTION, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize' ),
		) );
	}

	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = array();
		$clean['enabled']           = ! empty( $input['enabled'] ) ? 1 : 0;
		$clean['use_site_title']    = ! empty( $input['use_site_title'] ) ? 1 : 0;
		$clean['wait_images']       = ! empty( $input['wait_images'] ) ? 1 : 1;
		$clean['show_on_mobile']    = ! empty( $input['show_on_mobile'] ) ? 1 : 0;
		$clean['hide_for_logged_in']= ! empty( $input['hide_for_logged_in'] ) ? 1 : 0;

		$target = isset( $input['target'] ) ? sanitize_key( $input['target'] ) : 'front_page';
		$clean['target'] = in_array( $target, array( 'front_page','all_pages','home_posts','posts_only','pages_only','woocommerce' ), true ) ? $target : 'front_page';

		$clean['preset'] = 'apple_star';

		$clean['text'] = isset( $input['text'] ) ? sanitize_text_field( $input['text'] ) : 'APPLE STAR';
		$clean['logo'] = isset( $input['logo'] ) ? esc_url_raw( $input['logo'] ) : '';

		foreach ( array( 'bg_color' => '#0a0a0f', 'text_color' => '#ffffff', 'accent_color' => '#0071e3' ) as $k => $def ) {
			$clean[ $k ] = isset( $input[ $k ] ) && sanitize_hex_color( $input[ $k ] ) ? sanitize_hex_color( $input[ $k ] ) : $def;
		}
		$clean['bg_opacity']    = isset( $input['bg_opacity'] ) ? max( 0, min( 100, absint( $input['bg_opacity'] ) ) ) : 100;
		$clean['blur_amount']   = isset( $input['blur_amount'] ) ? max( 0, min( 50, absint( $input['blur_amount'] ) ) ) : 0;
		$clean['timeout']       = isset( $input['timeout'] ) ? max( 1, min( 120, absint( $input['timeout'] ) ) ) : 15;
		$clean['min_time']      = isset( $input['min_time'] ) ? max( 0, min( 10000, absint( $input['min_time'] ) ) ) : 800;
		$clean['fade_duration'] = isset( $input['fade_duration'] ) ? max( 100, min( 5000, absint( $input['fade_duration'] ) ) ) : 700;
		$clean['z_index']       = isset( $input['z_index'] ) ? max( 1000, absint( $input['z_index'] ) ) : 99999999;

		$clean['custom_css'] = isset( $input['custom_css'] ) ? (string) $input['custom_css'] : '';
		$clean['code']       = '';
		return $clean;
	}

	private function hex2rgba( $hex, $a = 100 ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
		if ( strlen( $hex ) !== 6 ) return 'rgba(0,0,0,'.($a/100).')';
		$r=hexdec(substr($hex,0,2)); $g=hexdec(substr($hex,2,2)); $b=hexdec(substr($hex,4,2));
		return 'rgba('.$r.','.$g.','.$b.','.($a/100).')';
	}

	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) wp_die( esc_html__( 'You do not have permission.', 'apple-star-loader' ) );
		$opts = self::get_options();
		$code = ASPL_Defaults::get_preset_code( 'apple_star' );
		?>
<div class="wrap aspl-admin">
	<div class="aspl-head">
		<div class="aspl-head-left">
			<span class="aspl-logo">✦</span>
			<div>
				<h1>Apple Star Loader <small>v<?php echo esc_html( ASPL_VERSION ); ?></small></h1>
				<p><?php esc_html_e( 'موجی زیبا از حروف تا بارگذاری کامل صفحه — بدون نوار پیشرفت، بدون درصد، فقط حروف.', 'apple-star-loader' ); ?></p>
			</div>
		</div>
		<div class="aspl-head-right">
			<span class="aspl-pill <?php echo $opts['enabled']?'on':'off'; ?>">
				<span class="dot"></span><?php echo $opts['enabled']?esc_html__('Active','apple-star-loader'):esc_html__('Disabled','apple-star-loader'); ?>
			</span>
		</div>
	</div>

	<div class="aspl-grid">
		<form method="post" action="options.php" id="aspl-form" class="aspl-card-col">
			<?php settings_fields( self::GROUP ); ?>

			<section class="aspl-card">
				<h2>🔌 <?php esc_html_e('وضعیت و نمایش','apple-star-loader'); ?></h2>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked($opts['enabled']); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e('فعال‌بودن لودینگ','apple-star-loader'); ?></span>
					</label>
				</div>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_on_mobile]" value="1" <?php checked($opts['show_on_mobile']); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e('نمایش روی موبایل','apple-star-loader'); ?></span>
					</label>
				</div>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[hide_for_logged_in]" value="1" <?php checked($opts['hide_for_logged_in']); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e('مخفی برای کاربران واردشده','apple-star-loader'); ?></span>
					</label>
				</div>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[wait_images]" value="1" <?php checked($opts['wait_images']); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e('صبر تا لود آخرین عکس (صفحه کامل باز شود)','apple-star-loader'); ?></span>
					</label>
				</div>

				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e('نمایش در کدام صفحات؟','apple-star-loader'); ?></label>
					<select name="<?php echo esc_attr(self::OPTION); ?>[target]" class="aspl-select">
						<option value="front_page"   <?php selected($opts['target'],'front_page'); ?>><?php esc_html_e('فقط صفحه اصلی','apple-star-loader'); ?></option>
						<option value="all_pages"    <?php selected($opts['target'],'all_pages'); ?>><?php esc_html_e('همه صفحات','apple-star-loader'); ?></option>
						<option value="home_posts"   <?php selected($opts['target'],'home_posts'); ?>><?php esc_html_e('صفحه اصلی و وبلاگ','apple-star-loader'); ?></option>
						<option value="posts_only"   <?php selected($opts['target'],'posts_only'); ?>><?php esc_html_e('فقط نوشته‌ها','apple-star-loader'); ?></option>
						<option value="pages_only"   <?php selected($opts['target'],'pages_only'); ?>><?php esc_html_e('فقط برگه‌ها','apple-star-loader'); ?></option>
						<option value="woocommerce"  <?php selected($opts['target'],'woocommerce'); ?>><?php esc_html_e('فقط فروشگاه (WooCommerce)','apple-star-loader'); ?></option>
					</select>
				</div>
			</section>

			<section class="aspl-card">
				<h2>🔤 <?php esc_html_e('متن و لوگو','apple-star-loader'); ?></h2>
				<div class="aspl-row">
					<label class="aspl-switch">
						<input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[use_site_title]" value="1" <?php checked($opts['use_site_title']); ?>>
						<span class="slider"></span>
						<span class="lbl"><?php esc_html_e('استفاده از عنوان سایت','apple-star-loader'); ?></span>
					</label>
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e('متن لودینگ','apple-star-loader'); ?></label>
					<input type="text" name="<?php echo esc_attr(self::OPTION); ?>[text]" value="<?php echo esc_attr($opts['text']); ?>" class="aspl-text aspl-text-xl" maxlength="40" data-k="text">
					<small class="aspl-hint"><?php esc_html_e('فارسی یا انگلیسی — هر حرف/کلمه یکی‌یکی موج بالا/پایین می‌رود.','apple-star-loader'); ?></small>
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e('لوگو (اختیاری)','apple-star-loader'); ?></label>
					<div class="aspl-logo-row">
						<div class="aspl-logo-prev" id="logoPrev">
							<?php if ( $opts['logo'] ): ?><img src="<?php echo esc_url($opts['logo']); ?>" alt=""><?php else: ?><span><?php esc_html_e('بدون لوگو','apple-star-loader'); ?></span><?php endif; ?>
						</div>
						<input type="hidden" id="logoIn" name="<?php echo esc_attr(self::OPTION); ?>[logo]" value="<?php echo esc_attr($opts['logo']); ?>">
						<div class="aspl-logo-btns">
							<button type="button" class="button" id="logoUp"><?php esc_html_e('انتخاب لوگو','apple-star-loader'); ?></button>
							<button type="button" class="button-link-delete" id="logoRm"><?php esc_html_e('حذف','apple-star-loader'); ?></button>
						</div>
					</div>
				</div>
			</section>

			<section class="aspl-card">
				<h2>🎨 <?php esc_html_e('رنگ‌ها','apple-star-loader'); ?></h2>
				<div class="aspl-row aspl-color-row">
					<label class="aspl-lbl"><?php esc_html_e('رنگ پس‌زمینه','apple-star-loader'); ?></label>
					<input type="text" class="aspl-color" name="<?php echo esc_attr(self::OPTION); ?>[bg_color]" value="<?php echo esc_attr($opts['bg_color']); ?>" data-default-color="#0a0a0f" data-k="bg_color">
					<label class="aspl-lbl small"><?php esc_html_e('شفافیت','apple-star-loader'); ?> <b id="bgOpLbl"><?php echo (int)$opts['bg_opacity']; ?>%</b></label>
					<input type="range" min="0" max="100" value="<?php echo (int)$opts['bg_opacity']; ?>" name="<?php echo esc_attr(self::OPTION); ?>[bg_opacity]" class="aspl-range" data-k="bg_opacity">
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e('رنگ متن','apple-star-loader'); ?></label>
					<input type="text" class="aspl-color" name="<?php echo esc_attr(self::OPTION); ?>[text_color]" value="<?php echo esc_attr($opts['text_color']); ?>" data-default-color="#ffffff" data-k="text_color">
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e('رنگ موج (هایلایت حروف)','apple-star-loader'); ?></label>
					<input type="text" class="aspl-color" name="<?php echo esc_attr(self::OPTION); ?>[accent_color]" value="<?php echo esc_attr($opts['accent_color']); ?>" data-default-color="#0071e3" data-k="accent_color">
				</div>
				<div class="aspl-row aspl-color-row">
					<label class="aspl-lbl small"><?php esc_html_e('میزان تاری (Blur)','apple-star-loader'); ?> <b id="blurLbl"><?php echo (int)$opts['blur_amount']; ?>px</b></label>
					<input type="range" min="0" max="30" value="<?php echo (int)$opts['blur_amount']; ?>" name="<?php echo esc_attr(self::OPTION); ?>[blur_amount]" class="aspl-range" data-k="blur_amount">
				</div>
			</section>

			<section class="aspl-card">
				<h2>⏱️ <?php esc_html_e('زمان‌بندی','apple-star-loader'); ?></h2>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e('حداقل زمان نمایش','apple-star-loader'); ?> — <b id="minLbl"><?php echo (int)$opts['min_time']; ?></b> ms</label>
					<input type="range" min="0" max="3000" step="100" value="<?php echo (int)$opts['min_time']; ?>" name="<?php echo esc_attr(self::OPTION); ?>[min_time]" class="aspl-range" data-k="min_time">
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e('زمان محو شدن','apple-star-loader'); ?> — <b id="fadeLbl"><?php echo (int)$opts['fade_duration']; ?></b> ms</label>
					<input type="range" min="100" max="2000" step="50" value="<?php echo (int)$opts['fade_duration']; ?>" name="<?php echo esc_attr(self::OPTION); ?>[fade_duration]" class="aspl-range" data-k="fade_duration">
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl"><?php esc_html_e('حداکثر انتظار (Timeout)','apple-star-loader'); ?></label>
					<input type="number" min="1" max="120" value="<?php echo (int)$opts['timeout']; ?>" name="<?php echo esc_attr(self::OPTION); ?>[timeout]" class="small-text"> <span>ثانیه</span>
				</div>
				<div class="aspl-row">
					<label class="aspl-lbl">Z-Index</label>
					<input type="number" min="1000" value="<?php echo (int)$opts['z_index']; ?>" name="<?php echo esc_attr(self::OPTION); ?>[z_index]" class="small-text">
				</div>
			</section>

			<section class="aspl-card">
				<h2>🧩 <?php esc_html_e('CSS سفارشی','apple-star-loader'); ?></h2>
				<textarea name="<?php echo esc_attr(self::OPTION); ?>[custom_css]" rows="5" class="aspl-code" spellcheck="false" dir="ltr"><?php echo esc_textarea($opts['custom_css']); ?></textarea>
			</section>

			<div class="aspl-savebar">
				<?php submit_button( __('💾 ذخیره تنظیمات','apple-star-loader'), 'primary', 'submit', false, array('class'=>'button button-primary button-hero') ); ?>
			</div>
		</form>

		<aside class="aspl-side">
			<section class="aspl-card aspl-preview-card">
				<h2>👀 <?php esc_html_e('پیش‌نمایش زنده','apple-star-loader'); ?></h2>
				<div class="aspl-framebox">
					<iframe id="asplFrame" class="aspl-frame" title="preview" sandbox="allow-scripts allow-same-origin" srcdoc=""></iframe>
				</div>
				<button type="button" class="button" id="asplFs" style="margin-top:10px;"><?php esc_html_e('تمام صفحه','apple-star-loader'); ?></button>
			</section>

			<section class="aspl-card">
				<h2>ℹ️ <?php esc_html_e('نحوه کار','apple-star-loader'); ?></h2>
				<ul class="aspl-tips">
					<li><?php esc_html_e('لودینگ در بالای صفحه تزریق می‌شود و تا زمان لود کامل (شامل آخرین عکس) می‌ماند.','apple-star-loader'); ?></li>
					<li><?php esc_html_e('برای جلوگیری از فلیکر «حداقل زمان نمایش» روی ۴۰۰ تا ۸۰۰ میلی‌ثانیه باشد.','apple-star-loader'); ?></li>
					<li><?php esc_html_e('حروف فارسی به صورت کلمه‌به‌کلمه موج می‌خورند تا اتصال حروف به‌هم نریزد.','apple-star-loader'); ?></li>
					<li><?php esc_html_e('Timeout تضمین می‌کند سایت هیچ‌گاه پشت لودینگ قفل نمی‌ماند.','apple-star-loader'); ?></li>
				</ul>
			</section>
		</aside>
	</div>

	<!-- fullscreen modal -->
	<div class="aspl-modal" id="asplModal">
		<div class="aspl-modal-bg"></div>
		<button type="button" class="aspl-modal-x" id="asplModalX">✕</button>
		<iframe id="asplModalFrame" class="aspl-modal-frame" sandbox="allow-scripts allow-same-origin"></iframe>
	</div>
</div>

<style><?php echo $this->adminCss(); ?></style>
<script>window.__asplSiteTitle = <?php echo wp_json_encode( get_bloginfo('name') ); ?>;</script>
<script>
window.__asplPresetCode = <?php echo wp_json_encode($code, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
<?php echo $this->adminJs(); ?>
</script>
		<?php
	}

	private function adminCss() {
		return <<<'CSS'
:root{--p:#0071e3;--bd:#e5e7eb;--bg:#f6f8fb;--tx:#1d2327;--mut:#6b7280;--sh:0 1px 3px rgba(0,0,0,.05),0 8px 24px rgba(17,24,39,.06);}
.aspl-admin{margin:10px 20px 0 0;color:var(--tx);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Vazirmatn",Tahoma,sans-serif;max-width:1300px;direction:rtl;}
.aspl-admin *{box-sizing:border-box;}
.aspl-admin h1,.aspl-admin h2{color:var(--tx);font-family:inherit;}
.aspl-admin input,.aspl-admin select,.aspl-admin button{font-family:inherit;}
.aspl-head{display:flex;align-items:center;justify-content:space-between;padding:20px 26px;background:linear-gradient(135deg,#1d1d1f 0%,#0071e3 100%);color:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,113,227,.25);margin-bottom:18px;gap:14px;flex-wrap:wrap;}
.aspl-head-left{display:flex;align-items:center;gap:14px;}
.aspl-logo{width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:24px;border:1px solid rgba(255,255,255,.2);}
.aspl-head h1{color:#fff;margin:0;font-size:20px;line-height:1.2;}
.aspl-head h1 small{font-size:11px;background:rgba(255,255,255,.2);padding:2px 8px;border-radius:8px;margin-right:8px;font-weight:600;}
.aspl-head p{margin:4px 0 0;opacity:.85;font-size:12px;}
.aspl-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);padding:6px 12px;border-radius:999px;font-size:12px;font-weight:600;}
.aspl-pill.on{background:rgba(16,185,129,.35);}
.aspl-pill.off{background:rgba(239,68,68,.3);}
.aspl-pill .dot{width:8px;height:8px;border-radius:50%;background:#fff;animation:asplp 2s infinite;}
@keyframes asplp{0%,100%{opacity:1;}50%{opacity:.4;}}
.aspl-grid{display:grid;grid-template-columns:1fr 360px;gap:18px;}
@media (max-width:1000px){.aspl-grid{grid-template-columns:1fr;}}
.aspl-card-col{min-width:0;display:flex;flex-direction:column;gap:18px;}
.aspl-side{display:flex;flex-direction:column;gap:18px;align-self:start;position:sticky;top:30px;}
@media (max-width:1000px){.aspl-side{position:static;}}
.aspl-card{background:#fff;border:1px solid var(--bd);border-radius:14px;padding:20px;box-shadow:var(--sh);}
.aspl-card h2{margin:0 0 14px;font-size:15px;font-weight:700;}
.aspl-row{margin-bottom:16px;}
.aspl-row:last-child{margin-bottom:0;}
.aspl-lbl{display:block;font-weight:600;font-size:13px;margin-bottom:6px;}
.aspl-lbl.small{font-weight:500;font-size:12px;color:var(--mut);}
.aspl-hint{display:block;color:var(--mut);font-size:12px;margin-top:6px;}
.aspl-text{width:100%;max-width:480px;padding:10px 12px;border:1px solid var(--bd);border-radius:10px;font-size:14px;background:#fafbfc;transition:.15s;}
.aspl-text:focus{outline:none;border-color:var(--p);box-shadow:0 0 0 3px rgba(0,113,227,.12);background:#fff;}
.aspl-text-xl{font-size:20px;font-weight:700;text-align:center;letter-spacing:.08em;}
.aspl-select{padding:8px 12px;border:1px solid var(--bd);border-radius:8px;background:#fff;min-width:260px;}
.aspl-range{width:100%;accent-color:var(--p);}
.aspl-code{width:100%;font-family:ui-monospace,SF Mono,Menlo,Consolas,monospace;font-size:13px;line-height:1.6;border:1px solid var(--bd);border-radius:10px;background:#fafbfc;padding:12px;direction:ltr;text-align:left;}
.aspl-code:focus{outline:none;border-color:var(--p);box-shadow:0 0 0 3px rgba(0,113,227,.12);}

/* Switch */
.aspl-switch{display:flex;align-items:center;gap:10px;cursor:pointer;}
.aspl-switch input{position:absolute;opacity:0;}
.aspl-switch .slider{position:relative;width:44px;height:24px;background:#d1d5db;border-radius:999px;transition:.2s;flex-shrink:0;}
.aspl-switch .slider::before{content:"";position:absolute;top:2px;inset-inline-start:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 2px 4px rgba(0,0,0,.2);}
html[dir="rtl"] .aspl-switch .slider::before{right:2px;left:auto;}
.aspl-switch input:checked + .slider{background:var(--p);}
.aspl-switch input:checked + .slider::before{transform:translateX(20px);}
html[dir="rtl"] .aspl-switch input:checked + .slider::before{transform:translateX(-20px);}
.aspl-switch .lbl{font-size:14px;font-weight:500;}

/* logo */
.aspl-logo-row{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.aspl-logo-prev{width:180px;height:90px;border:2px dashed var(--bd);border-radius:10px;display:flex;align-items:center;justify-content:center;background:#fafbfc;padding:6px;overflow:hidden;}
.aspl-logo-prev img{max-width:100%;max-height:100%;object-fit:contain;}
.aspl-logo-prev span{color:var(--mut);font-size:12px;}
.aspl-logo-btns{display:flex;flex-direction:column;gap:6px;}

/* preview */
.aspl-framebox{background:#0a0a0f;border-radius:10px;padding:8px;}
.aspl-frame{width:100%;height:260px;border:0;border-radius:8px;background:#0a0a0f;}
.aspl-modal{position:fixed;inset:0;z-index:1000000;display:none;align-items:center;justify-content:center;}
.aspl-modal.open{display:flex;}
.aspl-modal-bg{position:absolute;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(10px);}
.aspl-modal-x{position:absolute;top:18px;inset-inline-end:18px;width:44px;height:44px;border-radius:50%;border:0;background:rgba(255,255,255,.12);color:#fff;font-size:18px;cursor:pointer;z-index:5;}
.aspl-modal-frame{position:relative;width:100%;height:100%;border:0;background:#000;}
.aspl-tips{margin:0;padding:0 0 0 20px;color:var(--mut);font-size:13px;line-height:1.9;}
.aspl-tips li{margin:0;}
.aspl-savebar{display:flex;justify-content:flex-start;}
.aspl-savebar .button{border-radius:12px!important;padding:8px 28px!important;font-size:15px!important;box-shadow:0 6px 20px rgba(0,113,227,.3)!important;}
.wp-picker-holder{position:absolute;z-index:20;}
CSS;
	}

	private function adminJs() {
		return <<<'JS'
(function($){
	if(!window.__asplPresetCode) return;
	var $f = $('#asplFrame'), $mf = $('#asplModalFrame'), $form = $('#aspl-form'), $modal=$('#asplModal');

	function isRTLText(s){return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF\u0590-\u05FF]/.test(s);}
	function hex2rgba(hex,a){hex=(hex||'#000').replace('#','');if(hex.length===3)hex=hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];if(hex.length!==6)return 'rgba(0,0,0,'+a/100+')';var r=parseInt(hex.substring(0,2),16),g=parseInt(hex.substring(2,4),16),b=parseInt(hex.substring(4,6),16);return 'rgba('+r+','+g+','+b+','+a/100+')';}
	function val(name){var sel='[name$="['+name+']"]'; var $el=$form.find(sel); if(!$el.length) return ''; if($el.is(':checkbox'))return $el.is(':checked')?1:0; if($el.is(':radio'))return $form.find(sel+':checked').val()||''; return $el.val()||'';}
	function build(){
		var st = window.__asplSiteTitle || '';
		var useTitle = $form.find('[name$="[use_site_title]"]').is(':checked');
		var text = (useTitle && st) ? st : (val('text')||'APPLE STAR');
		var bg = hex2rgba(val('bg_color'), parseInt(val('bg_opacity'),10)||100);
		var vars = '--asp-bg:'+bg+';--asp-text:'+val('text_color')+';--asp-accent:'+val('accent_color')+';--asp-blur:'+(val('blur_amount')||0)+'px;';
		var cssVars = 'html,body{margin:0;padding:0;height:100%;background:#0a0a0f;overflow:hidden;}#asp-loader-root{position:fixed;inset:0;'+vars+'z-index:1;}';
		var logo=val('logo'), logoHtml='';
		if(logo){logoHtml='<style>.asp-logo-wrap img{max-height:90px;max-width:280px;object-fit:contain;filter:drop-shadow(0 6px 24px rgba(0,0,0,.5));}</style><div class="asp-logo-wrap"><img src="'+logo.replace(/"/g,'&quot;')+'"></div>';}

		// build spans
		var rtl=isRTLText(text), spans='', idx=0;
		if(rtl){
			var words=String(text).split(/(\s+)/);
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
		var dirAttr = rtl?'dir="rtl"':'dir="ltr"';

		var preset = window.__asplPresetCode
			.replace(/id=\"asp-word\"/, 'id="asp-word" '+dirAttr)
			.replace(/<div class="asp-stage\">/, '<div class="asp-stage">'+logoHtml);
		// Inject spans into #asp-word: replace its inner content
		preset = preset.replace(/(<div[^>]*id="asp-word"[^>]*>)[\s\S]*?(<\/div>)/, '$1'+spans+'$2');

		var doc = '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'+cssVars+'</style></head><body>'+preset+'</body></html>';
		return doc;
	}
	var t=null;
	function update(){clearTimeout(t);t=setTimeout(function(){var d=build();$f[0].srcdoc=d;$mf[0].srcdoc=d;},80);}

	$form.on('input change',':input',function(){
		var k = $(this).attr('name');
		k = k ? k.replace(/^.+\[(\w+)\]$/,'$1') : '';
		// range labels
		if($(this).hasClass('aspl-range')){
			var lbl = document.getElementById(k+'Lbl')||document.getElementById(k==='bg_opacity'?'bgOpLbl':k+'Lbl');
			if(lbl) lbl.textContent = $(this).val() + (k==='blur_amount'?'px':(k==='bg_opacity'?'%':'ms'));
		}
		update();
	});

	// logo picker
	var media=null;
	$('#logoUp').on('click',function(e){
		e.preventDefault();
		if(media){media.open();return;}
		media=wp.media({title:'Select logo',button:{text:'Use this logo'},library:{type:'image'},multiple:false});
		media.on('select',function(){
			var a=media.state().get('selection').first().toJSON();
			var url=a.url||(a.sizes&&a.sizes.medium&&a.sizes.medium.url)||'';
			$('#logoIn').val(url);$('#logoPrev').html('<img src="'+url+'">');
			update();
		});
		media.open();
	});
	$('#logoRm').on('click',function(e){e.preventDefault();$('#logoIn').val('');$('#logoPrev').html('<span>بدون لوگو</span>');update();});

	// fullscreen
	$('#asplFs').on('click',function(){$modal.addClass('open');});
	$('#asplModalX,.aspl-modal-bg').on('click',function(){$modal.removeClass('open');});
	$(document).on('keydown',function(e){if(e.key==='Escape')$modal.removeClass('open');});

	// color picker init is handled by wp_add_inline_script — trigger initial preview
	setTimeout(update, 100);
})(jQuery);
JS;
	}
}
