<?php
/**
 * Frontend: loader injection, scroll lock, load detection, fade-out.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles output of the loader on the frontend.
 *
 * The loader is wrapped in a single root element (#asp-loader-root) so the
 * user code, the helper styles and the helper script are injected at the
 * very top of <body> (wp_body_open, priority 1) and removed together.
 *
 * @package Apple_Star_Loader
 */
class ASPL_Frontend {

	/**
	 * Whether the loader markup has already been output on this request.
	 *
	 * @var bool
	 */
	private $rendered = false;

	/**
	 * Hook registration.
	 */
	public function __construct() {
		// Earliest possible injection point: right after <body> opens.
		add_action( 'wp_body_open', array( $this, 'render' ), 1 );

		// Safety net: if the theme never fires wp_body_open, output the
		// loader at the very end of <body> instead of nowhere.
		add_action( 'wp_footer', array( $this, 'render_fallback' ), 999 );
	}

	/**
	 * Whether the loader should be output on the current request.
	 *
	 * @return bool
	 */
	public function should_render() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return false;
		}

		$options = ASPL_Settings::get_options();

		if ( empty( $options['enabled'] ) ) {
			return false;
		}

		// Empty code = nothing to show (the enable switch still works).
		if ( '' === trim( (string) $options['code'] ) ) {
			return false;
		}

		// Display target: front page only, or every page.
		if ( 'front_page' === $options['target'] && ! is_front_page() ) {
			return false;
		}

		return true;
	}

	/**
	 * Main injection point (wp_body_open, priority 1).
	 *
	 * @return void
	 */
	public function render() {
		if ( ! $this->should_render() ) {
			return;
		}
		$this->rendered   = true;
		$this->output_loader();
	}

	/**
	 * Fallback for themes that do not call wp_body_open().
	 *
	 * @return void
	 */
	public function render_fallback() {
		if ( $this->rendered || ! $this->should_render() ) {
			return;
		}
		$this->rendered   = true;
		$this->output_loader();
	}

	/**
	 * Builds and outputs the loader markup + helper styles + helper script.
	 *
	 * The user code is intentionally output as-is: the "Loader Code" field
	 * is a raw HTML/CSS field by design (like a code area), and it only
	 * ever runs in the visitor's own browser.
	 *
	 * @return void
	 */
	private function output_loader() {
		$options = ASPL_Settings::get_options();
		$code    = trim( (string) $options['code'] );
		$timeout = max( 1, (int) $options['timeout'] );

		ob_start();
		?>
		<div id="asp-loader-root" role="status" aria-live="polite" aria-label="<?php esc_attr_e( 'Page is loading', 'apple-star-loader' ); ?>">
			<!-- Custom loader code — editable in: Apple Star Loader settings. -->
			<?php echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional raw HTML/CSS field (the user's own loader code, runs only in their visitors' browsers). ?>
		</div>
		<style>
			/* Smooth fade-out, then the whole root is removed from the DOM. */
			#asp-loader-root { transition: opacity 0.6s ease; }
			#asp-loader-root.asp-fade-out { opacity: 0 !important; }
			/* Plugin-side scroll lock (works even if the user code has none). */
			html.asp-scroll-lock, html.asp-scroll-lock body { overflow: hidden !important; }
		</style>
		<script>
			( function () {
				'use strict';

				if ( window.__aspLoaderActive ) { return; }
				window.__aspLoaderActive = true;

				var root = document.getElementById( 'asp-loader-root' );
				if ( ! root ) { return; }

				// Fallback timeout from the settings page (seconds -> ms).
				var timeoutMs     = <?php echo (int) $timeout; ?> * 1000;
				var finished      = false;
				var fallbackTimer = null;

				function removeLoader() {
					if ( 'function' === typeof root.remove ) {
						root.remove();
					} else if ( root.parentNode ) {
						root.parentNode.removeChild( root );
					}
				}

				function finish() {
					if ( finished ) { return; }
					finished = true;

					window.removeEventListener( 'load', onWindowLoad );
					if ( fallbackTimer ) { clearTimeout( fallbackTimer ); }

					// Unlock scrolling.
					document.documentElement.classList.remove( 'asp-scroll-lock' );

					// Soft fade-out via opacity ...
					root.classList.add( 'asp-fade-out' );

					// ... then remove the element completely from the DOM.
					var removed = false;
					function doRemove() {
						if ( removed ) { return; }
						removed = true;
						removeLoader();
					}
					root.addEventListener( 'transitionend', function ( event ) {
						if ( 'opacity' === event.propertyName && event.target === root ) {
							doRemove();
						}
					} );
					// Safety net: always remove it, even if transitionend never fires.
					window.setTimeout( doRemove, 1500 );
				}

				function onWindowLoad() {
					finish();
				}

				// 1) Lock scrolling while the loader is active.
				document.documentElement.classList.add( 'asp-scroll-lock' );

				// 2) Wait for the real "load" event: every heavy asset
				//    (Elementor components, web fonts, images, iframes)
				//    has finished downloading.
				window.addEventListener( 'load', onWindowLoad );

				// 3) Fallback: if something gets stuck (broken request,
				//    hanging image) and "load" never fires, close the
				//    loader after the configured timeout so the site
				//    can never stay locked.
				fallbackTimer = window.setTimeout( finish, timeoutMs );

				// Edge case: the page was already fully loaded before
				// this script could run.
				if ( 'complete' === document.readyState ) {
					finish();
				}
			}() );
		</script>
		<noscript><style>#asp-loader-root { display: none !important; } body { overflow: auto !important; }</style></noscript>
		<?php
		echo trim( (string) ob_get_clean() );
	}
}
