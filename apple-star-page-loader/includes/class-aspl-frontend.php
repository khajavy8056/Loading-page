<?php
/**
 * Frontend — v3.3.1 (STREAMED first-paint architecture).
 *
 * Why this version exists (fixes the "loader appears but does not animate
 * until the whole page has loaded" bug):
 *
 *   v3.1.0/v3.3.0 captured the ENTIRE page in an output buffer
 *   (ob_start on template_redirect) and only released it when PHP had
 *   finished rendering the whole page. On heavy sites (Elementor,
 *   WooCommerce, big plugins) that server-side rendering can take
 *   seconds — so the browser did not receive the loader markup until the
 *   very end, and its SMIL animation could not start earlier.
 *
 *   v3.3.1 streams instead, the way fast sites (Digikala, etc.) do:
 *     1. A tiny "no buffering" hint (X-Accel-Buffering: no) is sent for
 *        nginx/PHP-FPM so chunks reach the browser immediately.
 *     2. Critical loader CSS is printed at the top of <head>
 *        (wp_head, priority -99999) — first bytes of the document.
 *     3. The loader markup + synchronous scroll-lock bootstrap are echoed
 *        right after <body> opens (wp_body_open, priority -99999) and
 *        the output is FLUSHED at that moment. The browser paints the
 *        loader and its SMIL animation starts IMMEDIATELY, while the
 *        server is still generating the rest of the page.
 *     4. The control script (fade-out / image-wait / countdown) stays at
 *        the end of <body> — it never blocks the initial paint.
 *
 * The loader is therefore the FIRST thing the visitor sees, animating
 * from the very first paint, and the page behind is revealed as soon as
 * the DOM is ready (default) or after full window load (optional).
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Frontend {

	private $rendered = false;
	private $opts     = null;

	public function __construct() {
		// Disable nginx/PHP-FPM buffering for this response so the loader
		// can stream to the browser before the page is fully rendered.
		add_action( 'template_redirect', array( $this, 'stream_headers' ), 0 );

		// Critical CSS in <head>, as early as possible.
		add_action( 'wp_head', array( $this, 'print_head_css' ), -99999 );
		// Preload logo image if any.
		add_action( 'wp_head', array( $this, 'print_preloads' ), 1 );
		// PRIMARY injection point: right after <body> opens — then flush so
		// the loader + its SMIL animation reach the browser immediately.
		add_action( 'wp_body_open', array( $this, 'render_loader' ), -99999 );
		// Control script at the end of <body> (does not block initial paint).
		add_action( 'wp_footer', array( $this, 'print_control_script' ), 0 );
		// Safety net for themes that never call wp_body_open(): render the
		// loader just before the control script so it exists in the DOM when
		// the script runs.
		add_action( 'wp_footer', array( $this, 'render_fallback' ), -99999 );
	}

	/* ---------- streaming ---------- */

	/**
	 * Tell nginx (and any respecting proxy) not to buffer this response.
	 * Combined with flush() in render_loader(), this streams the loader to
	 * the browser in the first chunk while the server keeps rendering.
	 *
	 * @return void
	 */
	public function stream_headers() {
		if ( is_admin() || headers_sent() ) {
			return;
		}
		if ( ! $this->should_render() ) {
			return;
		}
		@header( 'X-Accel-Buffering: no' ); // phpcs:ignore WordPress.Security.NonceVerification -- response header, not a form action
	}

	/* ---------- decision ---------- */

	public function should_render() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || wp_doing_cron() ) {
			return false;
		}
		if ( $this->opts === null ) {
			$this->opts = ASPL_Settings::get_options();
		}
		$o = $this->opts;
		if ( empty( $o['enabled'] ) ) {
			return false;
		}
		if ( ! empty( $o['hide_for_logged_in'] ) && is_user_logged_in() ) {
			return false;
		}
		if ( empty( $o['show_on_mobile'] ) && wp_is_mobile() ) {
			return false;
		}
		if ( isset( $o['maintenance_mode'] ) && $o['maintenance_mode'] ) {
			return true;
		}
		$t = isset( $o['target'] ) ? $o['target'] : 'front_page';
		if ( 'front_page' === $t && ! is_front_page() ) {
			return false;
		}
		if ( 'all_pages' === $t ) {
			return true;
		}
		if ( 'home_posts' === $t && ! ( is_front_page() || is_home() ) ) {
			return false;
		}
		if ( 'posts_only' === $t && ! is_singular( 'post' ) ) {
			return false;
		}
		if ( 'pages_only' === $t && ! is_page() ) {
			return false;
		}
		if ( 'woocommerce' === $t ) {
			if ( ! function_exists( 'is_woocommerce' ) ) {
				return false;
			}
			return is_woocommerce() || is_cart() || is_checkout() || is_account_page()
				|| ( function_exists( 'is_shop' ) && is_shop() )
				|| ( function_exists( 'is_product' ) && is_product() );
		}
		$code = ASPL_Defaults::get_preset_code( isset( $o['preset'] ) ? $o['preset'] : 'apple_star' );
		return '' !== trim( (string) $code );
	}

	private function opts() {
		if ( $this->opts === null ) {
			$this->opts = ASPL_Settings::get_options();
		}
		return $this->opts;
	}

	/* ---------- head: critical CSS ---------- */

	public function print_head_css() {
		if ( ! $this->should_render() ) {
			return;
		}
		$o = $this->opts();

		$bg       = $this->hex2rgba(
			isset( $o['bg_color'] )   ? $o['bg_color']   : '#000000',
			isset( $o['bg_opacity'] ) ? min( 100, max( 0, (int) $o['bg_opacity'] ) ) : 85
		);
		$text_col = isset( $o['text_color'] )   ? $o['text_color']   : '#ffffff';
		$blur     = max( 0, (int) ( isset( $o['blur_amount'] ) ? $o['blur_amount'] : 16 ) );
		$z        = max( 1000, (int) ( isset( $o['z_index'] ) ? $o['z_index'] : 99999999 ) );
		$fade     = max( 100, (int) ( isset( $o['fade_duration'] ) ? $o['fade_duration'] : 350 ) );
		$custom   = isset( $o['custom_css'] ) ? (string) $o['custom_css'] : '';

		// Solid immediate background for <html> to prevent white flash.
		$solid_bg = isset( $o['bg_color'] ) ? $o['bg_color'] : '#000000';
		?>
<!-- Apple Star Page Loader v<?php echo esc_html( ASPL_VERSION ); ?> (early-paint critical CSS) -->
<style id="asp-loader-styles">
/* Layer 0: immediate <html> background so the FIRST pixel the browser paints is our color, never white. */
html{background:<?php echo $solid_bg; ?>!important;}
html.asp-scroll-lock,html.asp-scroll-lock body{overflow:hidden!important;height:auto!important;}
body{background:transparent!important;}
/* Layer 1: loader covers everything. */
#asp-loader-root{position:fixed;inset:0;z-index:<?php echo (int) $z; ?>;
  background:<?php echo $bg; ?>;
  backdrop-filter:blur(<?php echo (int) $blur; ?>px);-webkit-backdrop-filter:blur(<?php echo (int) $blur; ?>px);
  display:flex;align-items:center;justify-content:center;
  margin:0!important;padding:0!important;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Vazirmatn",Tahoma,Arial,sans-serif!important;
  contain:paint;}
