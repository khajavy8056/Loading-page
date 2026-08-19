<?php
/**
 * Frontend loader injection — v3.0.3.
 *
 * Architecture:
 *   • CSS (variables + preset rules + @keyframes + custom CSS) is printed in
 *     <head> via wp_head, so that CSS/optimizer/aggregator plugins see it as a
 *     normal document stylesheet and DO NOT strip or mangle the @keyframes.
 *   • The loader DOM is injected at wp_body_open (priority 0) so it is the
 *     very first child of <body> and paints before any other content.
 *   • The JS (image-wait, maintenance countdown, fade-out, DOM removal) runs
 *     from a small <script> printed right after the loader markup with
 *     data-cfasync="false" and data-rocket-defer="no" so CDN/cache plugins
 *     that defer/async scripts will leave this one alone.
 *
 * RTL:
 *   Latin text (detected via script ranges) is split letter-by-letter for the
 *   classic wave; Arabic-script / Hebrew text is split word-by-word to
 *   preserve Arabic-script contextual joining (prevents "mirrored/disconnected
 *   letters" bug).
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Frontend {

	/** @var bool */
	private $rendered = false;

	/** @var array|null Options snapshot used for the current request. */
	private $opts = null;

	public function __construct() {
		// Priority 0 = print CSS as early as possible in <head>.
		add_action( 'wp_head', array( $this, 'print_head_css' ), 0 );
		// Priority 0 = print loader markup as the very first child of <body>.
		add_action( 'wp_body_open', array( $this, 'render' ), 0 );
		// Fallback for themes that don't fire wp_body_open.
		add_action( 'wp_footer', array( $this, 'render_fallback' ), 0 );
	}

	/**
	 * Whether the loader should render on this request.
	 */
	public function should_render() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || wp_doing_cron() ) {
			return false;
		}
		if ( $this->opts === null ) {
			$this->opts = ASPL_Settings::get_options();
		}
		$opts = $this->opts;
		if ( empty( $opts['enabled'] ) ) {
			return false;
		}
		if ( ! empty( $opts['hide_for_logged_in'] ) && is_user_logged_in() ) {
			return false;
		}
		if ( empty( $opts['show_on_mobile'] ) && wp_is_mobile() ) {
			return false;
		}
		// Maintenance mode forces the loader on every page.
		$maintenance = ! empty( $opts['maintenance_mode'] );
		if ( ! $maintenance ) {
			$target = isset( $opts['target'] ) ? $opts['target'] : 'front_page';
			if ( 'front_page' === $target && ! is_front_page() ) {
				return false;
			}
			if ( 'home_posts' === $target && ! ( is_front_page() || is_home() ) ) {
				return false;
			}
			if ( 'posts_only' === $target && ! is_singular( 'post' ) ) {
				return false;
			}
			if ( 'pages_only' === $target && ! is_page() ) {
				return false;
			}
			if ( 'woocommerce' === $target ) {
				if ( ! function_exists( 'is_woocommerce' ) ) {
					return false;
				}
				if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
					if ( ! ( function_exists( 'is_shop' ) && is_shop() ) && ! ( function_exists( 'is_product' ) && is_product() ) ) {
						return false;
					}
				}
			}
		}
		$code = $this->get_preset_code( $opts );
		if ( '' === trim( (string) $code ) ) {
			return false;
		}
		return true;
	}

	private function get_options() {
		if ( $this->opts === null ) {
			$this->opts = ASPL_Settings::get_options();
		}
		return $this->opts;
	}

	private function get_preset_code( $opts ) {
		$preset = isset( $opts['preset'] ) ? $opts['preset'] : 'apple_star';
		$code   = ASPL_Defaults::get_preset_code( $preset );
		if ( '' === $code ) {
			$code = ASPL_Defaults::get_preset_code( 'apple_star' );
		}
		return $code;
	}

	private function hex_to_rgba( $hex, $alpha = 100 ) {
		$hex = ltrim( (string) $hex, '#' );
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

	/** Detect RTL scripts (Persian/Arabic/Hebrew). */
	private function is_rtl_text( $s ) {
		return (bool) preg_match( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{0590}-\x{05FF}]/u', $s );
	}

	/**
	 * Build the server-rendered inner HTML of #asp-word.
	 *
	 * @return array{ html:string, rtl:bool }
	 */
	private function build_word_html( $text ) {
		$rtl = $this->is_rtl_text( $text );
		$html = '';
		if ( $rtl ) {
			$words = preg_split( '/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
			if ( ! is_array( $words ) ) {
				$words = array( $text );
			}
			$idx = 0;
			foreach ( $words as $chunk ) {
				if ( '' === $chunk ) {
					continue;
				}
				if ( preg_match( '/^\s+$/u', $chunk ) ) {
					$html .= '<span class="sp">' . esc_html( $chunk ) . '</span>';
					continue;
				}
				$html .= '<span class="rtl" style="--w:' . (int) $idx . '">' . esc_html( $chunk ) . '</span>';
				$idx++;
			}
		} else {
			if ( function_exists( 'mb_str_split' ) ) {
				$chunks = mb_str_split( $text );
			} else {
				$chunks = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
				if ( ! is_array( $chunks ) ) {
					$chunks = str_split( $text );
				}
			}
			$idx = 0;
			foreach ( $chunks as $ch ) {
				if ( ' ' === $ch ) {
					$html .= '<span class="sp" style="--w:' . (int) $idx . '">&nbsp;</span>';
				} else {
					$html .= '<span class="ltr" style="--w:' . (int) $idx . '">' . esc_html( $ch ) . '</span>';
				}
				$idx++;
			}
		}
		return array(
			'html' => $html,
			'rtl'  => $rtl,
		);
	}

	/**
	 * Extract <style> blocks out of preset HTML and return (html, css).
	 */
	private function split_preset( $code ) {
		$css    = '';
		$struct = $code;
		if ( preg_match_all( '#<style[^>]*>([\s\S]*?)</style>#i', $code, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$css    .= "\n" . $m[1];
				$struct = str_replace( $m[0], '', $struct );
			}
		}
		$struct = preg_replace( '/\n\s*\n\s*\n/', "\n\n", trim( $struct ) );
		return array(
			'html' => $struct,
			'css'  => $css,
		);
	}

	/**
	 * Print the loader CSS (variables + preset CSS + @keyframes + custom CSS)
	 * inside <head> via wp_head. This is the KEY fix: when <style> lives in
	 * <head>, every caching/optimization plugin treats it as part of the
	 * document stylesheet and @keyframes always register.
	 */
	public function print_head_css() {
		if ( ! $this->should_render() ) {
			return;
		}
		$opts = $this->get_options();

		$timeout       = max( 1, (int) $opts['timeout'] );
		$min_time      = max( 0, (int) $opts['min_time'] );
		$fade_duration = max( 100, (int) $opts['fade_duration'] );
		$z_index       = max( 1000, (int) $opts['z_index'] );
		$blur          = max( 0, (int) $opts['blur_amount'] );
		$bg_opacity    = max( 0, min( 100, (int) $opts['bg_opacity'] ) );
		$bg_color      = isset( $opts['bg_color'] ) ? $opts['bg_color'] : '#0a0a0f';
		$text_color    = isset( $opts['text_color'] ) ? $opts['text_color'] : '#ffffff';
		$accent_color  = isset( $opts['accent_color'] ) ? $opts['accent_color'] : '#00c3ff';
		$custom_css    = isset( $opts['custom_css'] ) ? (string) $opts['custom_css'] : '';

		$bg_rgba = $this->hex_to_rgba( $bg_color, $bg_opacity );

		$raw_code = $this->get_preset_code( $opts );
		$split    = $this->split_preset( $raw_code );
		$preset_css = $split['css'];
		?>
<style id="asp-loader-styles">
/* Apple Star Page Loader v<?php echo esc_html( ASPL_VERSION ); ?> — head stylesheet */
#asp-loader-root{
  position:fixed;inset:0;z-index:<?php echo (int) $z_index; ?>;margin:0;padding:0;
  --asp-bg:<?php echo wp_strip_all_tags( $bg_rgba ); ?>;
  --asp-text:<?php echo esc_attr( $text_color ); ?>;
  --asp-accent:<?php echo esc_attr( $accent_color ); ?>;
  --asp-blur:<?php echo (int) $blur; ?>px;
}
#asp-loader-root.asp-fade-out{opacity:0!important;pointer-events:none!important;}
#asp-loader-root{transition:opacity <?php echo (int) $fade_duration; ?>ms ease;}
html.asp-scroll-lock,html.asp-scroll-lock body{overflow:hidden!important;height:100%!important;}
<?php echo $preset_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</style>
<noscript><style>#asp-loader-root{display:none!important;}html,body{overflow:auto!important;}</style></noscript>
		<?php
	}

	public function render() {
		if ( ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		$this->output_loader_markup();
	}

	public function render_fallback() {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		$this->output_loader_markup();
	}

	private function output_loader_markup() {
		$opts = $this->get_options();

		$timeout       = max( 1, (int) $opts['timeout'] );
		$min_time      = max( 0, (int) $opts['min_time'] );
		$fade_duration = max( 100, (int) $opts['fade_duration'] );
		$wait_images   = ! empty( $opts['wait_images'] );

		$text = isset( $opts['text'] ) ? (string) $opts['text'] : 'APPLE STAR';
		if ( ! empty( $opts['use_site_title'] ) ) {
			$blogname = get_bloginfo( 'name' );
			if ( ! empty( $blogname ) ) {
				$text = $blogname;
			}
		}
		$logo = isset( $opts['logo'] ) ? (string) $opts['logo'] : '';

		$maintenance       = ! empty( $opts['maintenance_mode'] );
		$maint_h           = max( 0, (int) $opts['maintenance_hours'] );
		$maint_m           = max( 0, min( 59, (int) $opts['maintenance_minutes'] ) );
		$maint_s           = max( 0, min( 59, (int) $opts['maintenance_seconds'] ) );
		$maint_total_sec   = ( $maint_h * 3600 ) + ( $maint_m * 60 ) + $maint_s;
		$maint_msg         = isset( $opts['maintenance_msg'] ) ? (string) $opts['maintenance_msg'] : 'ما در حال بروز رسانی هستیم. بزودی با ارائه خدمات بهتر در خدمت شما هستیم.';

		$raw_code = $this->get_preset_code( $opts );
		$split    = $this->split_preset( $raw_code );
		$struct   = $split['html'];

		// Pre-render word spans on the server so the text appears in the
		// initial HTML without waiting for JS.
		$w = $this->build_word_html( $text );
		$struct = preg_replace(
			'/(<[^>]*\bid=["\']asp-word["\'][^>]*>)[\s\S]*?(<\/[^>]+>)/',
			'$1' . $w['html'] . '$2',
			$struct,
			1
		);
		$struct = preg_replace(
			'/(<[^>]*\bid=["\']asp-word["\']([^>]*?))>/',
			'$1 ' . ( $w['rtl'] ? 'dir="rtl"' : 'dir="ltr"' ) . '>',
			$struct,
			1
		);

		// Inject logo if uploaded.
		if ( $logo ) {
			$logo_html = '<div class="asp-logo-wrap"><img src="' . esc_url( $logo ) . '" alt="" draggable="false"></div>';
			if ( strpos( $struct, 'id="asp-logo-slot"' ) !== false ) {
				$struct = str_replace(
					'<div class="asp-logo-wrap" id="asp-logo-slot"></div>',
					$logo_html,
					$struct
				);
			} else {
				$struct = preg_replace(
					'/(<div class="asp-stage">)/',
					'$1' . $logo_html,
					$struct,
					1
				);
			}
		} else {
			$struct = str_replace( '<div class="asp-logo-wrap" id="asp-logo-slot"></div>', '', $struct );
		}
		?>
<div id="asp-loader-root" role="status" aria-live="polite" aria-label="<?php esc_attr_e( 'Page is loading', 'apple-star-loader' ); ?>">
<?php echo $struct; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<script data-cfasync="false" data-rocket-defer="no" data-no-optimize="1" data-no-minify="1">
(function(){
  'use strict';
  if(window.__aspLoaderActive)return;
  window.__aspLoaderActive=true;

  var root=document.getElementById('asp-loader-root');
  if(!root)return;

  var startedAt=Date.now();
  var minTime=<?php echo (int)$min_time; ?>;
  var fadeMs=<?php echo (int)$fade_duration; ?>;
  var timeoutMs=<?php echo (int)$timeout*1000; ?>;
  var waitImages=<?php echo $wait_images?'true':'false'; ?>;
  var maintenance=<?php echo $maintenance?'true':'false'; ?>;
  var maintTotal=<?php echo (int)$maint_total_sec; ?>;
  var maintMsg=<?php echo wp_json_encode($maint_msg); ?>;

  var finished=false;
  var fallbackT=null;
  var pageReady=false;
  var timerReady=false;

  // Scroll lock immediately (also set from html via class for the brief
  // moment before this script runs).
  document.documentElement.classList.add('asp-scroll-lock');
  if(document.body)document.body.classList.add('asp-scroll-lock');

  // ---- Maintenance block + countdown ----
  if(maintenance){
    var stage=root.querySelector('.asp-stage');
    if(stage){
      var remaining=Math.max(0,maintTotal|0);
      var wrap=document.createElement('div');
      wrap.className='asp-maint';
      wrap.setAttribute('dir','rtl');
      function pad(n){n=n|0;return n<10?'0'+n:''+n;}
      function renderTimer(){
        var h=Math.floor(remaining/3600);
        var m=Math.floor((remaining%3600)/60);
        var s=remaining%60;
        var hh=wrap.querySelector('.asp-hh');
        var mm=wrap.querySelector('.asp-mm');
        var ss=wrap.querySelector('.asp-ss');
        if(hh)hh.textContent=pad(h);
        if(mm)mm.textContent=pad(m);
        if(ss)ss.textContent=pad(s);
      }
      wrap.innerHTML=
        '<div class="asp-maint-lbl">در حال بروز رسانی</div>'+
        '<div class="asp-maint-timer" id="asp-maint-timer">'+
          '<span class="asp-hh">00</span><span class="asp-sep">:</span>'+
          '<span class="asp-mm">00</span><span class="asp-sep">:</span>'+
          '<span class="asp-ss">00</span>'+
        '</div>'+
        '<div class="asp-maint-msg"></div>';
      wrap.querySelector('.asp-maint-msg').textContent=maintMsg;
      stage.appendChild(wrap);
      renderTimer();
      if(remaining<=0){
        timerReady=true;
      }else{
        var iv=setInterval(function(){
          remaining--;
          if(remaining<=0){
            remaining=0;renderTimer();
            clearInterval(iv);
            timerReady=true;
            tryFinish();
          }else{
            renderTimer();
          }
        },1000);
        // 12 hour hard cap.
        setTimeout(function(){
          clearInterval(iv);timerReady=true;remaining=0;renderTimer();tryFinish();
        },12*60*60*1000);
      }
    }else{
      timerReady=true;
    }
  }else{
    timerReady=true;
  }

  // ---- Fade / removal ----
  function removeLoader(){
    // Remove root.
    if(root&&root.parentNode){
      if('function'===typeof root.remove)root.remove();
      else if(root.parentNode)root.parentNode.removeChild(root);
    }
    // Remove head style block.
    var st=document.getElementById('asp-loader-styles');
    if(st&&st.parentNode)st.parentNode.removeChild(st);
  }

  function doFinish(){
    if(finished)return;
    finished=true;
    window.removeEventListener('load',onWindowLoad);
    if(fallbackT)clearTimeout(fallbackT);

    document.documentElement.classList.remove('asp-scroll-lock');
    if(document.body)document.body.classList.remove('asp-scroll-lock');

    // Force reflow so the transition actually runs.
    /* eslint-disable no-unused-expressions */
    root.offsetHeight;
    root.classList.add('asp-fade-out');

    var removed=false;
    function done(){
      if(removed)return;
      removed=true;
      removeLoader();
    }
    root.addEventListener('transitionend',function(ev){
      if(ev.target===root&&ev.propertyName==='opacity')done();
    });
    setTimeout(done,fadeMs+500);
  }

  function tryFinish(){
    if(finished)return;
    if(!pageReady)return;
    if(maintenance&&!timerReady)return;
    var elapsed=Date.now()-startedAt;
    var wait=Math.max(0,minTime-elapsed);
    setTimeout(doFinish,wait);
  }

  function markPageReady(){
    pageReady=true;
    tryFinish();
  }

  function onWindowLoad(){
    if(!waitImages){markPageReady();return;}
    var imgs=Array.prototype.slice.call(document.images||[]);
    var remaining=imgs.length;
    if(remaining===0){markPageReady();return;}
    function oneDone(){
      remaining--;
      if(remaining<=0)markPageReady();
    }
    imgs.forEach(function(im){
      if(im.complete){oneDone();return;}
      im.addEventListener('load',oneDone);
      im.addEventListener('error',oneDone);
    });
    var timeLeft=Math.max(0,timeoutMs-(Date.now()-startedAt));
    setTimeout(markPageReady,timeLeft);
  }

  window.addEventListener('load',onWindowLoad);

  var hardLimit=maintenance?Math.max(timeoutMs*3,60000):timeoutMs;
  fallbackT=setTimeout(function(){
    pageReady=true;
    if(maintenance)timerReady=true;
    tryFinish();
  },hardLimit);

  // Edge case: injected after load already completed.
  if(document.readyState==='complete'){
    setTimeout(onWindowLoad,0);
  }
})();
</script>
		<?php
	}
}
