# نسخهٔ 3.1.2 — «اخبار روز» زیر هدر + فضای بیشترِ موبایل

## ۱) درخواست
> «اون قسمتی که زیر هدر گذاشتی (نوار دسترسی سریع) تبدیل بشه به **اخبار روز**، تو موبایل هم کمی فاصله با هدر بیشتر بشه و از پست‌ها و دستهٔ **NEWS** اخبار جدید رو دریافت کنه.»

## ۲) پیاده‌سازی
| فایل | نقش |
|---|---|
| `inc/ab-news-ticker.php` **(تازه)** | سه تابع: `javan_seda_news_category_id()` (پیدا کردن دسته از slug = `news` یا id) · `javan_seda_news_posts()` (WP_Query با کش ۵ دقیقه‌ای) · `javan_seda_news_ticker()` (چاپِ `<nav class="ab-quickbar ab-news">`) + شورت‌کد `[ab_news count="6"]`. **بدون هیچ هوکِ رندر** — هیچ `add_action` روی `init/template_redirect/wp_head` ندارد |
| `home.php` | همان «نوار دسترسی سریع» جای خود را به نوار اخبار داد. اگر فایل اخبار روی هاست نباشد، **همان نوار سه‌تاییِ قدیمی** چاپ می‌شود (fallback) ⇒ هیچ صفحه‌ای نمی‌میرد |
| `myfunctions.php` | `require_once` محافظت‌شدهٔ فایل اخبار (کنار include لایۀ سایدبار و سازگار ACF) |
| `style.css` | **بلوک ۲۲** (۹۹۸ براکت متوازن، `gradient = 0`) |
| `header.php` | CSS حیاتی inline: ارتفاع هدر موبایل و فاصلهٔ بالای صفحه |
| `patches/header.original-patched.php` | نسخهٔ ۲ پچِ «فایل اصلیِ خودتان» — فقط ۱۹ سطر افزوده، هیچ خطی حذف/ویرایش نشده (تأیید با diff) |
| `ab-mobile-probe.php` | بخش ۴.۱ جدید: سلامتِ منبع اخبار (دسته، تعداد، حالت fallback، تنظیم‌ها) |

## ۳) ظاهرِ نوار
```
[ برچسبِ «اخبار روز» (پس‌زمینه #185ADB، متن #FFFFFF) ] | ۳ اسفند — تیتر خبر۱  |  ۲ اسفند — تیتر خبر۲ | … | [ همهٔ اخبار → ]
```
- **دسکتاپ (≥801px):** ردیف‌ها با انیمیشن `ab-news-marquee` (۳۰ ثانیه، `linear infinite`) حرکت می‌کنند؛ روی هاور می‌ایستد؛ نسخهٔ دومِ لیست با `aria-hidden="true"` برای تکرارِ بی‌درز.
- **موبایل (≤800px):** انیمیشن خاموش؛ جایش **اسکرول لمسیِ افقی** (`overflow-x:auto` + `-webkit-overflow-scrolling:touch`) و هر خبر حداکثر `74vw` تا تیتر کوتاه نشود.
- تیتر طولانی با `text-overflow:ellipsis` بریده می‌شود (بدون شکستِ چیدمان).
- `prefers-reduced-motion: reduce` ⇒ انیمیشن کاملاً خاموش.
- حالت شب (`.night`) فقط سطح رنگ را عوض می‌کند؛ **هیچ گرادینتی** در کل بسته نیست.

## ۴) فاصله‌های تازه در موبایل (بخش «کمی فاصله با هدر بیشتر بشه»)
| مورد | قبل | حالا |
|---|---|---|
| ارتفاع `#mobiles_header` | ۶۵px | **۷۰px** |
| `body{padding-top}` در موبایل | ۶۵px | **۹۰px** (۷۰ + ۲۰ فاصله) |
| `.ab-news` (نوار اخبار) | — | `padding:8px 10px` · `margin-top:20px` · `margin-bottom:18px` |
| هدرِ **قدیمیِ** قالب (بی‌کلاس `ab-has-mh`) | `60px`ِ کهنه | `@media(max-width:800px)` ⇒ همان ۹۰px/۷۰px از طریق CSS حیاتی inline (یعنی با style.cssِ کهنه هم درست است) |

