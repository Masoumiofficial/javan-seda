# نقشۀ تغییرات — نسخۀ 3.2.0 (ساخته‌شدهٔ مستقیم روی 3.1.4)

تاریخ: 2026-10-16 · مرجع: `v3.1.4` · این نسخه **هیچ چیزی از 3.1.5 تا 3.1.8 را وارد نمی‌کند**.

سه خواستۀ این دور، دقیقاً همان‌طور که تأیید شد:

| # | خواسته | وضعیت |
|---|--------|--------|
| ۱ | حذف لینک‌های اجتماعی از پایین صفحه | ✅ فقط **باکس شناور پایین-راست** حذف شد؛ ستون چهارم فوتر («ما را دنبال کنید») سر جایش است و حتی دو آیکن تازه هم گرفت |
| ۲ | باکس خوشگلِ لینک‌های اجتماعی در صفحۀ پست، **بالای** لینک‌های دانلود (تلگرام · اینستاگرام · بله · روبیکا) با مدیریت از تنظیمات قالب | ✅ باکس `.ab-social-box` در `single.php` **بالای باکس پلیر** (زیر متن/ترانه، قبل از ویدیو و پلیر → در عمل بالاتر از بلوک دانلود) |
| ۳ | پلیر فعلی جایش بماند + نوار چسبانِ پخش پایین صفحه | ✅ پلیر قالب **دست نخورد**؛ نوار `.ab-sticky` کنترل همان `<audio id="audio">` است |

> نکتهٔ ۲: جای نهایی باکس را «بالای باکس پلیر» گذاشتم (درخواست دوم شما در همین دور)؛ این جای‌گیری را با یک خط در `single.php` می‌شود به بالای لینک‌های دانلود منتقل کرد (پایین‌این سند: §۶).

---

## ۱) فایل‌های قابل دانلود (raw — مستقیم از برچسب `v3.2.0`)

جایگزینی در `wp-content/themes/javanseda/`:

