<?php
/**
 * Frontend: loader injection, maintenance mode, scroll lock, load detection, fade-out.
 *
 * @package Apple_Star_Loader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASPL_Frontend {

	private $rendered = false;

	public function __construct() {
		add_action( 'wp_head', array( $this, 'print_critical' ), 0 );
		add_action( 'wp_body_open', array( $this, 'render' ), 1 );
		add_action( 'wp_body_open', array( $this, 'render_admin_banner' ), 5 );
		add_action( 'wp_footer', array( $this, 'render_fallback' ), 999 );
	}

	/**
	 * Critical CSS + instant scroll-lock in <head> so loader is visible
	 * from the very first paint, before heavy Elementor/Woo assets load.
	 */
	public function print_critical() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return;
		}
		$show_maint = $this->should_show_maintenance();
		$show_load  = $this->should_render();
		if ( ! $show_maint && ! $show_load ) {
			return;
		}
		// Instant scroll-lock + placeholder background to avoid FOUC.
		echo "<style id=\"aspl-critical\">html.asp-scroll-lock,html.asp-scroll-lock body{overflow:hidden!important}#asp-loader-root,#aspm-root{position:fixed;inset:0;z-index:99999999}</style>\n";
		echo "<script>(function(){try{document.documentElement.classList.add('asp-scroll-lock')}catch(e){}})();</script>\n";
	}

	public function maintenance_is_live() {
		$options = ASPL_Settings::get_options();
		if ( empty( $options['maintenance_enabled'] ) ) {
			return false;
		}
		$end = isset( $options['countdown_end'] ) ? (int) $options['countdown_end'] : 0;
		if ( 0 === $end ) {
			return true;
		}
		return $end > time();
	}

	public function should_show_maintenance() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return false;
		}
		if ( ! $this->maintenance_is_live() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	public function should_render() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return false;
		}
		$options = ASPL_Settings::get_options();
		if ( empty( $options['enabled'] ) ) {
			return false;
		}
		if ( '' === trim( (string) $options['code'] ) ) {
			return false;
		}
		if ( 'front_page' === $options['target'] && ! is_front_page() ) {
			return false;
		}
		return true;
	}

	public function render() {
		if ( $this->rendered ) {
			return;
		}
		if ( $this->should_show_maintenance() ) {
			$this->rendered = true;
			$this->output_maintenance();
			return;
		}
		if ( ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		$this->output_loader();
	}

	public function render_fallback() {
		if ( $this->rendered ) {
			return;
		}
		if ( $this->should_show_maintenance() ) {
			$this->rendered = true;
			$this->output_maintenance();
			return;
		}
		if ( ! $this->should_render() ) {
			return;
		}
		$this->rendered = true;
		$this->output_loader();
	}

	/**
	 * Admin banner when maintenance is live (visible only to admins on frontend).
	 */
	public function render_admin_banner() {
		if ( is_admin() ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! $this->maintenance_is_live() ) {
			return;
		}
		$options = ASPL_Settings::get_options();
		$end     = isset( $options['countdown_end'] ) ? (int) $options['countdown_end'] : 0;
		$date    = $end ? wp_date( 'Y-m-d H:i', $end ) : __( 'no expiry', 'apple-star-loader' );
		$left    = $end ? human_time_diff( time(), $end ) : '';
		$url     = admin_url( 'admin.php?page=apple-star-loader' );
		echo '<div id="aspm-admin-banner" style="position:fixed;top:0;left:0;right:0;z-index:99999998;background:#1d1d1f;color:#fff;font-size:12px;line-height:1.5;padding:8px 16px;text-align:center;font-family:-apple-system,BlinkMacSystemFont,sans-serif;">';
		echo esc_html__( '🛠 Apple Star Loader: Maintenance mode is ON. Visitors see the maintenance page — countdown ends at ', 'apple-star-loader' ) . esc_html( $date );
		if ( $left ) {
			echo ' (' . esc_html__( 'about ', 'apple-star-loader' ) . esc_html( $left ) . esc_html__( ' left', 'apple-star-loader' ) . ')';
		}
		echo '. <a href="' . esc_url( $url ) . '" style="color:#6ec1ff;text-decoration:underline;">' . esc_html__( 'Open settings', 'apple-star-loader' ) . '</a>';
		echo '</div>';
	}

	private function output_loader() {
		$options = ASPL_Settings::get_options();
		$code    = trim( (string) $options['code'] );
		$timeout = max( 1, (int) $options['timeout'] );
		ob_start();
		?>
		<div id="asp-loader-root" dir="ltr" role="status" aria-live="polite" aria-label="<?php esc_attr_e( 'Page is loading', 'apple-star-loader' ); ?>">
			<?php echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional raw HTML/CSS field. ?>
		</div>
		<style>
			#asp-loader-root { transition: opacity 0.6s ease, transform 0.6s ease, filter 0.6s ease; }
			#asp-loader-root.asp-fade-out { opacity: 0 !important; transform: scale(1.04); filter: blur(6px); pointer-events:none; }
			html.asp-scroll-lock, html.asp-scroll-lock body { overflow: hidden !important; }
		</style>
		<script>
			( function () {
				'use strict';
				if ( window.__aspLoaderActive ) { return; }
				window.__aspLoaderActive = true;
				var root = document.getElementById( 'asp-loader-root' );
				if ( ! root ) { return; }
				var timeoutMs     = <?php echo (int) $timeout; ?> * 1000;
				var finished      = false;
				var fallbackTimer = null;
				function removeLoader() {
					if ( 'function' === typeof root.remove ) { root.remove(); } else if ( root.parentNode ) { root.parentNode.removeChild( root ); }
				}
				function finish() {
					if ( finished ) { return; }
					finished = true;
					window.removeEventListener( 'load', onWindowLoad );
					if ( fallbackTimer ) { clearTimeout( fallbackTimer ); }
					document.documentElement.classList.remove( 'asp-scroll-lock' );
					root.classList.add( 'asp-fade-out' );
					var removed = false;
					function doRemove() { if ( removed ) { return; } removed = true; removeLoader(); }
					root.addEventListener( 'transitionend', function ( event ) {
						if ( 'opacity' === event.propertyName && event.target === root ) { doRemove(); }
					} );
					window.setTimeout( doRemove, 1500 );
				}
				function onWindowLoad() { finish(); }
				document.documentElement.classList.add( 'asp-scroll-lock' );
				window.addEventListener( 'load', onWindowLoad );
				fallbackTimer = window.setTimeout( finish, timeoutMs );
				if ( 'complete' === document.readyState ) { finish(); }
			}() );
		</script>
		<noscript><style>#asp-loader-root { display: none !important; } body { overflow: auto !important; }</style></noscript>
		<?php
		echo trim( (string) ob_get_clean() );
	}

	private function output_maintenance() {
		$options = ASPL_Settings::get_options();
		$message = isset( $options['maintenance_message'] ) ? (string) $options['maintenance_message'] : ASPL_Defaults::DEFAULT_MAINTENANCE_MESSAGE;
		$end     = isset( $options['countdown_end'] ) ? (int) $options['countdown_end'] : 0;
		if ( $end > time() ) {
			status_header( 503 );
			header( 'Retry-After: ' . ( $end - time() ) );
		}
		$has_countdown = $end > 0;
		ob_start();
		?>
		<div id="aspm-root" role="status" aria-live="polite">
			<div class="aspm__inner">
				<div class="aspm__brand" dir="ltr"><span class="aspm__star"></span><span class="aspm__brand-text">APPLE STAR</span></div>
				<h1 class="aspm__msg"><?php echo esc_html( $message ); ?></h1>
				<?php if ( $has_countdown ) : ?>
				<div class="aspm__count" id="aspm-count" dir="ltr" data-end="<?php echo esc_attr( (string) $end ); ?>">
					<span class="aspm__unit"><b data-d>00</b><i>Days</i></span>
					<span class="aspm__unit"><b data-h>00</b><i>Hours</i></span>
					<span class="aspm__unit"><b data-m>00</b><i>Minutes</i></span>
					<span class="aspm__unit"><b data-s>00</b><i>Seconds</i></span>
				</div>
				<?php endif; ?>
				<p class="aspm__hint"><?php esc_html_e( 'We will be back shortly. Thank you for your patience.', 'apple-star-loader' ); ?></p>
			</div>
		</div>
		<style>
			body{overflow:hidden!important}
			#aspm-root{position:fixed;top:0;right:0;bottom:0;left:0;inset:0;z-index:99999999;display:flex;align-items:center;justify-content:center;background:rgba(4,4,8,0.97);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
			.aspm__inner{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:clamp(16px,4vh,28px);max-width:92vw;text-align:center;padding:24px;box-sizing:border-box}
			.aspm__brand{display:flex;align-items:center;gap:10px;direction:ltr}
			.aspm__star{width:30px;height:30px;background:#fff;clip-path:polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%);animation:aspm-pulse 2.4s infinite ease-in-out}
			.aspm__brand-text{letter-spacing:0.45em;font-size:clamp(0.75rem,2.5vw,0.9rem);color:rgba(255,255,255,0.9);font-weight:700}
			.aspm__msg{font-size:clamp(1.4rem,6vw,2.6rem);color:#fff;margin:0;overflow-wrap:anywhere;line-height:1.3}
			.aspm__count{display:flex;gap:10px;flex-wrap:wrap;justify-content:center}
			.aspm__unit{min-width:62px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);border-radius:10px;padding:10px 8px;display:flex;flex-direction:column;align-items:center}
			.aspm__unit b{font-size:clamp(1.2rem,4vw,1.6rem);color:#fff;font-variant-numeric:tabular-nums;line-height:1}
			.aspm__unit i{font-style:normal;font-size:10px;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.6);margin-top:4px}
			.aspm__hint{font-size:clamp(0.8rem,2.5vw,0.95rem);color:rgba(255,255,255,0.55);margin:0}
			.aspm__count--done{opacity:0.7}
			@keyframes aspm-pulse{0%,100%{transform:scale(1);filter:drop-shadow(0 0 8px rgba(255,255,255,0.6))}50%{transform:scale(1.08);filter:drop-shadow(0 0 14px rgba(255,255,255,0.9))}}
			@media (max-width:420px){.aspm__inner{padding:16px}.aspm__unit{min-width:56px;padding:8px 6px}}
			@media (prefers-reduced-motion: reduce){.aspm__star{animation:none!important}}
		</style>
		<script>
			( function () {
				'use strict';
				if ( window.__aspmActive ) { return; }
				window.__aspmActive = true;
				var el = document.getElementById( 'aspm-count' );
				if ( ! el ) { return; }
				var end = parseInt( el.getAttribute( 'data-end' ), 10 );
				if ( ! end ) { return; }
				var dEl = el.querySelector( '[data-d]' );
				var hEl = el.querySelector( '[data-h]' );
				var mEl = el.querySelector( '[data-m]' );
				var sEl = el.querySelector( '[data-s]' );
				function pad(n){ return n < 10 ? '0' + n : '' + n; }
				function tick(){
					var diff = Math.floor( end - Date.now() / 1000 );
					if ( diff <= 0 ) {
						if ( dEl ) dEl.textContent = '00';
						if ( hEl ) hEl.textContent = '00';
						if ( mEl ) mEl.textContent = '00';
						if ( sEl ) sEl.textContent = '00';
						el.classList.add( 'aspm__count--done' );
						clearInterval( timer );
						return;
					}
					var d = Math.floor( diff / 86400 );
					var h = Math.floor( ( diff % 86400 ) / 3600 );
					var m = Math.floor( ( diff % 3600 ) / 60 );
					var s = diff % 60;
					if ( dEl ) dEl.textContent = pad( d );
					if ( hEl ) hEl.textContent = pad( h );
					if ( mEl ) mEl.textContent = pad( m );
					if ( sEl ) sEl.textContent = pad( s );
				}
				tick();
				var timer = setInterval( tick, 1000 );
			}() );
		</script>
		<?php
		echo trim( (string) ob_get_clean() );
	}
}
