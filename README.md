# 🍎 Apple Star Page Loader — WordPress Plugin

Animated Apple-style page preloader for WordPress & WooCommerce. Version **3.1.0**.

![Version](https://img.shields.io/badge/version-3.1.0-blue) ![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892bf) ![License](https://img.shields.io/badge/license-GPLv2-green)

## ✨ ویژگی‌ها (Features)

- **۱۱ مدل لودینگ اپل‌استایل** — همه انیمیشن‌ها با **SMIL SVG** (`<animate>` / `<animateTransform>` داخل اینلاین SVG) و در نتیجه **مقاوم به بهینه‌سازها** (Autoptimize، WP Rocket، LiteSpeed، Cloudflare Rocket Loader و …):
  - **Apple Star Pulse (ECG)** — مدل اصلی: پس‌زمینه شیشه‌ای، خط ECG، نقطه اسکنر متحرک
  - Classic Pulse, Equalizer Bars, Sine Wave Dots, ECG Heartbeat, Siri Orbit
  - Concentric Radar, Breathing Core, Quantum Spin, Wave Morph, Dot Rhythm
- **متن برند ثابت (Static Text)** — متن (فارسی یا انگلیسی) بدون انیمیشن حرف/کلمه نمایش داده می‌شود (طبق درخواست شما) و مشکل حروف آینه‌ای/جدا در RTL کاملاً رفع شده
- **حالت بروزرسانی (Maintenance Mode)** با شمارنده معکوس ساعت/دقیقه/ثانیه و پیام سفارشی (پیش‌فرض خاموش)
- **پشتیبانی کامل فارسی/RTL** — جهت متن به‌طور خودکار از روی محتوا تشخیص داده می‌شود (`dir="rtl"`/`dir="ltr"`)
- **پیش‌نمایش زنده** در ادمین با iframe واقعی (جاوااسکریپت اجرا می‌شود) + دکمه تمام‌صفحه
- **صبر تا لود آخرین عکس** — صفحه تا کامل‌بارگذاری همه چیز پشت لودینگ می‌ماند
- **آپلود لوگو** با کتابخانه رسانه وردپرس
- **انتخابگر رنگ، بلور، شفافیت، زمان‌بندی**
- **سوئیچ‌های iOS-styel** سبز (روشن) / قرمز (خاموش)
- **کاملاً ریسپانسیو** (موبایل، تبلت، دسکتاپ)
- **CSS سفارشی** برای کاربران حرفه‌ای

## 📦 نصب (Installation)

1. فایل زیپ پلاگین را از قسمت **Releases** گیتهاب دانلود کنید.
2. در وردپرس به **افزونه‌ها ← افزودن جدید ← بارگذاری افزونه** بروید و فایل زیپ را آپلود کنید.
3. افزونه را فعال کنید.
4. از منوی **Apple Star Loader** در سایدبار ادمین تنظیمات را باز کنید، مدل دلخواه را انتخاب کنید و ذخیره کنید.

یا: پوشه `apple-star-page-loader` را درون `wp-content/plugins/` اکستراکت کنید و از بخش افزونه‌ها فعالش کنید.

## 🛠️ حالت بروزرسانی (Maintenance Mode)

- پیش‌فرض **خاموش** است.
- وقتی روشن شود، سایت برای بازدیدکنندگان تا پایان شمارنده معکوس پشت صفحه لودینگ می‌ماند.
- در حالت بروزرسانی لودینگ در **همه صفحات** نمایش داده می‌شود (نه فقط صفحه اصلی).
- یادتان باشد بعد از اتمام کار، دوباره آن را خاموش کنید!

## 🧑‍💻 توسعه (Development)

```
apple-star-page-loader/
├── apple-star-page-loader.php     # فایل اصلی پلاگین
├── includes/
│   ├── class-aspl-defaults.php    # تنظیمات پیش‌فرض + لیست پریست‌ها
│   ├── class-aspl-frontend.php    # تزریق لودینگ در فرانت‌اند
│   └── class-aspl-settings.php    # صفحه ادمین + پیش‌نمایش زنده
├── assets/
│   ├── default-loader-code.html
│   └── presets/                   # فایل‌های HTML پریست‌ها (هر مدل یک فایل)
└── readme.txt                     # readme مخزن وردپرس
```

برای ساخت مجدد فایل زیپ نصب:
```bash
rm -f dist/apple-star-page-loader.zip
zip -r dist/apple-star-page-loader.zip apple-star-page-loader -x "*/.DS_Store"
```

## 📝 تغییرات (Changelog)

### 3.1.0
- همه انیمیشن‌ها به **SMIL SVG** منتقل شدند (در برابر بهینه‌سازهای CSS/JS مقاوم)
- متن برند **ثابت** شد (مطابق درخواست) — دیگر هیچ انیمیشن روی حروف/کلمات نیست
- فایل‌های پریست به فرمت تمیز با placeholderهای ساده (`{{LOGO}}`, `{{TEXT}}`, `{{DIR}}`, `{{TEXT_COLOR}}`, `{{ACCENT}}`, `{{MAINT}}`) بازنویسی شدند
- CSS فرانت‌اند فقط layout/color است؛ `@keyframes` حذف شد
- پیش‌نمایش زنده ادمین بازنویسی شد تا با سیستم placeholder جدید هماهنگ باشد
- رفع مشکل دوقلو بودن صفت `class` در پریست Quantum Spin

### 3.0.x (3.0.0 – 3.0.3)
- بازطراحی کامل: مدل اصلی **Apple Star Pulse (ECG)** دقیقاً مطابق HTML مرجع کاربر بازگشت
- اضافه‌شدن ۱۰ مدل لودینگ جدید اپل‌استایل (مجموع ۱۱ مدل)
- اضافه‌شدن **حالت بروزرسانی** با شمارنده معکوس و پیام سفارشی
- بازنویسی پنل ادمین با کارت‌های پریست، سوئیچ‌های واضح سبز/قرمز، بخش بروزرسانی
- رفع مشکل پیش‌نمایش زنده (iframe با `allow-scripts` واقعی اجرا می‌شود)
- بهبود پشتیبانی فارسی/RTL (انیمیشن کلمه‌به‌کلمه)
- حذف نوار پیشرفت و درصد مطابق درخواست

## 📄 لایسنس (License)

GPL v2 or later — same as WordPress.

---

**ساخته‌شده با ❤ برای وردپرس فارسی**
