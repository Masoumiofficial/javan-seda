# javan-seda · v3.4.8 — تمام‌کردنِ «خواننده → هنرمند» + رفعِ لینکِ کهنهٔ `/singers/`

## چیزی که کاربر دید

بعد از آپدیت به 3.4.7 و عوض‌کردنِ اسلاگِ برگه به `artists`:

- `https://ahangban.ir/artists/` درست کار می‌کند (۷ هنرمند · ۱۷ آهنگ، `?sort=songs` سالم) ✓
- ولی در صفحۀ هر هنرمند دو لینک هنوز به `/singers/…` می‌رفت:
  «همهٔ خواننده‌ها (فهرست الفبایی)» و «خواننده‌های حرف «م»».
- و متن‌ها هنوز جای‌جای «خواننده» بود.

## علتِ لینکِ کهنه (ریشه‌ای)

`taxonomy-artist.php` تابعی به‌نام `javanseda_artist_directory_url()` داشت که صفحۀ فهرست را
با یک کوئریِ سنگین (`meta_query` روی `_wp_page_template`) پیدا می‌کرد و نتیجه را در
**ترنزینتِ ۱۲ ساعته** با کلیدِ ثابت `javanseda_artist_directory_url` می‌گذاشت.
اسلاگِ برگه که عوض شد، آن مقدارِ کهنه (`/singers/`) تا ۱۲ ساعت سرو می‌شد.
(لینک‌هایی که از `javan_seda_artists_index_url()` ساخته می‌شدند — فوتر، کوییک‌بار، ۴۰۴، منوی موبایل —
از اول درست بودند؛ به همین دلیل نصفِ سایت درست و نصفش کهنه به‌نظر می‌آمد.)

**اصلاح (قاعده‌ای که برای بقیه هم گذاشته شد):** هر resolverِ کش‌دار باید
۱) کلیدِ کشش با شمارۀ قالب بسازد و ۲) با رویدادهای مرتبط باطل شود.

```php
// inc/ab-tax-artist.php
javan_seda_artist_url_cache_key()  →  'javan_seda_artist_dir_' . <Versionِ style.css>
foreach ( array( 'save_post', 'deleted_post', 'switch_theme', 'updated_option' ) as $h )
    add_action( $h, 'javan_seda_artist_flush_url_cache', 50 );
```

پس با آپلودِ این نسخه، کشِ کهنه خودبه‌خود بی‌اثر می‌شود (کلید عوض شده) و بعد از آن
هر تغییرِ اسلاگ/برگه/تنظیم هم فوراً اعمال می‌شود. تابعِ کهنه دیگر اصلاً آن کلید را نمی‌خواند.

## ترتیبِ حل‌کردنِ آدرسِ «همهٔ هنرمندان»

`javan_seda_artists_index_url()` (لایه) — اولینِ پیدا‌شده برنده:

| # | منبع | نکته |
|---|---|---|
| ۰ | تنظیمات مادر `arti_url` (ردیف «صفحه هنرمندان») | اگر پر باشد، نظرِ مدیر بالاتر از همه؛ `user_trailingslashit` می‌خورد |
| ۱ | برگه با اسلاگ `artists` → `artist` → `singers` → `singer` | `get_page_by_path( …, 'OBJECT', 'page' )` — ارزان، بدون کش، پست اشتباهی را نمی‌گیرد |
| ۲ | برگه‌ای که قالبش `artist-template.php` است | سنگین ⇒ کشِ ۱۲ ساعتهٔ **نسخه‌دار** |
| ۳ | آرشیوِ خودِ تاکسونومی | `rewrite['slug']` |
| ۴ | `home_url('/singers/')` | آخرینِ چاره ⇒ هیچ لینکی خالی/نسوخته نمی‌ماند |

`javanseda_artist_directory_url()` در `taxonomy-artist.php` حالا فقط یک رابطِ سازگار است که
به همین تابع واگذار می‌کند (با `apply_filters( 'javanseda_artist_directory_url', … )`).

به‌علاوه: اگر `/singers/` یا `/singers` دیگر ۴۰۴ باشد (یعنی اسلاگِ برگه عوض شده)، با **۳۰۱**
به آدرسِ تازه می‌رود و کوئری‌استرینگ (`?letter=…`) حفظ می‌شود. این ریدایرکت فقط داخل
`is_404()` اجرا می‌شود، پس هیچ صفحۀ زنده‌ای را نمی‌پوشاند.

## نام‌ها: هر جا هنوز `singer` بود

۹۷ رشته در ۱۴ فایل از «خواننده/خواننده‌ها» به «هنرمند/هنرمندان» رسید. شکل‌ها یکی‌یکی
جایگزین شدند تا حرف اضافه جا نیفتد (`خواننده‌های` → `هنرمندانِ`، `خواننده‌ای` → `هنرمندی`،
`خوانندهٔ` → `هنرمندِ`) و تکرارِ عمدیِ نویسندۀ قبلی («خواننده‌ها و هنرمندان») یک‌پارچه شد.

