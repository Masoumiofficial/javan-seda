# نقشۀ تغییرات — نسخۀ 3.3.0 (روی 3.2.4) · رابط شیشه‌ای + تراز سراسری

تاریخ: 2026-10-18 · زنجیره: `3.2.2` → `3.2.3` → `3.2.4` → **`3.3.0`**

مأموریت: ارتقای بصری به یک زبان طراحی منسجم (الهام از iOS/visionOS، Apple Music، Linear، Raycast) روی
صفحات: **اصلی** · **نوشته تکی** · **۴۰۴** + **رفع ریشۀ وسط‌چین‌نبودن** — بدون قربانی‌کردن سرعت، خوانایی،
RTL، فارسی، سئو، schema، پلیر، لینک دانلود و دسترس‌پذیری. بدون بازنویسی قالب.

## ۰) کشف معماری (قبل از هر تغییر)

- مسیر رندر: `header.php` → (`home.php` → `inc/vip_slider` · `inc/tarlanweb_player` · `inc/new_posts` · `inc/mostviews_posts` · حلقۀ `hty` در home.php → `inc/songs_post.php` · `inc/artist_slider`) یا (`index.php` برای `/page/N/` — رفع v3.1.4) → `footer.php`.
- `single.php`: `:18` `<h1>` داخل `.tarlanweb_post_box` · `.single_content_box` از `:38` · کاور `:48` · ترانه `:275` · باکس اجتماعی + شناسنامه `:284-285` · پلیر تک‌آهنگ `:304` / آلبوم `:360` · دانلود `:407` · شناسنامۀ `after_download` `:459` · مرتبط‌ها `:505/541` · سایدبار/دیدگاه `:577/603/635`.
- **علت ریشۀ «وسط‌چین نبودن»:** `.tarlanweb_center{width:1300px;margin:0 auto}` با پلّه‌های `1200/1100/…/250` در ۱۱ media query و **بدون هیچ padding طرفین** ⇒ حاشیه‌ها بین پله‌ها ناگهان عوض می‌شد؛ به‌علاوه `.song_boxs_posts ul{margin:0 -15px}` ریل را ۳۰px از ظرف بیرون می‌زد و `body{overflow-x:hidden}` در RTL آن را یک‌طرفه می‌بُرد ⇒ «کج» دیده می‌شد. در بلوک‌های قدیمی‌تر هم قواعد grid با `!important` روی همان ریل نشسته بود (دو منبعِ رقیق).
- `functions.php` قفلِ ionCube است ⇒ تنها لایۀ منطقی قابل‌ویرایش `myfunctions.php` + `inc/ab-*.php`.

## ۱) فایل‌ها

| فایل | وضعیت | نقش |
|---|---|---|
| `style.css` | ✏️ **بلوک ۲۶** در انتهای فایل + `Version: 3.3.0` + پالایش بلوک ۲۲ | توکن‌های ساختار (ظرف، گاتر، فاصله، شعاع)، ظرف سیالِ مرکزی، شبکهٔ کارت‌ها، ریتم بخش‌ها، تیتر بخش، تایپوگرافی فارسی صفحۀ نوشته، دیدگاه‌ها، ساختار ۴۰۴، سلسله‌مراتب عنوان |
| `css/ab-glass.css` | ➕ جدید (۲۷۹ خط) | **فقط** پوسته/عمق: سطوح شیشه‌ای، blur متعادل، مرز نازک، هایلایت ۱px، سه سایهٔ نرم، هاور/فوکوس، fallback مرورگر بدون blur، سازگاری شب، `prefers-reduced-motion` |
| `inc/ab-glass.php` | ➕ جدید (۷۳ خط) | بارگذارِ فایل بالا روی `wp_enqueue_scripts` با اولویت ۲۰ (بعد از استایل قالب ⇒ بدون `!important`) · `filemtime` برای کش · اگر فایل نبود **سکوت** می‌کند |
| `myfunctions.php` | ✏️ ۵ خط | `require_once` همان فایل، در همان الگوی بقیۀ لایه‌ها (CRLF حفظ شد) |
| `404.php` | ✏️ بازنویسی نشانه‌گذاری (۵۵→۸۴ خط) | hero با نشانِ ۴۰۴، جست‌وجوی واقعی، «بازگشت به صفحۀ اصلی» + «صفحۀ قبلی» (با `wp_get_referer()`)، میان‌بُرهای مفید، کادر هنری، ۶ آینۀ واقعی؛ HTTP همچنان ۴۰۴ |
| `preview/glass-ui.html` | ➕ دمو (نصب نمی‌شود) | هدر/نوار سریع/ریل کارت‌ها/صفحۀ نوشته/نوار پخش/۴۰۴ با **CSS واقعی همین نسخه** + دو سوییچ (شب، خاموش‌کردن منطقۀ شیشه) + نشانگر عرض viewport |
| `single.php` · `header.php` · `footer.php` · `home.php` · `index.php` · `js/scripts.js` · `inc/ab-*.php` · `functions.php` | ⏸ بدون تغییر | هیچ منطق، هوک، کوئری، سؤالساختاری یا داده‌ای تغییر نکرد |

