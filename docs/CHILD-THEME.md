# ساخت Child Theme برای «ترانه یار»

**این سند اختیاری است.** تا وقتی «به‌روزرسانی قالب مادر» را از پیشخوان نمی‌زنید،
گارد `inc/ab-ui-guard.php` کار را راه می‌اندازد: اگر مارکر لایۀ اختصاصی از
`style.css` پاک شود، کپی لایه از `css/ab-ui.css` خودکار enqueue می‌شود. خاموش‌کردنش:

```php
define( 'TARA_UI_GUARD', false );
```

Child theme تنها راهی است که بعداً `style.css` مادر **هرگز** لمس نمی‌شود — ولی یک
هزینهٔ مهم دارد که باید قبل از شروع بدانید.

---

## ⚠️ هزینهٔ اصلی: تنظیمات به نام پوشهٔ قالب ذخیره می‌شوند

قالب تنظیمات OptionTree را در گزینۀ `option_tree` نگه می‌دارد و بخشی از چیزها
(`theme_mods_…`) به نام stylesheet بسته است. اگر child را فعال کنید، `stylesheet`
عوض می‌شود و ممکن است لوگو/تبلیغات/شمارنده‌ها خالی به‌نظر برسند. راه‌حل‌ها:

1. **ساده‌ترین و توصیه‌شدهٔ ما:** child نسازید؛ همان گارد + zip بکاپ کافی است.
   گزینه‌های `option_tree` مستقل از نام پوشه‌اند و با child هم خوانده می‌شوند؛
   خطر اصلی فقط `theme_mods` است.
2. اگر child می‌سازید، بعد از فعال‌کردنش یک‌بار این را با WP-CLI بزنید:
   ```
   wp option get theme_mods_taranehyar --format=json > /tmp/tm.json
   wp option update theme_mods_taranehyar-child --format=json < /tmp/tm.json
   ```
   و `wp option delete theme_mods_taranehyar` را تا وقتی همه‌چیز درست است، نزنید.
3. موازی‌کاری نکنید: یا کل قالب را child می‌کنید، یا همان روش «Overwrite فایل‌های
   قالب» را ادامه می‌دهید.

---

## ساختار پیشنهادی (کمینه‌ترین حالت)

```
wp-content/themes/taranehyar/            ← قالب مادر، دست‌نخورده (آپدیت‌شدنی)
wp-content/themes/taranehyar-child/
├── style.css                           ← فقط هدر + بچه‌بودن
├── functions.php                       ← enqueue استایل مادر + لایۀ اختصاصی
└── css/
    └── ab-ui.css                       ← کپی لایۀ اختصاصی (از همین مخزن)
```

### ۱) `style.css`

```css
/*
Theme Name:   ترانه یار (فرزند)
Template:     taranehyar
Version:      3.7.0
Text Domain:  taranehyar-child
*/
```

> `Template: taranehyar` باید **دقیقاً** نام پوشهٔ مادر باشد. `Version` را هم بنویسید؛
> کش‌شکنی وردپرس از همین عدد استفاده می‌کند.

### ۲) `functions.php`

```php
<?php
add_action( 'wp_enqueue_scripts', 'taranehyar_child_assets', 20 );

function taranehyar_child_assets() {
	// هندل استایل مادر: taranehyar-style (functions.php ثبت می‌کند)
	$deps   = wp_style_is( 'taranehyar-style', 'registered' ) ? array( 'taranehyar-style' ) : array();
	$deps[] = 'taranehyar-ui'; // اختیاری: توکن‌های --ty-*

	wp_enqueue_style(
		'taranehyar-child',
		get_stylesheet_directory_uri() . '/css/ab-ui.css',
		array_values( array_filter( $deps, 'wp_style_is' ) ),
		wp_get_theme()->get( 'Version' )
	);
}
```

> اگر روی `taranehyar-ui` وابسته می‌شوید، مطمئن شوید `wp_style_is( 'taranehyar-ui',
> 'registered' )` هم true است، چون آن فایل فقط در صفحه‌هایی بار می‌شود که قالب
> detect می‌کند. امن‌ترین حالت: فقط `taranehyar-style`.

### ۳) `css/ab-ui.css`

همان فایلی که از 3.3.8 در مخزن است (`taranehyar/css/ab-ui.css`) — کپی کامل ناحیهٔ
`ab-ui-layer` تا انتهای `style.css`. یک خط هم به ابتدایش اضافه کنید تا اگر روزی گارد
را حذف کردید، مشخص باشد نسخهٔ کدام است.

---

## چه چیزی را «در child» بگذاریم و چه چیزی را نه

| مورد | در child؟ | چرا |
|------|-----------|-----|
| CSS لایۀ اختصاصی | ✅ بله، در `css/ab-ui.css` | دقیقاً همان چیزی که آپدیت مادر پاکش می‌کند |
| `css/ab-glass.css` | ⚪ می‌تواند در مادر بماند | فایل جداست؛ آپدیت مادر حذفش نمی‌کند |
| `css/tara-ui.css` / `tara-artist.css` / `tara-home.css` | ⛔ نه | ۳.۷.۰ از همین‌ها می‌خواند؛ کپی‌کردنشان دو نسخه می‌سازد |
| `inc/tara-*.php` (UI، تاکسونومی‌ها، آرشیو) | ⛔ نه، در مادر بماند | با `require_once` از مادر include می‌شوند |
| `inc/ab-*.php` (perf, credits, seo, glass, sidebars…) | ⛔ نه | همین دلیل |
| `header.php` / `single.php` / `home.php` / `taxonomy-*.php` | ⛔ نه (مگر ناگزیر) | از این به بعد **شما** باید هر تغییر مادر را دستی merge کنید |
| `css/admin-settings.css` | ⛔ نه | پوستهٔ صفحهٔ تنظیمات، فقط با همان فایل مادر کار می‌کند |
| `style.css` مادر | ⛔ دست نزنید | بگذارید آپدیت‌شدنی بماند |
| تنظیمات OptionTree | ⚠️ باید منتقل شوند | بخش ⚠️ بالا |

قاعده کلی: **CSS و فایل‌های اختصاصی خودمان → child؛ تمپلیت‌های مادر → هرگز.**
هر تمپلیتی که در child کپی کنید، از آن لحظه روی آپدیت‌های مادر به‌روز نمی‌شود و
باگ‌های جدید مادر را هم به ارث نمی‌برید.

---

## پس از ساخت child، این دو کار را فراموش نکنید

1. گارد را خاموش کنید تا لایه دو بار enqueue نشود:
   ```php
   define( 'TARA_UI_GUARD', false );
   ```
2. تست‌های سریع: `/` (اسلایدر تک‌اسلاید)، یک صفحۀ آهنگ (پلیر + چیپ تاکسونومی‌ها)،
   `/genre/…` و `/artist/…`، یک آهنگ بدون فایل صوتی، `/404`، حالت شب، منوی موبایل،
   و نوار چسبان پلیر بعد از تغییر صفحه.

## بازگشت

فعال‌سازی مجدد «ترانه یار» (مادر) در پیشخوان → نمایش → پوسته‌ها کافی است؛ چون هیچ
فایل مادر و هیچ داده‌ای تغییر نکرده، سایت دقیقاً برمی‌گردد. `theme_mods_taranehyar`
را تا آخرین لحظه پاک نکنید تا گزینه‌ها سر جایشان باشند.
