<?php
/**
 * Frontend loader injection — v3.0.
 *
 * Supports 11 presets, RTL word-level splitting for Persian/Arabic text,
 * optional logo upload, image-wait until the last asset loads, minimum
 * display time, fallback timeout, AND a maintenance mode with a live
 * HH:MM:SS countdown that locks the loader until the timer reaches 0.
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
	 *
	 * @return bool
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
			// Exception: admin/editor previewing? We still hide for all logged-in per option.
			return false;
		}

		if ( empty( $options['show_on_mobile'] ) && wp_is_mobile() ) {
			return false;
		}

		// Maintenance mode: show everywhere it's enabled regardless of target,
		// because you never want a half-open "under maintenance" site.
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

	/**
	 * Resolve preset HTML code.
	 *
	 * @param array $options
	 * @return string
	 */
	private function resolve_code( $options ) {
		$preset = isset( $options['preset'] ) ? $options['preset'] : 'apple_star';
		$code   = ASPL_Defaults::get_preset_code( $preset );
		if ( '' === $code ) {
			$code = ASPL_Defaults::get_preset_code( 'apple_star' );
		}
		return $code;
	}

	/**
	 * Convert a hex color (+ opacity 0-100) to rgba().
	 *
	 * @param string $hex
	 * @param int    $alpha
	 * @return string
	 */
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

	private function output_loader() {
		$options       = ASPL_Settings::get_options();
		$code          = $this->resolve_code( $options );
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

		// Maintenance mode config.
		$maintenance       = ! empty( $options['maintenance_mode'] );
		$maint_h           = max( 0, (int) ( isset( $options['maintenance_hours'] ) ? $options['maintenance_hours'] : 0 ) );
		$maint_m           = max( 0, min( 59, (int) ( isset( $options['maintenance_minutes'] ) ? $options['maintenance_minutes'] : 30 ) ) );
		$maint_s           = max( 0, min( 59, (int) ( isset( $options['maintenance_seconds'] ) ? $options['maintenance_seconds'] : 0 ) ) );
		$maint_total_sec   = ( $maint_h * 3600 ) + ( $maint_m * 60 ) + $maint_s;
		$maint_msg         = isset( $options['maintenance_msg'] ) ? (string) $options['maintenance_msg'] : 'ما در حال بروز رسانی هستیم. بزودی با ارائه خدمات بهتر در خدمت شما هستیم.';

		$bg_rgba = $this->hex_to_rgba( $bg_color, $bg_opacity );

		ob_start();
		?>
<div id="asp-loader-root" role="status" aria-live="polite" aria-label="<?php esc_attr_e( 'Page is loading', 'apple-star-loader' ); ?>">
	<?php echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<style id="asp-vars">
	#asp-loader-root{position:fixed;inset:0;z-index:<?php echo (int) $z_index; ?>;}
	#asp-loader-root{
		--asp-bg:<?php echo wp_strip_all_tags( $bg_rgba ); ?>;
		--asp-text:<?php echo esc_attr( $text_color ); ?>;
		--asp-accent:<?php echo esc_attr( $accent_color ); ?>;
		--asp-blur:<?php echo (int) $blur; ?>px;
	}
	#asp-loader-root.asp-fade-out{opacity:0!important;pointer-events:none!important;}
	#asp-loader-root{transition:opacity <?php echo (int) $fade_duration; ?>ms ease;}
	html.asp-scroll-lock,html.asp-scroll-lock body{overflow:hidden!important;height:100%!important;}
	<?php echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</style>
