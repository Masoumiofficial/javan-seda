# ساخت Child Theme برای «جاوان‌سدا» (جواب همیشگیِ مشکل آپدیت قالب مادر)

**این سند اختیاری است.** تا وقتی که «بروزرسانی قالب مادر» را از پیشخوان نمی‌زنید،
همان گارد `inc/ab-ui-guard.php` (افزودۀ 3.3.8) کار را راه می‌اندازد: اگر مارکر
`ab-ui-layer` از `style.css` پاک شود، کپیِ لایه از `css/ab-ui.css` به‌طور خودکار
enqueue می‌شود.

Child theme تنها راهی است که بعداً `style.css` مادر **هرگز** لمس نمی‌شود — ولی یک
هزینهٔ مهم دارد که باید قبل از شروع بدانید.

---

## ⚠️ هزینهٔ اصلی: تنظیمات Option Tree به نام پوشۀ قالب ذخیره می‌شود

این قالب تنظیماتش را با Option Tree در `theme_mods_<stylesheet>` نگه می‌دارد
(`logo` ، `favicon` ، `vip_num` ، `arti_title` ، `ads_*` ، `index_pv` ، …). اگر child
theme را فعال کنید، `stylesheet` عوض می‌شود ⇒ **همۀ این گزینه‌ها برای وردپرس خالی
به‌نظر می‌رسند** (لوگو، تبلیغات، تعداد VIP، متن فوتر و …). راه‌حل‌ها:

1. **ساده‌ترین و توصیه‌شدۀ ما:** child نسازید؛ همان گارد + zip بکاپ کافی است.
2. اگر اصرار به child دارید، بعد از فعال‌کردن child این را یک‌بار با WP-CLI اجرا کنید
   (نسخۀ `theme_mods` را از parent به نام child کپی می‌کند):
   ```
   wp option get theme_mods_javanseda --format=json > /tmp/tm.json
   wp option update theme_mods_javanseda_child --format=json < /tmp/tm.json
   wp option delete theme_mods_javanseda   # فقط بعد از اینکه همه‌چیز درست به‌نظر رسید
   ```
   و حتماً در `functions.php` چایل بگذارید تا اگر نام پوشه فرق داشت، دستی برگردانید.
3. موازی‌کاری نکنید: یا کل قالب را child می‌کنید، یا همان روش «فایل‌های قالب + drop-in» را ادامه می‌دهید.

---

## ساختار پیشنهادی (کمینه‌ترین حالت)

```
wp-content/themes/javanseda/            ← قالب مادر، دست‌نخورده (آپدیت‌شدنی)
wp-content/themes/javanseda-child/
├── style.css                           ← فقط هدر + بچه‌بودن
├── functions.php                       ← enqueue استایل مادر + لایۀ اختصاصی
└── css/
    └── ab-ui.css                       ← کپی لایۀ اختصاصی (از همین مخزن)
```

### ۱) `style.css`

```css
/*
Theme Name:   آهنگ‌بان (فرزندِ جاوان‌سدا)
Template:     javanseda
Version:      3.3.8
Text Domain:  javanseda-child
*/
```

> قید `Template: javanseda` باید **دقیقاً** نام پوشۀ مادر باشد. `Version` را هم بنویسید؛
> کش‌شکنی وردپرس از همین عدد استفاده می‌کند.

### ۲) `functions.php`

```php
<?php
add_action( 'wp_enqueue_scripts', 'javanseda_child_assets', 20 );
function javanseda_child_assets() {
	// استایل مادر (همان handle که myfunctions.php ثبت کرده، تا وابستگی‌ها نشکنند)
	$main = wp_style_is( 'javanSedaMainStyle', 'registered' ) ? 'javanSedaMainStyle' : '';
	wp_enqueue_style(
		'javanseda-child',
		get_stylesheet_directory_uri() . '/css/ab-ui.css',
		$main ? array( $main ) : array(),
		wp_get_theme()->get( 'Version' )
	);
}
```

### ۳) `css/ab-ui.css`

همان فایلی که از 3.3.8 در مخزن است (`javanseda/css/ab-ui.css`) — کپیِ کامل ناحیۀ
`ab-ui-layer` تا انتهای `style.css`. یک خط هم به ابتدایش اضافه کنید تا اگر روزی
گارد را حذف کردید، مشخص باشد نسخهٔ کدام است.

---

## چه چیزی را «در چایل» بگذاریم و چه چیزی را نه

| مورد | در چایل؟ | چرا |
|------|----------|-----|
| CSS لایۀ اختصاصی (بلوک ۲۴ → ۳۲) | ✅ بله، در `css/ab-ui.css` | دقیقاً همان چیزی که آپدیت مادر پاکش می‌کند |
| `css/ab-glass.css` | ⚪ می‌تواند در مادر بماند | فایل جدا است و آپدیت مادر آن را حذف نمی‌کند؛ اگر خواستید کپی کنید |
| `inc/ab-*.php` (perf, credits, seo, glass, sidebars…) | ⛔ نه، در مادر بماند | با `get_template_directory()` include می‌شوند؛ بردنشان به چایل فقط duplicate می‌سازد |
| `header.php` / `single.php` / `home.php` و بقیۀ تمپلیت‌ها | ⛔ نه (مگر ناگزیر) | کپی‌کردن تمپلیت یعنی از این به بعد **شما** باید هر تغییر مادر را دستی merge کنید |
| `style.css` مادر | ⛔ دست نزنید | دیگر مال شما نیست؛ بگذارید آپدیت‌شدنی بماند |
| تنظیمات Option Tree | ⚠️ باید منتقل شوند | بخش ⚠️ بالا |

قاعدهٔ کلی: **CSS و فایل‌های «abi» که خودمان ساخته‌ایم → چایل؛ تمپلیت‌های مادر → هرگز.**
هر تمپلیتی که در چایل کپی کنید، از آن لحظه به بعد روی آپدیت‌های مادر به‌روز نمی‌شود و
باگ‌های جدید مادر را هم به‌ارث نمی‌برید — و رفعیهایی که بعداً روی همان تمپلیت در مادر
انجام می‌دهم، باید دوباره در چایل تکرار شوند.

---

## بعد از ساخت چایل، این دو چیز را فراموش نکنید

1. `JAVANSEDA_UI_GUARD` را خاموش کنید تا گارد مادر دو بار لایه را enqueue نکند:
   ```php
   define( 'JAVANSEDA_UI_GUARD', false );
   ```
2. تست‌های سریع: `/` (ریل‌ها و تیکر)، صفحۀ یک آهنگ (پلیر + شناسنامه + JSON-LD)،
   `/singers/`، `/404` (دستی)، حالت شب، و منوی موبایل. در همۀ این‌ها باید فقط
   `ab-ui.css` دیده شود، نه `style.css` دوباره‌سازی‌شده.

## بازگشت

فعال‌سازی مجدد «جاوان‌سدا» (مادر) در پیشخوان → نمایش → پوسته‌ها کافی است؛ چون هیچ فایل
مادر و هیچ داده‌ای تغییر نکرده، سایت دقیقاً برمی‌گردد. اگر `theme_mods_javanseda` را
پاک نکرده باشید، تنظیمات Option Tree هم سرِ جایشان هستند (به همین دلیل در مرحلهٔ ۲
بالا، `wp option delete` را آخر انجام دهید).
