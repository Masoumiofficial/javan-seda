# 3.4.11 — hotfix دوم: رقم‌های فارسی + ناظرِ fatal

دو کارِ این دور؛ هیچ‌کدام مخرب نیست.

## ۱) رفعِ «\u{0\u{06F6}F4}…» روی خطِ بازدید
`javan_seda_fa_digits()` در `inc/ab-tax-artist.php` رقم‌های لاتین را با رشته‌های
`'\u{06F0}'` … (داخل **نقل‌قولِ تکی**) جایگزین می‌کرد. PHP آن را هرگز به کاراکتر
تبدیل نمی‌کند (فرمِ `\u{…` فقط داخل نقل‌قولِ دوقولی و فقط از PHP 7.0 معنا دارد)، پس
متنِ خام چاپ می‌شد؛ بدتر این‌که `str_replace` ده بار پشتِ هم اجرا می‌شد و رقم‌های
همان رشته‌های تازه را هم دوباره جایگزین می‌کرد: `\u{06F0}` → `\u{0\u{06F6}F0}`.

رفع: کاراکترِ واقعیِ فارسی داخل فایل + `strtr()` (تک‌گذر، بلندترینِ تطبیق).
در `javan_seda_artist_normalize_name()` هم `\u{200c}` دوقولی به بایت‌های UTF-8
(`"\xE2\x80\x8C"`) تبدیل شد تا نیم‌فاصله روی PHP 5.6 هم پاک شود و
تطبیقِ نامِ هنرمند در `/artist/…` درست کار کند.

## ۲) ناظرِ fatal (تا علتِ خطای پیشخوان لو برود)
`javan_seda_fatal_watch()` در ابتدای `myfunctions.php` (پیش از همهٔ `require`ها) با
`register_shutdown_function` ثبت می‌شود. اگر خطای مرگباری رخ دهد — حتی خطایی که هیچ
`try/catch` به آن نمی‌رسد (سینتکس، حافظه، فایلِ نیمه‌بالاگذاری‌شده) — این سطر نوشته
می‌شود:

    [javan-seda FATAL] Allowed memory size of … @ /home/…/themes/javanseda/inc/x.php:123 | php=7.4.33 | mem=40.0MB/128M | wp=6.6.2 | admin=1 | uri=/wp-admin/

مقصد: `error_log` هاست **و** `wp-content/javan-seda-error.log` (بدون نیاز به
`WP_DEBUG`، سقف ۶۴KB، و فقط خطاهای داخل همین پوسته در فایل نوشته می‌شود تا خطای
افزونۀ دیگر را ما به گردن نگیریم). `javan_seda_safe_log()` دورِ قبل هم حالا همان
فایل را می‌نویسد، پس همه‌چیز یک‌جا است.

## ۳) دو کلیدِ خاموش + حائلِ کامل‌تر
- `define( 'JAVANSEDA_TERM_FIELDS', false )` واقعاً کل لایه را خاموش می‌کند:
  `myfunctions.php` دیگر `inc/ab-terms-ui.php` را require نمی‌کند ⇒ نه بلوک
  «اطلاعات تکمیلی» در پیشخوان، نه بیو/توضیح‌ها در سایت. همه‌ی جای‌استفاده‌ها در
  قالب‌ها با `function_exists` گارد شده‌اند (بررسی شد: تنها استثنای بیرونی،
  `javan_seda_term_more_html` در `taxonomy-artist.php` است که خودش داخل شاخۀ
  گاردشدۀ `javan_seda_term_bio` قرار دارد) ⇒ صفحۀ خالی یا خطا نمی‌دهد.
- `define( 'JAVANSEDA_ARTIST_ACF_RULES', false )` فقط قاعدۀ موازیِ ACF را کنار می‌گذارد.
- فیلتر ACF محافظ `static $busy` گرفت (ضدِ بازگشتِ بی‌نهایت) و دو هُکِ بی‌حائلِ دیگر
  (`wp_footer` و `admin_notices`) هم از `javan_seda_safe()` رد می‌شوند.

## فایل‌ها
- `inc/ab-sidebars.php`
- `inc/ab-tax-artist.php`
- `inc/ab-terms-ui.php`
- `myfunctions.php`
- `style.css`

## راستی‌آزمایی
lint ۶۱/۶۱ فایل PHP · `expected-hashes` ۲۵/۲۵ · `verify-ot` ۱۰۰ ردیف / problems 0 ·
`compat-check` ۰ · هر سه بسته crc-ok و محتوای drop-in بایت‌به‌بایت برابرِ درخت.
