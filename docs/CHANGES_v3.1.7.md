# نقشۀ تغییرات — نسخهٔ 3.1.7 (دور ۱۲.۲ · hotfix قطعیِ «خطای مهم سایت»)

> # ⛔ این نسخۀ «لایۀ محبوبیت» نصب نشود
> مالک در تاریخ ۱۶ اکتبر ۲۰۲۶ تصمیم گرفت **همه‌چیز به `v3.1.4` برگردد**. فایل‌های `drop-in/` و زیپ‌های
> 3.1.5 تا 3.1.8 از برنچ حذف شده‌اند و فقط همین سند برای تاریخچه مانده است. وضعیت فعلی مرجع = تگ `v3.1.4`
> (سورس قالب و `drop-in/` روی همین برنچ، بایت‌به‌بایت با تگ یکسان است). علت لغو: یک `add_action` با تابع
> تعریف‌نشده در `inc/ab-views.php` کل سایت را روی PHP 8 می‌خواباند (خطای کشندۀ زمان `init`).


مخزن: `Masoumiofficial/javan-seda` · تگ `v3.1.7` · PR `#3`
این دور هم فقط **ایمنی** است؛ قابلیت/ظاهر تازه‌ای اضافه نشده.

---

## ۱) علتِ قطعی — از روی `debug (1).log` که گذاشتید

```
PHP Fatal error:  Uncaught TypeError: call_user_func_array(): Argument #1 ($callback) must be a valid
callback, function "javan_seda_charts_add_shortcode" not found or invalid function name
in /home/etehadya/.../wp-includes/class-wp-hook.php:353
#2 wp-settings.php(779): do_action('init')      ← روی هر درخواست، و روی wp-cron هم
```

یعنی: در `inc/ab-views.php` (نسخۀ 3.1.5) ثبت شورت‌کد `[ab_charts]` با نامِ تابعی به هوک `init` داده شده بود که **آن تابع هیچ‌جا تعریف نشده بود** — در دور ۱۲ هنگام تبدیلِ بستۀ `static function(){…}` به تابع نام‌دار، خط `add_action( 'init', 'javan_seda_charts_add_shortcode' )` اضافه شد ولی خودِ تابع نوشته نشد. روی PHP 7 فقط یک اخطار بود؛ روی **PHP 8** که هاست شماست، یک `TypeError` کشنده ⇒ کل سایت و پیشخوان.

نکتۀ مهم برای دفعات بعد: صفحهٔ «خطای مهم» فقط با `debug.log` یا ایمیل Recovery Mode تشخیص داده می‌شود؛ کنسول مرورگر (آنچه فرستادید: `MaxListenersExceededWarning` ، `contentscript.js` ، `MozBar`) کارِ افزونۀ مرورگر است و به PHP ربطی ندارد.

**همچنین در لاگ:** `PHP Notice: Only variables should be passed by reference in inc/option-tree/ot-loader.php on line 329` — مربوط به خودِ option-tree است (قالب)، بی‌ضرر، و از کارهای ما نیست. اگر خواستید، در دور بعد همان یک خط را هم تمیز می‌کنم.

## ۲) اصلاحات این دور

| فایل | کار |
|---|---|
| `inc/ab-views.php` | ثبت شورت‌کد به `add_shortcode( 'ab_charts', 'javan_seda_charts_shortcode' )` تبدیل شد — **بدون هوک `init`، بدون نامِ معلق**، و داخل `if ( function_exists( 'add_shortcode' ) && function_exists( 'javan_seda_charts_shortcode' ) )`. نتیجه: اگر فایل لایه هم نبود یا تابعی جابه‌جا شد، نه شورت‌کد خطا می‌دهد و نه سایت. |
| `inc/ab-ratings.php` | `[ab_rating]` هم از حالت «init + تابع افزاینده» به همین الگو آمد (پیش‌گیری از همان نقص). |
| هر ۵ لایه (`ab-views`, `ab-ratings`, `ab-perf`, `ab-seo`, `ab-sidebars`) | **۱۹ ثبتِ هوک** یک‌خطی و یکنواخت شدند: `if ( function_exists( 'cb' ) ) { add_action( … ); }` ⇒ کلاسِ «callback تعریف‌نشده» دیگر در کد ما وجود ندارد (اسکن شد: ۰ مورد در کل قالب). |
| `myfunctions.php` | دروازۀ `javan_seda_layer_loadable()` ارتقا یافت: علاوه بر «ساختار تازه‌تر از PHP»، حالا **dangling callback** را هم می‌گیرد و در آن صورت فقط همان لایه خاموش می‌شود، سایت بالا می‌ماند. برایی دقت، فایل با `token_get_all()` شکافته می‌شود (نه regex) تا آپوستروف داخل کامنت، توابع واقعی را «تعریف‌نشده» نشان ندهد. کشِ داوری بر پایهٔ `filemtime` در option `javan_seda_gate_cache` ⇒ در حالت عادی فقط یک `get_option` + چند `filemtime`. خاموشی: `define( 'JAVANSEDA_GATE_OFF', true );` |
| `ab-rescue.php` | بخش تازهٔ «ارجاع به تابع تعریف‌نشده» برای هر فایل لایه با شمارۀ خط (همان چیزی که این‌بار سایت را خواباند، از این پس در گزارش نجات قابل دیدن است). |
| `style.css` | `Version: 3.1.7` (cache-bust) + یادداشتِ علتِ خطا در انتهای فایل، داخل کامنت. |
| `tools/compat-check.py` | پاس دوم اضافه شد: هر `add_action/add_filter/add_shortcode` با callback رشته‌ای، باید تابعش در همان فایل باشد یا با `function_exists` پیچیده شده باشد. **قبل از هر build اجرا می‌شود**؛ الان: `compat-check: 0 hit(s)`. |