<script>
( function () {
	'use strict';
	if ( window.__aspLoaderActive ) { return; }
	window.__aspLoaderActive = true;

	var root        = document.getElementById( 'asp-loader-root' );
	if ( ! root ) { return; }

	var startedAt   = Date.now();
	var minTime     = <?php echo (int) $min_time; ?>;
	var fadeMs      = <?php echo (int) $fade_duration; ?>;
	var timeoutMs   = <?php echo (int) $timeout; ?> * 1000;
	var waitImages  = <?php echo $wait_images ? 'true' : 'false'; ?>;

	var maintenance = <?php echo $maintenance ? 'true' : 'false'; ?>;
	var maintTotal  = <?php echo (int) $maint_total_sec; ?>; // seconds
	var maintMsg    = <?php echo wp_json_encode( $maint_msg ); ?>;

	var finished    = false;
	var fallbackT   = null;
	var pageReady   = false;
	var timerReady  = false;

	var text        = <?php echo wp_json_encode( $text ); ?>;
	var logoUrl     = <?php echo wp_json_encode( $logo ); ?>;

	/* ------------ RTL detection & word/letter filling ------------- */

	function isRTLText( s ) {
		return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF\u0590-\u05FF]/.test( s );
	}

	function fillWord( el, txt ) {
		if ( ! el ) { return; }
		if ( el.dataset.filled ) { return; }
		el.dataset.filled = '1';
		el.innerHTML = '';
		var rtl = isRTLText( txt );
		el.setAttribute( 'dir', rtl ? 'rtl' : 'ltr' );

		if ( rtl ) {
			var words = String( txt || '' ).split( /(\s+)/ );
			var idx = 0;
			for ( var i = 0; i < words.length; i++ ) {
				var chunk = words[ i ];
				if ( /^\s+$/.test( chunk ) ) {
					var sp = document.createElement( 'span' );
					sp.className = 'sp';
					sp.textContent = chunk;
					el.appendChild( sp );
					continue;
				}
				if ( ! chunk ) { continue; }
				var w = document.createElement( 'span' );
				w.className = 'rtl';
				w.textContent = chunk;
				w.style.setProperty( '--w', idx );
				el.appendChild( w );
				idx++;
			}
		} else {
			var chars = String( txt || '' ).split( '' );
			for ( var k = 0; k < chars.length; k++ ) {
				var ch = chars[ k ];
				if ( ch === ' ' ) {
					var s2 = document.createElement( 'span' );
					s2.className = 'sp';
					s2.innerHTML = '&nbsp;';
					s2.style.setProperty( '--w', k );
					el.appendChild( s2 );
				} else {
					var s1 = document.createElement( 'span' );
					s1.className = 'ltr';
					s1.textContent = ch;
					s1.style.setProperty( '--w', k );
					el.appendChild( s1 );
				}
			}
		}
	}

	/* ------------ Logo injection ------------- */

	var stage = root.querySelector( '.asp-stage' );
	if ( logoUrl && stage ) {
		var logoSlot = stage.querySelector( '#asp-logo-slot' );
		var wrap;
		if ( logoSlot ) {
			wrap = logoSlot;
			wrap.className = 'asp-logo-wrap';
		} else {
			wrap = document.createElement( 'div' );
			wrap.className = 'asp-logo-wrap';
			stage.insertBefore( wrap, stage.firstChild );
		}
		var img = document.createElement( 'img' );
		img.src = logoUrl;
		img.alt = '';
		img.draggable = false;
		wrap.appendChild( img );
	}

	/* ------------ Fill #asp-word ------------- */

	var word = root.querySelector( '#asp-word' ) || root.querySelector( '.asp-wave-word' );
	fillWord( word, text );

	/* ------------ Maintenance mode: countdown + message ------------- */

	if ( maintenance && stage ) {
		var maint = document.createElement( 'div' );
		maint.className = 'asp-maint';

		var lbl = document.createElement( 'div' );
		lbl.className = 'asp-maint-lbl';
		lbl.textContent = 'در حال بروز رسانی';
		maint.appendChild( lbl );

		var timer = document.createElement( 'div' );
		timer.className = 'asp-maint-timer';
		timer.id = 'asp-maint-timer';
		maint.appendChild( timer );

		var msg = document.createElement( 'div' );
		msg.className = 'asp-maint-msg';
		msg.textContent = maintMsg;
		maint.appendChild( msg );

		// Append at end of stage (works for all presets since they have .asp-stage).
		stage.appendChild( maint );

		// Also ensure page direction is reflected so Persian text renders correctly.
		maint.setAttribute( 'dir', 'rtl' );

		// Run countdown. If the total seconds is 0, we treat as "immediate"
		// but still wait for page load.
		var remaining = Math.max( 0, maintTotal | 0 );
		var timerEl = document.getElementById( 'asp-maint-timer' );

		function pad( n ) { n = n | 0; return n < 10 ? '0' + n : '' + n; }
		function renderTimer() {
			if ( ! timerEl ) { return; }
			var h = Math.floor( remaining / 3600 );
			var m = Math.floor( ( remaining % 3600 ) / 60 );
			var s = remaining % 60;
			timerEl.innerHTML =
				'<span class="asp-hh">' + pad( h ) + '</span>' +
				'<span class="asp-sep">:</span>' +
				'<span class="asp-mm">' + pad( m ) + '</span>' +
				'<span class="asp-sep">:</span>' +
				'<span class="asp-ss">' + pad( s ) + '</span>';
		}
		renderTimer();

		if ( remaining <= 0 ) {
			timerReady = true;
		} else {
			var iv = setInterval( function () {
				remaining--;
				if ( remaining <= 0 ) {
					remaining = 0;
					renderTimer();
					clearInterval( iv );
					timerReady = true;
					tryFinish();
				} else {
					renderTimer();
				}
			}, 1000 );
			// Hard cap: never let maintenance run more than 12h.
			setTimeout( function () {
				clearInterval( iv );
				timerReady = true;
				remaining = 0;
				renderTimer();
				tryFinish();
			}, 12 * 60 * 60 * 1000 );
		}
	} else {
		timerReady = true;
	}

	// Lock scroll.
	document.documentElement.classList.add( 'asp-scroll-lock' );
	if ( document.body ) { document.body.classList.add( 'asp-scroll-lock' ); }

	/* ------------ Fade out / removal ------------- */

	function removeLoader() {
		if ( root && root.parentNode ) {
			if ( 'function' === typeof root.remove ) { root.remove(); }
			else if ( root.parentNode ) { root.parentNode.removeChild( root ); }
		}
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

		root.classList.add( 'asp-fade-out' );

		var removed = false;
		function doRemove() {
			if ( removed ) { return; }
			removed = true;
			removeLoader();
		}
		root.addEventListener( 'transitionend', function ( ev ) {
			if ( ev.target === root && ev.propertyName === 'opacity' ) { doRemove(); }
		} );
		// Safety net.
		setTimeout( doRemove, fadeMs + 300 );
	}

	function markPageReady() {
		pageReady = true;
		tryFinish();
	}

	function onWindowLoad() {
		if ( ! waitImages ) { markPageReady(); return; }
		// Wait for every image to complete (load or error) so the last image
		// the visitor sees is actually rendered.
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
		// Don't wait for stuck images beyond the timeout.
		var timeLeft = Math.max( 0, timeoutMs - ( Date.now() - startedAt ) );
		setTimeout( markPageReady, timeLeft );
	}

	// Wait for real window load event (fonts, images, heavy assets).
	window.addEventListener( 'load', onWindowLoad );

	// Hard fallback: if load event never fires, release page anyway.
	// (Longer for maintenance mode because countdown is the real release.)
	var hardLimit = maintenance ? Math.max( timeoutMs * 3, 60000 ) : timeoutMs;
	fallbackT = setTimeout( function () {
		pageReady = true;
		// In maintenance we still respect the countdown — but if the countdown
		// timer element is somehow broken, force release after a generous cap.
		if ( maintenance ) { timerReady = true; }
		tryFinish();
	}, hardLimit );

	// Edge case: script injected after load already completed.
	if ( document.readyState === 'complete' ) {
		setTimeout( onWindowLoad, 0 );
	}
}() );
</script>
<noscript><style>#asp-loader-root{display:none!important;}html,body{overflow:auto!important;}</style></noscript>
		<?php
		echo trim( (string) ob_get_clean() );
	}
}
