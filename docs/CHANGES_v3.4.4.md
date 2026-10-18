# javan-seda · v3.4.4 — اسلایدر صدا از «پاپ‌آپِ عمودیِ مخفی» به گوشۀ ردیف

داغ‌ترین فایلِ این دور یک بلوک CSS است؛ سه فایل آپلود می‌شود و هیچ فایل PHP
تغییر نمی‌کند (پس هاشِ ۱۶ فایل دیگر گارد دست‌نخورده است).

## گزارش و علت ریشۀ کار

> «تو قسمت پلیر، کم‌وزیاد کردن صدا آمده رو خط پخش موزیک، همان اول؛ در حالی که باید
> گوشۀ سمت چپ یا راست، با فاصله از المان‌های دیگر بایستد.»

علت در **خودِ قالب مادر** است، نه در 3.4.2 یا 3.4.3. پلیر این قالب هرگز اسلایدر
افقی نداشته:

| خط `style.css` | قاعدۀ قالب مادر | چه می‌کند |
|---|---|---|
| 1117 | `.volume_box{width:30px; float:right; margin:-7px -40px 0 0; position:relative}` | جایگاه ۳۰px‌ای، با ۷px بالا و **۴۰px به‌سمت بیرون** کشیده شده |
| 1119–1127 | `.progress-box input[type=range]{position:absolute; top:-50px; left:-30px; transform:rotate(-90deg); visibility:hidden; opacity:0}` | خودِ اسلایدر = **نوار عمودیِ چرخیده** که ۵۰px بالاتر از جای خودش، مطلق و **مخفی** رندر می‌شود |
| 1128 | `.volume_box:hover input[type=range]{visibility:visible;opacity:1}` | فقط با هاور روی آیکن دیده می‌شد ⇒ روی موبایل و لمسی عملاً نامرئی/غیرقابل‌استفاده |
| 1649 | `@media (max-width:450px){ .volume_box{display:none !important} }` | قالب مادر در موبایلِ خیلی کوچک کلاً پنهانش می‌کند (دست نزدیم) |

بلوک ۳۴ (v3.4.1) عرض و ارتفاع اسلایدر را درست کرد و `.volume_box` را flex-item
کرد، ولی آن پنج اعلامیۀ `position / top / left / transform / visibility` را
خنثی نکرد. نتیجه: یک اسلایدر ۹۲px‌ای که همچنان `absolute` و `rotate(-90deg)` و
`margin-right:-40px` است، ۵۰px بالاتر و ۴۰px به‌سمت بیرون رانده می‌شود ⇒ روی خطِ
دکمۀ پخش می‌افتد و با بقیۀ المان‌ها قاطی می‌شود. دقیقاً همان چیزی که گزارش شد.

## بلوک ۳۶ ( انتهای `style.css` )

```css
.music_box_player .volume_box{                      /* از float و مارجین منفی آزاد می‌شود */
  display:flex; align-items:center; gap:10px;
  flex:0 0 auto; min-width:0;
  float:none; width:auto;
  margin:0; margin-inline-start:auto;               /* ⇒ چسبیده به گوشۀ انتهایی ردیف (RTL: چپ) */
  padding-inline-start:16px;                        /* ⇒ فاصله از نوار پیشرفت */
  position:relative; top:auto; left:auto; right:auto; bottom:auto;
}
.music_box_player .volume_box input[type=range]{    /* پاپ‌آپِ عمودی → کنترل افقیِ همیشگی */
  position:static; top:auto; bottom:auto; left:auto; right:auto;
  visibility:visible; opacity:1;
  -webkit-transform:none; -moz-transform:none; transform:none;
  -webkit-appearance:auto; -moz-appearance:auto; appearance:auto;
  direction:ltr;                                    /* کم ← چپ، زیاد ← راست */
  width:108px; max-width:26vw; height:20px;
  margin:0; padding:0; border:0; background:transparent;
  accent-color:var(--ab-blue); cursor:pointer;
}
```