## ۳) اگر هنوز صفحهٔ «خطای مهم» را می‌بینید

1. **F5 چندباره / Purge All** — کشِ HTMLِ خراب هم همان صفحه را نگه می‌دارد.
2. فایل‌های این دور را بگذارید (حداقل `inc/ab-views.php` + `myfunctions.php`).
3. اگر بالا نیامد: `…/wp-content/themes/javanseda/ab-rescue.php?scan=1` را باز کنید (مستقل است، وردپرس را load نمی‌کند) و خروجی‌اش را برایم بفرستید؛ یا ۱۰ خط آخر `wp-content/debug.log` را.
4. برای بالا آمدنِ فوری سایت در هر حالت: `inc/ab-views.php` را به `inc/ab-views.php.off` تغییر نام دهید (فایل‌های لایه همگی با `is_readable()` صدا زده می‌شوند ⇒ نبودنشان خطا نمی‌دهد؛ فقط `[ab_charts]` و نشانِ بازدید موقتاً کار نمی‌کند).

## ۴) فایل‌ها (۴ فایل = کمینۀ این دور)

پایه: `wp-content/themes/javanseda/` · لینک خام: `https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.1.7/drop-in/<مسیر>`

| فایل | بایت | sha256 (۱۶) | وضعیت |
|---|---|---|---|
| `inc/ab-views.php` | 18991 | `633cd5d01defbdae` | **لازم — فایلِ علتِ خطا** |
| `myfunctions.php` | 32345 | `9712de791b3bfba4` | **لازم — دروازۀ جدید** |
| `inc/ab-ratings.php` | 12554 | `8bdaec5ccb40e6fe` | لازم (همان اصلاح برای `[ab_rating]`) |
| `style.css` | 117721 | `c943639a06d4e5af` | لازم (`Version: 3.1.7`) |
| `inc/ab-perf.php` | 19929 | `b4b91567398e649b` | پیشنهادی (۱۹ ثبت محافظت‌شده) |
| `inc/ab-seo.php` | 23262 | `26045f63648c2f8b` | پیشنهادی |
| `inc/ab-sidebars.php` | 22934 | `1e17c698c815cf71` | اختیاری (مانیفست ۱۹ فایل) |
| `ab-rescue.php` | 12371 | `0b8aa931f1e997fa` | پیشنهادی (ابزار نجات) |
| بقیۀ `drop-in/` | — | — | بدون تغییر از v3.1.6 |

زیپ‌ها: `artifacts/drop-in-v3.1.7.zip` · `artifacts/javanseda-v3.1.7-theme.zip` · `artifacts/rollback-v3.1.7.zip` · `artifacts/drop-in-explained-v3.1.7.zip`.

## ۵) سلامت فنی

- `node php-parser` روی هر فایل تغییریافته **OK**: `myfunctions.php` (۴۴ نود) · `inc/ab-views.php` (۱۹) · `inc/ab-ratings.php` (۱۴) · `inc/ab-seo.php` (۱۷) · `inc/ab-perf.php` (۱۴) · `inc/ab-sidebars.php` (۱۵) · `ab-rescue.php` (۳۷) · `ab-mobile-probe.php` (۱۷۵).
- `python3 tools/compat-check.py javanseda` ⇒ **۰ مورد** (هم ساختار نسخه‌ای، هم dangling callback، هم `WP_Error` cast).
- اسکن جداگانهٔ «کد خالص» کل قالب: `live dangling callbacks = 0` · `static function = 0` · `?? / ?-> / fn() / match() = 0`.
- `style.css`: براکت ۱۰۸۳/۱۰۸۳ · `gradient` ۰ · پالت `#FFFFFF / #185ADB / #111111` · بدون متن بیرون از کامنت.
- قاعده‌ها: بدون فیلتر روی `the_content`/`template_include` · بدون دست‌زدن به تاکسونومی/هوک سراسری · صفر بایت قبل از `get_header()` · سه مورد انتهایی footer · هدر موبایل رندر و فقط با `@media (max-width:800px)` مخفی می‌شود.

## ۶) بازگردانی

`artifacts/rollback-v3.1.7.zip` = نسخۀ v3.1.6 از `myfunctions.php` · `inc/ab-views.php` · `inc/ab-ratings.php` · `inc/ab-perf.php` · `inc/ab-seo.php` · `style.css` · `inc/ab-sidebars.php` + `drop-in-v3.1.6.zip`.
⚠ بازگشت به v3.1.5/3.1.6 یعنی همان باگِ `javan_seda_charts_add_shortcode` برمی‌گردد؛ اگر خواستید فقط لایۀ بازدید را خاموش کنید، `inc/ab-views.php` را `.off` کنید (بی‌خطر).
