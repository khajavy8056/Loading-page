# Apple Star Page Loader v2.0 🌟

یک افزونه **حرفه‌ای، کاملاً متحرک و پر از جذابیت بصری** برای نمایش صفحه‌ی لودینگ در وردپرس و ووکامرس — با حروف موجی، درصد شمارنده پرش‌کننده، نوار پیشرفت متحرک، ۶ طرح آماده، آپلود لوگو، شخصی‌سازی رنگ‌ها، پنل مدیریت کاملاً بازطراحی‌شده و پیش‌نمایش زنده.

A professional, fully animated preloader plugin for WordPress & WooCommerce — wave letters, bouncing percentage counter, animated progress bar, 6 built-in presets, logo upload, color customization, a beautiful redesigned admin dashboard and live preview.

---

## ✨ امکانات / Features

| قابلیت | توضیح |
|---|---|
| 🎞️ **حروف موجی (Wave Letters)** | هر حرف به‌صورت مجزا بالا و پایین می‌رود با افکت موج پشت سر هم |
| 🔢 **درصد شمارنده پرش‌کننده** | عدد ۰ تا ۱۰۰ با انیمیشن و بانس در هر ~۱۰٪ تغییر می‌کند |
| 📊 **نوار پیشرفت متحرک** | نوار با Easing نرم، شاین، نور متحرک و داتِ دنبال‌کننده پر می‌شود |
| 🎨 **۶ طرح آماده حرفه‌ای** | Apple Star (Glass), Wave Letters, Spinner Pro, Progress Bar, Particles Glow, Minimal Dot |
| 🖼️ **آپلود لوگو** | از Media Library وردپرس، بالاتر از همه چیز نمایش داده می‌شود |
| 🔤 **متن دلخواه** | متن لودینگ را به اسم برند خودتان تغییر دهید — خودکار موجی می‌شود |
| 🎛️ **داشبورد جدید** | ۶ تب: Dashboard، General، Design، Content، Timing، Advanced |
| 👀 **پیش‌نمایش زنده** | Preview داخل سایدبار + دکمه Fullscreen + سوییچ موبایل/تبلت/دسکتاپ |
| 🌈 **رنگ‌بندی کامل** | Color Picker وردپرس برای بک‌گراند، متن، اکسنت، پرایمری |
| 💬 **نکات چرخشی (Tips)** | جملات دوستانه هنگام لود به‌صورت متناوب |
| ⏱️ **کنترل زمان** | Min Display Time (جلوگیری از فلیکر)، Fallback Timeout، Fade Duration |
| 🌫️ **Blur و شفافیت** | محو کردن پشت‌زمینه و تنظیم اپاسیتی |
| 📍 **هدف نمایش** | تمام صفحات، فقط صفحه اصلی، وبلاگ، پست‌ها، برگه‌ها، یا فقط ووکامرس |
| 🔒 **قفل اسکرول** | هنگام لود اسکرول قفل و بعد نرم باز می‌شود |
| 🚫 **عدم قفل دائم** | Fallback تضمین می‌کند هیچ‌گاه سایت پشت لودینگ گیر نمی‌کند |
| 📱 **کاملاً ریسپانسیو** | موبایل کوچک تا دسکتاپ ۴K با clamp() و media query |
| ♿ **پشتیبانی از `prefers-reduced-motion`** | برای کاربرانی که انیمیشن خاموش کرده‌اند |
| 🧩 **Custom CSS + Custom Code** | امکان اضافه کردن CSS دلخواه یا کد HTML/CSS کاملاً سفارشی |
| 🎆 **Particles Canvas** | پرست Particles با شبکه‌ی ذرات متحرک روی بوم (canvas) |

---

## 📁 ساختار فایل‌ها / File structure

