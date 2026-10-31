# javan-seda · v3.4.6 — «کامنت در دیتابیس هست ولی در فرانتِ پست نمایش داده نمی‌شود»

## ۳ خط

1. **ریشه:** حلقۀ «سایر آهنگ‌های این هنرمند» در `single.php` (خط ۵۶۹، `$second_query->the_post()`)
   هیچ `wp_reset_postdata()` بعد از خودش نمی‌گذاشت؛ وقتی به خط باکس دیدگاه (۵۹۱) می‌رسیدیم
   `global $post` و `$GLOBALS['id']` روی **آخرین آهنگِ مربوطه** ست بودند، و `comments_template()`
   همان `$post` را می‌خواند ⇒ کوئری کامنت روی نوشتهٔ اشتباه می‌رفت و نتیجه خالی بود.
2. **دروازۀ دوم (در `comments.php`):** اگر «کاربر باید وارد شود» روشن باشد، فایل **قبل از چاپ
   فهرست** یک `return` داشت ⇒ نظراتِ تاییدشده هم برای بازدیدکنندۀ خارج‌شده دیده نمی‌شد.
3. **اصلاح:** `wp_reset_postdata()` پیش از باکس دیدگاه؛ پیامِ «وارد شوید» فقط جای *فرم* را
   می‌گیرد نه جای فهرست؛ و یک کوئری پشتیبان (`get_comments()` با `status=approve`) اگر
   `have_comments()` خالی بود ولی `get_comments_number()` غیرصفر — تا نوشته‌ای که
   `comment_status=closed` و `comment_count=2` دارد هم نظراتش را نشان بدهد.

## چیزهایی که بررسی شد و **نیازی به تغییر نداشت**

| بند خواسته‌شده | نتیجه |
|---|---|
| گیت `if ( comments_open() ) comments_template();` | در این قالب **وجود ندارد** — `single.php:594` همان `comments_template()` را بی‌شرط صدا می‌زند. (در هستۀ وردپرس هم `comments_template()` فقط `if ( ! ( is_single() || is_page() || $withcomments ) \|\| empty( $post ) ) return;` دارد — هیچ گیت `comments_open()` در هسته نیست؛ از منبع `wordpress-develop/trunk` خوانده شد.) پس فرم `if ( comments_open() \|\| get_comments_number() )` لازم نبود و اضافه نشد. |
| فایل‌های `single-post.php` / `content-single.php` / `template-parts/*` | وجود ندارند؛ تنها قالبِ تک‌نوشته `single.php` است. `comments_template()` در کل قالب فقط یک‌جا صدا زده می‌شود (`single.php:594`) و جای دیگری شرطی نشده. `page.php`/`artist-template.php` اصلاً دیدگاه چاپ نمی‌کنند (رفتار والد — دست نزده). |
| وجود `comments.php` + `wp_list_comments()` + `comment_form()` | `comments.php` بود (۱٬۲۵۶ B) و `wp_list_comments( array('callback'=>'comment_loop_cd') )` را داشت؛ فرم هم `comment_form_cd()` است (نسخۀ بازطراحی‌شدۀ `comment_form()` در `myfunctions.php:218`) ⇒ فایل بازسازی نشد، فقط دروازۀ `return`ش برداشته شد. |
| CSS: `#comments` / `.comments-area` / `.comment-list` با `display:none` | **هیچ.** با پارس CSS و حذف کامنت‌ها، تنها سه `display:none` روی سلکتورهای دیدگاه وجود دارد که عمدی‌اند: `.comment-form-url` (فیلد وب‌سایت)، `.comment-meta`، `.commentlist .says` («می‌گوید»). هیچ‌کدام کانتینر را نمی‌پوشانند ⇒ CSS دست‌نخورده. |
| منطق بازدید / views / kksr | **دست نخورد.** تنها تغییرِ رفتاری، همان `wp_reset_postdata()` + بازشدن فهرست دیدگاه است. `get_field('artist')` در بلوک «آهنگ‌های مرتبط» هم از همین بازنشانی سود می‌برد (پیش‌تر `$post` اشتباه بود). |

## تغییرات دقیق

