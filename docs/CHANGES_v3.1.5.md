# نقشۀ تغییرات — نسخهٔ 3.1.5 (دور ۱۲ · «لایۀ محبوبیت»)

> # ⛔ این نسخۀ «لایۀ محبوبیت» نصب نشود
> مالک در تاریخ ۱۶ اکتبر ۲۰۲۶ تصمیم گرفت **همه‌چیز به `v3.1.4` برگردد**. فایل‌های `drop-in/` و زیپ‌های
> 3.1.5 تا 3.1.8 از برنچ حذف شده‌اند و فقط همین سند برای تاریخچه مانده است. وضعیت فعلی مرجع = تگ `v3.1.4`
> (سورس قالب و `drop-in/` روی همین برنچ، بایت‌به‌بایت با تگ یکسان است). علت لغو: یک `add_action` با تابع
> تعریف‌نشده در `inc/ab-views.php` کل سایت را روی PHP 8 می‌خواباند (خطای کشندۀ زمان `init`).


مخزن: `Masoumiofficial/javan-seda` · PR `#3` · تگ `v3.1.5` · بعد از `v3.1.4` (لایۀ دیده‌شدن)
مبنای این دور: `docs/AUDIT_2026-10.md` بند ۵ (و جدول «وضعیت اجرا — دور ۱۲» در انتهای همان سند)

---

## ۱) ریشهٔ مشکل

قالب کلید meta به نام `views` را **فقط می‌خواند** — در `inc/mostviews_posts.php` (بلوک «پربازدیدترین آثار ماه» صفحۀ اصلی) و در ابزارک «(اختصاصی) موسیقی محبوب (بازدید)» — ولی هیچ کد آن را **نمی‌نوشت**. در بازدید زنده هم تأیید شد: تیتر هست و **صفر** مورد زیرش. پس سایت هیچ سیگنال محبوبیتی نداشت: نه چارت، نه مرتب‌سازی، نه امتیاز، نه `AggregateRating` برای ستاره‌های گوگل.

## ۲) `inc/ab-views.php` (تازه · 18,016 بایت)

- **نوشتنِ همان `views`** ⇒ به‌محض نصب، بلوک صفحۀ اصلی و ابزارک پر می‌شوند (هیچ فایل دیگری لازم نیست).
- دو کلید تازه: `views_m` (بازدید همین ماه) و `views_ym` (مُهر `YYYYmM`) تا «چارت ماه» واقعاً ماهانه باشد و هر ماه خودش را شروع کند.
- **سازگار با کش:** شمارش داخل HTML انجام نمی‌شود؛ یک اسکریپت کوچک (`#ab-views-js`) بعد از لود با `navigator.sendBeacon` (fallback: `fetch`) به `admin-ajax.php?action=ab_view` POST می‌کند ⇒ صفحه همچنان کش‌شده سرو می‌شود، ولی شمارش واقعی است.
- **ضدتقلب:** nonce مخصوص هر نوشته + پنجرۀ تکرار (پیش‌فرض ۶ ساعت، به‌ازای IP+نوشته، با transient) + ردّ ربات‌ها/UA تهی + ردّ کاربر لاگین‌شده (پیش‌فرض روشن) + فقط `post` منتشرشده + پاسخ بی‌بدن (۲۰۴) تا کنسول مرورگر تمیز بماند.
- IP خام نگه داشته نمی‌شود (hash ۲۴ کاراکتری) ⇒ سازگار با حریم خصوصی؛ اگر پشت CDN هستید `CF-Connecting-IP` / `X-Forwarded-For` رعایت می‌شود.
- `javan_seda_views_month_key()` : اگر دست‌کم ۳ نوشته برای همین ماه داده داشته باشند `views_m`، وگرنه `views` (تا بلوک خالی نماند) — نتیجه ۱ ساعت کش می‌شود.
- `javan_seda_views_badge()` : نشان «N بازدید · M این ماه» با رقم فارسی.
- شورت‌کد: `[ab_charts period="month|week|all" count="10" cat="" cols="1|2" title="…"]` با کلاس‌های خودِ قالب (`song_boxs`/`songs_title_box`)، رتبهٔ ۱–۳ لهجه‌دار، تصویر ۴۶px lazy، و شمارۀ بازدید. اگر هیچ داده‌ای نبود، چیزی چاپ نمی‌کند (تیترِ بی‌مورد نه).
- تنظیم‌ها: `JAVANSEDA_VIEWS` · `JAVANSEDA_VIEWS_DEDUPE_HOURS` · `JAVANSEDA_VIEWS_SKIP_LOGGED` · `JAVANSEDA_CHART_MONTH`.

