<?php
/**
 * Frontend v2.0.0 — preset loader, live progress %, animated wave letters,
 * progress bar, color injection, logo support, loading tips, min-time, etc.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles output of the loader on the frontend.
 *
 * @package Apple_Star_Loader
 */
class ASPL_Frontend {

	/**
	 * Whether already rendered.
	 *
	 * @var bool
	 */
	private $rendered = false;

	/**
	 * Hook registration.
	 */
	public function __construct() {
		add_action( 'wp_body_open', array( $this, 'render' ), 1 );
		add_action( 'wp_footer', array( $this, 'render_fallback' ), 999 );
	}

	/**
	 * Whether the loader should be output on the current request.
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

		// Hide for logged-in users if desired.
		if ( ! empty( $options['hide_for_logged_in'] ) && is_user_logged_in() ) {
			return false;
		}

		// Hide on mobile if desired.
		if ( empty( $options['show_on_mobile'] ) && wp_is_mobile() ) {
			return false;
		}

		// Resolve preset/custom code.
		$code = $this->resolve_code( $options );
		if ( '' === trim( (string) $code ) ) {
			return false;
		}

		// Display target.
		$target = isset( $options['target'] ) ? $options['target'] : 'all_pages';
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
			if ( ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
				if ( ! ( function_exists( 'is_shop' ) && is_shop() ) && ! ( function_exists( 'is_product' ) && is_product() ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Resolve the HTML code (from preset or custom).
	 *
	 * @param array $options Options array.
	 * @return string
	 */
	private function resolve_code( $options ) {
		$preset = isset( $options['preset'] ) ? $options['preset'] : 'apple_star';
		if ( 'custom_code' === $preset ) {
			return isset( $options['code'] ) ? (string) $options['code'] : '';
		}
		$code = ASPL_Defaults::get_preset_code( $preset );
		if ( '' === $code ) {
			$code = ASPL_Defaults::get_preset_code( 'apple_star' );
		}
		return $code;
	}

