# نقشۀ تغییرات — نسخۀ 3.3.3 (ریل لمسی موبایل · کشوی منو · دیدگاه‌ها · ۴۰۴ در حالت روشن)

تاریخ: 2026-10-18 · زنجیره: `3.3.2` → **`3.3.3`** · چهار مورد، همه گزارشِ خودِ مالک پس از نصب 3.3.2

| # | گزارش مالک | علت ریشه‌ای (پیدا‌شده از فایل‌ها) | رفع |
|---|---|---|---|
| ۱ | «صفحۀ اصلی در موبایل، بخش‌ها مثلاً خواننده‌ها اسلایدری باشند؛ الان هر ردیف یک دونه و کلی فضا گرفته» | `js/scripts.js` روی `.artists_slider` و `.vip_top_slider` فراخوانی `owlCarousel` دارد، اما **`owl.carousel.css` اصلاً در قالب نیست** (در `css/` فقط `icofont.min.css` و `ab-glass.css` است) و تکه‌های owl در `style.css` ناقص‌اند ⇒ `.owl-carousel` عملاً `owl-loaded` نمی‌شود و آیتم‌ها بلوکی روی هم می‌چینند. بدتر: در `@media (max-width:450px)` خودِ قالب `.artist_boxs a,.artist_boxs a img{width:100%}` را نوشته ⇒ **یک خواننده در هر ردیف، تمام‌عرض**. | ریل بومی با `scroll-snap` (لمسی، صفر کتابخانه، صفر JS) برای ریل‌های `body.home` و `.ab-related`؛ گرید فشرده برای ریل خواننده‌ها در دسکتاپ؛ **دو ستون** برای فهرست‌های بلند (صفحۀ خواننده، نتایج جست‌وجو، بایگانی‌ها). همه با قید `:not(.owl-loaded)` ⇒ اگر روزی owl واقعی لود شود، owl کنترل را پس می‌گیرد و چیزی دو‌تا نمی‌شود |
| ۲ | «منو تو موبایل کلا رفته و فقط شبکه‌های اجتماعی نمایش داده می‌شه» | **دو علت، یکی از خودمان:** (الف) `backdrop-filter` روی `#mobiles_header` که در 3.3.0 اضافه شد، «ظرف مرجع» فرزندانه `position:fixed` را عوض می‌کند (قانون filter/transform/backdrop-filter) ⇒ `.mobiles_menu` و `.bodydeactive` که `position:fixed;height:100%` هستند به جعبۀ ۶۵px همان هدر گیر می‌کردند و کشو عملاً ناپدید می‌شد؛ (ب) `inc/mobiles_header.php` یک `wp_nav_menu(array('theme_location'=>'mobiles_menu'…))` بدون هیچ fallback داشت؛ اگر آن مکان در پیشخوان به منویی وصل نباشد، وردپرس به فهرست «برگه‌ها» می‌رود و سایتی که فقط «آهنگ» دارد یعنی لیست خالی | (الف) شیشه به `#mobiles_header::before` منتقل شد (`background:transparent` روی خودِ هدر) ⇒ هیچ فیلتری روی والدِ fixed نیست و کشو دوباره تمام‌قد می‌شود؛ (ب) زنجیرۀ fallback: `mobiles_menu` ← `top_menu` (همان منوی دسکتاپ) ← `wp_list_pages` ← دو پیوند همیشگی، به‌همراه `fallback_cb => false` و محافظت `class_exists('CSS_Menu_Maker_Walker')` (نبودِ آن کلاس، `new CSS_Menu_Maker_Walker()` را به خطای کشنده تبدیل می‌کرد) |
| ۳ | «بخش کامنت‌ها هم یکم بد شده، درستش کن» | `.commentlist li{background:rgba(255,255,255,.2);border:1px solid rgba(0,0,0,.4);border-radius:5px}` روی صفحۀ سفید یعنی بی‌رنگ با حاشیۀ خاکستریِ خشن؛ `.fn a{color:#49962d !important}` سبزِ خارج از پالت؛ `.comment-meta{display:none}`؛ `.comment-content{float:left;text-align:justify}`؛ تورفتگی با `.depth-2..5{width:97%}` (نه با indent)؛ در شب همان حاشیۀ تیره روی کارت تیره | بلوک ۲۷: کارت با `var(--ab-surface)` + مرز `var(--ab-line)` + شعاع توکنی، آواتار دایره‌ای، نام کاربر با لهجۀ آبی، تاریخ/«در انتظار تأیید» در خط دوم، `.children` با `padding-inline-start` (راست‌به‌چپ، بدون دست‌زدن به DOM)، دکمۀ «پاسخ» به‌شکل pill، `#respond` داخل ظرف با `grid` (فیلدها در موبایل تمام‌عرض)، حلقۀ فوکوس آبی روی inputها، سازگاری صریح `.night`؛ دو قاعدۀ تکراری بلوک ۲۶ حذف شد تا **یک منبع حقیقت** بماند |
| ۴ | «۴۰۴ در مود روشن رنگش سفیده و هیچی مشخص نیس» | در `ab-glass.css` یک قاعدۀ گروهی، `background: var(--ab-glass-bg-strong)` (در حالت روشن = سفید) را روی `.ab-404-search input · .ab-404-search button · .ab-404-links a · .ab-404-home · .ab-404-back` نشانده بود؛ چون این فایل **بعد از** استایل قالب enqueue می‌شود، آبیِ دکمۀ «بازگشت به صفحۀ اصلی» را هم سفید کرد درحالی‌که `color:#fff` همان‌جا مانده بود ⇒ سفید روی سفید | آن قاعدۀ گروهی از `ab-glass.css` حذف شد و مالکیت فرم/دکمه به بلوک ۲۷ (در `style.css`) داده شد: input سطح روشن + متن `--ab-text` + `placeholder` کم‌رنگ + حلقۀ فوکوس؛ دکمۀ جست‌وجو و `.ab-404-home` آبی با متن سفید و hover؛ `.ab-404-links a` و `.ab-404-back` سطحی با مرز نازک؛ `.night` هم صریح (`rgba(255,255,255,.06)` + مرز `rgba(255,255,255,.16)`) ⇒ در هر دو حالت دیده می‌شوند و از هم تفکیک می‌شوند |

