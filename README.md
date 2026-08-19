# Apple Star Page Loader v1.3.0 🌟

یک افزونه اختصاصی وردپرس (Production-Ready) برای نمایش **لودینگ صفحه‌ای شیشه‌ای «Apple Star»** + حالت **Maintenance / Coming Soon** — کاملاً ریسپانسیو، سازگار با **WordPress 7.0** و **WooCommerce 11.0**.

A production-ready WordPress plugin — custom **"Apple Star" glass preloader** + **Maintenance / Coming Soon** mode — fully responsive, WP 7.0 & WooCommerce 11.0 compatible.

---

## ✨ امکانات / Features

| قابلیت | توضیح |
|---|---|
| 🎛️ صفحه تنظیمات | **Apple Star Loader** در سایدبار + **Settings → Apple Star Loader** |
| ✅ سوئیچ Master ON/OFF | با بنر وضعیت و پیل Active/Disabled |
| 🎨 ۱۰ مدل لودینگ | یک‌کلیک انتخاب + حالت Custom |
| 🔧 کد لودینگ باز | HTML/CSS خام (فقط trim) + پیش‌نمایش زنده (375/768/100%) + Reset |
| ⏱️ Fallback Timeout | پیش‌فرض ۱۰ث (1–120) |
| 🛠️ Maintenance / Coming Soon | صفحه تمام‌صفحه opaque + پیام قابل ویرایش + تایمر زنده (پیش‌فرض 48h) + خاموش خودکار + 503 + Retry-After + bypass ادمین |

**10 Loader Designs / ۱۰ مدل:**

| # | کلید | ظاهر |
|---|---|---|
| 1 | `apple-star` | شیشه تیره + ECG + scanner + حروف APPLE STAR stagger 0.09s forwards |
| 2 | `star-frost` | شیشه روشن + حروف تیره |
| 3 | `dots` | سه نقطه پرشی |
| 4 | `spinner` | حلقه اسپینر کلاسیک |
| 5 | `progress-bar` | نوار گرادیان indeterminate |
| 6 | `pulse-ring` | نقطه + دو حلقه ripple |
| 7 | `orbit` | ستاره clip-path + دو نقطه در مدار |
| 8 | `typing` | LOADING... چشمک‌زن |
| 9 | `neon` | ستاره نئونی pulsing glow |
| 10 | `wave` | حروف موجی + LOADING |

## 🛠️ Maintenance Flow / جریان حالت تعمیر

1. ادمین Maintenance را ON + پیام + نوع تایمر (off / hours / datetime) را ذخیره می‌کند.
2. `countdown_end` محاسبه می‌شود (hours: `time()+hours*3600` / datetime: `strtotime()` / off: 0).
3. WP-Cron `aspl_maintenance_end` برای زمان پایان زمان‌بندی می‌شود.
4. بازدیدکنندگان عادی: `status_header(503)` + `Retry-After` + صفحه opaque `rgba(4,4,8,0.97)` با `z-index:99999999` — پشتش چیزی دیده نمی‌شود.
5. تایمر هر ثانیه با `Date.now()` به‌روز می‌شود (d/h/m/s با `tabular-nums`).
6. وقتی `diff<=0` → همه 00 + کلاس `aspm__count--done` + `clearInterval`.
7. Cron به‌صورت خودکار `maintenance_enabled=0` می‌کند — سایت باز می‌شود. ادمین‌ها همیشه bypass می‌کنند و بنر بالای صفحه را می‌بینند.

## 📁 ساختار فایل‌ها / File structure

```
apple-star-page-loader/
├── apple-star-page-loader.php        # v1.3.0 — singleton + cron handler
├── includes/
│   ├── class-aspl-defaults.php       # پیش‌فرض‌ها (maintenance خاموش، 48h)
│   ├── class-aspl-designs.php        # رجیستری ۱۰ مدل
│   ├── class-aspl-settings.php       # Settings API + JS منطق‌ها
│   └── class-aspl-frontend.php       # لودینگ + Maintenance + بنر ادمین
├── assets/designs/
│   ├── 01-apple-star.html  ... 10-wave.html   # ۱۰ مدل (HTML+CSS، بدون script)
│   └── default-loader-code.html      # legacy fallback
├── uninstall.php
└── readme.txt                        # Stable tag: 1.2.0
dist/apple-star-page-loader.zip       # فایل نصبی (با فولدر سطح‌بالا)
```

## 📦 نصب / Installation

**zip:** `dist/apple-star-page-loader.zip` → **Plugins → Add New → Upload Plugin**
**کپی:** فولدر `apple-star-page-loader` → `wp-content/plugins/` → Activate

## 🧪 سازگاری / Compatibility

- WordPress: Tested up to **7.0** (Requires at least 6.0)
- WooCommerce: **11.0**
- PHP: 7.4+ (8.2/8.4 tested)
- Browser: Chrome, Safari, Firefox, Edge — iOS/Android

## 🔒 امنیت / Security

- `manage_options` برای تنظیمات
- فیلد `code` خام — فقط trim، بدون strip/escape (مثل Footer Code)
- `maintenance_message` با `wp_strip_all_tags` + `esc_html`
- `uninstall.php` → `delete_option('aspl_settings')`

## 📄 لایسنس / License

GPLv2 or later.