```
apple-star-page-loader/
├── apple-star-page-loader.php        # فایل اصلی افزونه
├── includes/
│   ├── class-aspl-defaults.php       # گزینه‌های پیش‌فرض و پرست‌ها
│   ├── class-aspl-settings.php       # صفحه تنظیمات حرفه‌ای جدید (تب‌بندی شده)
│   └── class-aspl-frontend.php       # تزریق لودینگ + ردیابی پیشرفت + انیمیشن %
├── assets/
│   ├── default-loader-code.html      # کد پیش‌فرض (ارتقا یافته)
│   └── presets/
│       ├── apple_star.html
│       ├── wave_letters.html
│       ├── spinner_pro.html
│       ├── progress_bar.html
│       ├── particles.html
│       └── minimal_dot.html
├── uninstall.php
└── readme.txt
```

---

## 🚀 نصب / Installation

**پیشنهادی:** فایل آماده‌ی نصب در `dist/apple-star-page-loader.zip` است.
In WP admin: **Plugins → Add New → Upload Plugin** → فایل zip را آپلود و Active کنید.

**روش دستی:** فولدر `apple-star-page-loader` را داخل `wp-content/plugins/` کپی و Active کنید.

بعد از فعال‌سازی: منوی **Apple Star Loader** در نوار کناری پیشخوان ظاهر می‌شود. از تب **Design** یک طرح انتخاب کنید، رنگ‌ها و متن دلخواه را بدهید، از Preview لذت ببرید و ذخیره کنید.

---

## ⚙️ منطق فنی / Technical flow

1. `wp_body_open` (priority 1): کد پرست انتخابی + متغیرهای CSS رنگ‌ها + لوگو + اسکریپت پیشرفت تزریق می‌شود.
2. کلاس `html.asp-scroll-lock` روی `<html>` → اسکرول قفل.
3. **پیشرفت ۰→۱۰۰**:
   * بلافاصله ۸٪، سپس ۱۸٪ و ۳۰٪ (برای حس زنده بودن)
   * از روی `img` های صفحه: هر عکسی که لود شد درصدی اضافه می‌شود (تا ~۸۵٪)
   * به‌صورت time-based در طول ۸ ثانیه به‌سمت ۹۲٪ میل می‌کند
   * در لحظه `window.load` به ۱۰۰٪ می‌رسد و با بانس نمایش داده می‌شود
4. حروف متن بارگذاری با JS به span های مجزا تبدیل شده و animation-delay پلکانی می‌گیرند (افکت موج).
5. پس از `load` → fade-out نرم → حذف کامل از DOM.
6. اگر `load` نیامد → Fallback Timeout (پیش‌فرض ۱۵ ثانیه) لودینگ را می‌بندد.
7. `noscript` fallback: اگر JS خاموش باشد لودینگ نمایش داده نمی‌شود.

---

## 🧪 سازگاری / Compatibility

- WordPress: **6.0+** (tested up to **7.0**)
- WooCommerce: **11.0+**
- Elementor: بله، طراحی‌شده برای صفحات سنگین المنتور
- PHP: **7.4+** (سازگار با 8.2 / 8.4)
- Browser: Chrome, Safari, Firefox, Edge (iOS / Android)

---

## 📝 تغییرات نسخه ۲.۰ / What's new in v2.0

* بازنویسی کامل موتور لودینگ با انیمیشن‌های نرم‌تر
* ۶ پرست حرفه‌ای جدید
* درصد شمارنده با انیمیشن bounce در هر تغییر عدد
* نوار پیشرفت با shine / dot / gradient متحرک
* پنل مدیریت کاملاً جدید با UI مدرن (تب، کارت، سوئیچ، رنج اسلایدر)
* آپلود لوگو از Media Library
* پیش‌نمایش زنده داخل سایدبار + فول‌اسکرین
* رنگ‌بندی کامل با WP Color Picker
* قابلیت نمایش روی برگه‌ها، پست‌ها، صفحه اصلی یا فقط ووکامرس
* گزینه مخفی کردن از کاربران لاگین‌شده / موبایل
* Custom CSS و Custom Code
* افکت Particles با canvas

## 📄 لایسنس / License

GPLv2 or later.