`single.php` (۵۹۱..۵۹۷ → بلوک جدید):
```php
wp_reset_postdata();                      // ◀ اصلاح اصلی
$cm_off  = function_exists('ot_get_option') ? ot_get_option('cm_off') : 'on';
$cm_has  = (int) get_comments_number() > 0;
$cm_lock = ( 'off' === $cm_off && $cm_has );
if ( $cm_lock ) { add_filter( 'comments_open', '__return_false', 99 ); }
if ( 'off' !== $cm_off || $cm_has ) { /* باکس + comments_template() */ }
if ( $cm_lock ) { remove_filter( 'comments_open', '__return_false', 99 ); }
```
یعنی «نمایش دیدگاه‌ها» در تنظیمات قالب (کلید `cm_off`) از این پس **فقط فرم** را می‌بندد؛ اگر
کسی آن را روی «خاموش» گذاشته باشد ولی نوشته کامنت داشته باشد، فهرست دیده می‌شود (سرتیتر
«دیدگاه‌ها» به‌جای «نظر خود را بنویسید»). با `cm_off=off` و صفر کامنت، دقیقاً مثل قبل هیچ باکسی چاپ نمی‌شود.
فیلتر `comments_open` بی‌درنگ بعد از `comments_template()` برداشته می‌شود ⇒ روی سایدبار/فوتر اثری ندارد.

`comments.php` (بازنوشت هموار، CRLF و کلاس‌ها حفظ شد): `$jv_have = have_comments()`، در نبودش
`get_comments()` با همان `callback=comment_loop_cd` و همان `wp_list_comments()` ⇒ DOM یکسان،
CSS تازه لازم نیست. `page_comments` روشن باشد، پشتیبان هم صفحه‌بندی را رعایت می‌کند
(`number` از `comments_per_page`، `page` از `cpage`).

## فایل‌ها (۵ فایل؛ برای رفع باگ ۲ فایل کافی است)

| فایل | حجم | لازم برای |
|---|---|---|
| `single.php` | 39٬789 B | رفع باگ (بازنشانی `$post`) |
| `comments.php` | 2٬970 B | رفع باگ (حذف `return` + کوئری پشتیبان) |
| `style.css` | 196٬785 B | فقط `Version: 3.4.6` (صحت‌سنجی نصب) |
| `css/ab-ui.css` | 86٬665 B | آینۀ `style.css` (خروجی `tools/ab-ui-sync.py`) |
| `inc/ab-sidebars.php` | 22٬604 B | به‌روزرسانی دو هاش در `javan_seda_expected_hashes()` |

## اعتبارسنجی

`php-parser` روی **۵۸/۵۸** فایل PHP OK · **۰** کامنت/متن چاپی بیرون از `<?php ?>` روی ۴۳ فایل ·
**۰** BOM (و CRLF هر دو فایل حفظ شد: `single.php` ۶۹۸→۷۱۳ خط (CRLF حفظ شد)، `comments.php` ۷۲ CRLF و ۰ LF تنها) ·
`expected-hashes --check` **۱۷/۱۷** · `tools/compat-check.py` **۰** · پارس CSS ۰ خطا (CSS تغییر نکرد) ·
هیچ سلکتور تازه‌ای اضافه نشد، پس `style.css` جز خط شمارهٔ نسخه تغییری ندارد.
نام متغیرهای تازه (`$cm_lock`, `$cm_has`, `$jv_*`) در کل قالب یکتا هستند (بدون تداخل).

**تست‌ناپذیر از این سندباکس (صریح):** PHP روی این ماشین نصب نیست و سایت از اینجا قابل‌دسترس نیست ⇒
رفتار بازنویسی‌شده با خواندن منطق هستۀ وردپرس ( از `wordpress-develop/trunk`: `comments_template()`,
`get_comments_number()`, `have_comments()` ) و پیاده‌سازی دستی جدول شرایط ثابت شده، نه با رندر.
روی هاست، این سه حالت را امتحان کنید: (۱) پستِ `closed` با ۲ کامنت ⇒ فهرست می‌آید، فرم نه؛
(۲) `comment_registration` روشن + بازدیدکنندۀ مهمان ⇒ فهرست می‌آید + پیام «وارد شوید»؛
(۳) `cm_off=off` ⇒ با کامنت، فقط فهرست؛ بدون کامنت، هیچ.

## بازگشت

`artifacts/rollback-v3.4.6.zip` = بایت‌های v3.4.5 برای همین ۵ فایل (با پسوند `.3.4.5`).
ساده‌تر: چیزی برنگردانید — اگر فقط می‌خواهید باکس دیدگاه مثل قبل شود، در «تنظیمات پوسته ←
صفحۀ دانلود آهنگ ← نمایش دیدگاه ها» همان toggle را روی خاموش بگذارید.