## ۳) `inc/ab-ratings.php` (تازه · 12,386 بایت)

- امتیاز ۱ تا ۵ با **فرم POST روی همان نشانی** (هندل در `init` و سپس 302 → `#ab-rate`) ⇒ بدون JS کار می‌کند و چون POST است، از کش سرو نمی‌شود.
- `rating_avg` (دو رقم اعشار) + `rating_count` · یک رأی به‌ازای IP/نوشته (پیش‌فرض ۳۰ روز) · honeypot (`ab_hp`) + nonce · بازۀ ۱..۵ اعتبارسنجی می‌شود و ربات فقط «وانمود» به موفقیت می‌کند.
- نمایش: میانگین + ستاره‌های پرشده (با عرض متحرک — **بدون گرادینت**) + تعداد رأی؛ اگر رأی داده باشید پیام «ثبت شده» و فرم حذف می‌شود.
- JSON-LD: `@type: WebPage` با `@id`/`url` نشانیِ نوشته و `aggregateRating` (bestRating 5 / worstRating 1 / ratingCount) — عمداً `MusicRecording` خودِ قالب دست‌نخورده مانده تا گوگل دو موجودیتِ متناقض روی یک URL نبیند.
- شورت‌کد `[ab_rating id=""]` برای هر جا که بخواهید.
- تنظیم‌ها: `JAVANSEDA_RATINGS` · `JAVANSEDA_RATE_IP_DAYS` · `JAVANSEDA_RATE_LD`.

## ۴) سه فایل ویرایش‌شده

- `inc/mostviews_posts.php` (تازه در drop-in، 1,563 بایت): اگر `views_m` داده داشته باشد ⇒ رتبه‌بندی ماهانه روی **کل کاتالوگ**؛ وگرنه دقیقاً رفتار قبلی (بازدیدِ کل + فقط آثار ۳۰ روز اخیر). `esc_html` روی عنوانِ تنظیمات هم اضافه شد.
- `single.php` (34,924 بایت): دقیقاً پیش از بلوک نظرات، دو فراخوانی محافظت‌شده: `javan_seda_views_badge()` و `javan_seda_rating_block()`. اگر فایل لایه‌ها روی هاست نباشد، این دو خط هیچ خروجی/خطایی تولید نمی‌کنند (الگوی همیشۀ پروژه).
- `myfunctions.php`: بارگذاری محافظت‌شدۀ دو لایۀ تازه (کنار سه لایۀ قبلی) · `style.css` → `Version: 3.1.5` + **بلوک ۲۴**.

## ۵) یافتۀ جدید: ۴ آیکن که هیچ‌وقت دیده نمی‌شدند

با مقایسۀ «کلاس‌های `icofont-*` در php» و ۴۹۶ قاعدۀ `css/icofont.min.css`:
`icofont-microphone-alt` (footer.php · home.php · 404.php) · `icofont-music-notes` (footer.php · home.php) · `icofont-arrow-left` (inc/ab-news-ticker.php) · `icofont-fire-alt` (home.php — «پربازدیدترین‌ها») **قاعده‌ای نداشتند** ⇒ جای آیکن خالی بود.
رفع در انتهای بلوک ۲۴ با codepoint‌های **موجودِ همان فونت** (بدون تغییر فایل markup):

