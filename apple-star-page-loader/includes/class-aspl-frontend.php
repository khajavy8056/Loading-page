<?php
/**
 * Frontend loader injection — v3.0.2.
 *
 * Outputs a single <style id="asp-loader-styles"> block (containing BOTH the
 * CSS variables AND the active preset's CSS, including @keyframes) placed
 * DIRECTLY BEFORE the loader markup, not nested inside it. This avoids
 * WordPress optimizer plugins (Autoptimize, WP Rocket, LiteSpeed, etc.)
 * stripping inline <style> tags that live inside <body> content.
 *
 * Letters/words are now pre-rendered on the server so the wordmark appears
 * fully-formed in the initial HTML — no JS dependency for the text to show.
 * JS only handles: logo injection (optional), maintenance countdown,
 * wait-for-images, and fade-out removal.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Frontend {

	/** @var bool */
	private $rendered = false;

	public function __construct() {
		add_action( 'wp_body_open', array( $this, 'render' ), 1 );
		add_action( 'wp_footer', array( $this, 'render_fallback' ), 999 );
	}

	/**
	 * Whether the loader should render on this request.
	 */
	public function should_render() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || wp_doing_cron() ) {
			return false;
		}

		$options = ASPL_Settings::get_options();

		if ( empty( $options['enabled'] ) ) {
			return false;
		}

		if ( ! empty( $options['hide_for_logged_in'] ) && is_user_logged_in() ) {
			return false;
		}

		if ( empty( $options['show_on_mobile'] ) && wp_is_mobile() ) {
			return false;
		}

		$maintenance = ! empty( $options['maintenance_mode'] );

		if ( ! $maintenance ) {
			$target = isset( $options['target'] ) ? $options['target'] : 'front_page';
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

		$code = $this->resolve_code( $options );
		if ( '' === trim( (string) $code ) ) {
			return false;
		}

		return true;
	}

	private function resolve_code( $options ) {
		$preset = isset( $options['preset'] ) ? $options['preset'] : 'apple_star';
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

	public function render() {
		if ( ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		$this->output_loader();
	}

	public function render_fallback() {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		$this->output_loader();
	}

	/**
	 * Detect RTL script (Persian/Arabic/Hebrew).
	 */
	private function is_rtl_text( $s ) {
		return (bool) preg_match( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{0590}-\x{05FF}]/u', $s );
	}

	/**
	 * Build the pre-rendered HTML for #asp-word.
	 * Returns array( 'html' => string, 'is_rtl' => bool )
	 */
	private function build_word_html( $text ) {
		$rtl  = $this->is_rtl_text( $text );
		$html = '';
		if ( $rtl ) {
			$words = preg_split( '/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
			$idx   = 0;
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
			// Split LTR text character-by-character. Use mbstring when available
			// for proper Unicode handling; fall back to a preg_split that works
			// on UTF-8 even without mbstring.
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
			'html'   => $html,
			'is_rtl' => $rtl,
		);
	}

	/**
	 * Split a preset HTML file into (struct_html, css_string) and strip out
	 * its nested <style> tag so we can place all CSS in a single id'd <style>
	 * block before the loader markup.
	 */
	private function split_preset( $code ) {
		$css    = '';
		$struct = $code;
		// Pull out EVERY <style>...</style> block.
		if ( preg_match_all( '#<style[^>]*>([\s\S]*?)</style>#i', $code, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$css   .= "\n" . $m[1];
				$struct = str_replace( $m[0], '', $struct );
			}
		}
		// Clean leftover blank lines/whitespace.
		$struct = preg_replace( '/\n\s*\n\s*\n/', "\n\n", trim( $struct ) );
		return array(
			'html' => $struct,
			'css'  => $css,
		);
	}

	private function output_loader() {
		$options       = ASPL_Settings::get_options();
		$raw_code      = $this->resolve_code( $options );
		$preset        = isset( $options['preset'] ) ? $options['preset'] : 'apple_star';

		$timeout       = max( 1, (int) ( isset( $options['timeout'] ) ? $options['timeout'] : 20 ) );
		$min_time      = max( 0, (int) ( isset( $options['min_time'] ) ? $options['min_time'] : 800 ) );
		$fade_duration = max( 100, (int) ( isset( $options['fade_duration'] ) ? $options['fade_duration'] : 700 ) );
		$z_index       = max( 1000, (int) ( isset( $options['z_index'] ) ? $options['z_index'] : 99999999 ) );
		$blur          = max( 0, (int) ( isset( $options['blur_amount'] ) ? $options['blur_amount'] : 16 ) );
		$bg_opacity    = max( 0, min( 100, (int) ( isset( $options['bg_opacity'] ) ? $options['bg_opacity'] : 85 ) ) );

		$bg_color      = isset( $options['bg_color'] ) ? $options['bg_color'] : '#0a0a0f';
		$text_color    = isset( $options['text_color'] ) ? $options['text_color'] : '#ffffff';
		$accent_color  = isset( $options['accent_color'] ) ? $options['accent_color'] : '#00c3ff';
		$wait_images   = ! empty( $options['wait_images'] );

		// Resolve text.
		$text = isset( $options['text'] ) ? (string) $options['text'] : 'APPLE STAR';
		if ( ! empty( $options['use_site_title'] ) ) {
			$blogname = get_bloginfo( 'name' );
			if ( ! empty( $blogname ) ) {
				$text = $blogname;
			}
		}

		$logo       = isset( $options['logo'] ) ? (string) $options['logo'] : '';
		$custom_css = isset( $options['custom_css'] ) ? (string) $options['custom_css'] : '';

		// Maintenance.
		$maintenance       = ! empty( $options['maintenance_mode'] );
		$maint_h           = max( 0, (int) ( isset( $options['maintenance_hours'] ) ? $options['maintenance_hours'] : 0 ) );
		$maint_m           = max( 0, min( 59, (int) ( isset( $options['maintenance_minutes'] ) ? $options['maintenance_minutes'] : 30 ) ) );
		$maint_s           = max( 0, min( 59, (int) ( isset( $options['maintenance_seconds'] ) ? $options['maintenance_seconds'] : 0 ) ) );
		$maint_total_sec   = ( $maint_h * 3600 ) + ( $maint_m * 60 ) + $maint_s;
		$maint_msg         = isset( $options['maintenance_msg'] ) ? (string) $options['maintenance_msg'] : 'ما در حال بروز رسانی هستیم. بزودی با ارائه خدمات بهتر در خدمت شما هستیم.';

		$bg_rgba = $this->hex_to_rgba( $bg_color, $bg_opacity );

		// Pre-build word spans on the server so they exist in raw HTML.
		$word_data    = $this->build_word_html( $text );
		$word_html    = $word_data['html'];
		$word_is_rtl  = $word_data['is_rtl'];
		$word_dir_attr = $word_is_rtl ? 'dir="rtl"' : 'dir="ltr"';

		// Split preset into HTML + CSS; inject pre-rendered spans into #asp-word.
		$split = $this->split_preset( $raw_code );
		$struct = $split['html'];
		$preset_css = $split['css'];

		// Inject server-rendered spans into #asp-word element.
		$struct = preg_replace(
			'/(<[^>]*\bid=["\']asp-word["\'][^>]*>)([\s\S]*?)(<\/[^>]+>)/',
			'$1' . $word_html . '$3',
			$struct,
			1
		);
		// Also make sure the dir attribute is set on #asp-word.
		$struct = preg_replace(
			'/(<[^>]*\bid=["\']asp-word["\']([^>]*?))>/',
			'$1 ' . $word_dir_attr . '>',
			$struct,
			1
		);

		// If the user uploaded a logo, inject the logo image directly into the
		// #asp-logo-slot or prepend it to .asp-stage server-side.
		if ( $logo ) {
			$logo_html = '<img src="' . esc_url( $logo ) . '" alt="" draggable="false">';
			if ( strpos( $struct, 'id="asp-logo-slot"' ) !== false ) {
				$struct = str_replace(
					'<div class="asp-logo-wrap" id="asp-logo-slot"></div>',
					'<div class="asp-logo-wrap" id="asp-logo-slot">' . $logo_html . '</div>',
					$struct
				);
			} else {
				$struct = preg_replace(
					'/(<div class="asp-stage">)/',
					'$1<div class="asp-logo-wrap">' . $logo_html . '</div>',
					$struct,
					1
				);
			}
		} else {
			$struct = str_replace( '<div class="asp-logo-wrap" id="asp-logo-slot"></div>', '', $struct );
		}

		// Note: maintenance block is injected by JS at runtime (safer than
		// trying to surgically insert HTML into varying preset DOM shapes).
		// JS also handles the countdown timer.

		ob_start();
		?>
<style id="asp-loader-styles">
/* Root layout + variables */
#asp-loader-root{position:fixed;inset:0;z-index:<?php echo (int) $z_index; ?>;margin:0;padding:0;}
#asp-loader-root{
	--asp-bg:<?php echo wp_strip_all_tags( $bg_rgba ); ?>;
	--asp-text:<?php echo esc_attr( $text_color ); ?>;
	--asp-accent:<?php echo esc_attr( $accent_color ); ?>;
	--asp-blur:<?php echo (int) $blur; ?>px;
}
#asp-loader-root.asp-fade-out{opacity:0!important;pointer-events:none!important;}
#asp-loader-root{transition:opacity <?php echo (int) $fade_duration; ?>ms ease;}
html.asp-scroll-lock,html.asp-scroll-lock body{overflow:hidden!important;height:100%!important;}
/* Preset CSS (extracted from preset HTML) */
<?php echo $preset_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
/* Custom CSS */
<?php echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</style>
<div id="asp-loader-root" role="status" aria-live="polite" aria-label="<?php esc_attr_e( 'Page is loading', 'apple-star-loader' ); ?>">
	<?php echo $struct; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<noscript><style>#asp-loader-root{display:none!important;}html,body{overflow:auto!important;}</style></noscript>
<script data-cfasync="false" data-rocket-defer="no">
( function () {
	'use strict';
	if ( window.__aspLoaderActive ) { return; }
	window.__aspLoaderActive = true;

	var root = document.getElementById( 'asp-loader-root' );
	if ( ! root ) { return; }

	var startedAt   = Date.now();
	var minTime     = <?php echo (int) $min_time; ?>;
	var fadeMs      = <?php echo (int) $fade_duration; ?>;
	var timeoutMs   = <?php echo (int) $timeout; ?> * 1000;
	var waitImages  = <?php echo $wait_images ? 'true' : 'false'; ?>;

	var maintenance = <?php echo $maintenance ? 'true' : 'false'; ?>;
	var maintTotal  = <?php echo (int) $maint_total_sec; ?>;
	var maintMsg    = <?php echo wp_json_encode( $maint_msg ); ?>;

	var finished    = false;
	var fallbackT   = null;
	var pageReady   = false;
	var timerReady  = false;

	// Scroll lock immediately.
	document.documentElement.classList.add( 'asp-scroll-lock' );
	if ( document.body ) { document.body.classList.add( 'asp-scroll-lock' ); }

	/* ---- Build maintenance block (JS-side, so it works with any preset DOM) ---- */
	if ( maintenance ) {
		var stage = root.querySelector( '.asp-stage' );
		if ( stage ) {
			var total = Math.max( 0, maintTotal | 0 );
			function pad2( n ) { n = n | 0; return n < 10 ? '0' + n : '' + n; }
			var hh = pad2( Math.floor( total / 3600 ) );
			var mm = pad2( Math.floor( ( total % 3600 ) / 60 ) );
			var ss = pad2( total % 60 );
			var wrap = document.createElement( 'div' );
			wrap.className = 'asp-maint';
			wrap.setAttribute( 'dir', 'rtl' );
			wrap.innerHTML =
				'<div class="asp-maint-lbl">در حال بروز رسانی</div>' +
				'<div class="asp-maint-timer" id="asp-maint-timer">' +
					'<span class="asp-hh">' + hh + '</span>' +
					'<span class="asp-sep">:</span>' +
					'<span class="asp-mm">' + mm + '</span>' +
					'<span class="asp-sep">:</span>' +
					'<span class="asp-ss">' + ss + '</span>' +
				'</div>' +
				'<div class="asp-maint-msg"></div>';
			wrap.querySelector( '.asp-maint-msg' ).textContent = maintMsg;
			stage.appendChild( wrap );
		}
	}

	/* ---- Maintenance countdown ---- */
	function setupCountdown() {
		var timerEl = document.getElementById( 'asp-maint-timer' );
		if ( ! timerEl ) { timerReady = true; return; }
		var remaining = Math.max( 0, maintTotal | 0 );
		if ( remaining <= 0 ) { timerReady = true; return; }
		function pad( n ) { n = n | 0; return n < 10 ? '0' + n : '' + n; }
		function render() {
			var h = Math.floor( remaining / 3600 );
			var m = Math.floor( ( remaining % 3600 ) / 60 );
			var s = remaining % 60;
			var hh = timerEl.querySelector( '.asp-hh' );
			var mm = timerEl.querySelector( '.asp-mm' );
			var ss = timerEl.querySelector( '.asp-ss' );
			if ( hh ) hh.textContent = pad( h );
			if ( mm ) mm.textContent = pad( m );
			if ( ss ) ss.textContent = pad( s );
		}
		var iv = setInterval( function () {
			remaining--;
			if ( remaining <= 0 ) {
				remaining = 0; render();
				clearInterval( iv );
				timerReady = true; tryFinish();
			} else {
				render();
			}
		}, 1000 );
		// 12h hard cap.
		setTimeout( function () {
			clearInterval( iv ); timerReady = true; remaining = 0; render(); tryFinish();
		}, 12 * 60 * 60 * 1000 );
	}
	setupCountdown();

	/* ---- Fade / removal ---- */
	function removeLoader() {
		if ( root && root.parentNode ) {
			if ( 'function' === typeof root.remove ) { root.remove(); }
			else if ( root.parentNode ) { root.parentNode.removeChild( root ); }
		}
		// Also remove the style block.
		var st = document.getElementById( 'asp-loader-styles' );
		if ( st && st.parentNode ) { st.parentNode.removeChild( st ); }
	}

	function tryFinish() {
		if ( finished ) { return; }
		if ( ! pageReady ) { return; }
		if ( maintenance && ! timerReady ) { return; }
		var elapsed = Date.now() - startedAt;
		var wait    = Math.max( 0, minTime - elapsed );
		setTimeout( doFinish, wait );
	}

	function doFinish() {
		if ( finished ) { return; }
		finished = true;
		window.removeEventListener( 'load', onWindowLoad );
		if ( fallbackT ) { clearTimeout( fallbackT ); }

		document.documentElement.classList.remove( 'asp-scroll-lock' );
		if ( document.body ) { document.body.classList.remove( 'asp-scroll-lock' ); }

		// Force a reflow before adding the fade class so transition fires.
		/* eslint-disable no-unused-expressions */
		root.offsetHeight;
		root.classList.add( 'asp-fade-out' );

		function doRemove() {
			if ( root.classList.contains( 'asp-removed' ) ) { return; }
			root.classList.add( 'asp-removed' );
			removeLoader();
		}
		root.addEventListener( 'transitionend', function ( ev ) {
			if ( ev.target === root && ( ev.propertyName === 'opacity' || ev.propertyName === 'visibility' ) ) { doRemove(); }
		} );
		// Safety net.
		setTimeout( doRemove, fadeMs + 500 );
	}

	function markPageReady() {
		pageReady = true;
		tryFinish();
	}

	function onWindowLoad() {
		if ( ! waitImages ) { markPageReady(); return; }
		var imgs = Array.prototype.slice.call( document.images || [] );
		var remaining = imgs.length;
		if ( remaining === 0 ) { markPageReady(); return; }
		function oneDone() {
			remaining--;
			if ( remaining <= 0 ) { markPageReady(); }
		}
		imgs.forEach( function ( im ) {
			if ( im.complete ) { oneDone(); return; }
			im.addEventListener( 'load', oneDone );
			im.addEventListener( 'error', oneDone );
		} );
		var timeLeft = Math.max( 0, timeoutMs - ( Date.now() - startedAt ) );
		setTimeout( markPageReady, timeLeft );
	}

	window.addEventListener( 'load', onWindowLoad );

	var hardLimit = maintenance ? Math.max( timeoutMs * 3, 60000 ) : timeoutMs;
	fallbackT = setTimeout( function () {
		pageReady = true;
		if ( maintenance ) { timerReady = true; }
		tryFinish();
	}, hardLimit );

	// If we were injected after load already.
	if ( document.readyState === 'complete' ) {
		setTimeout( onWindowLoad, 0 );
	}
}() );
</script>
		<?php
		echo trim( (string) ob_get_clean() );
	}
}
