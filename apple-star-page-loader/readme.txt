=== Apple Star Page Loader ===
Contributors: khajavy8056
Tags: loader, preloader, loading, loading screen, apple star, page loader
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

A production-ready, fully responsive "Apple Star" glass preloader for WordPress & WooCommerce, with a fully editable loader code editor in the admin panel.

== Description ==

Apple Star Page Loader puts a beautiful custom loading screen in front of your visitors while the page is getting ready — perfect for heavy Elementor and WooCommerce pages.

**What it does**

* Injects your loader at the very top of the page via `wp_body_open` (earliest possible point), with a `wp_footer` safety fallback for themes that never fire `wp_body_open`.
* Locks scrolling (`overflow: hidden`) while the loader is visible — on the plugin side, so it works even if your custom code has no lock of its own.
* Waits for the real `window` "load" event, so heavy Elementor components, web fonts and images are fully downloaded before the page appears.
* Fades out smoothly (opacity transition) and then removes the loader completely from the DOM (`loader.remove()`).
* Fallback timeout (default 10 s): if a request gets stuck the loader closes anyway — your site can never stay locked.
* Fully responsive: the default "Apple Star" design scales from small phones to desktop (`clamp()` + media queries) and honors `prefers-reduced-motion`.
* Compatible with the latest WordPress (7.0) and WooCommerce (11.0); no conflicts with themes, Elementor or store pages.

**Admin panel** — in the admin sidebar: **Apple Star Loader**, and also under **Settings → Apple Star Loader**

* **Enable / Disable** switch.
* **Display target:** front page only, or all pages.
* **Loader Code:** a large textarea with the complete loader HTML + CSS — fully open, replaceable with your own design any time. Includes a **live preview** (mobile / tablet / desktop widths) and a **Reset to default code** button.
* **Fallback timeout** in seconds (1–120).

== Installation ==

1. Upload `apple-star-page-loader.zip` via **Plugins → Add New → Upload Plugin** (the installable zip is in `dist/` in the repository), or extract the `apple-star-page-loader` folder into `wp-content/plugins/`.
2. Activate the plugin.
3. Open **Apple Star Loader** in the admin sidebar (or **Settings → Apple Star Loader**) and configure it.

== Frequently Asked Questions ==

= Does it work with Elementor or WooCommerce? =
Yes, it is built for heavy pages. The loader waits for the window load event, which covers Elementor renders, web fonts and images. Store, product and archive pages all work with the "all pages" target.

= Can I use my own loader design? =
Yes — paste any self-contained HTML + CSS into the Loader Code field. Keep it self-contained (the plugin wraps the code in one root block and removes it as a whole when the page is ready). The live preview helps you check the result and the responsive behavior before saving.

= What if the page never finishes loading? =
The fallback timeout closes the loader after the configured number of seconds (default 10 s), so the site is never locked behind the loader.

= Does it show to logged-in users? =
Yes, so you can preview it on your own device. Turn it off any time with the switch.

= Is the code field safe? =
The field is a raw HTML/CSS area by design (the user's own loader markup), only visible to users with the `manage_options` capability (admin-level). It is only ever rendered in the visitors' browsers of the site that installed it.

== Changelog ==

= 1.0.0 =
* Initial release: enable/disable switch, front-page / all-pages display target, fully editable HTML+CSS loader code with live preview and reset button, fallback timeout, scroll lock, window "load" detection, smooth fade-out + full DOM removal, fully responsive default "Apple Star" design.