```css
.icofont-microphone-alt:before{content:"\ea47";}  /* میکروفن */
.icofont-music-notes:before{content:"\eca1";}     /* نت */
.icofont-arrow-left:before{content:"\ea93";}      /* فلش چپ */
.icofont-fire-alt:before{content:"\ef21";}        /* چشم — برای «پربازدیدترین‌ها» */
```

## ۶) فایل‌ها (drop-in · ۲۳ فایل) + امضا

پایۀ مقصد: `wp-content/themes/javanseda/` · لینک مستقیم:
`https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.1.5/drop-in/<مسیر>`

| فایل | بایت | sha256 (۱۶) |
|------|------|--------------|
| `inc/ab-views.php` **(تازه)** | 18016 | `6800d5c8f5ee72c8` |
| `inc/ab-ratings.php` **(تازه)** | 12386 | `d3964a3c64f42cfd` |
| `inc/mostviews_posts.php` **(تازه در drop-in)** | 1563 | `539de6f68edbbdec` |
| `style.css` | 116708 | `b587e4626cf6b8d3` |
| `myfunctions.php` | 26200 | `ccb4f388f948116b` |
| `single.php` | 34924 | `03d49214211e9b13` |
| `inc/ab-sidebars.php` (مانیفست ۱۹ فایل) | 22805 | `3fa8ac8b99627766` |
| `ab-mobile-probe.php` (§۱۲ تازه) | 52221 | `a78bc54c875810b2` |
| `inc/ab-seo.php` | 22283 | `a1f55722e52835ad` |
| `inc/ab-perf.php` | 19432 | `1a923af24840955f` |
| `inc/ab-news-ticker.php` | 8804 | `b9eebd2bc03497b9` |
| `header.php` · `patches/header.original-patched.php` · `patches/README.txt` | 4859 · 3991 · 4758 | بدون تغییر از 3.1.3/3.1.4 |
| `home.php` | 3761 | `6bf766679e28f87c` (تغییر 3.1.4) |
| `404.php` · `search.php` | 2594 · 1115 | تغییر نکردند (3.1.4) |
| `page.php` · `footer.php` · `inc/acf-compat.php` · `inc/vip_slider.php` | 1347 · 7301 · 6402 · 2890 | بدون تغییر |
| `inc/songs_post.php` · `inc/tarlanweb_player.php` | 1529 · 3277 | بدون تغییر از 3.1.4 |

**کمینۀ نصب این دور (۴ فایل، هیچ‌کدام حذفی ندارند):**
```
inc/ab-views.php · inc/ab-ratings.php · myfunctions.php · style.css
```
⇒ بلوک «پربازدیدترین آثار ماه» و ابزارک «موسیقی محبوب» و آیکن‌های شکسته درست می‌شوند. برای دیدن **بکجِ بازدید و فرم امتیاز** روی صفحۀ آهنگ، `single.php` را هم بگذارید (یا شورت‌کد `[ab_rating]` را در انتهای نوشته‌ها استفاده کنید). برای «چارت ماهانۀ واقعی» `inc/mostviews_posts.php` را هم بگذارید. بعدش Purge All + Purge CDN.

## ۷) راستی‌آزمایی