#asp-loader-root,#asp-loader-root *{box-sizing:border-box;}
#asp-loader-root.asp-fade-out{opacity:0!important;pointer-events:none!important;}
#asp-loader-root{transition:opacity <?php echo (int) $fade; ?>ms ease;}
.asp-loader-inner{display:flex;flex-direction:column;align-items:center;gap:28px;padding:20px;text-align:center;max-width:92vw;}
.asp-logo{display:block;}
.asp-logo img{max-height:80px;max-width:260px;object-fit:contain;filter:drop-shadow(0 6px 20px rgba(0,0,0,.55));}
.asp-icon-svg{width:clamp(160px,42vw,280px);height:auto;max-height:150px;display:block;overflow:visible;}
.asp-brand-text{font-size:clamp(1.4rem,6.5vw,3.2rem);font-weight:800;letter-spacing:.08em;line-height:1.1;color:<?php echo esc_attr( $text_col ); ?>;padding:0;margin:0;-webkit-font-smoothing:antialiased;}
.asp-brand-text[dir="rtl"]{letter-spacing:0;font-weight:700;font-family:"Vazirmatn",Tahoma,Arial,sans-serif;}
.asp-maint{color:rgba(255,255,255,.9);margin-top:8px;}
.asp-maint-lbl{font-size:11px;letter-spacing:.2em;text-transform:uppercase;opacity:.55;margin-bottom:8px;}
.asp-maint-timer{font-family:ui-monospace,SF Mono,Menlo,Consolas,monospace;font-size:clamp(1.2rem,3.5vw,2rem);font-weight:700;letter-spacing:.08em;color:#fff;text-shadow:0 0 20px rgba(255,255,255,.4);direction:ltr;unicode-bidi:isolate;display:inline-block;}
.asp-maint-timer .asp-sep{opacity:.5;}
.asp-maint-msg{margin-top:12px;font-size:clamp(.85rem,1.8vw,1rem);line-height:1.8;opacity:.82;max-width:560px;}
<?php echo $custom; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intentional raw CSS field ?>
</style>
<meta name="theme-color" content="<?php echo esc_attr( $solid_bg ); ?>">
<noscript><style>#asp-loader-root{display:none!important;}html,body{overflow:auto!important;background:<?php echo esc_attr( $solid_bg ); ?>;}</style></noscript>
		<?php
	}

	public function print_preloads() {
		if ( ! $this->should_render() ) {
			return;
		}
		$o = $this->opts();
		$logo_url = isset( $o['logo'] ) ? (string) $o['logo'] : '';
		if ( $logo_url ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $logo_url ) . '" fetchpriority="high">' . "\n";
		}
	}

	/* ---------- body: loader markup (PRIMARY injection, streamed) ---------- */

	/**
	 * Echoes the loader right after <body> opens and FLUSHES the output so
	 * the browser paints the loader and starts the SMIL animation while the
	 * server is still rendering the rest of the page.
	 *
	 * @return void
	 */
	public function render_loader() {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;

		echo $this->build_body_html();

		// Push the loader to the browser NOW. On nginx this works with the
		// X-Accel-Buffering: no header set in stream_headers(); on Apache it
		// works natively. Result: the loader + animation start at the very
		// first paint, exactly like fast sites (Digikala, etc.).
		if ( function_exists( 'flush' ) ) {
			flush();
		}
	}

	/**
	 * Safety net: if the theme never calls wp_body_open(), render the loader
	 * right before </body> (at the very end of the page).
	 *
	 * @return void
	 */
	public function render_fallback() {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		echo $this->build_body_html();
	}

	/* ---------- body: loader markup builder ---------- */

	private function build_body_html() {
		$o = $this->opts();
		$preset = isset( $o['preset'] ) ? $o['preset'] : 'apple_star';
		$html   = ASPL_Defaults::get_preset_code( $preset );
		if ( '' === trim( $html ) ) {
			$html = ASPL_Defaults::get_preset_code( 'apple_star' );
		}

		// Text (static).
		$text = isset( $o['text'] ) ? (string) $o['text'] : 'APPLE STAR';
		if ( ! empty( $o['use_site_title'] ) ) {
			$bn = get_bloginfo( 'name' );
			if ( ! empty( $bn ) ) {
				$text = $bn;
			}
		}
		$text     = esc_html( $text );
		$is_rtl   = (bool) preg_match( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{0590}-\x{05FF}]/u', $text );
		$dir_attr = $is_rtl ? 'dir="rtl"' : 'dir="ltr"';

		// Logo.
		$logo_url  = isset( $o['logo'] ) ? (string) $o['logo'] : '';
		$logo_html = '';
		if ( $logo_url ) {
			$logo_html = '<div class="asp-logo"><img src="' . esc_url( $logo_url ) . '" alt="" draggable="false" fetchpriority="high" decoding="async"></div>';
		}

		// Maintenance block.
		$maint = ! empty( $o['maintenance_mode'] );
		$maint_html = '';
		$maint_total = 0;
		if ( $maint ) {
			$mh = max( 0, (int) $o['maintenance_hours'] );
			$mm = max( 0, min( 59, (int) $o['maintenance_minutes'] ) );
			$ms = max( 0, min( 59, (int) $o['maintenance_seconds'] ) );
			$maint_total = ( $mh * 3600 ) + ( $mm * 60 ) + $ms;
			$msg = esc_html( isset( $o['maintenance_msg'] ) ? $o['maintenance_msg'] : 'ما در حال بروز رسانی هستیم. بزودی با ارائه خدمات بهتر در خدمت شما هستیم.' );
			$maint_html =
				'<div class="asp-maint" dir="rtl">' .
				'  <div class="asp-maint-lbl">در حال بروز رسانی</div>' .
				'  <div class="asp-maint-timer" id="asp-maint-timer">' .
				'    <span class="asp-hh">' . sprintf( '%02d', $mh ) . '</span><span class="asp-sep">:</span>' .
				'    <span class="asp-mm">' . sprintf( '%02d', $mm ) . '</span><span class="asp-sep">:</span>' .
				'    <span class="asp-ss">' . sprintf( '%02d', $ms ) . '</span>' .
				'  </div>' .
				'  <div class="asp-maint-msg">' . $msg . '</div>' .
				'</div>';
		}

		$replace = array(
			'{{LOGO}}'       => $logo_html,
			'{{TEXT}}'       => $text,
			'{{DIR}}'        => $dir_attr,
			'{{TEXT_COLOR}}' => esc_attr( isset( $o['text_color'] )   ? $o['text_color']   : '#ffffff' ),
			'{{ACCENT}}'     => esc_attr( isset( $o['accent_color'] ) ? $o['accent_color'] : '#00c3ff' ),
			'{{MAINT}}'      => $maint_html,
		);
		$html = strtr( $html, $replace );
		// Safety: if preset does not contain {{MAINT}}, append.
		if ( $maint_html && strpos( $html, 'asp-maint' ) === false ) {
			$html .= $maint_html;
		}
		// Strip any leftover <style> blocks from third-party presets.
		$html = preg_replace( '/<style[^>]*>[\s\S]*?<\/style>/i', '', $html );

		$inner = '<div id="asp-loader-root" role="status" aria-live="polite" aria-label="Loading">'
		       .   '<div class="asp-loader-inner">' . $html . '</div>'
		       . '</div>';

		// Immediately apply scroll-lock so the page can't scroll behind the
		// loader. This runs SYNCHRONOUSLY the moment the loader tag is
		// parsed, before any other body content paints — no flicker.
		$bootstrap = '<script data-cfasync="false" data-rocket-defer="no" data-no-optimize="1">'
		           . '(function(){'
		           .   'var d=document.documentElement;'
		           .   'd.classList.add("asp-scroll-lock");'
		           .   'window.__aspMaint=' . (int) $maint . ';'
		           .   'window.__aspMaintSec=' . (int) $maint_total . ';'
		           . '})();'
		           . '</script>';

		return $inner . $bootstrap;
	}

	/* ---------- footer: control JS (runs after paint) ---------- */

	public function print_control_script() {
		if ( ! $this->rendered && ! $this->should_render() ) {
			return;
		}
		$o = $this->opts();
		$min_time    = max( 0, (int) ( isset( $o['min_time'] )       ? $o['min_time']       : 350 ) );
		$fade        = max( 100, (int) ( isset( $o['fade_duration'] ) ? $o['fade_duration'] : 350 ) );
		$timeout     = max( 1, (int) ( isset( $o['timeout'] )        ? $o['timeout']        : 15 ) );
		$hide_when   = isset( $o['hide_when'] ) && 'window_load' === $o['hide_when'] ? 'window_load' : 'dom_ready';
		$wait_images = ! empty( $o['wait_images'] );
		$maint       = ! empty( $o['maintenance_mode'] );
		$maint_sec   = 0;
		if ( $maint ) {
			$mh = max( 0, (int) $o['maintenance_hours'] );
			$mm = max( 0, min( 59, (int) $o['maintenance_minutes'] ) );
			$ms = max( 0, min( 59, (int) $o['maintenance_seconds'] ) );
			$maint_sec = ( $mh * 3600 ) + ( $mm * 60 ) + $ms;
		}
		?>
<script data-cfasync="false" data-rocket-defer="no" data-no-optimize="1">
(function(){
  'use strict';
  if(window.__aspLoaded)return;window.__aspLoaded=true;
  var root=document.getElementById('asp-loader-root');
  if(!root)return;
  var started=Date.now();
  var minTime=<?php echo (int) $min_time; ?>;
  var fadeMs=<?php echo (int) $fade; ?>;
  var tOut=<?php echo (int) $timeout * 1000; ?>;
  var hideWhen='<?php echo esc_js( $hide_when ); ?>';
  var waitImg=<?php echo $wait_images ? 'true' : 'false'; ?>;
  var maint=<?php echo $maint ? 'true' : 'false'; ?>;
  var maintSec=window.__aspMaintSec|0;
  var finished=false,pageReady=false,timerReady=false,fallback=null;

  if(!maint){timerReady=true;}

  /* Maintenance countdown */
  if(maint){
    var t=document.getElementById('asp-maint-timer');
    if(!t){timerReady=true;}
    else{
      var r=Math.max(0,maintSec|0);
      function p(n){n=n|0;return n<10?'0'+n:''+n;}
      function draw(){
        var hh=Math.floor(r/3600),mm=Math.floor((r%3600)/60),ss=r%60;
        var e1=t.querySelector('.asp-hh'),e2=t.querySelector('.asp-mm'),e3=t.querySelector('.asp-ss');
        if(e1)e1.textContent=p(hh);if(e2)e2.textContent=p(mm);if(e3)e3.textContent=p(ss);
      }
      draw();
      if(r<=0){timerReady=true;}
      else{
        var iv=setInterval(function(){
          r--;
          if(r<=0){r=0;draw();clearInterval(iv);timerReady=true;finish();}
          else draw();
        },1000);
        setTimeout(function(){clearInterval(iv);timerReady=true;r=0;draw();finish();},12*3600*1000);
      }
    }
  }

  function remove(){
    if(root&&root.parentNode){
      var s=document.getElementById('asp-loader-styles');
      if(s&&s.parentNode)s.parentNode.removeChild(s);
      document.documentElement.classList.remove('asp-scroll-lock');
      if(document.body)document.body.classList.remove('asp-scroll-lock');
      if('function'===typeof root.remove)root.remove();else root.parentNode.removeChild(root);
    }
  }
  function done(){
    if(finished)return;finished=true;
    window.removeEventListener('load',onLoad);
    document.removeEventListener('DOMContentLoaded',onDomReady);
    if(fallback)clearTimeout(fallback);
    root.offsetHeight;root.classList.add('asp-fade-out');
    var gone=false;
    function go(){if(gone)return;gone=true;remove();}
    root.addEventListener('transitionend',function(e){if(e.target===root&&e.propertyName==='opacity')go();});
    /* Reduced motion: hide instantly, never fade. */
    if(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches){
      go();return;
    }
    setTimeout(go,fadeMs+500);
  }
  function finish(){
    if(finished)return;if(!pageReady)return;if(maint&&!timerReady)return;
    var el=Date.now()-started,w=Math.max(0,minTime-el);
    setTimeout(done,w);
  }
  function ready(){pageReady=true;finish();}

  function onDomReady(){
    /* FAST-REVEAL: the page behind appears as soon as the DOM is
       interactive — no waiting for images/fonts/iframes. */
    ready();
  }
  function onLoad(){
    if(!waitImg){ready();return;}
    var imgs=Array.prototype.slice.call(document.images||[]);
    var n=imgs.length;if(n===0){ready();return;}
    var left=n;
    function one(){left--;if(left<=0)ready();}
    imgs.forEach(function(im){if(im.complete){one();return;}im.addEventListener('load',one);im.addEventListener('error',one);});
    setTimeout(ready,Math.max(0,tOut-(Date.now()-started)));
  }

  if('window_load'===hideWhen){
    window.addEventListener('load',onLoad);
    if(document.readyState==='complete')setTimeout(onLoad,0);
  }else{
    if(document.readyState!=='loading'){ready();}
    else{document.addEventListener('DOMContentLoaded',onDomReady);}
  }

  var hard=maint?Math.max(tOut*3,60000):tOut;
  fallback=setTimeout(function(){pageReady=true;if(maint)timerReady=true;finish();},hard);
})();
</script>
		<?php
	}

	/* ---------- helpers ---------- */

	private function hex2rgba( $hex, $a = 100 ) {
		$h = ltrim( (string) $hex, '#' );
		if ( strlen( $h ) === 3 ) {
			$h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
		}
		if ( strlen( $h ) !== 6 ) {
			return 'rgba(0,0,0,' . ( $a / 100 ) . ')';
		}
		$r = hexdec( substr( $h, 0, 2 ) );
		$g = hexdec( substr( $h, 2, 2 ) );
		$b = hexdec( substr( $h, 4, 2 ) );
		return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . ( $a / 100 ) . ')';
	}
}
