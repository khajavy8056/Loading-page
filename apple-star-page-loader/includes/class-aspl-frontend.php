<?php
/**
 * Frontend — v3.1.0.
 *
 * Bullet-proof architecture:
 *   • EVERY animation uses SVG SMIL (<animate>, <animateTransform> tags INSIDE
 *     the inline <svg>) — same technique used for the preset thumbnail icons
 *     that the user confirmed are moving in the admin panel. SMIL animations
 *     are immune to CSS/optimizer stripping.
 *   • Text is rendered STATIC (no letter animation, no @keyframes dependency)
 *     — this is what the user explicitly asked for: "متن ثابت باشه".
 *   • CSS is minimal (just layout/positioning/colors, zero @keyframes).
 *   • Server-rendered: entire markup comes out of PHP as a single string with
 *     no DOM-manipulating JS required for the visuals to appear.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class ASPL_Frontend {

	private $rendered = false;
	private $opts = null;

	public function __construct() {
		// Direct output at wp_body_open — priority -1 to be absolutely first.
		add_action( 'wp_body_open', array( $this, 'render' ), -1 );
		add_action( 'wp_footer',   array( $this, 'render_fallback' ), -1 );
	}

	public function should_render() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || wp_doing_cron() ) return false;
		if ( $this->opts === null ) $this->opts = ASPL_Settings::get_options();
		$o = $this->opts;
		if ( empty( $o['enabled'] ) ) return false;
		if ( ! empty( $o['hide_for_logged_in'] ) && is_user_logged_in() ) return false;
		if ( empty( $o['show_on_mobile'] ) && wp_is_mobile() ) return false;

		$maintenance = ! empty( $o['maintenance_mode'] );
		if ( ! $maintenance ) {
			$t = isset( $o['target'] ) ? $o['target'] : 'front_page';
			if ( 'front_page' === $t && ! is_front_page() ) return false;
			if ( 'all_pages'  === $t ) { /* ok */ }
			elseif ( 'home_posts' === $t && ! ( is_front_page() || is_home() ) ) return false;
			elseif ( 'posts_only' === $t && ! is_singular( 'post' ) ) return false;
			elseif ( 'pages_only' === $t && ! is_page() ) return false;
			elseif ( 'woocommerce' === $t ) {
				if ( ! function_exists( 'is_woocommerce' ) ) return false;
				$in = is_woocommerce() || is_cart() || is_checkout() || is_account_page()
					|| ( function_exists('is_shop') && is_shop() )
					|| ( function_exists('is_product') && is_product() );
				if ( ! $in ) return false;
			}
		}
		$code = ASPL_Defaults::get_preset_code( isset($o['preset']) ? $o['preset'] : 'apple_star' );
		return '' !== trim( (string) $code );
	}

	private function opts() {
		if ( $this->opts === null ) $this->opts = ASPL_Settings::get_options();
		return $this->opts;
	}

	private function hex2rgba( $hex, $a=100 ) {
		$h = ltrim( (string)$hex, '#' );
		if ( strlen($h)===3 ) $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
		if ( strlen($h)!==6 ) return 'rgba(0,0,0,'.($a/100).')';
		$r=hexdec(substr($h,0,2)); $g=hexdec(substr($h,2,2)); $b=hexdec(substr($h,4,2));
		return 'rgba('.$r.','.$g.','.$b.','.($a/100).')';
	}

	public function render() {
		if ( ! $this->should_render() ) return;
		$this->rendered = true;
		$this->output();
	}
	public function render_fallback() {
		if ( $this->rendered || ! $this->should_render() ) return;
		$this->rendered = true;
		$this->output();
	}

	private function output() {
		$o = $this->opts();
		$preset = isset($o['preset']) ? $o['preset'] : 'apple_star';
		$html   = ASPL_Defaults::get_preset_code( $preset );
		if ( '' === trim($html) ) $html = ASPL_Defaults::get_preset_code( 'apple_star' );

		$bg_rgba = $this->hex2rgba(
			isset($o['bg_color']) ? $o['bg_color'] : '#000000',
			isset($o['bg_opacity']) ? min(100,max(0,(int)$o['bg_opacity'])) : 85
		);
		$text_color   = isset($o['text_color'])   ? $o['text_color']   : '#ffffff';
		$accent_color = isset($o['accent_color']) ? $o['accent_color'] : '#00c3ff';
		$blur         = max(0, (int)( isset($o['blur_amount']) ? $o['blur_amount'] : 16 ) );
		$z            = max(1000, (int)( isset($o['z_index']) ? $o['z_index'] : 99999999 ) );
		$fade         = max(100, (int)( isset($o['fade_duration']) ? $o['fade_duration'] : 700 ) );
		$min_time     = max(0, (int)( isset($o['min_time']) ? $o['min_time'] : 500 ) );
		$timeout      = max(1, (int)( isset($o['timeout']) ? $o['timeout'] : 20 ) );
		$wait_images  = ! empty( $o['wait_images'] );
		$custom_css   = isset( $o['custom_css'] ) ? (string)$o['custom_css'] : '';

		// Text (static)
		$text = isset($o['text']) ? (string)$o['text'] : 'APPLE STAR';
		if ( ! empty($o['use_site_title']) ) {
			$bn = get_bloginfo('name');
			if ( ! empty($bn) ) $text = $bn;
		}
		$text = esc_html( $text );
		$is_rtl = (bool) preg_match( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{0590}-\x{05FF}]/u', $text );
		$dir_attr = $is_rtl ? 'dir="rtl"' : 'dir="ltr"';

		// Logo
		$logo_url = isset($o['logo']) ? (string)$o['logo'] : '';
		$logo_html = '';
		if ( $logo_url ) {
			$logo_html = '<div class="asp-logo"><img src="'.esc_url($logo_url).'" alt="" draggable="false"></div>';
		}

		// Maintenance
		$maint = ! empty( $o['maintenance_mode'] );
		$mh = max(0,(int)$o['maintenance_hours']);
		$mm = max(0,min(59,(int)$o['maintenance_minutes']));
		$ms = max(0,min(59,(int)$o['maintenance_seconds']));
		$maint_total = ($mh*3600)+($mm*60)+$ms;
		$maint_msg   = esc_html( isset($o['maintenance_msg']) ? $o['maintenance_msg'] : 'ما در حال بروز رسانی هستیم. بزودی با ارائه خدمات بهتر در خدمت شما هستیم.' );

		// Replace simple placeholders in preset HTML
		$maint_html = $maint ? (
				'<div class="asp-maint" dir="rtl">'.
				  '<div class="asp-maint-lbl">در حال بروز رسانی</div>'.
				  '<div class="asp-maint-timer" id="asp-maint-timer">'.
				    '<span class="asp-hh">'.sprintf('%02d',$mh).'</span>'.
				    '<span class="asp-sep">:</span>'.
				    '<span class="asp-mm">'.sprintf('%02d',$mm).'</span>'.
				    '<span class="asp-sep">:</span>'.
				    '<span class="asp-ss">'.sprintf('%02d',$ms).'</span>'.
				  '</div>'.
				  '<div class="asp-maint-msg">'.$maint_msg.'</div>'.
				'</div>'
			) : '';

		// Replace simple placeholders in preset HTML
		$replace = array(
			'{{LOGO}}'      => $logo_html,
			'{{TEXT}}'      => $text,
			'{{DIR}}'       => $dir_attr,
			'{{TEXT_COLOR}}'=> esc_attr($text_color),
			'{{ACCENT}}'    => esc_attr($accent_color),
			'{{MAINT}}'     => $maint_html,
		);
		$html = strtr( $html, $replace );
		// If preset doesn't use {{MAINT}}, append it anyway
		if ( $maint_html && strpos($html, 'asp-maint') === false ) {
			$html .= $maint_html;
		}

		// Inline styles — NO @keyframes (animations live inside SVG as SMIL)
		?>
<style id="asp-loader-styles">
#asp-loader-root{
  position:fixed;inset:0;z-index:<?php echo (int)$z; ?>;
  background:<?php echo $bg_rgba; ?>;
  backdrop-filter:blur(<?php echo (int)$blur; ?>px);
  -webkit-backdrop-filter:blur(<?php echo (int)$blur; ?>px);
  display:flex;align-items:center;justify-content:center;
  margin:0;padding:0;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Vazirmatn",Tahoma,Arial,sans-serif;
}
#asp-loader-root.asp-fade-out{opacity:0!important;pointer-events:none!important;}
#asp-loader-root{transition:opacity <?php echo (int)$fade; ?>ms ease;}
#asp-loader-root *,.asp-loader-inner *{box-sizing:border-box;}
.asp-loader-inner{display:flex;flex-direction:column;align-items:center;gap:28px;padding:20px;text-align:center;max-width:92vw;}
.asp-logo img{max-height:80px;max-width:260px;object-fit:contain;filter:drop-shadow(0 6px 20px rgba(0,0,0,.55));}
.asp-icon-svg{width:clamp(140px,40vw,240px);height:auto;max-height:120px;display:block;overflow:visible;}
.asp-brand-text{font-size:clamp(1.4rem,6.5vw,3.2rem);font-weight:800;letter-spacing:.08em;line-height:1.1;color:<?php echo esc_attr($text_color); ?>;padding:0;margin:0;}
.asp-brand-text[dir="rtl"]{letter-spacing:0;font-weight:700;}
.asp-maint{color:rgba(255,255,255,.9);}
.asp-maint-lbl{font-size:11px;letter-spacing:.2em;text-transform:uppercase;opacity:.55;margin-bottom:8px;}
.asp-maint-timer{font-family:ui-monospace,SF Mono,Menlo,Consolas,monospace;font-size:clamp(1.2rem,3.5vw,2rem);font-weight:700;letter-spacing:.08em;color:#fff;text-shadow:0 0 20px rgba(255,255,255,.4);direction:ltr;unicode-bidi:isolate;display:inline-block;}
.asp-maint-timer .asp-sep{opacity:.5;}
.asp-maint-msg{margin-top:12px;font-size:clamp(.85rem,1.8vw,1rem);line-height:1.8;opacity:.82;}
html.asp-scroll-lock,html.asp-scroll-lock body{overflow:hidden!important;height:100%!important;}
<?php echo $custom_css; // phpcs:ignore ?>
</style>
<noscript><style>#asp-loader-root{display:none!important;}html,body{overflow:auto!important;}</style></noscript>
<div id="asp-loader-root" role="status" aria-live="polite" aria-label="Loading">
  <div class="asp-loader-inner"><?php echo $html; ?></div>
</div>
<script data-cfasync="false" data-rocket-defer="no" data-no-optimize="1">
(function(){
  'use strict';
  if(window.__aspLoaded)return;window.__aspLoaded=true;
  document.documentElement.classList.add('asp-scroll-lock');
  if(document.body)document.body.classList.add('asp-scroll-lock');

  var root=document.getElementById('asp-loader-root');
  if(!root)return;
  var started=Date.now();
  var minTime=<?php echo (int)$min_time; ?>;
  var fadeMs=<?php echo (int)$fade; ?>;
  var tOut=<?php echo (int)$timeout*1000; ?>;
  var waitImg=<?php echo $wait_images?'true':'false'; ?>;
  var maint=<?php echo $maint?'true':'false'; ?>;
  var maintSec=<?php echo (int)$maint_total; ?>;
  var finished=false;
  var pageReady=false;
  var timerReady=false;
  var fallback=null;

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
  }else{timerReady=true;}

  function remove(){
    if(root&&root.parentNode){
      var s=document.getElementById('asp-loader-styles');
      if(s&&s.parentNode)s.parentNode.removeChild(s);
      if('function'===typeof root.remove)root.remove();else root.parentNode.removeChild(root);
    }
  }
  function done(){
    if(finished)return;finished=true;
    window.removeEventListener('load',onLoad);if(fallback)clearTimeout(fallback);
    document.documentElement.classList.remove('asp-scroll-lock');
    if(document.body)document.body.classList.remove('asp-scroll-lock');
    root.offsetHeight;root.classList.add('asp-fade-out');
    var gone=false;
    function go(){if(gone)return;gone=true;remove();}
    root.addEventListener('transitionend',function(e){if(e.target===root&&e.propertyName==='opacity')go();});
    setTimeout(go,fadeMs+500);
  }
  function finish(){
    if(finished)return;if(!pageReady)return;if(maint&&!timerReady)return;
    var el=Date.now()-started,w=Math.max(0,minTime-el);
    setTimeout(done,w);
  }
  function ready(){pageReady=true;finish();}
  function onLoad(){
    if(!waitImg){ready();return;}
    var imgs=Array.prototype.slice.call(document.images||[]);
    var n=imgs.length;if(n===0){ready();return;}
    function one(){n--;if(n<=0)ready();}
    imgs.forEach(function(im){if(im.complete){one();return;}im.addEventListener('load',one);im.addEventListener('error',one);});
    setTimeout(ready,Math.max(0,tOut-(Date.now()-started)));
  }
  window.addEventListener('load',onLoad);
  var hard=maint?Math.max(tOut*3,60000):tOut;
  fallback=setTimeout(function(){pageReady=true;if(maint)timerReady=true;finish();},hard);
  if(document.readyState==='complete')setTimeout(onLoad,0);
})();
</script>
		<?php
	}
}
