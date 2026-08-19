# Apple Star Page Loader — Apple Star Page Loader 🌟

یک افزونه اختصاصی وردپرس (Production-Ready) برای نمایش **لودینگ صفحه‌ای شیشه‌ای «Apple Star»** روی سایت — کاملاً ریسپانسیو، سازگار با آخرین نسخه‌های **WordPress 7.0** و **WooCommerce 11.0** و مناسب برای صفحات سنگین المنتور.

A production-ready WordPress plugin that puts a custom **"Apple Star" glass preloader** in front of your visitors — fully responsive, compatible with the latest WordPress (7.0) and WooCommerce (11.0), built for heavy Elementor pages.

---

## ✨ امکانات / Features

| قابلیت | توضیح |
|---|---|
| 🎛️ صفحه تنظیمات | در نوار کناری پیشخوان: **Apple Star Loader** + زیر **Settings → Apple Star Loader** |
| ✅ Enable / Disable | کلید روشن/خاموش بدون دست زدن به کد |
| 📍 Display Target | فقط صفحه اصلی (پیشنهادی) یا تمام صفحات سایت |
|  کد لودینگ کاملاً باز | Textarea بزرگ برای HTML/CSS لودینگ — هر لحظه با طراحی خودتان عوض می‌شود + **پیش‌نمایش زنده** (موبایل/تبلت/دسکتاپ) + دکمه Reset به کد پیش‌فرض |
| ⏱️ Fallback Timeout | عدد بر حسب ثانیه (پیش‌فرض ۱۰) — اگر عکسی گیر کند لودینگ بسته می‌شود و سایت قفل نمی‌ماند |
| 📄 تزریق در بالاترین نقطه | از طریق هوک `wp_body_open` (priority 1) + بازگشت‌پذیر روی `wp_footer` برای تم‌های بدون `body_open` |
| 🔒 قفل اسکرول | `overflow: hidden` در زمان فعال بودن لودینگ (از سمت خود افزونه، حتی اگر کد شما قفل نداشته باشد) |
| 🖼️ شنود رویداد load | `window.addEventListener('load')` — صبر تا تمام اجزای سنگین المنتور، فونت‌ها و عکس‌ها دانلود شوند |
| 🌫️ محو شدن نرم | Fade-out با تغییر `opacity` و سپس حذف کامل المان از DOM با `loader.remove()` |
| 📱 کاملاً ریسپانسیو | کد پیش‌فرض با `clamp()` + media queries از موبایل کوچک تا دسکتاپ + پشتیبانی از `prefers-reduced-motion` |

## 📁 ساختار فایل‌ها / File structure

```
apple-star-page-loader/
├── apple-star-page-loader.php        # فایل اصلی افزونه (هدرهای استاندارد WP)
├── includes/
│   ├── class-aspl-defaults.php       # گزینه‌ها و کد پیش‌فرض
│   ├── class-aspl-settings.php       # صفحه تنظیمات پیشخوان (Settings API)
│   └── class-aspl-frontend.php       # تزریق لودینگ، قفل اسکرول، fade-out
├── assets/
│   └── default-loader-code.html      # کد پیش‌فرض «Apple Star» (ریسپانسیو)
├── uninstall.php                     # پاک‌سازی کامل هنگام حذف افزونه
└── readme.txt                        # readme استاندارد وردپرس
```

##  نصب / Installation

**گزینه ۱ — فایل نصبی (پیشنهادی):**
فایل آماده‌ی نصب در [`dist/apple-star-page-loader.zip`](dist/apple-star-page-loader.zip) موجود است.
در پیشخوان وردپرس: **Plugins → Add New → Upload Plugin** → فایل zip را آپلود و Active کنید.

*Option 1 — installable zip:* grab [`dist/apple-star-page-loader.zip`](dist/apple-star-page-loader.zip), then in WP admin: **Plugins → Add New → Upload Plugin** → upload and activate.

**گزینه ۲ — کپی فولدر:**
فولدر `apple-star-page-loader` را داخل `wp-content/plugins/` کپی و Active کنید.

*Option 2 — copy the `apple-star-page-loader` folder into `wp-content/plugins/` and activate.*

بعد از فعال‌سازی: **Apple Star Loader** در نوار کناری پیشخوان (آیکون ستاره) یا **Settings → Apple Star Loader**.

## ⚙️ منطق فنی / Technical flow

1. `wp_body_open` (priority 1): کل کد لودینگ داخل یک ریشه‌ی واحد `<div id="asp-loader-root">` تزریق می‌شود.
2. `html.asp-scroll-lock` روی `<html>` → اسکرول قفل.
3. `window.addEventListener('load', ...)` → بعد از دانلود کامل همه‌ی داربست‌ها (المنتور، فونت، عکس):
   - کلاس `asp-fade-out` → `opacity: 0` در ۰.۶ ثانیه،
   - سپس `loader.remove()` → حذف کامل از DOM (با time-out امنیتی ۱.۵ ثانیه‌ای حتی اگر `transitionend` نیاید).
4. اگر رویداد `load` تا زمان Timeout نیامد → لودینگ به هر حال بسته می‌شود (سایت قفل نمی‌ماند).
5. `noscript` fallback: اگر JS خاموش باشد، لودینگ اصلاً نمایش داده نمی‌شود.

## 🧪 سازگاری / Compatibility

- WordPress: Tested up to **7.0** (Requires at least 6.0)
- WooCommerce: سازگار با **11.0** (بدون تداخل با هوک‌های فروشگاه)
- Elementor: طراحی‌شده برای صبر روی صفحات سنگین المنتور
- PHP: 7.4+ (سازگار با PHP 8.2/8.4)
- Browser: همه‌ی مرورگرهای مدرن (Chrome, Safari, Firefox, Edge — iOS/Android)

## 🔒 امنیت / Security

- دسترسی به صفحه تنظیمات فقط با `manage_options` (ادمین).
- فیلد «Loader Code» به‌صورت پیش‌فرض یک فیلد کد خام HTML/CSS است (مثل قسمت Footer Code) و فقط در مرورگر بازدیدکنندگان همان سایت اجرا می‌شود؛ محتوای آن عیناً ذخیره و ارسال می‌شود و مسئولیت کد با نویسنده‌ی آن است.
- با حذف افزونه (`uninstall.php`) تمام optionها پاک می‌شود.

## 📄 لایسنس / License

GPLv2 or later.