	/**
	 * Render at wp_body_open.
	 */
	public function render() {
		if ( ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		$this->output_loader();
	}

	/**
	 * Fallback render at wp_footer.
	 */
	public function render_fallback() {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		$this->output_loader();
	}

	/**
	 * Convert a hex color to rgba.
	 *
	 * @param string $hex Hex color.
	 * @param float  $alpha Alpha 0-100.
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

	/**
	 * Build and output the loader.
	 *
	 * @return void
	 */
	private function output_loader() {
		$options       = ASPL_Settings::get_options();
		$code          = $this->resolve_code( $options );
		$timeout       = max( 1, (int) ( isset( $options['timeout'] ) ? $options['timeout'] : 15 ) );
		$min_time      = max( 0, (int) ( isset( $options['min_time'] ) ? $options['min_time'] : 600 ) );
		$fade_duration = max( 100, (int) ( isset( $options['fade_duration'] ) ? $options['fade_duration'] : 600 ) );
		$z_index       = max( 1000, (int) ( isset( $options['z_index'] ) ? $options['z_index'] : 99999999 ) );
		$blur          = max( 0, (int) ( isset( $options['blur_amount'] ) ? $options['blur_amount'] : 0 ) );
		$bg_opacity    = max( 0, min( 100, (int) ( isset( $options['bg_opacity'] ) ? $options['bg_opacity'] : 100 ) ) );

		$bg_color      = isset( $options['bg_color'] ) ? $options['bg_color'] : '#0b0b0f';
		$primary_color = isset( $options['primary_color'] ) ? $options['primary_color'] : '#ffffff';
		$accent_color  = isset( $options['accent_color'] ) ? $options['accent_color'] : '#0071e3';
		$text_color    = isset( $options['text_color'] ) ? $options['text_color'] : '#ffffff';
		$bar_bg_color  = isset( $options['bar_bg_color'] ) ? $options['bar_bg_color'] : 'rgba(255,255,255,0.15)';
		$bar_fg_color  = isset( $options['bar_fg_color'] ) ? $options['bar_fg_color'] : '#ffffff';

		// Compute bg as RGBA so opacity slider works.
		if ( preg_match( '/^#([0-9a-f]{3,6})$/i', $bg_color ) ) {
			$bg_rgba = $this->hex_to_rgba( $bg_color, $bg_opacity );
		} else {
			$bg_rgba = $bg_color;
		}

		$text = isset( $options['text'] ) ? $options['text'] : 'LOADING';
		$logo = isset( $options['logo'] ) ? (string) $options['logo'] : '';

		$show_percentage   = ! empty( $options['show_percentage'] );
		$show_progress_bar = ! empty( $options['show_progress_bar'] );
		$show_tips         = ! empty( $options['show_tips'] );

		$custom_css = isset( $options['custom_css'] ) ? (string) $options['custom_css'] : '';

		$tips = array(
			__( 'Almost there…', 'apple-star-loader' ),
			__( 'Preparing your experience…', 'apple-star-loader' ),
			__( 'Loading assets…', 'apple-star-loader' ),
			__( 'Just a moment…', 'apple-star-loader' ),
			__( 'Hang tight!', 'apple-star-loader' ),
			__( 'Waking up the servers…', 'apple-star-loader' ),
			__( 'Assembling the pieces…', 'apple-star-loader' ),
			__( 'Polishing pixels…', 'apple-star-loader' ),
		);

		ob_start();
		?>
<div id="asp-loader-root" role="status" aria-live="polite" aria-label="<?php esc_attr_e( 'Page is loading', 'apple-star-loader' ); ?>">
	<?php echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional raw HTML/CSS preset/custom. ?>
</div>
<style id="asp-vars">
	#asp-loader-root{position:fixed;inset:0;z-index:<?php echo (int) $z_index; ?>;}
	#asp-loader-root{
		--asp-bg:<?php echo wp_strip_all_tags( $bg_rgba ); ?>;
		--asp-text:<?php echo esc_attr( $text_color ); ?>;
		--asp-accent:<?php echo esc_attr( $accent_color ); ?>;
		--asp-primary:<?php echo esc_attr( $primary_color ); ?>;
		--asp-bar-bg:<?php echo esc_attr( $bar_bg_color ); ?>;
		--asp-bar-fg:<?php echo esc_attr( $bar_fg_color ); ?>;
		--asp-blur:<?php echo (int) $blur; ?>px;
	}
	#asp-loader-root.asp-fade-out{opacity:0!important;}
	#asp-loader-root{transition:opacity <?php echo (int) $fade_duration; ?>ms ease;}
	html.asp-scroll-lock,html.asp-scroll-lock body{overflow:hidden!important;height:100%!important;}
	<?php echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-supplied custom CSS. ?>
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
	var finished    = false;
	var fallbackTimer = null;
	var progress    = 0;
	var targetProg  = 0;
	var rafId       = null;

	var percentEl   = root.querySelector( '.asp-percent' );
	var percentIn   = root.querySelector( '.asp-bar-percent-inline' );
	var barFill     = root.querySelector( '.asp-bar-fill' );
	var barDot      = root.querySelector( '.asp-bar-dot' );
	var bar         = root.querySelector( '.asp-bar' );

	var showPercent = <?php echo $show_percentage ? 'true' : 'false'; ?>;
	var showBar     = <?php echo $show_progress_bar ? 'true' : 'false'; ?>;
	var showTips    = <?php echo $show_tips ? 'true' : 'false'; ?>;
	var tips        = <?php echo wp_json_encode( $tips ); ?>;
	var loadingText = <?php echo wp_json_encode( $text ); ?>;
	var logoUrl     = <?php echo wp_json_encode( $logo ); ?>;

	// Inject logo (if set and a logo slot exists) before the stage.
	if ( logoUrl ) {
		var stage = root.querySelector( '.asp-stage' );
		if ( stage ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'asp-logo-wrap';
			var img = document.createElement( 'img' );
			img.src = logoUrl;
			img.alt = '';
			img.draggable = false;
			wrap.appendChild( img );
			stage.insertBefore( wrap, stage.firstChild );
		}
	}

	// Turn text into wave-letters in every .asp-wave-text element and
	// any .asp-sub, .asp-sub-text element that is empty.
	function waveify( el, text ) {
		if ( ! el ) { return; }
		// If element already has content from preset, leave it alone only if it has letters.
		if ( el.getAttribute( 'data-text-attr' ) === 'text' || el.childElementCount === 0 && el.textContent.trim() === '' ) {
			el.textContent = '';
			var words = String( text || '' ).split( /(\s+)/ );
			var idx = 0;
			words.forEach( function ( chunk ) {
				if ( /^\s+$/.test( chunk ) ) {
					var sp = document.createElement( 'span' );
					sp.className = 'asp-wave-letter asp-space';
					sp.innerHTML = '&nbsp;';
					sp.style.setProperty( '--w', idx );
					el.appendChild( sp );
					idx++;
					return;
				}
				for ( var i = 0; i < chunk.length; i++ ) {
					var s = document.createElement( 'span' );
					s.className = 'asp-wave-letter';
					s.textContent = chunk.charAt( i );
					s.style.setProperty( '--w', idx );
					el.appendChild( s );
					idx++;
				}
			} );
		}
	}
	// Apply to all .asp-wave-text nodes and known sub text classes.
	root.querySelectorAll( '.asp-wave-text, .asp-sub, .asp-sub-text' ).forEach( function ( el ) {
		waveify( el, loadingText );
	} );
	// Dedicated wave word target (used by wave_letters preset).
	var waveWord = root.querySelector( '.asp-wave-word' );
	if ( waveWord ) { waveify( waveWord, loadingText ); }

	// Hide percentage / bar when disabled.
	if ( ! showPercent ) {
		root.querySelectorAll( '.asp-percent, .asp-percent-sign, .asp-percent-row, .asp-percent-big, .asp-percent-wrap, .asp-ring-core' ).forEach( function( el ){
			// For ring core keep the ring visual but hide text.
			if ( el.classList.contains( 'asp-ring-core' ) ) { el.style.display = 'none'; }
			else { el.style.display = 'none'; }
		});
	}
	if ( ! showBar ) {
		root.querySelectorAll( '.asp-bar, .asp-bar-wrap' ).forEach( function( el ){ el.style.display = 'none'; });
	}

	// Rotating tips.
	var tipEl = root.querySelector( '.asp-tip' );
	var tipIndex = 0;
	var tipTimer = null;
	function showNextTip() {
		if ( ! tipEl || ! showTips ) { return; }
		tipEl.style.opacity = 0;
		setTimeout( function () {
			tipEl.textContent = tips[ tipIndex % tips.length ];
			tipIndex++;
			tipEl.style.transition = 'opacity 0.5s ease';
			tipEl.style.opacity = 1;
		}, 400 );
	}
	if ( tipEl && showTips ) {
		showNextTip();
		tipTimer = setInterval( showNextTip, 2500 );
	}

	// --- Progress estimation -----------------------------------------
	// We observe image elements and performance entries to approximate
	// real loading progress. 0..90 during loading, jumps to 100 on "load".
	function setTarget( p ) {
		targetProg = Math.max( targetProg, Math.min( 100, p ) );
		if ( ! rafId ) { rafId = requestAnimationFrame( tickProgress ); }
	}
	function applyProgress( p ) {
		p = Math.max( 0, Math.min( 100, Math.round( p ) ) );
		if ( percentEl ) {
			var old = parseInt( percentEl.textContent, 10 ) || 0;
			percentEl.textContent = p;
			if ( p !== old ) {
				percentEl.classList.remove( 'asp-digit-bounce' );
				// Force reflow to restart animation.
				void percentEl.offsetWidth;
				percentEl.classList.add( 'asp-digit-bounce' );
			}
		}
		if ( percentIn ) {
			percentIn.textContent = p;
		}
		if ( barFill ) {
			barFill.style.width = p + '%';
		}
		if ( barDot && bar ) {
			barDot.style.left = p + '%';
		}
		progress = p;
	}
	function tickProgress() {
		rafId = null;
		if ( progress < targetProg ) {
			// Ease toward target.
			var diff = targetProg - progress;
			var step = Math.max( 0.6, diff * 0.12 );
			var next = progress + Math.min( diff, step );
			applyProgress( next );
			if ( Math.abs( 100 - progress ) > 0.1 ) {
				rafId = requestAnimationFrame( tickProgress );
			}
		} else if ( progress > targetProg ) {
			applyProgress( targetProg );
		}
	}

	// Kick off a little artificial progress immediately for feedback.
	setTarget( 8 );
	setTimeout( function () { setTarget( 18 ); }, 250 );
	setTimeout( function () { setTarget( 30 ); }, 700 );

	// Count images and listen for their load/error events.
	function hookResources() {
		var imgs = document.querySelectorAll( 'img' );
		var total = imgs.length;
		var loaded = 0;
		if ( total === 0 ) {
			setTarget( 65 );
			return;
		}
		function oneDone() {
			loaded++;
			var pct = 30 + ( loaded / total ) * 55; // up to ~85
			setTarget( pct );
		}
		imgs.forEach( function ( img ) {
			if ( img.complete ) { oneDone(); return; }
			img.addEventListener( 'load', oneDone );
			img.addEventListener( 'error', oneDone );
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', hookResources );
	} else {
		hookResources();
	}

	// PerformanceNavigation / Resource timing — grow progress over time.
	var navStart = performance.timing && performance.timing.navigationStart ? performance.timing.navigationStart : Date.now();
	function timeBasedProgress() {
		var elapsed = Date.now() - navStart;
		// 8s of smooth artificial ramp up to ~92%.
		var t = Math.min( 1, elapsed / 8000 );
		var base = 30 + t * 62;
		setTarget( base );
		if ( ! finished && t < 1 ) {
			setTimeout( timeBasedProgress, 200 );
		}
	}
	setTimeout( timeBasedProgress, 300 );

	// --- Fade out / removal ------------------------------------------
	function removeLoader() {
		if ( root && root.parentNode ) {
			if ( 'function' === typeof root.remove ) { root.remove(); }
			else { root.parentNode.removeChild( root ); }
		}
	}
	function finish() {
		if ( finished ) { return; }
		var elapsed = Date.now() - startedAt;
		var wait = Math.max( 0, minTime - elapsed );
		setTimeout( doFinish, wait );
	}
	function doFinish() {
		if ( finished ) { return; }
		finished = true;
		if ( rafId ) { cancelAnimationFrame( rafId ); rafId = null; }
		if ( tipTimer ) { clearInterval( tipTimer ); tipTimer = null; }
		window.removeEventListener( 'load', onWindowLoad );
		if ( fallbackTimer ) { clearTimeout( fallbackTimer ); }

		// Snap percentage to 100% then fade.
		setTarget( 100 );
		applyProgress( 100 );

		setTimeout( function () {
			document.documentElement.classList.remove( 'asp-scroll-lock' );
			document.body && document.body.classList.remove( 'asp-scroll-lock' );
			root.classList.add( 'asp-fade-out' );

			var removed = false;
			function doRemove() {
				if ( removed ) { return; }
				removed = true;
				removeLoader();
			}
			root.addEventListener( 'transitionend', function ( ev ) {
				if ( ev.target === root && ( 'opacity' === ev.propertyName || 'opacity' === ( ev.propertyName + '' ) ) ) {
					doRemove();
				}
			} );
			setTimeout( doRemove, fadeMs + 200 );
		}, 200 );
	}

	function onWindowLoad() { finish(); }

	// Lock scroll.
	document.documentElement.classList.add( 'asp-scroll-lock' );
	if ( document.body ) { document.body.classList.add( 'asp-scroll-lock' ); }

	window.addEventListener( 'load', onWindowLoad );
	fallbackTimer = setTimeout( finish, timeoutMs );

	// Edge case: page already complete.
	if ( document.readyState === 'complete' ) {
		// Even if loaded, respect min-time so users see the animation briefly.
		setTimeout( finish, Math.max( 0, minTime - ( Date.now() - startedAt ) ) );
	}
}() );
</script>
<noscript><style>#asp-loader-root{display:none!important;}html,body{overflow:auto!important;}</style></noscript>
		<?php
		echo trim( (string) ob_get_clean() );
	}
}
