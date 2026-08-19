<?php
/**
 * Frontend v2.1 — Simple wave-letter preloader.
 *
 * Core goal: show the wave-letter wordmark until EVERYTHING on the page is
 * fully loaded (DOM, images, fonts, last image) so the visitor never sees a
 * half-rendered / broken layout. Then fade out cleanly and remove itself.
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
			return false;
		}

		if ( empty( $options['show_on_mobile'] ) && wp_is_mobile() ) {
			return false;
		}

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
		if ( 'custom_code' === $preset ) {
			return isset( $options['code'] ) ? (string) $options['code'] : '';
		}
		$code = ASPL_Defaults::get_preset_code( $preset );
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

	private function output_loader() {
		$options       = ASPL_Settings::get_options();
		$code          = $this->resolve_code( $options );
		$timeout       = max( 1, (int) ( isset( $options['timeout'] ) ? $options['timeout'] : 15 ) );
		$min_time      = max( 0, (int) ( isset( $options['min_time'] ) ? $options['min_time'] : 800 ) );
		$fade_duration = max( 100, (int) ( isset( $options['fade_duration'] ) ? $options['fade_duration'] : 700 ) );
		$z_index       = max( 1000, (int) ( isset( $options['z_index'] ) ? $options['z_index'] : 99999999 ) );
		$blur          = max( 0, (int) ( isset( $options['blur_amount'] ) ? $options['blur_amount'] : 0 ) );
		$bg_opacity    = max( 0, min( 100, (int) ( isset( $options['bg_opacity'] ) ? $options['bg_opacity'] : 100 ) ) );

		$bg_color      = isset( $options['bg_color'] ) ? $options['bg_color'] : '#0a0a0f';
		$text_color    = isset( $options['text_color'] ) ? $options['text_color'] : '#ffffff';
		$accent_color  = isset( $options['accent_color'] ) ? $options['accent_color'] : '#0071e3';
		$wait_images   = ! empty( $options['wait_images'] );

		// Resolve text: prefer site title if use_site_title is on.
		$text = isset( $options['text'] ) ? (string) $options['text'] : 'APPLE STAR';
		if ( ! empty( $options['use_site_title'] ) ) {
			$blogname = get_bloginfo( 'name' );
			if ( ! empty( $blogname ) ) {
				$text = $blogname;
			}
		}

		$logo = isset( $options['logo'] ) ? (string) $options['logo'] : '';
		$custom_css = isset( $options['custom_css'] ) ? (string) $options['custom_css'] : '';

		// Compute bg RGBA so opacity slider works.
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

	var root       = document.getElementById( 'asp-loader-root' );
	if ( ! root ) { return; }

	var startedAt  = Date.now();
	var minTime    = <?php echo (int) $min_time; ?>;
	var fadeMs     = <?php echo (int) $fade_duration; ?>;
	var timeoutMs  = <?php echo (int) $timeout; ?> * 1000;
	var waitImages = <?php echo $wait_images ? 'true' : 'false'; ?>;
	var finished   = false;
	var fallbackT  = null;

	var text       = <?php echo wp_json_encode( $text ); ?>;
	var logoUrl    = <?php echo wp_json_encode( $logo ); ?>;

	/** RTL detection (Persian/Arabic/Hebrew) */
	function isRTLText( s ) {
		return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF\u0590-\u05FF]/.test( s );
	}

	/**
	 * Fill the word element. For Latin: one <span> per letter (classic wave).
	 * For RTL/Arabic-script: one <span> per WORD (preserves connected glyphs
	 * and bidi; avoids the "mirrored letters" bug).
	 */
	function fillWord( el, txt ) {
		if ( ! el ) return;
		if ( el.dataset.filled ) return;
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
				if ( ! chunk ) continue;
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

	// Inject logo above the wordmark if provided.
	var stage = root.querySelector( '.asp-stage' );
	if ( logoUrl && stage ) {
		var wrap = document.createElement( 'div' );
		wrap.className = 'asp-logo-wrap';
		var img  = document.createElement( 'img' );
		img.src  = logoUrl;
		img.alt  = '';
		img.draggable = false;
		wrap.appendChild( img );
		stage.insertBefore( wrap, stage.firstChild );
	}

	// Fill the word element with wave spans.
	var word = root.querySelector( '#asp-word' ) || root.querySelector( '.asp-wave-word' );
	fillWord( word, text );

	// Lock scroll.
	document.documentElement.classList.add( 'asp-scroll-lock' );
	if ( document.body ) document.body.classList.add( 'asp-scroll-lock' );

	/* --- Fade-out / removal -------------------------------------------- */
	function removeLoader() {
		if ( root && root.parentNode ) {
			if ( 'function' === typeof root.remove ) root.remove();
			else if ( root.parentNode ) root.parentNode.removeChild( root );
		}
	}

	function finish() {
		if ( finished ) return;
		var elapsed = Date.now() - startedAt;
		var wait    = Math.max( 0, minTime - elapsed );
		setTimeout( doFinish, wait );
	}

	function doFinish() {
		if ( finished ) return;
		finished = true;
		window.removeEventListener( 'load', onWindowLoad );
		if ( fallbackT ) clearTimeout( fallbackT );

		document.documentElement.classList.remove( 'asp-scroll-lock' );
		if ( document.body ) document.body.classList.remove( 'asp-scroll-lock' );

		root.classList.add( 'asp-fade-out' );

		var removed = false;
		function doRemove() {
			if ( removed ) return;
			removed = true;
			removeLoader();
		}
		root.addEventListener( 'transitionend', function ( ev ) {
			if ( ev.target === root && ev.propertyName === 'opacity' ) doRemove();
		} );
		// Safety net.
		setTimeout( doRemove, fadeMs + 300 );
	}

	function onWindowLoad() {
		if ( ! waitImages ) { finish(); return; }
		// Wait for every image on the page to finish loading (or error out),
		// so the "last image" the user waits for is actually there.
		var imgs = Array.prototype.slice.call( document.images || [] );
		var remaining = imgs.length;
		if ( remaining === 0 ) { finish(); return; }
		function oneDone() {
			remaining--;
			if ( remaining <= 0 ) finish();
		}
		imgs.forEach( function ( im ) {
			if ( im.complete ) { oneDone(); return; }
			im.addEventListener( 'load', oneDone );
			im.addEventListener( 'error', oneDone );
		} );
		// But don't wait forever for images beyond the timeout.
		setTimeout( finish, Math.max( 0, timeoutMs - ( Date.now() - startedAt ) ) );
	}

	// Wait for the real window load event (fonts, images, heavy assets).
	window.addEventListener( 'load', onWindowLoad );

	// Hard fallback: if the load event never fires, release the page anyway.
	fallbackT = setTimeout( finish, timeoutMs );

	// Edge case: script injected after load.
	if ( document.readyState === 'complete' ) {
		setTimeout( finish, Math.max( 0, minTime - ( Date.now() - startedAt ) ) );
	}
}() );
</script>
<noscript><style>#asp-loader-root{display:none!important;}html,body{overflow:auto!important;}</style></noscript>
		<?php
		echo trim( (string) ob_get_clean() );
	}
}
