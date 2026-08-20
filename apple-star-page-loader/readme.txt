=== Apple Star Page Loader ===
Contributors: khajavy8056
Tags: loader, preloader, loading, loading screen, page loader, animated loader, maintenance mode, coming soon
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 3.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Animated Apple-style page preloader for WordPress with 11 built-in loader designs, maintenance-mode countdown timer, live preview, logo upload, full Persian/RTL support, and smart "wait until last image" loading.

== Description ==

Apple Star Page Loader shows a clean animated loading overlay to your visitors while WordPress, Elementor, WooCommerce, fonts and images finish loading — so visitors never see a half-rendered broken page.

Version 3.0 is a major update:

**11 Apple-style loader designs**
* **Apple Star Pulse (ECG)** — the flagship: glass backdrop, scanning blip, per-letter heartbeat wave.
* Classic Pulse, Equalizer Bars, Sine Wave Dots, ECG Heartbeat, Siri Orbit,
* Concentric Radar, Breathing Core, Quantum Spin, Wave Morph, Dot Rhythm.
* Pick any design with one click from visual thumbnail cards.

**Maintenance / Under-Construction mode**
* Toggle switch (default OFF).
* Configurable hours/minutes/seconds countdown.
* Custom Persian/English message under the timer.
* When ON the loader stays up across the whole site until the timer hits zero.

**Smart loading**
* Waits for the real `window.load` event AND every image to complete
  (so the very last image is rendered before the overlay fades).
* Minimum display time to prevent flicker on fast loads.
* Hard fallback timeout — site can never stay locked.

**Persian / RTL support**
* RTL text (Persian, Arabic, Hebrew) is split word-by-word so Arabic-script
  letter joining is preserved — no more "mirrored/disconnected letters".
* LTR text is split letter-by-letter for the classic wave effect.
* Admin UI is fully RTL-aware.

**Admin panel**
* Single clean page (no tabs).
* Built-in live preview iframe that really runs JavaScript
  (sandboxed with allow-scripts / allow-same-origin).
* Fullscreen preview button.
* iOS-style green/red on-off switches.
* Native WordPress color picker, range sliders with live value labels,
  Media Library logo uploader.
* Fully mobile responsive.

**Other features**
* Display targets: front page, all pages, home/blog, posts only, pages only, WooCommerce only.
* Optional hide for logged-in users, optional hide on mobile.
* Configurable background color + opacity + backdrop blur.
* Text and accent colors.
* Custom CSS box for advanced overrides.
* Smooth fade-out with full DOM removal.
* Respects `prefers-reduced-motion`.

== Installation ==

1. Upload `apple-star-page-loader.zip` via **Plugins → Add New → Upload Plugin**
   (the installable zip lives in `dist/`), or extract the `apple-star-page-loader`
   folder into `wp-content/plugins/`.
2. Activate the plugin.
3. Open **Apple Star Loader** in the admin sidebar, pick a preset, optionally
   upload a logo, and save.

== Frequently Asked Questions ==

= Does it work with Elementor / WooCommerce / heavy themes? =
Yes. The loader waits for the real `window.load` event and for every image to
finish, so Elementor, web fonts and hero images are all there before it fades.

= How do I put the site in maintenance mode? =
Turn on the **"حالت بروزرسانی"** switch, set the countdown timer (hours/minutes/seconds)
and the message, then save. Visitors will see the loader + timer until it reaches
zero (or the hard timeout expires). Remember to turn it off when you're done.

= Why are Persian words animated word-by-word instead of letter-by-letter? =
Because Arabic-script letters change shape depending on their neighbors
(initial / medial / final / isolated forms). Splitting them into individual
spans breaks joining and causes the "mirrored letters" bug. Word-level
animation preserves correct typography while still giving a nice pulse.

= Will the loader ever lock my site forever? =
No. A hard fallback timeout (default 20 seconds, up to 600) always releases
the page even if some asset never loads.

== Changelog ==

= 3.1.0 =
* **First-paint guarantee.** Critical loader CSS is now injected in `<head>` at priority `-99999` (before all theme/plugin styles), the loader HTML is inserted as the very first child of `<body>` via an output buffer (works even on themes that forget `wp_body_open()`), and `<html>` is forced to the loader's background color immediately — so the loader is the first thing the browser paints, before any of the page's own content or styles load. The fade/image-wait/countdown control script has been moved to `wp_footer` so it never blocks initial paint.
* **Bullet-proof animations.** All loader animations now use inline SVG SMIL (`<animate>` / `<animateTransform>` tags inside the SVG itself) — the exact same technique that powers the preset thumbnail icons in the admin panel. This makes animations immune to CSS/JS optimizer plugins (Autoptimize, WP Rocket, LiteSpeed Cache, SG Optimizer, W3 Total Cache, Cloudflare Rocket Loader, etc.) that were stripping `@keyframes` rules.
* **Static brand text.** Per request, the brand/site-title text is rendered as a plain static element — no per-letter spans, no text animation. Persian/RTL text is rendered with correct `dir` auto-detection so Arabic-script letters never mirror or disconnect.
* **Richer SMIL visuals.** All 11 presets upgraded with stronger SMIL effects (accent-colored glow gradients, SVG `filter` glow, trail/path-draw, multiple offset rings/wedges, animated stroke-width, fade-in+slide-in on load).
* Preset HTML files are clean fragments using `{{LOGO}}` / `{{TEXT}}` / `{{DIR}}` / `{{TEXT_COLOR}}` / `{{ACCENT}}` / `{{MAINT}}` placeholders replaced server-side; no `<style>` blocks inside presets.
* Frontend CSS is pure layout/colors/typography — zero `@keyframes`.
* Live admin preview rebuilt to mirror the new placeholder-based rendering (SMIL still runs inside the sandboxed iframe).

= 3.0.3 / 3.0.2 / 3.0.1 =
* Hotfixes trying (and failing) to shield CSS `@keyframes` from optimizer plugins by moving styles to `wp_head`, adding `data-cfasync="false"`, `data-no-optimize="1"`, etc. These approaches all proved unreliable across real-world caching stacks, which is why 3.1.0 moved everything to SMIL.

= 3.0.0 =
* Major redesign. The original Apple Star Pulse (ECG track + scanning blip + per-letter heartbeat) is back as the default preset, matching the user's reference HTML exactly.
* Added 10 more Apple-style loader designs (Classic Pulse, Equalizer Bars, Sine Wave, ECG Heartbeat, Siri Orbit, Radar Sweep, Breathing Core, Quantum Spin, Wave Morph, Dot Rhythm) — total 11 presets selectable via visual thumbnail cards.
* New **maintenance mode** with countdown timer (HH:MM:SS) and custom message. When enabled the loader locks the site until the timer reaches zero. Default OFF.
* Admin panel rebuilt: preset thumbnail grid, iOS-style green/red switches, dedicated maintenance section, better color controls, mobile responsive, full RTL.
* Live preview iframe fixed (sandboxed with `allow-scripts allow-same-origin`) and now correctly previews all presets, logo upload, colors, text, and a working countdown demo.
* Persian/RTL text still animates word-by-word to preserve Arabic-script letter joining (no more mirrored/disconnected glyphs).
* Waits for all images to load before fading.
* Removed the percentage counter and progress bar (per request).

= 2.0.1 =
* Fix: Persian/Arabic (RTL) text no longer appears "mirrored" or split.
* Fix: Live preview iframe in admin now runs scripts.
* Added extra presets (total 10 at the time).

= 2.0.0 =
* Rewrite: wave-animated letters, percentage counter, progress bar, admin dashboard.

= 1.0.0 =
* Initial release.