1. یک صفحۀ آهنگ باز کنید، ۵ ثانیه صبر کنید → در DevTools/Network یک درخواست `admin-ajax.php` با `action=ab_view` (۲۰۰/۲۰۴) باید باشد.
2. `ab-mobile-probe.php?html=1` → **بخش ۱۲**: «نوشته با views>0»، مجموع بازدید، کلید چارت فعلی، وضعیت لایۀ امتیاز. (بعد از بررسی فایل کاوشگر را پاک کنید.)
3. صفحۀ اصلی: «پربازدیدترین آثار ماه» باید پر شود (بعد از این‌که چند بازدید شمرده شود).
4. چارت دلخواه: در یک برگه بنویسید `[ab_charts period="month" count="10" title="چارت ماه آهنگ‌بان"]` — یا در `home.php`/`index.php` با `echo javan_seda_charts_html( array( 'period' => 'month', 'count' => 8 ) );`.
5. امتیاز: ستاره‌ها را انتخاب و «ثبت امتیاز» بزنید → همان صفحه با پیام «ممنون» + میانگینِ تازه؛ در View Source دنبال `aggregateRating` بگردید (ستاره‌های نتایج گوگل از همین می‌آید).

**انتظارِ واقع‌گرایانه:** تا کشِ صفحه بازنشانی نشود، عددِ نمایشی ممکن است یک بازدید عقب بماند (چون HTML کش‌شده است) — شمارشِ واقعی انجام می‌شود. این طبیعی و به‌بهایِ داشتن کش است.

## ۸) سلامت فنی

- همهٔ فایل‌های PHP تغییریافته/تازه با `node php-parser`: **OK** — `inc/ab-views.php`, `inc/ab-ratings.php`, `inc/mostviews_posts.php`, `single.php`, `myfunctions.php`, `ab-mobile-probe.php`, `inc/ab-sidebars.php`, `inc/ab-perf.php`, `inc/ab-seo.php`, `404.php`, `search.php`, `home.php`, `header.php`, `page.php`, `footer.php`, `inc/songs_post.php`, `inc/tarlanweb_player.php`.
- هیچ فیلتری روی `the_content` / `template_include` اضافه نشد (قاعدۀ پروژه)؛ جاگذاری امتیاز/بازدید با فراخوانیِ صریح در `single.php` و شورت‌کد است.
- `style.css`: براکت **۱۰۸۳/۱۰۸۳** · `gradient` **۰** · پالت `#FFFFFF / #185ADB / #111111`.
- سازگاری PHP: بدون تابع/بستنِ `static` (سازگار با PHP ۵.۶/۷.۰)، بدون `??`/arrow-function، همهٔ توابع با `function_exists` محافظت‌شده؛ نبودِ فایل‌های تازه = رفتار دقیقاً مثل قبل (چون `require`ها با `is_readable()` و فراخوانی‌ها با `function_exists()` هستند).
- مانیفست `inc/ab-sidebars.php`: ۱۵ → **۱۹ فایل** · `drop-in/` هم‌سان **ALL MATCH (۲۳)**.

## ۹) بازگردانی

`artifacts/rollback-v3.1.5.zip` : نسخهٔ 3.1.4 این فایل‌ها — `style.css.3.1.4` · `myfunctions.php.3.1.4` · `single.php.3.1.4` · `inc/mostviews_posts.php.3.1.4` · `ab-mobile-probe.php.3.1.4` · `inc/ab-sidebars.php.3.1.4` · `drop-in-v3.1.4.zip`.
ساده‌ترین بازگشت: `rm inc/ab-views.php inc/ab-ratings.php` + حذف دو بلوک `require` از `myfunctions.php` (یا برگرداندن `myfunctions.php.3.1.4`). برای خاموش‌کردنِ موقت بدون حذف فایل: `define('JAVANSEDA_VIEWS', false); define('JAVANSEDA_RATINGS', false);`.

## ۱۰) باقی‌مانده (دور ۱۳)

فرم **گزارش لینک خراب** (AJAX + شمارش + مخفی‌کردن خودکار دکمۀ دانلود بعد از N گزارش) · صفحۀ **DMCA/حذف اثر** و **تماس** · «پلی‌لیست من» با localStorage · جست‌وجوی زنده (افزونۀ SearchWP Live + template آماده در قالب هست) · فیلد `duration` برای `MusicRecording.duration` و `og:duration`.
