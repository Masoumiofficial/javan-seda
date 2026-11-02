# v3.5.0 — «بستۀ قالبِ کاملِ سالم» + کلیدِ بازگشتِ امن

تاریخ: ۲۰۲۶-۱۰-۲۰ · پیش‌نیاز: ۳.۴.۱۲ (یا هر ۳.۴.x)

## چرا این نسخه
تا ۳.۴.۱۱ «قالبِ کامل» دو فایلِ رمزگذاری‌شدۀ لایسنس را هم داشت و نصبش روی سرور
فایلِ لایسنسِ اصلی را با کپیِ مخزن عوض می‌کرد ⇒ `The file …/functions.php is corrupted.`
از ۳.۴.۱۲ بسته‌ها آن دو فایل را ندارند، ولی فقط به‌شکل drop-in. این نسخه همان
«قالبِ کامل» را برمی‌گرداند، این بار **بدونِ** فایل‌های `ICB0` (در `tools/package.py`
assert شده) و با دو چیزِ تازه:

## تازه‌ها
1. **پوشۀ `javanseda/original/`** — نسخۀ اصلیِ فروشنده از ۱۲ قالبِ بازنویسی‌شده
   (header, footer, home, single, page, category, tag, search, 404, comments,
   artist-template, taxonomy-singer) + `taxonomy-singer.php` به‌عنوان مرجعِ
   `taxonomy-artist.php`. بایت‌به‌بایت برابر با کامیتِ `805e28c` (تأیید با sha256).
2. **قفلِ بازگشت در ابتدای ۱۳ قالب** — با یک خط در `wp-config.php`:
   `define( 'JAVANSEDA_ORIGINAL_TEMPLATES', true );`
   همۀ آن قالب‌ها به نسخۀ اصلیِ فروشنده رندر می‌شوند، بدون پاک‌کردنِ هیچ فایلی.
   (بدونِ آن خط: صفر تغییر در رفتار؛ بلوک فقط `defined()` را چک می‌کند.)
3. **`README-INSTALL-3.5.0.txt`** داخل پوسته: شیوۀ نصب و همان کلیدِ اضطراری.

## بسته‌ها (artifacts/)
- `javanseda-v3.5.0-theme.zip` — کل کدِ پوسته بدونِ `functions.php` و `RTL_License_*.php`
- `drop-in-v3.5.0.zip` — فقط ۱۵ فایلِ تغییریافته + README
- `rollback-v3.5.0.zip` — نسخۀ ۳.۴.۱۲ همان فایل‌ها

## آزمون‌ها
- `php-parser` روی هر ۱۳ قالبِ پچ‌شده و ۱۲ فایلِ `original/`: OK (۰ خطا)
- `tools/compat-check.py`: ۰ برخورد (کفِ PHP 5.6 حفظ شد)
- `tools/expected-hashes.py`: ۲۵/۲۵ هشِ موردانتظار به‌روز و مطابق
- `tools/ab-ui-sync.py`: `css/ab-ui.css` بازساخته شد (نسخه ۳.۵.۰)
- هیچ فایلِ `ICB0` در هیچ‌یک از سه بسته (assert خودکار)

## نکته‌ها
- این بسته **به‌تنهایی نصبِ از صفر نیست**: `functions.php`ِ لایسنس، `myfunctions.php`
  و OptionTree را بار می‌کند. برای سرورِ تازه، اول نسخۀ خریداری‌شده را نصب کنید.
- خط‌شکن‌ها دست‌نخورده‌اند (CRLF/LF هر فایل مثل قبل) تا diffِ گیت و ابزارهای هش دقیق بمانند.
- باگِ بازِ `/artists/` و `/artist/<slug>/` در این نسخه رفع نشده؛ برای گرفتنِ سطرِ خطا:
  `…/wp-content/javan-seda-check.php?admin=1&route=/artists/&raw=1`
