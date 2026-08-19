=== Apple Star Page Loader ===
Contributors: khajavy8056
Tags: loader, preloader, loading, loading screen, page loader, progress, animated loader
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A professional, fully animated preloader for WordPress & WooCommerce with 6 built-in designs, wave-animated letters, bouncing percentage counter, animated progress bar, rotating tips, logo upload, color customization and a beautiful redesigned dashboard.

== Description ==

Apple Star Page Loader puts a beautiful, fully animated loading screen in front of your visitors while the page is getting ready — perfect for heavy Elementor, WooCommerce and content-heavy sites.

Version 2.0 is a complete rewrite focused on **motion, feel and customization**:

**Animated loading that actually moves**
* Every letter in the wordmark animates up/down in a smooth cascading **wave** — one letter after another.
* The **percentage counter bounces and rolls** as it counts from 0 to 100, digit by digit, every ~10%.
* The **progress bar** fills with smooth easing, shine effects and moving dots.
* 6 completely different presets to choose from: Apple Star (glass), Wave Letters, Spinner Pro, Progress Bar, Particles Glow and Minimal Dot.

**Smart progress tracking**
* Progress is estimated in real time: image loads, resource timing, network and time-based easing.
* Jumps to 100% on the real `window.load` event.
* Minimum display time option (no flicker on fast loads).
* Fallback timeout (the site can never stay locked).

**Professional admin dashboard**
* Clean tabbed UI: Dashboard, General, Design, Content, Timing, Advanced.
* Built-in **live preview pane** on every screen, with mobile / tablet / desktop device toggle.
* One-click **fullscreen preview** button.
* WordPress color picker for every color.
* Logo upload with native Media Library.
* Range sliders with live value labels.
* Card-based radio selectors for preset and display target.

**Branding**
* Upload your logo (PNG/SVG recommended).
* Custom loading text (any word up to 40 characters — wave-animated automatically).
* Background color + opacity + blur.
* Text / accent / primary / bar colors.
* Custom CSS box for advanced overrides.
* Custom HTML/CSS mode for complete designs.

**Other features**
* Injected via `wp_body_open` (priority 1) with a `wp_footer` safety fallback.
* Scroll lock while loading.
* Smooth fade-out + full DOM removal.
* Respects `prefers-reduced-motion`.
* Fully responsive (mobile, tablet, desktop).
* Tested with WordPress 7.0, WooCommerce 11.0, Elementor, PHP 7.4–8.4.
* Display target: All pages, front page, home/blog, posts only, pages only, or WooCommerce only.
* Optional hide for logged-in users, optional hide on mobile.

== Installation ==

1. Upload `apple-star-page-loader.zip` via **Plugins → Add New → Upload Plugin** (the installable zip is in `dist/` in the repository), or extract the `apple-star-page-loader` folder into `wp-content/plugins/`.
2. Activate the plugin.
3. Open **Apple Star Loader** in the admin sidebar and pick a preset.

== Frequently Asked Questions ==

= Does it work with Elementor or WooCommerce? =
Yes. It is built specifically for heavy pages: it waits for the real window `load` event so Elementor, web fonts and images are all downloaded before the loader fades.

= Are the letters animated individually? =
Yes. Each letter rises/falls in a wave pattern with a per-letter delay. When you change the loading text to your own brand name, the new letters are wave-animated automatically.

= Does the progress bar move? =
Yes. The bar fills smoothly, with easing and shine/dot effects, updating in real time as resources load. It always finishes at 100% when the page is fully ready.

= Can I use my own loader design? =
Yes — choose the "Custom Code" preset and paste your own HTML/CSS. If you include an `.asp-percent` element and an `.asp-bar-fill` element in your markup, the counter and progress bar will animate automatically.

= What if the page never finishes loading? =
The fallback timeout (default 15 seconds) closes the loader so the site can never stay locked.

= Is the plugin responsive? =
Yes. Every preset uses `clamp()`, flexbox and media queries to scale from phones to 4K.

== Screenshots ==
1. The redesigned dashboard with hero stats and quick start.
2. The Design tab: 6 preset cards, color pickers, element toggles.
3. The General tab: enable switch and display-target cards.
4. The Content tab: logo uploader and loading text field.
5. The live side preview with device toggle.
6. The fullscreen preview.

== Changelog ==

= 2.0.0 =
* Complete rewrite: new animated engine with wave letters, bouncing percentage counter, smooth progress bar.
* Added 6 professional presets: Apple Star, Wave Letters, Spinner Pro, Progress Bar, Particles Glow, Minimal Dot.
* Real-time progress tracking based on resource loads + time-based easing (updates every ~10%).
* New admin dashboard with tabs, hero panel, preset cards, color pickers, range sliders, device-switchable live preview, and fullscreen preview.
* Logo upload (native Media Library), customizable loading text (auto wave-ified).
* Rotating loading tips, min display time, fade duration, blur, z-index, custom CSS box.
* Display targets: all pages, front page, home/blog, posts, pages, WooCommerce only.
* Options: hide for logged-in users, hide on mobile.
* Particles preset uses a live canvas-based particle network.

= 1.0.0 =
* Initial release.

== Changelog ==

= 2.0.1 =
* Fix: Persian/Arabic (RTL) text no longer appears "mirrored" or split into disconnected letters — RTL text is animated word-by-word, preserving connected glyphs.
* Fix: Live preview iframe in the admin dashboard now runs scripts (sandbox `allow-scripts`), so animations and the bouncing percentage counter work while editing.
* Fix: Mobile admin layout — cards, preset grid, color pickers and buttons stack correctly on small screens.
* Fix: Apple Star preset returned to true ECG/heartbeat feel with a per-letter pulse synced to the accent color.
* New presets added: Pulse Ring, Equalizer Bars, Dots Bounce, Neon Line (total 10 presets).
* Percentage counter now bounces on every change on the frontend as well, not just in preview.
* Progress bar advances smoothly during load.
* Better font stack with Vazirmatn/Tahoma fallback for Persian text.

= 2.0.0 =
* Initial v2 release: wave-animated letters, bouncing percentage counter, animated progress bar, presets, color customization, logo upload, rotating tips, redesigned admin.
