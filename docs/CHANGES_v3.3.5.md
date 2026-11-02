# javanseda v3.3.5 — تغییرات نسبت به v3.3.4

**موضوع:** دو ایرادِ گزارش‌شدۀ صفحۀ دسته‌ها (`/music/` و بقیه) + استانداردسازی فاصلۀ ریل هنرمندان + یک پاسِ بررسی سراسری روی قالب.

**مجموعۀ تغییر:** `javanseda/style.css` (`+62/−27`) · `javanseda/css/ab-glass.css` (`+10/−3`) · `javanseda/category.php` (`+7/−1`) · `preview/glass-ui.html` (بخش نمایشی ۶). حجم `style.css`: 146,471 → 150,267 بایت (+2.6٪ · ۳۱۰۵ خط).

---

## ۱) «آهنگ ج…» — عنوان دسته بریده می‌شد

**علت ریشه‌ای:** در `style.css` بلوک ۲۶ این قاعده برای تیترِ بخش‌های صفحه‌ی اصلی نوشته شده بود:

```css
.songs_title_box h1,.songs_title_box h2,.songs_title_box h3{…white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:60%}
```

صفحۀ دسته هم تیترش را داخل همان `.songs_title_box` و با تگ `h1` چاپ می‌کند (`category.php:10`)، پس «آهنگ جدید، …» روی یک خط قفل می‌شد و با «…» بریده می‌شد.

**رفع:** سه انتخابگر جدا شدند. `h3` (تیتر بخش‌ها، همیشه کوتاه) همان رفتار قبلی را نگه داشت؛ `h1/h2` (تیتر بایگانی/دسته/برچسب/هنرمند) اجازهٔ شکستن خط دارند و حداکثر دو خط با `line-clamp` کوتاه می‌شوند، و `min-width:0` هم روی هر سه نشست تا در ظرف flex له نشوند.

## ۲) «کادر خالی سفید» زیر عنوان

**علت ریشه‌ای:** `category.php:11` بی‌قیدوشرط `<div class="archive_txt_box">` را چاپ می‌کرد و داخلش `category_description()`؛ وقتی توضیح دسته در پیشخوان خالی باشد، یک `div` بی‌محتوا چاپ می‌شود و قاعدۀ `css/ab-glass.css` روی همان کلاس `padding:14px 16px` + مرز + سطح + سایه گذاشته است ⇒ **یک کادرِ کاملاً خالی**. همین الگو در ردیف آلبوم‌ها برای `.download_box` هم وجود داشت (اگر هیچ‌کدام از لینک‌های ۱۲۸/۳۲۰ ست نشده باشند، باکس خالی رندر می‌شد).

**رفع (دولایه، تا اگر فقط یک فایل آپلود شد هم درست شود):**

- `category.php`: بلوک فقط وقتی چاپ می‌شود که `trim( wp_strip_all_tags( category_description() ) )` تهی نباشد.
- `css/ab-glass.css`: قاعدۀ `.archive_txt_box` به `.archive_txt_box:has(p,ul,ol,blockquote,h2,h3,h4,strong,em,a,img,br)` تبدیل شد و حالت پایه بدون کادر است؛ قاعدۀ باکس `.download_box` هم به `.download_box:has(a)` تغییر کرد. اگر مرورگری `:has()` را پشتیبانی نکند، فقط کادر اعمال نمی‌شود (محتوا هرگز پنهان نمی‌شود).

## ۳) ریل هنرمندان صفحه‌ی اصلی — فاصله و استانداردسازی

**علت ریشه‌ای:** owl فاصلۀ بین آیتم‌ها را از `margin` روی `.owl-item` می‌گیرد که در owl carousel تنظیم می‌شود، اما تکهٔ فشردهٔ owl داخل `style.css` قالب آن margin‌ها را کامل ندارد؛ از طرفی آیتم‌ها `width:180px` با دایرۀ ۱۵۰px بودند و هیچ حاشیۀ داخلی نداشتند ⇒ دایره‌ها و نام‌ها به هم چسبیده به‌نظر می‌رسیدند. چون مالک تأیید کرد owl باید تعداد و اندازه را خودش تعیین کند و نباید هیچ `!important` روی `.owl-item` یا `.artist_boxs a` برود، فضا **داخل باکس** ساخته شد.

**رفع (همه در بلوک ۲۹، بی‌دست‌زدن به عرض‌ها و به شمارش owl):**

```css
.artist_boxs a{padding:6px 9px 2px;direction:rtl;text-align:center}
.artist_boxs a img{box-shadow:0 0 0 1px var(--ab-line)}
.artist_boxs a span{max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:0 2px;font-size:12.5px;line-height:1.8}
.artists_slider .owl-item{padding-block:2px}
.night .artist_boxs a img{box-shadow:0 0 0 1px rgba(255,255,255,.14)}
@media (max-width:767px){ .artist_boxs a{padding:4px 7px 0} .artist_boxs a span{font-size:11.5px} }
```

