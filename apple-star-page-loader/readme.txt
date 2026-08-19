=== Apple Star Page Loader ===
Contributors: khajavy8056
Tags: loader, preloader, loading, loading screen, apple star, page loader, maintenance, coming soon
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

A production-ready, fully responsive "Apple Star" glass preloader for WordPress & WooCommerce — 10 one-click designs + Maintenance / Coming Soon mode with live countdown.

== Description ==

Apple Star Page Loader puts a beautiful custom loading screen in front of your visitors while the page is getting ready — perfect for heavy Elementor and WooCommerce pages.

**10 built-in loader designs (one-click):**

* **Apple Star** — dark glass (rgba(0,0,0,.88)+blur16), ECG line, moving scanner dot, APPLE STAR letters with one-time fade-in-up stagger (0.09s forwards) staying on baseline
* **Star Frost** — like Apple Star but light glass (rgba(250,250,252,.92)) with dark text
* **Dots** — three bouncing white dots + "Loading"
* **Spinner** — classic spinner ring on white + "Loading"
* **Progress Bar** — indeterminate gradient bar on dark + "Please wait…"
* **Pulse Ring** — center dot + two expanding ripple rings + "Loading"
* **Orbit** — CSS star (clip-path polygon) center + two orbiting dots at different speeds + "APPLE STAR"
* **Typing** — "LOADING..." with three sequential blinking dots, white background
* **Neon** — cyan neon CSS star with pulsing glow (drop-shadow) + "APPLE STAR"
* **Wave** — APPLE STAR letters with continuous wave + "LOADING" underline

Plus **Custom** — paste any HTML/CSS of your own.

**Maintenance / Coming Soon mode:**

* Full-screen opaque cover (`rgba(4,4,8,0.97)` + blur24) — nothing behind is visible
* Editable message (default: "We'll be back soon") — `overflow-wrap:anywhere` for Persian/English
* Live countdown timer (default 48 hours, configurable 1–240 hours or until a specific date/time) with 4 units Days/Hours/Minutes/Seconds
* Auto-shutdown when timer reaches zero (WP-Cron `aspl_maintenance_end`)
* Correct HTTP 503 + `Retry-After` header while maintenance is live
* Admin bypass — logged-in admins always see the real site with a top banner showing end time and time left

**Core loader:**

* Injected at the very top via `wp_body_open` (priority 1) with `wp_footer` fallback
* Scroll lock (`overflow:hidden`) while loading — RTL fix: root is always `dir="ltr"`
* Waits for real `window.load` (Elementor, fonts, images) then fades out (opacity 0.6s) and removes from DOM (`loader.remove()` + safety 1500ms + noscript fallback)
* Fallback timeout (1–120s, default 10s) — site can never stay locked
* Fully responsive (`clamp()` + media queries) and `prefers-reduced-motion` support

**Admin panel** — **Apple Star Loader** in sidebar (dashicons-star-filled) + **Settings → Apple Star Loader**

== Installation ==

1. Upload `apple-star-page-loader.zip` via **Plugins → Add New → Upload Plugin** (the installable zip is in `dist/`), or extract the `apple-star-page-loader` folder into `wp-content/plugins/`.
2. Activate the plugin.
3. Open **Apple Star Loader** in the admin sidebar and configure it.

== Frequently Asked Questions ==

= How does Maintenance mode work? =

Enable **Maintenance / Coming Soon** in Apple Star Loader settings, set a message and countdown. Visitors see a full-screen opaque page with your message and a live countdown (updates every second). When the timer hits zero the plugin automatically disables maintenance via WP-Cron. The page sends HTTP 503 with a `Retry-After` header so search engines know it's temporary.

= Does it lock me out? =

No — users with `manage_options` (admins) always bypass maintenance and see the real site, with a top banner reminding them maintenance is ON.

= Why was my loader mirrored (fixed 1.1)? =

On RTL sites (Persian/Arabic) the loader was mirrored by the page's `dir="rtl"`. Since v1.1 the loader root is forced to `dir="ltr"` so letters always read `APPLE STAR` left-to-right.

= Is the Loader Code field safe? =

The field is a raw HTML/CSS area by design (your own loader markup), only visible to users with `manage_options`. It is only rendered in your visitors' browsers. Content is stored with only `trim()` — no stripping or escaping.

== Changelog ==

= 1.3.1 =
* Fix: پیش‌نمایش زنده (هم گرید و هم قاب بزرگ) کاملاً زنده شد — هر مدل مربع خودش را با انیمیشن واقعی نشان می‌دهد، قاب بزرگ دیگر سیاه نمی‌ماند و با تغییر مدل/تایپ زنده آپدیت می‌شود.
* پنل مدیریت پولیش حرفه‌ای: کارت‌ها، گرید، فاصله‌ها و استایل‌ها مرتب و مدرن شد.

= 1.3.0 =
* Instant first-paint: critical CSS + scroll-lock in wp_head priority 0 so loader appears before any Elementor/Woo content and stays above all.
* Polished all 10 loaders with distinct attractive motions + visual 4-column grid with live mini-previews in settings.
* Attractive fade-out (opacity + scale + blur) when page is ready.

= 1.2.0 =
* Maintenance / Coming Soon mode: full-screen opaque cover, editable message, live countdown (default 48h, hours or specific datetime), auto-shutdown via WP-Cron, HTTP 503 + Retry-After, admin bypass with top banner.

= 1.1.0 =
* RTL mirror fix: loader root forced to `dir="ltr"`
* Letters animate once (fade-in-up with forwards) and stay on baseline
* Master ON/OFF switch with status banner
* 10 built-in loader designs with one-click selector + Custom mode

= 1.0.0 =
* Initial release: enable/disable, front-page/all-pages target, editable HTML+CSS loader code with live preview and reset button, fallback timeout, scroll lock, window "load" detection, smooth fade-out + full DOM removal, responsive default "Apple Star" design.

== Upgrade Notice ==

= 1.3.1 =
* Fix: پیش‌نمایش زنده (هم گرید و هم قاب بزرگ) کاملاً زنده شد — هر مدل مربع خودش را با انیمیشن واقعی نشان می‌دهد، قاب بزرگ دیگر سیاه نمی‌ماند و با تغییر مدل/تایپ زنده آپدیت می‌شود.
* پنل مدیریت پولیش حرفه‌ای: کارت‌ها، گرید، فاصله‌ها و استایل‌ها مرتب و مدرن شد.

= 1.3.0 =
* Instant first-paint: critical CSS + scroll-lock in wp_head priority 0 so loader appears before any Elementor/Woo content and stays above all.
* Polished all 10 loaders with distinct attractive motions + visual 4-column grid with live mini-previews in settings.
* Attractive fade-out (opacity + scale + blur) when page is ready.

= 1.2.0 =
New: Maintenance / Coming Soon mode with live countdown and auto-shutdown. Update and configure from Apple Star Loader settings.
