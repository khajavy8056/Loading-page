=== Apple Star Page Loader ===
Contributors: khajavy8056
Tags: loader, preloader, loading, loading screen, page loader, animated loader, maintenance mode, coming soon
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 3.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Animated Apple-style page preloader for WordPress with 100 built-in SVG/SMIL loader designs, instant animation start, fast DOM-ready reveal, maintenance-mode countdown timer, live preview, logo upload, full Persian/RTL support.

== Description ==

Apple Star Page Loader shows a clean animated loading overlay to your visitors while WordPress, Elementor, WooCommerce, fonts and images finish loading — so visitors never see a half-rendered broken page.

Version 3.3 is a major update:

**100 loader designs (SVG + SMIL)**
* Every animation is pure inline SVG SMIL (`<animate>` / `<animateTransform>`) — immune to CSS/JS optimizer plugins (Autoptimize, WP Rocket, LiteSpeed, SG Optimizer, Cloudflare Rocket Loader, etc.).
* 11 original designs (Apple Star Pulse ECG, Classic Pulse, Equalizer Bars, Sine Wave Dots, ECG Heartbeat, Siri Orbit, Concentric Radar, Breathing Core, Quantum Spin, Wave Morph, Dot Rhythm) plus 89 new ones: pulse rings, glowing halos, bouncing dots, sine waves, equalizers, arc spinners, atom orbits, radar sweeps, twinkling stars, ECG lines, planet orbits, infinity loops, shape morphing, dot matrices, battery charge, spinning clocks, camera aperture, pinwheels, water drops, orbital triangles and more.
* Visual card grid with **live animated thumbnails**, search box and category filter.

**Instant animation + fast reveal (v3.3 fix)**
* The loader is the FIRST thing the browser paints: critical CSS in `<head>` (priority -99999), loader HTML injected immediately after `<body>`, `<html>` background pre-painted (no white flash).
* The SMIL animation starts the moment the SVG is parsed — no JS, no CSS keyframes, no waiting.
* NEW: by default the loader fades out as soon as the DOM is ready (DOMContentLoaded) — the page behind appears almost immediately instead of waiting for every image/font.
* Optional legacy "wait for full page load" mode (window load + optionally all images) for pages that need it.
* Short minimum display time (default 350 ms) prevents flicker; hard fallback timeout means the site can never stay locked.

**Maintenance / Under-Construction mode**
* Toggle switch (default OFF), configurable hours/minutes/seconds countdown, custom Persian/English message. When ON the loader stays up across the whole site until the timer hits zero.

**Persian / RTL support**
* Brand text is static with automatic `dir` detection — Persian/Arabic letters never mirror or disconnect.

**Admin panel**
* Single clean page, live preview iframe that really runs JavaScript, fullscreen preview, iOS-style switches, native WordPress color picker, range sliders with live labels, Media Library logo uploader, mobile responsive.

**Other features**
* Display targets: front page, all pages, home/blog, posts only, pages only, WooCommerce only.
* Optional hide for logged-in users, optional hide on mobile.
* Configurable background color + opacity + backdrop blur, text and accent colors.
* Custom CSS box for advanced overrides.
* Smooth fade-out with full DOM removal; respects `prefers-reduced-motion`.

== Installation ==

1. Upload `apple-star-page-loader.zip` via **Plugins → Add New → Upload Plugin**
   (the installable zip lives in `dist/`), or extract the `apple-star-page-loader`
   folder into `wp-content/plugins/`.
2. Activate the plugin.
3. Open **Apple Star Loader** in the admin sidebar, pick one of the 100 presets,
   optionally upload a logo, and save.

== Frequently Asked Questions ==

= Does it work with Elementor / WooCommerce / heavy themes? =
Yes. The loader appears instantly (SMIL animation starts on first paint) and
by default fades as soon as the DOM is ready. If you want it to wait for every
asset, switch "کی لودینگ بسته شود؟" to the full-page-load mode.

= How do I put the site in maintenance mode? =
Turn on the **"حالت بروزرسانی"** switch, set the countdown timer
(hours/minutes/seconds) and the message, then save. Visitors will see the
loader + timer until it reaches zero (or the hard timeout expires).