+ آیکن `> i` از `float:left;font-size:2em;margin-top:-3px` قالب مادر آزاد شد
(۱۷px، بی‌فلوت) و `:focus-visible` حلقۀ آبی می‌گیرد.
+ `.progress-box{column-gap:14px}` ⇒ فاصله حتی وقتی flex به هر دلیلی اعمال نشود.
+ در `≤760px` همان رفتار پسندیدۀ قبلی: اسلایدر یک خطِ تمام‌عرض زیر نوار پیشرفت
(`flex:1 1 100%; order:5`، `margin-inline-start:0`) تا روی موبایل زیر دکمه‌ها نرود.

**چرا `appearance:auto` + `accent-color` و نه بازسازی pseudo-element؟** چون
قالب مادر `input` را `appearance:none` کرده و شش بلوک `::-webkit-slider-thumb` /
`::-moz-range-thumb` / `::-ms-*` / track دارد که برای *نوار عمودی* نوشته شده‌اند؛
ترمیم‌شان در سه موتور متفاوت یعنی سه نتیجهٔ متفاوت. کنترل بومیِ مرورگر با
`accent-color` در Chrome · Safari · Firefox یک خط کد است و همیشه با تم هم‌خوان
می‌ماند (در مرورگرهای خیلی قدیمی فقط رنگش پیش‌فرض است، نه اینکه بشکند).

**DOM دست‌نخورده است** — `inc/tarlanweb_player.php` و `js/scripts.js` هیچ تغییری
نکردند؛ `$("#volume-bar").change(…)` همان‌طور کار می‌کند و ما فقط `visibility` را
از حالت «مخفی تا هاور» درآورديم، پس روی موبایل/لمسی هم اسلایدر قابل استفاده است
(قبلاً نامرئی بود).

## خاموش‌کردن / بازگشت

* «تنظیمات قالب ← کنترل رابط ← **دکمۀ صدا در پلیر** = خاموش» ⇒ `body.ab-player-novol`
  و `display:none` روی `.volume_box` (همان کنترل 3.4.1، هیچ چیز تازه لازم نبود).
* «شکل پلیر = ساده (قالب مادر)» ⇒ کل کارت پلیر بی‌کادر.
* یا حذف بلوک ۳۶ از انتهای `style.css` (و اجرای `python3 tools/ab-ui-sync.py`).

## فایل‌ها (۳ فایل)

| فایل | حجم | چه |
|---|---|---|
| `javanseda/style.css` | 193,478 B | بلوک ۳۶ + `Version: 3.4.4` |
| `javanseda/css/ab-ui.css` | 83,358 B | کپیِ گارد، بازتولیدشده با `tools/ab-ui-sync.py` |
| `javanseda/inc/ab-sidebars.php` | 22,604 B | یک هاش بازنویسی‌شده (`style.css`)؛ `css/ab-ui.css` در فهرست هاش نیست ⇒ ۱۷/۱۷ سازگار |

## اعتبارسنجی

| بررسی | نتیجه |
|---|---|
| css-tree روی کل `style.css` | ۰ خطا |
| `!important` در بلوک ۳۶ | **۰** (در اعلامیه‌ها؛ دو بار فقط داخل کامنتِ توضیحی) |
| `gradient(` | ۰ |
| ترتیب Cascade | قاعدۀ بلوک ۳۶ با انتخابگر یکسانِ بلوک ۳۴، **آخرِ فایل** ⇒ برنده؛ ۶ قاعدۀ `input[type=range]` بازخوانی شد و هر شش‌تایشان شمرده شد |
| `tools/ab-ui-sync.py` | `css/ab-ui.css` ← 83,358 B |
| `tools/expected-hashes.py --check` | ۱۷/۱۷ مطابق |
| `tools/compat-check.py` | ۰ خطا |
| فایل‌های PHP | دست‌نخورده این دور (۵۸/۵۸ در v3.4.3 سازگار بودند) |

**تست‌ناپذیر (صریح):** رندر واقعی در مرورگر. این رفع بر پایۀ خواندن ترتیبیِ
قواعد نوشته شده، نه دیدن صفحه؛ اگر هنوز جابه‌جایی دارید، بگویید کدام گوشه
(چپ/راست) و چه فاصله‌ای، تا همان را عدد می‌دهم — یک عدد در `--ab-player-*`، نه یک بازطراحی دیگر.