| فایل مقصد روی هاست | لینک raw |
|---|---|
| `inc/ab-post-extras.php` (**تازه**) | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.0/drop-in/inc/ab-post-extras.php |
| `footer.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.0/drop-in/footer.php |
| `single.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.0/drop-in/single.php |
| `inc/theme-options.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.0/drop-in/inc/theme-options.php |
| `myfunctions.php` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.0/drop-in/myfunctions.php |
| `style.css` | https://github.com/Masoumiofficial/javan-seda/raw/refs/tags/v3.2.0/drop-in/style.css |

زیپ‌ها (در `artifacts/`، داخل مخزن):

* `drop-in-v3.2.0.zip` — شش فایل بالا با ساختار `new/` و `other/` + README فارسی ← **همین را نصب کنید**
* `rollback-v3.2.0.zip` — نسخۀ `v3.1.4` همان پنج فایلِ جایگزینی + `drop-in-v3.1.4.zip` + README بازگشت
* `javanseda-v3.2.0-theme.zip` — کل پوشۀ قالب (برای کسی که می‌خواهد قالب را از نو آپلود کند)

## ۲) ترتیب نصب (۴ قدم)

1. **اول** `inc/ab-post-extras.php` را در `wp-content/themes/javanseda/inc/` کپی کنید (فایل تازه است؛ حذفش = بازگشت کامل).
2. پنج فایل باقی‌مانده را با فایل هم‌نام قالب **جایگزین** کنید.
3. پیشخوان → تنظیمات قالب → بخش «شبکه‌های اجتماعی»: `بله` و `روبیکا` را پر کنید، متن دکمه‌ها را بنویسید و سوئیچ «باکس شبکه‌های اجتماعی در صفحهٔ پست» را روی **on** بگذارید.
4. کش را خالی کنید و یک صفحۀ پست باز کنید. **اگر هیچ لینکی پر نباشد، باکس اصلاً چاپ نمی‌شود** (این عمدی است تا باکس خالی نبینید).

---

## ۳) نقشۀ تغییرات فایل‌به‌فایل

### `inc/ab-post-extras.php` — جدید، ۱۲٫۹ کیلوبایت، کل منطق این دور
تنها فایلِ «مغز». هیچ فیلتر روی `the_content` و `template_include` ندارد، هیچ تاکسونومی/هوک سراسری اضافه نمی‌کند.

| تابع | کار |
|---|---|
| `javan_seda_x_option( $k, $d )` | خواندن گزینه با `ot_get_option` + `fallback`؛ اگر Option Tree نصب نباشد خطا نمی‌دهد |
| `javan_seda_x_field( $label, $value, $echo )` | کمکى برای متن فیلدها |
| `javan_seda_x_on()` | بررسی پرچم `JAVANSEDA_POST_EXTRAS` (پیش‌فرض `true`) |
| `javan_seda_social_box( $post_id, $echo )` | باکس اجتماعی؛ با `fixbtn==='off'` یا نبودِ هر چهار لینک ⇒ **رندر صفر** |
| `javan_seda_sticky_player_src()` | رشتهٔ JS نوار چسبان (بدون jQuery، بدون وابستگی به JS قالب) |
| `javan_seda_sticky_player()` | چاپ نوار + اسکریپت روی `wp_footer` با اولویت `30`، فقط در `is_single()` |

فراخوانی‌ها همه با `add_action( 'wp_footer', 'javan_seda_sticky_player', 30 )` و پیچیده در `function_exists` — همان درسی که از دور ۱۶ گرفتیم.

### `single.php` — ۵ خط اضافه، هیچ خطی حذف نشد
بالای بلوک ویدیو/پلیر (بلافاصله بعد از `<div class="clear"></div>` و پیش از `<?php if(get_field('video720')`) این بلوک اضافه شد:

```php
if ( function_exists( 'javan_seda_social_box' ) ) { javan_seda_social_box( get_the_ID() ); }
```

* `download_box` و `dl_links_box` و هر دو پلیر (`music_box_player taktarcks` و پلیر آلبوم + playlist) **دست‌نخورده‌اند**.
* فایل همچنان CRLF است (۶۷۴ CR؛ ۶۷۱ سطرِ قالب + ۵ سطر تازه، همۀ خطوط CRLF) و `\n` بیرونی‌ها حفظ شده.

### `footer.php` — حذف باکس شناور + دو آیکن تازه
* سطرهای ۶ تا ۱۲ (بلوک `fix_bottom_right_box` با `wp_is_mobile()` و `ot_get_option('fixbtn')`) **حذف** و با یک بلوک کامنت هفت‌خطی جایگزین شد که می‌گوید چطور برگرداند.
* ستون چهارم فوتر (`socials_box ab-footer__socials`) **باقی** و بعد از آیکن آپارات دو آیکن تازه اضافه شد: `bale` → `icofont-chat`، `rubika` → `icofont-comment` (الگوی `if(ot_get_option('x') !="")` حفظ شد).
* سه سطر انتهایی `<?php wp_footer(''); ?>` · `</body>` · `</html>` **بدون تغییر**.

### `inc/theme-options.php` — ۴ فیلد تازه + بازنویسی معنای `fixbtn`
در بخش `socials`:

| کلید | نوع | برچسب | پیش‌فرض |
|---|---|---|---|
| `bale` | text | بله | (خالی) |
| `baletxt` | text | متن بله | `کانال بله ` |
| `rubika` | text | روبیکا | (خالی) |
| `rubikatxt` | text | متن روبیکا | `کانال روبیکا ` |
| `fixbtn` | on-off (**بدون تغییر نوع/پیش‌فرض**) | **باکس شبکه‌های اجتماعی در صفحهٔ پست** | on |

`fixbtn` عمداً به‌همین‌کلید بازنویسی شد تا نیازی به مهاجرت داده نباشد: `on` = باکس صفحهٔ پست، `off` = هیچ باکسی. مقدار ذخیره‌شدۀ قبلی شما (`on`) همان‌جا هست و دست‌نخورده می‌ماند. `old_label` هم برای یادآوری برچسب قبلی اضافه شد.

### `myfunctions.php` — یک بلوک require، بعد از بلوک `ab-perf`
```php
$_javan_seda_extras = trailingslashit( get_template_directory() ) . 'inc/ab-post-extras.php';
if ( is_readable( $_javan_seda_extras ) ) { require_once $_javan_seda_extras; }
unset( $_javan_seda_extras );
```
الگوی عینِ بلوک‌های `ab-seo` / `ab-perf` / `ab-news`: با `is_readable` ⇒ نبود فایل = سکوت کامل.

### `style.css` — بلوک ۲۴ (بعد از بلوک ۲۳) + `Version: 3.2.0`
`/* --- انتهای بلوک ۲۴ (v3.2.0) --- */` در انتهای فایل. قواعد `.ab-social-box*` ، `.ab-social*` و `.ab-sticky` / `body.ab-sticky-on` / `#ab-sticky-player[hidden]` + `@media (max-width:767px)` + `@media print`.

* پالت: `#FFFFFF` پس‌زمینه · `#185ADB` لهجه · `#111111` متن + دو خاکستری خنک (`#8a92a6`, `#e6ebf3`, `#f2f5fb`). **هیچ `gradient(` در فایل نیست** (بررسی شد: صفر).
* `{` و `}` برابرند (فایل ۲۷۷۵ سطر). هیچ متنی بیرون از بلوک کامنت اضافه نشد.

---

## ۴) نوار چسبانِ پخش — رفتار

* فقط در `is_single()` و روی `wp_footer` چاپ می‌شود؛ اگر تگ `<audio id="audio">` قالب پیدا نشود، **هیچ چیزی** چاپ نمی‌شود (`if ( ! a || ! bar ) return;`).
* کنترل همان تگ صوتی قالب است، نه پلیر دوم: پلی/توقف، میلهٔ زمان (با `direction:ltr` تا در RTL گیج‌کننده نباشد)، نشان Time/Duration با ارقام فارسی، عنوان و خواننده، بندانگشتی `thumb3` (۶۰×۶۰، موجود در `myfunctions.php:50`) و دکمۀ «دانلود» که به `#download_box` اسکرول می‌کند.
* نوار با `hidden` چاپ می‌شود و JS آن را باز می‌کند ⇒ اگر اسکریپت اجرا نشود، نوار خاموش و بی‌آزار است.
* ✕ نوار را می‌بندد و در `sessionStorage.ab_sp_off` می‌نویسد تا در همان نشست دوباره باز نشود.
* `body.ab-sticky-on{padding-bottom:84px}` ⇒ نوار روی فوتر نمی‌افتد.

## ۵) خاموش‌کردن‌ها (هرکدام یک خط، هرکدام برگشت‌پذیر)

| کلید | پیش‌فرض | اثر |
|---|---|---|
| `define( 'JAVANSEDA_POST_EXTRAS', false );` | true | کل لایه (باکس + نوار) خاموش؛ کل فایل عملاً بی‌اثر |
| `define( 'JAVANSEDA_STICKY_PLAYER', false );` | true | فقط نوار چسبان حذف؛ باکس اجتماعی می‌ماند |
| تنظیمات قالب → `fixbtn` = off | on | فقط باکس اجتماعی صفحهٔ پست |
| حذف `inc/ab-post-extras.php` | — | بازگشت کامل؛ `single.php` و `myfunctions.php` هیچ خطایی نمی‌دهند |

## ۶) اگر خواستید باکس را به «بالای لینک‌های دانلود» منتقل کنید

در `single.php` همان بلوک `<?php … javan_seda_social_box … ?>` را از جایش کنونی بردارید و **بلافاصله قبل از** این بلوک بگذارید:

```php
<?php
/* ---------- تیتر بخش دانلود (هدف جست‌وجوی «دانلود آهنگ X از Y») ----------
```

لنگر یکتاست (`$taranehyar_download_title = true;` با شمارۀ تکرار ۱). جای فعلی هم عمداً بالاتر است تا موبایل‌ها قبل از پلیر راهِ دنبال‌کردن را ببینند.

## ۷) بررسی‌های اجراشده روی همین نسخه

* `node /home/user/tools/check.js` → `OK` برای `inc/ab-post-extras.php` · `footer.php` · `single.php` · `inc/theme-options.php` · `myfunctions.php`.
* `python3 tools/compat-check.py` روی پنج فایل دست‌خورده + فایل تازه → **0 hit** (بدون `??`، `fn()`، `match`، `?->`، بستۀ `static`، تایپ‌گذاری). شش هیت گزارش‌شدۀ کل قالب در `inc/ab-perf.php` و `inc/ab-seo.php` **مربوط به همین دور نیست** (در `v3.1.4` هم عیناً همین بود؛ فایل‌های لم‌خورده).
* `git diff --name-only v3.1.4 -- javanseda` → فقط پنج فایل + یک فایل تازه؛ `header.php`، `functions.php`، `inc/widgets.php`، `inc/ab-sidebars.php`، `index.php`، `home.php`، `404.php` **بدون تغییر**.
* هر شش فایل `drop-in/` با فایل معادلش در `javanseda/` sha1 یکسان (تأیید شد).
* شمارش‌ها: `javan_seda_social_box(` در `single.php` = ۱ · `gradient(` در `style.css` = ۰ · `download_box` در `single.php` = ۱ (دست‌نخورده).
* آیکن‌ها در `css/icofont.min.css` تأیید شدند: `paper-plane` · `instagram` · `chat` · `comment` · `share-alt` · `thin-left` · `pause` · `play-alt-2` · `close-line` · `download` · `music-note` · `thin-up`. (هرگز `icofont-telegram` یا `icofont-ui-message` که در این نسخه وجود ندارند.)

## ۸) کارهای دستی که از دست من خارج است

1. پرکردن `بله` و `روبیکا` (و در صورت نیاز همان `telegram`/`instagram`) در تنظیمات قالب — وگرنه باکس چاپ نمی‌شود.
2. `wp search-replace 'http://www.ahangban.ir' 'https://ahangban.ir'` + ریدایرکت ۳۰۱.
3. فعال‌کردن افزونۀ SEO/سایت‌مپ (هر دو sitemap الان ۴۰۴ هستند) و ساخت آرشیو `news` برای تیکر.
4. ادغام `singer`های دوبل و فیلد ACF `duration` (برای `duration` در schema).
5. اگر جایی باکس را خالی دیدید: اولین متهم، پرنبودن لینک‌ها یا کش است؛ اولین اقدام، حذف `inc/ab-post-extras.php`.