= Are the animations affected by caching / optimizer plugins? =
No. All 100 presets animate with inline SVG SMIL — there is no CSS `@keyframes`
and no JS involved in the animation, so optimizer plugins cannot strip it.

= Will the loader ever lock my site forever? =
No. A hard fallback timeout (default 15 seconds, up to 600) always releases
the page even if some asset never loads.

== Changelog ==

= 3.3.1 =
* **Instant animation fix (streaming first paint).** Removed the whole-page output buffer that held the loader until PHP finished rendering the entire page (the reason the icon appeared frozen until the site finished loading). The loader is now echoed right after `<body>` opens (`wp_body_open`, priority -99999) and the response is flushed at that moment, plus an `X-Accel-Buffering: no` header for nginx. The loader + its SMIL animation now start at the absolute first paint — exactly like fast sites (Digikala, etc.) — while the server keeps streaming the rest of the page. For themes without `wp_body_open` a footer fallback still renders the loader before the control script.

= 3.3.0 =
* **100 loader presets.** Added 89 new SVG/SMIL designs (pulse rings, glowing halos, bouncing dots, sine waves, equalizers, arc spinners, atom orbits, radar sweeps, twinkling stars, ECG lines, planet orbits, infinity loops, shape morphing, dot matrices, battery charge, spinning clocks, camera aperture, pinwheels, water drops, orbital triangles) on top of the 11 existing ones. Preset picker now shows live animated thumbnails with search and category filter.
* **Instant animation start.** The loader and its SMIL animation begin at the absolute first paint — critical CSS in `<head>` (priority -99999), loader HTML injected right after `<body>` via output buffer, `<html>` background pre-painted, only a 1-line synchronous scroll-lock script in `<body>`.
* **Fast reveal — no more delayed page.** NEW `hide_when` setting: default `dom_ready` fades the loader out as soon as the DOM is interactive (page behind appears almost immediately); `window_load` keeps the legacy "wait for everything" behavior. Defaults tuned to 350 ms min display / 350 ms fade / 15 s timeout.
* Fixed the "wait for last image" switch being locked ON (sanitizer bug) — it is now a real, default-OFF option.
* Fade-out respects `prefers-reduced-motion` (instant hide).
* Full Persian labels for all 100 presets; registry, admin and preview fully updated.

= 3.1.0 =
* **First-paint guarantee.** Critical loader CSS injected in `<head>` at priority `-99999`, loader HTML inserted as the very first child of `<body>` via an output buffer, `<html>` forced to the loader background immediately. The control script moved to `wp_footer` so it never blocks initial paint.
* **Bullet-proof animations.** All loader animations use inline SVG SMIL — immune to CSS/JS optimizer plugins.
* **Static brand text.** No per-letter spans; Persian/RTL text rendered with correct `dir` auto-detection.
* Preset HTML files are clean fragments using `{{LOGO}}` / `{{TEXT}}` / `{{DIR}}` / `{{TEXT_COLOR}}` / `{{ACCENT}}` / `{{MAINT}}` placeholders; no `<style>` blocks inside presets.

= 3.0.3 / 3.0.2 / 3.0.1 =
* Hotfixes trying (and failing) to shield CSS `@keyframes` from optimizer plugins by moving styles to `wp_head`, adding `data-cfasync="false"`, `data-no-optimize="1"`, etc. Superseded by the SMIL approach in 3.1.0.

= 3.0.0 =
* Major redesign. Apple Star Pulse (ECG track + scanning blip) back as the default preset; 10 more Apple-style designs (total 11); new maintenance mode with countdown; admin panel rebuilt with preset thumbnail grid, iOS-style switches, color controls, RTL, live preview iframe.

= 2.0.1 =
* Fix: Persian/Arabic (RTL) text no longer appears "mirrored" or split.
* Fix: Live preview iframe in admin now runs scripts.

= 2.0.0 =
* Rewrite: wave-animated letters, percentage counter, progress bar, admin dashboard.

= 1.0.0 =
* Initial release.