**حجم واقعی تغییر (`git diff --numstat`):** `style.css` ‎+۱۵۹/−۶۱ · `404.php` +۳۸/−۱۲ · `myfunctions.php` +۶/−۰ · دو فایل جدید (۲۷۹ + ۷۳ خط) · `single.php`/`header.php`/`footer.php`/`js/` دست‌نخورده. صفر تغییر دیتابیس، صفر پلاگین، صفر وابستگی بیرونی، صفر فایل تصویر/فونت تازه.

## ۲) توکن‌های طراحی

```css
/* style.css — ساختار (دائمی) */
:root{ --ab-container:1280px; --ab-gutter:clamp(14px,3.4vw,32px); --ab-gap:22px; --ab-gap-sm:14px;
       --ab-radius-s:10px; --ab-radius-m:14px; --ab-radius-l:20px; --ab-radius-xl:26px; --ab-radius-pill:999px;
       --ab-space-1:4px; … --ab-space-7:44px; }
/* css/ab-glass.css — پوسته (قابل خاموشی) */
:root{ --ab-glass-bg:#FFFFFF; --ab-glass-bg-strong:#FFFFFF; --ab-glass-border:rgba(17,17,17,.08);
       --ab-glass-hl:inset 0 1px 0 rgba(255,255,255,.85); --ab-glass-blur:14px; --ab-glass-saturate:1.5;
       --ab-ease:cubic-bezier(.2,.7,.3,1); --ab-dur:.18s; --ab-depth-1/2/3 (سایه‌های چندنقطه‌ای نرم); }
@supports (backdrop-filter: blur(2px)){ :root{--ab-glass-bg:rgba(255,255,255,.72);…} .night{…} }
```
* پالت سازمانی دست‌نخورده: `#FFFFFF · #185ADB · #111111` (+ `#EAF1FE`/`#222` در شب). **صفر `gradient(`** در هر دو فایل — عمق فقط با مرز نازک، سایه و هایلایت درون‌خطی ساخته می‌شود.
* شب: همان متغیرها در `.night` (که `header.php` با کلاس `body.night` روشن/خاموش می‌شود) ⇒ هر سطح شیشه‌ای خودکار تیره و کم‌نور می‌شود؛ `.night #header` هم صریح افزوده شد تا هدر در شب «charcoalِ بی‌مرز» نماند.

## ۳) قاعدۀ شیشه: کجا نه، کجا بله

شیشه فقط روی **سطح‌های شناور** (حداکثر ۶ نقطه در هر صفحه): `#header.darkHeader` (حالت چسبان هنگام اسکرول) · `#mobiles_header` · زیرمنوی `.menu_right li ul.sub-menu` · `.ab-sticky` (نوار پخش) · پنل `.ab-404-panel` · کلاس ابزارک `.ab-glass`.
**کارت‌های محتوا شیشه‌ای نیستند**: `.songs_post`، کاور، متن ترانه، لینک دانلود — ساترِ ماتِ **جامد** (`var(--ab-surface)`) + موک‌بردر + سایه، چون شفافیت پشت کاور/متن یعنی افت خوانایی و وضوح تصویر.
**fallback:** اگر `backdrop-filter` پشتیبانی نشود، توکن روی همان سطح جامد می‌ماند (هیچ عنصری نیمه‌شفافِ بی‌فایده نمی‌شود) و هیچ قاعدۀ شکسته‌ای باقی نمی‌ماند.

