# «The file …/functions.php is corrupted.» — علتِ واقعی: نسخۀ PHP هاست (نه کدِ پوسته)

## نتیجه
`javanseda/functions.php` و `javanseda/RTL_License_41d6c5f704e3a746.php` فایل‌های
رمزگذاری‌شدۀ **PHP Encoder / ionCube** هستند (سرآمدِ `<?php //ICB0 …`، «Encrypted at
2023-08-14»، محصولِ rtl-theme.com). لودرِ ionCube فقط در بازۀ نسخۀ PHPای که فایل
برای آن رمزگذاری شده این فایل‌ها را باز می‌کند. روی **PHP 8.4** (و به‌طورکلی 8.3+)
لودر فایل را decode نمی‌کند، بدنهٔ خامِ فایل اجرا می‌شود و این سطر کار را تمام می‌کند:

    if ( extension_loaded( 'ionCube Loader' ) ) { die( 'The file ' . __FILE__ . " is corrupted.\n" ); }

⇒ هر کپیِ این فایل (نسخۀ مخزن، نسخۀ بکاپ، نسخۀ اورجینال) روی PHP 8.4 همین پیام را
می‌دهد. **به‌همین دلیل برگرداندن فایل از بکاپ چیزی را درست نکرد.** تقصیر لایۀ
«هنرمند»، فیلدهای ترم، ACF یا بسته‌های 3.4.9 تا 3.4.12 نیست.

## شواهد (از debug.logِ خودِ سایت، ۲۷۴ سطر / ۱۲ دقیقه)
- **هیچ سطرِ لاگ به `themes/javanseda` اشاره نمی‌کند** و هیچ `Fatal error` در لاگ نیست؛
  چون `die()` خطا نیست، پس لاگی هم نمی‌گذارد — همان‌قدر که پیام را روی صفحه چاپ می‌کند.
- ۱۲×۲ پیام `PHP Deprecated: … Implicitly marking parameter $x as nullable is
  deprecated` ⇒ این deprecations **مخصوص PHP 8.4** است؛ یعنی همان چیزی که فرض را تأیید
  می‌کند: سرور روی PHP 8.4 است (افزونۀ kk-star-ratings، ACF PRO و RTL-CareUnit هم
  همین را گزارش می‌دهند).
- `https://ahangban.ir/wp-json/` (درخواستِ کاملاً کش‌نشدہ) همان یک سطرِ `corrupted` را
  برمی‌گرداند، ولی صفحۀ اصلی باز می‌شود ⇒ کشِ صفحهٔ اصلی، وضعیتِ مردهٔ PHP را می‌پوشاند.
- بقیۀ سطرهای لاگ (بی‌خطر، ولی قابل رفع توسط صاحب‌افزونه‌ها): `_load_textdomain_just_in_time`
  از `wptelegram-pro` (زورِ WP 6.7)، `Undefined array key "REMOTE_ADDR"` در
  `RTL-CareUnit/includes/classes/Helper.php`، و Puc v4p6 در پوشۀ `novin-update` افزونۀ ACF.

## کاری که باید کرد (به همین ترتیب)
1. **نسخۀ PHP سایت را پایین بیاورید:** 8.1 یا 8.2 (اگر نشد 8.0 و بعد 7.4).
   - DirectAdmin (پنل شماست: مسیر `/home/…/domains/ahangban.ir/public_html`):
     *Account/Domain Settings → PHP Version Selector* و انتخاب `PHP-8.1` (یا `multi`)
     یا از CLI هاست: `echo 8.1 > ./user.ini.d/php.ini` — معمولاً همان انتخابگر کافی است.
   - cPanel: *Select PHP Version* / *MultiPHP Manager*.
2. **برای همان نسخۀ PHP، افزونۀ ionCube Loader فعال باشد.** اگر با انتخاب 8.1 باز هم
   پیام «needs to be installed» دیدید، یعنی لودر روی آن نسخه نصب نیست — یک تیکت به
   هاست: «برای PHP 8.1 افزونۀ ionCube Loader را فعال کنید».
3. **تست:** `https://ahangban.ir/wp-json/` باید JSON بدهد؛ بعد `wp-admin` را باز کنید.
4. اگر هاست فقط PHP 8.3+ می‌دهد و پایین‌آمدن ممکن نیست: از فروشندهٔ قالب buildِ
   سازگار با PHP 8.3/8.4 را بخواهید (رمزگذاریِ مجدد با PHP Encoder جدید). هیچ تغییر
   کدی در پوستهٔ ما این مشکل را حل نمی‌کند، چون `functions.php` تنها ورودیِ پوسته است
   و تا باز نشود، هیچ‌چیز (حتی `myfunctions.php` و لایه‌های ما) بارگذاری نمی‌شود.

## چراغ‌قوه (همین تشخیص را خودکار می‌گوید)
`tools/diag/javan-seda-check.php` را در `wp-content/` بگذارید و باز کنید:
- `https://ahangban.ir/wp-content/javan-seda-check.php` ⇒ بخشِ «PHP، لودرِ لایسنس و
  فایل‌های رمزگذاری‌شده» نسخۀ PHP، وجود/نسخۀ ionCube Loader، اندازه + `ICB0 ✓/✗` +
  `sha256` دو فایل لایسنس و **نتیجه** را چاپ می‌کند (قبل از این‌که وردپرس بمیرد).
- `?raw=1` همان خروجی را بدون HTML می‌دهد (برای کپی‌کردن در چت).
- `?admin=1&full=1` کدهای پیشخوان و خودِ `wp-admin/admin.php` را هم اجرا می‌کند.
پس از استفاده فایل را پاک کنید.

## دو نکتهٔ بهداشتی
- `debug.log` را بعد از عیب‌یابی خاموش کنید (`WP_DEBUG`/`WP_DEBUG_LOG`)؛ و فایل
  `debug (2).log` را که در مخزن عمومی آپلود شده پاک کنید — مسیرِ مطلقِ سرور،
  نامِ افزونه‌ها و ساختار را لو می‌دهد.
- یادآوری بسته‌بندی (رفعِ 3.4.12): `tools/package.py` هر فایلِ `ICB0` را از بسته‌ها
  بیرون می‌گذارد و `.gitattributes` آن فایل‌ها را `binary` کرده است؛ ولی حتی اگر
  فایل را هم دقیقاً برگردانید، روی PHP 8.4 نتیجه همان است — کلیدِ مسئله نسخۀ PHP است.