| فایل | حجم | چه |
|---|---:|---|
| `inc/ab-tax-artist.php` | 24,760 B | resolverِ چندمرحله‌ای + کشِ نسخه‌دار + flush + ۳۰۱ِ `/singers` + `javan_seda_artist_default_image()` |
| `taxonomy-artist.php` | 32,363 B | رابطِ `javanseda_artist_directory_url()` واگذار شد؛ «همهٔ هنرمندان (فهرست الفبایی)»، «هنرمندانِ حرف «…»**، «هنرمندانِ مشابه»؛ تصویرِ `def-artist.webp` |
| `artist-template.php` | 37,640 B | ۴۰ رشته؛ ردیفِ تیتر؛ `def-artist.webp`؛ کلاسِ `ab-artists__grid` کنارِ `singers_box_tarlanweb` |
| `inc/theme-options.php` | 32,386 B | ردیفِ تازه `ab_artists_title` + اصلاحِ برچسب/توضیحِ ۱۶ ردیف (کلیدها ثابت) |
| `myfunctions.php` | 35,712 B | فالبکِ `javan_seda_artists_index_url()` کامل شد (arti_url و اسلاگِ برگه)؛ `JAVANSEDA_ARTIST_FIX`؛ کلیدِ `javanseda_artist_rewrite_version`؛ دیآگ با `?ab_artist_diag=1` |
| `inc/ab-credits.php` | 29,960 B | aliasِ `javan_seda_credit_artist()` + فیلترِ `javanseda_credit_singer_label` |
| `footer.php` / `home.php` / `404.php` / `tag.php` / `inc/mobiles_header.php` / `inc/ab-controls.php` | — | برچسبِ منوها و placeholder جست‌وجو |
| `single.php` / `inc/ab-seo.php` / `inc/ab-post-extras.php` / `inc/ab-admin-ui.php` | — | متن‌ها و توضیحات |
| `style.css` | 196,783 B | `Version: 3.4.8` + به‌روزرسانیِ دو توضیحِ ارجاع (`taxonomy-artist.php`، `/artists/`) |
| `images/def-artist.webp` | 26,512 B | فایلِ تازه (کپیِ `def-singer.webp`) |

## آنچه **عمداً** عوض نشد

| مورد | چرا مانده |
|---|---|
| `singer` در `inc/ab-tax-artist.php` / `myfunctions.php` | نامکِ مادر و ستونِ دیتابیس است؛ مهاجرت، فالبک و سازگاری به آن نیاز دارد |
| `.singers_box_tarlanweb` در `style.css` و markup | سلکتورهای CSS در ۱۰ نقطه به آن گره‌خورده‌اند؛ `ab-artists__grid` به‌عنوان کلاسِ تازه اضافه شد تا از این به بعد با آن کار کنید |
| `images/def-singer.webp` | فالبکِ تصویر؛ اگر نصبِ ناقص باشد کارت‌ها بی‌تصویر نمی‌شوند |
| `singer_en` در `$keys`ِ `artist-template.php` | کلیدِ postmeta/ACFِ موجود؛ `artist_en` اولویت اول است و این فقط فالبک |
| `javan_seda_credit_singer()` و برچسبِ «خواننده» در ردیفِ اعتبار | این «خوانندۀ آهنگ» است، نه نامِ تاکسونومی؛ `javan_seda_credit_artist()` اضافه شد |
| `JAVANSEDA_SINGER_FIX`، `javanseda_singer_rewrite_version`، `?ab_singer_term` | نام‌های قدیمی با alias پذیرفته می‌شوند؛ نوشتنِ هر دو کلید ⇒ فلشِ تکراریِ rewrite نمی‌افتد |
| `taxonomy-singer.php` | شیم؛ تا وقتی نامکِ فعال `singer` است لازم است |
| منطق `views` / `kksr` | خارج از دستورِ این دور |

## تیترِ «خواننده ها» روی `/artists/`

آن متن، **عنوانِ خودِ برگه در دیتابیس** است؛ کد آن را نمی‌سازد. دو راه:

1. پیشخوان ← برگه‌ها ← «خواننده ها» ← عنوان را «هنرمندان» کنید (یک کلیک، همۀ سئو را هم درست می‌کند).
2. یا «تنظیمات پوسته ← کنترل رابط ← تیترِ صفحۀ هنرمندان» (`ab_artists_title`) را پر کنید:
   فقط `H1` و `title` را روی این صفحه می‌پوشاند و عنوانِ برگه دست‌نخورده می‌ماند.

## نصب

پیش‌نیاز: 3.4.7. بیست فایل؛ **هیچ SQL دستی لازم نیست** و دیتابیس تغییر نمی‌کند.

1. فایل‌ها را در `wp-content/themes/javanseda/` بریزید (پوشه‌های `inc/` و `images/` را باز کنید).
2. کشِ افزونه‌ها/سرور را خالی کنید (لازم نیست چیزی را دستی پاک کنید — کلیدِ کش عوض شده).
3. صحت‌سنجی: `https://ahangban.ir/wp-content/themes/javanseda/style.css?v=check348` → `Version: 3.4.8`
4. در صفحۀ یک هنرمند، ماوس را روی «همهٔ هنرمندان (فهرست الفبایی)» ببرید: باید `/artists/` باشد.
5. دیآگ: `https://ahangban.ir/?ab_artist_diag=1` → خطوطِ «آدرسِ فهرستِ هنرمندان: …»

## بازگشت

بستهٔ `rollback-v3.4.8.zip` (پسوند `.3.4.7` را حذف کنید) یا فقط `style.css` +
`artist-template.php` + `taxonomy-artist.php` + `inc/ab-tax-artist.php` را به 3.4.7 برگردانید.
در 3.4.7 هم لینک‌ها کار می‌کنند، فقط ممکن است تا ۱۲ ساعت `/singers/` نشان داده شوند —
که با خالی‌کردنِ ترنزینت از «ابزار ← حذفِ کش» یا یک بار «تنظیمات ← پیوندهای یکتا ← ذخیره» درست می‌شود.