## ۴) تراز و شبکه (رفع ریشه‌ای، نه پوشاندن)

```css
.tarlanweb_center{width:auto;max-width:var(--ab-container);margin-right:auto;margin-left:auto;
                  padding-right:var(--ab-gutter);padding-left:var(--ab-gutter);text-align:right}
.song_boxs_posts ul{display:grid;grid-template-columns:repeat(auto-fill,minmax(188px,1fr));gap:var(--ab-gap);margin:0;padding:0}
.song_boxs_posts ul li{width:auto;float:none;padding:0;margin:0;min-width:0}
.songs_title_box{display:flex;align-items:center;gap:var(--ab-space-3)}   /* تیتر · خط · دکمه (با order) */
```
* `text-align:right` صریح روی ظرف ⇒ وسط‌چین‌شدن **ظرف** هیچ‌وقت به وسط‌چین‌شدن **متن** تبدیل نمی‌شود (قاصریت ۴ دستور).
* مارجین منفی ریل حذف شد ⇒ سرریز افقی و برشِ یک‌طرفه از بین می‌رود؛ ستون‌ها به‌جای پلّه‌های ۶/۴/۳/۲ با `auto-fill` پیوسته محاسبه می‌شوند.
* **پالایش override:** ۱۸ قاعدۀ تکراریِ بلوک‌های قبلی روی `.song_boxs_posts ul` / `.songs_post` / `#centers_box .song_boxs` (که همه با `!important` نوشته شده بودند) حذف شدند و معادلِ بهترشان در بلوک ۲۶ نشست ⇒ **یک منبع حقیقت**. `!important` کل فایل از ۴۷۱ به ۴۴۳ رسید و در بلوک ۲۶ فقط یک مورد (خنثی‌کردن `--ab-glass-blur` در `prefers-reduced-motion`/موبایل) باقی است.
* `min()` عمداً استفاده نشده؛ اگر مرورگر grid نداشته باشد، آیتم‌ها بلوکِ تمام‌عرض می‌شوند (نه بهم‌ریخته).

## ۵) صفحۀ اصلی / نوشته تکی / ۴۰۴

- **اصلی:** ریتم بخش‌ها با `--ab-space-7` (و ۲۴px در موبایل)، تیتر بخش با خطِ راهنمای انعطاف‌پذیر و دکمۀ «مشاهده بیشتر» به‌شکل pill، کارت آهنگ با ارتفاع محتوا‌محور (`height:100%` در ردیف)، کاور ۱:۱ با `object-fit:cover`، `footer` چسبان به پایین کارت، هاور: لیفت ۱px + زنگولۀ آبی روی لبه + زوم ۱.۰۳ روی کاور (به‌جای `transform:scale(1.08)` قبلی که کل کارت را می‌پراند).
- **نوشته تکی:** عنوان `clamp(19px,2.3vw,23px)/700`، ظرف با شعاع/فاصلۀ توکنی، متن `15px/2.05` (ارتفاع خط فارسی)، `overflow-wrap:anywhere` برای لینک‌های بلند، `ul/ol` دوباره نشان‌دار (ریست قالب `list-style:none` را روی همه‌جا تحمیل می‌کرد)، `blockquote` با نوار آبی ۳px، لینک‌های دانلود در شبکهٔ `auto-fit` (به‌جای `float` + مارجین منفی)، کاور با سایۀ نرم و شعاع ۲۰px.
- **۴۰۴:** hero تک‌ردیفه (نشان ۴۰۴ `clamp(44px,9vw,76px)` + متن) ⇒ در ≤700px تک‌ستون و مرکزی؛ فرم جست‌وجوی واقعی با `label` پنهان؛ CTA اصلی آبی + CTA ثانویه؛ چهار میان‌بُر واقعی؛ ۶ آهنگ تازه با همان `inc/songs_post.php`. بدون محتوای ساختگی، بدون تغییر وضعیت HTTP.

## ۶) Responsive · دسترس‌پذیری · سرعت