## ۵) تنظیم‌ها (اختیاری — در `wp-config.php` قبل از خط «That's all, stop editing!»)
```php
define( 'JAVANSEDA_NEWS_CATEGORY', 'news' ); // slug یا idِ دستهٔ اخبار
define( 'JAVANSEDA_NEWS_COUNT', 10 );        // ۱ تا ۲۰ (پیش‌فرض ۸)
define( 'JAVANSEDA_NEWS_CACHE', 300 );       // ثانیه؛ ۰ = بدون کش (برای تست)
```
- اگر دسته‌ای با `slug=news` **نبود** ⇒ به‌جای خطا، «تازه‌ترین نوشته‌ها» نمایش داده می‌شود و در کامنتِ HTML تذکر می‌آید (`cat=(هیچ→آخرین نوشته‌ها)`). اگر خواستید برچسبِ نوار همان نامِ دسته باشد، نامِ دسته را عوض کنید (مثلاً `news` → `اخبار`).
- برای دسته‌ای با id مشخص: `define('JAVANSEDA_NEWS_CATEGORY', 27);`

## ۶) اگر خواستید نوار اخبار در بقیۀ صفحه‌ها هم زیر هدر بیاید
فقط یک خط را **بلافاصله بعد از** `<?php get_header(''); ?>` در آن قالب اضافه کنید (هیچ کار دیگری لازم نیست):
```php
<?php if ( function_exists( 'javan_seda_news_ticker' ) ) { javan_seda_news_ticker(); } ?>
```
روی `single.php` و `page.php` این فایل‌ها در بسته هست؛ ولی اگر نمی‌خواهید آن‌ها را جایگزین کنید، همان یک خط را در فایل فعلی‌تان اضافه کنید. برای `header.php` (اگر نسخهٔ ۳.۱.x است) جای درست: **بلافاصله بعد از** `get_template_part( 'inc/mobiles_header' )` داخل شرط موبایل، یا بعد از `</header>` برای دسکتاپ.

## ۷) نصب و بازبینی
1. فایل‌های `inc/ab-news-ticker.php` + `home.php` + `myfunctions.php` + `style.css` + `header.php` را جایگزین کنید (اگر هدر هاستتان هنوز «اصلی» است، به‌جای `header.php` همان `patches/header.original-patched.php` را بگذارید — هر دو با هم نه).
2. کش افزونهٔ کش + CDN را Purge All کنید.
3. بازبینی: `View source` صفحهٔ اول باید `id="ab-news"` و `javan_seda: news ticker | cat=<id>` را داشته باشد.
4. اگر خبری نیامد: `https://ahangban.ir/ab-mobile-probe.php` ⇒ بخش **۴.۱** دقیقاً می‌گوید دسته پیدا شده یا نه و چند خبر برگشته است.
5. بعد از اتمام، `ab-mobile-probe.php` را از هاست پاک کنید.

## ۸) سلامت فنی
تجزیهٔ PHP OK (۹ فایل: home، myfunctions، header، ticker، sidebars، probe، single، page، footer) · براکت `style.css` ۹۹۸/۹۹۸ · **`gradient` = ۰** · صفر بایت قبل از `get_header()` (home.php با `?>`/`<?php` جابه‌جا شده ولی ساختار همان است) · سه مورد انتهایی `footer.php` دست‌نخورده · شناسه‌های `left_side*` و تاکسونومی `singer` تغییرناکرده · مانیفست ۸ فایل بازمحاسبه و `drop-in/` هم‌سان (۱۳ فایل ALL MATCH) · `Version: 3.1.2`.