- `padding` داخل `a` چون `*{box-sizing:border-box}` از خط ۱۰۶ فعال است، عرض آیتم را عوض نمی‌کند (۱۸۰ − ۱۸ = ۱۶۲ ≥ ۱۵۰ دایره) ⇒ در دسکتاپ/تبلت/موبایل و در هر شکلی که owl تعداد را می‌شمارد، فاصله حفظ می‌شود.
- `direction:rtl` لازم بود چون تکهٔ owlِ قالب روی `.owl-carousel .owl-item` مقدار `direction:ltr !important` دارد و نام فارسی را با نقطه‌گذاری جابه‌جا نشان می‌داد.
- `span` یک‌خطی با «…» ⇒ ارتفاع همۀ  کارت‌ها یکسان (قبلاً نام بلند دوخطی می‌شد و ردیف را می‌کج کرد).
- حلقۀ ۱px دور دایره، جدا از فضای خالی، مرز بصری می‌دهد تا دو دایره به هم نچسبند.
- در ≤۴۵۰px که خودِ قالب `.artist_boxs a{width:100%}` را دارد، همین padding حاشیۀ امن چپ‌راست ردیف را تأمین می‌کند.

## ۴) پاس بررسی سراسری (آنچه پیدا شد و رفع شد)

| مورد | وضعیت |
|---|---|
| `.ab-social-box` و `.ab-credit*` (ساختۀ 3.2.0/3.2.3) رنگ صلب روشن داشتند (`#FFFFFF`، `#111111`، `#f7f9fd`) و هیچ قاعدۀ `.night` نداشتند ⇒ در حالت شب، کارت سفید با متن مشکی روی زمینۀ تیره | **رفع:** همه به توکن‌های `--ab-surface/--ab-page/--ab-text/--ab-muted/--ab-line/--ab-blue/--ab-blue-soft/--ab-blue-accent/--ab-white` تبدیل شدند؛ در حالت شب خودکار درست می‌شوند و در حالت روشن فقط مرزها کمی ملایم‌تر و رنگ‌های کمکی کمی پررنگ‌تر شده‌اند |
| حلقۀ `:focus-visible` لینکِ کارت آهنگ بریده می‌شد | **رفع:** `overflow:hidden` از `.songs_post` به `.songs_post header figure` منتقل شد (برش تصویر و گوشه‌ها سر جای خود) |
| ریل‌های `ab-artists__*` / `ab-artist__*` / `ab-related__*` در نایت‌مود | بررسی شد: از قبل توکنی بودند ⇒ سالم |
| کلاس‌های `ab-*` بدون قاعدۀ CSS (سیزده مورد مثل `ab-footer__content`، `ab-cred-note`، `ab-related`، `dl_title_*`) | بررسی شد: همه یا مادری‌استایل‌شده‌اند یا فقط نقطۀ چسب/مارکرند؛ هیچ عنصر بی‌استایلی نماند |
| باکس‌های احتمالیِ خالی (`ab-news` تیکر، `ab-credits`، `ab-social-box`، `music_box_player`، `dl_links_box`، `video_player`، سایدبارها) | بررسی شد: همه در PHP گاردِ محتوا دارند (`have_posts`/`if(!$items)return`/`get_field`)؛ فقط `archive_txt_box` و `download_box` بی‌گارد بودند که در همین نسخه بسته شد |
| کنتراست رنگ‌ها (WCAG) | محاسبه شد و همه جفت‌های اصلی ≥ 4.5:1: متن روشن 18.9، لینک آبی 6.0، muted 4.8، متن شب 13.6، لینک شب 8.1، سفید روی آبی 6.0 |
| `php -l` / اجرای واقعی WordPress / مرورگر | **در این سندباکس ممکن نبود** (نه PHP، نه مرورگر). اعتبارسنجی با پارسر PHP (`tools/check.js`)، پارسر CSS (`css-tree`: صفر خطا)، `tools/compat-check.py` (صفر خطا)، شمارش براکت، صفر `gradient(`، صفر `!important` تازه (447 مثل قبل) انجام شد |

---

## آپلود

**حالت پیشنهادی — سه فایل (همۀ  رفع‌ها):**

1. `javanseda/style.css`
2. `javanseda/css/ab-glass.css`
3. `javanseda/category.php` ← فایل تازه در `drop-in/`

مسیرها روی هاست: `wp-content/themes/javanseda/style.css` · `.../css/ab-glass.css` · `.../category.php`

**اگر فقط `style.css` + `css/ab-glass.css` را آپلود کنید** (بدون فایل PHP): بریدگی عنوان، فاصلۀ هنرمندان، حلقۀ فوکوس و نایت‌مود درست می‌شوند و کادر خالی هم دیگر دیده نمی‌شود (چون گارد CSS آن را بدون استایل می‌گذارد)؛ فقط یک `div` بی‌محتوا در HTML می‌ماند که هیچ اثر بصری ندارد.

پیش‌نمایش محلی: `preview/glass-ui.html` → بخش «۶) صفحۀ دسته/بایگانی».

## بازگشت

`artifacts/rollback-v3.3.5.zip` فقط `style.css.3.3.4` را دارد؛ برای بازگشت کامل نام آن را به `style.css` بگذارید و `ab-glass.css` و `category.php` را از `artifacts/drop-in-v3.3.4.zip` بردارید. زنجیره: `rollback-v3.3.4.zip` → 3.3.3، `rollback-v3.3.3.zip` → 3.3.2.

## دست‌نخورده‌ها

هیچ قاعدۀ تازه روی `.owl-item` برای عرض، هیچ `!important` جدید، هیچ تاکیسونومی/هوک، هیچ افزونۀ تازه؛ `JAVANSEDA_PERF_DEFER` عمداً دست‌نخورده ماند؛ بلوک‌های ۲۶–۲۸ (ریل لمسی، گارد فلاش، کشو، دیدگاه‌ها، ۴۰۴) فقط در دو نقطۀ عنوان/figure ویرایش شدند.