## فایل‌ها (`git diff --numstat`)

| فایل | تغییر | نقش |
|---|---|---|
| `javanseda/style.css` | +۱۳۶/−۴ | **بلوک ۲۷**: ریل لمسی موبایل، گرید خواننده‌ها، دیدگاه‌ها، ۴۰۴ در هر دو حالت، ابعاد/کنتراست کشوی منو · `Version: 3.3.3` · حذف دو قاعدۀ تکراری بلوک ۲۶ |
| `javanseda/css/ab-glass.css` | +۱۹/−۱۲ | شیشۀ هدر موبایل → `::before` (رفع گرفتاری fixed) · حذف قاعدۀ گروهی ۴۰۴ · سپردن دیدگاه‌ها به `style.css` |
| `javanseda/inc/mobiles_header.php` | +۳۳/−۱ | زنجیرۀ fallback منوی کشویی + محافظت walker |
| `preview/glass-ui.html` | — | هدر موبایل + کشوی واقعی، ریل خواننده‌ها، بخش دیدگاه‌ها و فرم، راهنمای «پنجره را زیر ۷۶۸px بیاورید» |

## `!important` — فقط آن‌جا که چاره‌ای نبود (۴۴۳ → ۴۴۹؛ شش مورد، همه با دلیل)
- `.artists_slider.owl-carousel .owl-item{width:88px !important}` (و `80px` در ≤۴۰۰px) — owl عرض را **inline** روی `.owl-item` می‌نویسد و هیچ شیت‌استایل بدون `!important` نمی‌تواند inline را بشکند.
- `#comments .commentlist li .avatar{width/height/border-radius … !important}` — قالب روی `#comments .avatar` خودش `!important` داشت؛ با selector اختصاصی‌تر + `!important` خنثی شد تا آواتار دایره‌ای و هم‌اندازه شود.
- `.commentlist .fn a{color:var(--ab-blue-accent) !important}` — قالب `#49962d !important` سبز گذاشته بود.

بقیۀ کار بدون `!important` انجام شد: بلوک ۲۷ در انتهای `style.css` است (بعد از همه قواعد قالب) و لایۀ شیشه بعد از استایل قالب enqueue می‌شود. `gradient(` همچنان **۰** است.

## سازگاری
- صفر فیلتر سراسری تازه، صفر تغییر تاکسونومی/کوئری/دیتابیس؛ `the_content` و `template_include` دست‌نخورده (همان درس «باکس خالی»).
- `header.php`, `footer.php`, `single.php`, `home.php`, `index.php`, `js/*`, `functions.php` (قفل ionCube) تغییر نکردند؛ تنها `inc/mobiles_header.php` (یک خط → ۳۳ خط محافظت‌شده).
- با `JAVANSEDA_GLASS_UI=false` یا حذف `css/ab-glass.css`: ریل‌ها، دیدگاه‌ها، ۴۰۴ و کشوی منو **باز هم درست‌اند** (مالکیتشان در `style.css` است) و هدر موبایل هم همان پس‌زمینۀ سفید/ذغالیِ قالب را می‌گیرد.
- قالب کشو را با `margin:0 -262px` می‌بندد؛ عرض را `min(82vw,256px)` گذاشتم تا هیچ‌وقت تکه‌ای از لبه بیرون نزند.

## بررسی‌ها
`css-tree` ⇒ **۰ خطای پارس** در `style.css` و `css/ab-glass.css` · `tools/check.js` (php‑parser) ⇒ **OK** روی `inc/mobiles_header.php` · `single.php` · `404.php` · `inc/ab-glass.php` · `myfunctions.php` · `tools/compat-check.py javanseda` ⇒ **`0 hit`** (کف PHP 5.6) · بالانس براکت CSS · LF در CSS‌ها و CRLF در PHP حفظ شد · `sha1sum` یکسان `javanseda/*` ↔ `drop-in/*` (۸ فایل) · `new Function()` روی JS دمو OK و HTML دمو بالانس.

**اجرا نشد (محدودیت محیط، صریح):** در این سندباکس PHP+MySQL و مرورگر/headless Chrome نیست ⇒ **owl، کشوی منو، اسکرول لمسی، کنتراست رندر‌شده و Lighthouse تست نشده‌اند**. برای بازبینی محلی `preview/glass-ui.html` را باز کنید و پنجره را زیر ۷۶۸px بیاورید (همان CSS واقعی این نسخه)؛ نتیجهٔ نهایی را روی هاست/گوشی ببینید. اگر owl روی هاست کار کند، قید `:not(.owl-loaded)` خودکار کنار می‌کشد و owl جایش می‌نشیند.