- نقاط تست: ۳۲۰/۳۷۵/۳۹۰/۴۳۰ · ۷۶۸ · ۱۰۲۴/۱۴۴۰ · ۱۶۰۰+ ⇒ با `auto-fill` و گاتر `clamp()` پیوسته کار می‌کند؛ `html{-webkit-text-size-adjust:100%}`؛ `max-width:100%` روی تصویر/ویدیو/iframe داخل ظرف؛ `min-width:0` روی آیتم‌های شبکه ⇒ بدون اسکرول افقی.
- اهداف لمسی: دکمۀ پخش ۳۲px (موبایل ۲۸)، nav ۲۶px، pillهای ۴۰۴ با padding ۱۰–۱۶px.
- کیبورد: `:focus-visible` با حلقۀ ۲px آبی روی لینک/دکمه/فیلدها؛ ترتیب DOM و ساختار معنایی عوض نشده (فقط `order` بصری در تیتر بخش).
- حرکت: همه‌چیز روی `transform/opacity` با `--ab-dur:.18s`؛ در `prefers-reduced-motion:reduce` همه انتقال/انیمیشن خنثی و لیفت‌ها خاموش می‌شوند.
- سرعت: فایل CSS شیشه ≈ **۱۱.۷KB** (قبل از gzip، کش‌شونده، فقط صفحۀ عمومی، نه پیشخوان)؛ blur روی حداکثر ۶ سطح و در موبایل `--ab-glass-blur:10px`؛ بدون JS تازه؛ بدون فونت/تصویر/کتابخانۀ بیرونی؛ lazy/`aspect-ratio` تصاویر دست‌نخورده (LCP تغییری نمی‌کند).

## ۷) کلیدهای خاموشی

```php
define( 'JAVANSEDA_GLASS_UI', false );   // کل لایۀ شیشه ⇒ فقط ساختار/تراز می‌ماند
define( 'JAVANSEDA_GLASS_BLUR', false ); // سطوح نیمه‌شفاف بدون تارکردگی
```
بازگشت کامل: `artifacts/rollback-v3.3.0.zip` (سه فایل `*.3.2.4`) — دو فایل جدیدِ 3.3.0 می‌توانند بمانند؛ بدون `myfunctions.php` و `style.css` قدیمی هیچ اثری ندارند.

## ۸) بررسی‌های اجراشده / نگاشته‌شده

**اجرا شد:**
- `css-tree` (v3) روی `style.css` و `css/ab-glass.css` ⇒ **۰ خطای پارس** (۱۱۳۳ + ۴۷ قاعدۀ معتبر).
- `tools/check.js` (php-parser) ⇒ `OK` روی `404.php` · `inc/ab-glass.php` · `myfunctions.php` · `single.php` · `inc/ab-post-extras.php`.
- `tools/compat-check.py javanseda` ⇒ **`0 hit`** روی کل قالب (کف PHP 5.6).
- `new Function()` روی اسکریپت دمو ⇒ parse OK · HTMLParser روی `preview/glass-ui.html` و `404.php` ⇒ تگ‌ها بالانس.
- بازرش cascade: `grep -n` ⇒ بلوک ۲۶ بعد از هر ۱۲ قاعدۀ `.tarlanweb_center` و بعد از هر قاعدۀ `.song_boxs_posts ul` نشسته است؛ `!important` رقیبی روی آن سلکتورها نمانده.
- `grep -c 'gradient(' = 0` در هر دو CSS · `backdrop-filter: blur` = ۶ سطح · LF در `style.css`/`css/ab-glass.css`/`404.php` و CRLF در `myfunctions.php` حفظ شد.

**اجرا نشد (محدودیت محیط، صریح):** در این سندباکس مرورگر/headless Chrome و PHP+MySQL وجود ندارد ⇒ **رندر واقعی، کنتراست‌سنجی عددی روی پیکسل، تست Lighthouse و تست لمسی انجام نشده**. دمو (`preview/glass-ui.html`) همان CSS واقعی را در مرورگر شما رندر می‌کند و بهترین جای موجود برای قضاوت چشمی است؛ نتیجهٔ نهایی را روی هاست ببینید.
