<?php
/**
 * Initialize the options before anything else.
 */
add_action( 'admin_init', 'custom_theme_options', 1 );

/**
 * Build the custom settings & update OptionTree.
 */
function custom_theme_options() {
  /**
   * Get a copy of the saved settings array. 
   */
  $saved_settings = get_option( 'option_tree_settings', array() );
  
  /**
   * Custom settings array that will eventually be 
   * passes to the OptionTree Settings API Class.
   */
  $custom_settings = array( 
'contextual_help' => array( 
'sidebar'=> ''
),
'sections'=> array( 
array('id'=>'general','title'=>'همگانی'),
array('id'=>'brand','title'=>'رنگ‌ها و هویت'),
array('id'=>'homes','title'=>'صفحه اصلی'),
array('id'=>'dl_pages','title'=>'صفحه دانلود آهنگ'),
array('id'=>'ab_ui','title'=>'ترانه یار — ظاهر و رفتار'),
array('id'=>'radio','title'=>'رادیو و دی‌جی'),
array('id'=>'account','title'=>'حساب کاربری و ورود'),
array('id'=>'footer','title'=>'پانوشت'),
array('id'=>'socials','title'=>'شبکه های اجتماعی'),
array('id'=>'ads1','title'=>'تبلیغات'),
array('id'=>'about','title'=>'درباره سایت'),
array('id'=>'dev','title'=>'توسعه دهنده'),
),
'settings'=>array( 

/* ---------------------------------------------------------------------------
   v3.7.7 — «رنگ‌ها و هویت»: رنگ اصلی/فرعی سایت + رنگ منو و جست‌وجوی موبایل
   همه به‌صورت متغیر CSS چاپ می‌شوند (inc/tara-brand.php) و هیچ فایلی ویرایش
   نمی‌شود. خالی‌گذاشتن هر مورد = همان رنگ پیش‌فرض قبلی قالب.
   ------------------------------------------------------------------------- */
array(
'id'=>'brand_guide',
'label'=>'',
'desc'=>'<strong>رنگ‌ها را همین‌جا عوض کنید.</strong> «رنگ اصلی» روی دکمه‌ها، لینک‌ها، پلیر، نشانگرها و منو اثر می‌گذارد؛ «رنگ فرعی» برای برجسته‌سازی و گرادیان‌ها استفاده می‌شود. رنگ‌های موبایل فقط نوار بالای گوشی، کشوی منو و کادر جست‌وجوی موبایل را عوض می‌کنند. برای برگشت به پیش‌فرض، خانه را خالی و «ذخیره» را بزنید.',
'type'=>'textblock',
'std'=>'',
'section'=>'brand',
'class'=>'ab-guide'
),
array(
'id'=>'brand_primary',
'label'=>'رنگ اصلی سایت',
'desc'=>'پیش‌فرض: #185ADB. این رنگ در همه‌جای قالب (دکمه، لینک، پلیر، نشانگر فعال) به کار می‌رود.',
'std'=>'#185ADB',
'type'=>'colorpicker',
'section'=>'brand'
),
array(
'id'=>'brand_secondary',
'label'=>'رنگ فرعی سایت',
'desc'=>'پیش‌فرض: #6d28d9. برای گرادیان دکمه‌ها، برچسب‌ها و جزئیات تزئینی.',
'std'=>'#6d28d9',
'type'=>'colorpicker',
'section'=>'brand'
),
array(
'id'=>'brand_h_mobile',
'label'=>'',
'desc'=>'رنگ موبایل<span>فقط در گوشی/تبلت دیده می‌شود. هر مورد را خالی بگذارید تا رنگ قبلی همان جا بماند.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'brand',
'class'=>'ab-h'
),
array(
'id'=>'tara_mob_bar_bg',
'label'=>'رنگ نوار بالای گوشی',
'desc'=>'نوارِ بالای صفحه در حالت موبایل (همان‌جا که لوگو و دکمهٔ منو هست). پیش‌فرض: سفید.',
'std'=>'#ffffff',
'type'=>'colorpicker',
'section'=>'brand'
),
array(
'id'=>'tara_mob_btn_bg',
'label'=>'رنگ دکمهٔ منو و ذره‌بین',
'desc'=>'پس‌زمینهٔ مربعِ منو و جست‌وجو در نوار گوشی. پیش‌فرض: #111111.',
'std'=>'#111111',
'type'=>'colorpicker',
'section'=>'brand'
),
array(
'id'=>'tara_mob_bar_text',
'label'=>'رنگ آیکون‌های نوار گوشی',
'desc'=>'رنگ خطوطِ آیکون منو و ذره‌بین. باید با رنگ دکمهٔ بالا کنتراست داشته باشد. پیش‌فرض: سفید.',
'std'=>'#ffffff',
'type'=>'colorpicker',
'section'=>'brand'
),
array(
'id'=>'tara_mob_menu_bg',
'label'=>'رنگ کشوی منو (گوشی)',
'desc'=>'پس‌زمینهٔ منویی که از کنار باز می‌شود. پیش‌فرض: #222222.',
'std'=>'#222222',
'type'=>'colorpicker',
'section'=>'brand'
),
array(
'id'=>'tara_mob_menu_text',
'label'=>'رنگ متن کشوی منو',
'desc'=>'رنگ لینک‌های منوی موبایل. پیش‌فرض: سفید.',
'std'=>'#ffffff',
'type'=>'colorpicker',
'section'=>'brand'
),
array(
'id'=>'tara_mob_search_bg',
'label'=>'رنگ کادر جست‌وجوی گوشی',
'desc'=>'پس‌زمینهٔ کادری که با زدن ذره‌بین باز می‌شود. پیش‌فرض: سفید.',
'std'=>'#ffffff',
'type'=>'colorpicker',
'section'=>'brand'
),
array(
'id'=>'tara_mob_search_text',
'label'=>'رنگ متن جست‌وجوی گوشی',
'desc'=>'رنگ متن و راهنمای داخل کادر جست‌وجو. پیش‌فرض: #333333.',
'std'=>'#333333',
'type'=>'colorpicker',
'section'=>'brand'
),
// General
array(
'id'=>'general_guide',
'label'=>'',
'desc'=>'<strong>کمترین نیاز به تغییر.</strong> اگر نشانک و لوگو را یک بار بگذارید، بقیهٔ این تب اختیاری است. «کلمه کلیدی» تیترِ جایگزینِ هدر است و اگر خالی بماند نام سایت چاپ می‌شود.',
'type'=>'textblock',
'std'=>'',
'section'=>'general',
'class'=>'ab-guide'
),

array(
'id'=>'favicon',
'label'=>'فاویکون',
'desc'=>'پیشنهاد می‌شود اندازه‌ی فاویکون 30px × 30px باشد.',
'std'=> get_stylesheet_directory_uri().'/images/favicon.png',
'type'=>'upload',
'section'=>'general'
),
array(
'id'=>'logo',
'label'=>'لوگو (حالت روز)',
'desc'=>'اندازهٔ نمایش ۱۵۰×۴۰px است، پس تصویر ۳۰۰×۸۰px (دو برابر برای صفحهٔ رِتینا) بگذارید — PNG یا SVG با پس‌زمینهٔ شفاف. همین لوگو را می‌توانید بدون رفتن به این صفحه، از «ترانه یار ← پیشخوان ← لوگوی سایت» هم آپلود کنید.',
'std'=> get_stylesheet_directory_uri().'/images/logo.png',
'type'=>'upload',
'section'=>'general'
),
array(
'id'=>'logo_night',
'label'=>'لوگو (حالت شب)',
'desc'=>'اختیاری. اگر خالی بماند، همان لوگوی روز در حالت شب هم نمایش داده می‌شود. برای هدر تیره یک نسخهٔ روشن/سفید با همان نسبت ۱۵۰:۴۰ (۳۰۰×۸۰px) بگذارید.',
'std'=>'',
'type'=>'upload',
'section'=>'general'
),
array(
'id'=>'general_h_dark',
'label'=>'',
'desc'=>'حالت شب<span>«حالت شب» = دکمۀ تغییر تم در هدر. «تم تاریک» = همان حالت به‌صورت پیش‌فرضِ تاریک برای همهٔ بازدیدکننده‌ها.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'general',
'class'=>'ab-h'
),

array(
'id'=>'sun_moon',
'label'=>'حالت شب',
'desc'=>'امکان انتخاب حالت شب یا دارک مود نمایش داده شود؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'general',
),
array(
'id'=>'dark_modes',
'label'=>'تم تاریک',
'desc'=>'تم تاریک به صورت پیشفرض فعال شود ؟',
'std'=>'off',
'type'=>'on-off',
'section'=>'general',
),
array(
'id'=>'head_h1',
'label'=>'کلمه کلیدی',
'desc'=>'کلمه کلیدی ایندکس را وارد نمایید.',
'type'=>'text',
'std'=>'دانلود آهنگ جدید',
'section'=>'general'
),
array(
'id'=>'reltag',
'label'=>'مطالب بیشتر هنرمند',
'desc'=>'مطالب بیشتر هنرمند بر اساس برچسب یا همان tag نمایش داده شود؟',
'std'=>'off',
'type'=>'on-off',
'section'=>'general',
),
array(
'id' => 'navar_txt',
'label'=>'توضیحات',
'desc'=>'اگر آرشیو هنرمند را با «برچسب‌ها» ساخته‌اید، این گزینه را روی on بگذارید. اگر از بخش اختصاصی «هنرمند» (که زیر برچسب‌ها اضافه شده) استفاده می‌کنید، آن را روی off بگذارید. پیشنهاد ما استفاده از فیلد اختصاصی هنرمند است.',
'type'=>'textblock',
'section'=>'general',
),
array(
'id'=>'reltag1',
'label'=>'مطالب بیشتر',
'desc'=>' مطالب بیشتر زیر برچسب ها نمایش داده شود ؟ در صورت فعال نشدن مطالب مرتبط در ستون کناری نمایش داده میشود.', 
'std'=>'off',
'type'=>'on-off',
'section'=>'general',
),
// homes
array(
'id'=>'homes_guide',
'label'=>'',
'desc'=>'<strong>این تب شلوغ‌ترین تب است</strong>، پس سه‌بخش شده: ویژه و پلیر، بلوک‌های صفحه، ردیف هنرمندان. هر ردیف همان‌جا توضیحش را دارد؛ اگر گزینه‌ای را نمی‌بینید، یعنی بلوکش خاموش است.',
'type'=>'textblock',
'std'=>'',
'section'=>'homes',
'class'=>'ab-guide'
),

array(
'id'=>'fixbvip',
'label'=>'اسلایدر ویژه',
'desc'=>'اسلایدر ویژه نمایش داده شود؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'homes',
),
array(
'id'=>'vip_num',
'label'=>'تعداد اسلایدها',
'desc'=>'تعداد اسلایدهای قابل نمایش در «ویژه». پیش‌فرض ۴.',
'type'=>'numeric-slider',
'min_max_step'=>'1,30,1',
'std'=>'4',
'section'=>'homes'
),
array(
'id'=>'homes_h_player',
'label'=>'',
'desc'=>'پلیر و فهرست‌ها<span>«پلیر ایندکس» همان پخش‌کنندۀ پایینِ کارت‌هاست؛ شکل و فاصلۀ آن از تب «کنترل رابط» تنظیم می‌شود.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'homes',
'class'=>'ab-h'
),

array(
'id'=>'indpl',
'label'=>'پلیر ایندکس',
'desc'=>'پلیر ایندکس نمایش داده شود؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'homes',
),
array(
'id'=>'v_num',
'label'=>'تعداد آهنگ‌ها (این بلوک)',
'desc'=>'تعداد آهنگ‌های قابل نمایش در این بلوک. برای اینکه همه بیایند عدد را بزرگ کنید (مثلاً ۱۰۰).',
'type'=>'numeric-slider',
'min_max_step'=>'1,100,1',
'std'=>'8',
'section'=>'homes'
),
array(
'id'=>'homes_h_blocks',
'label'=>'',
'desc'=>'بلوک‌های صفحۀ اصلی<span>هر بلوک دو ردیف دارد: یکی روشن/خاموش‌کردنش و یکی تیترش. «تعداد آهنگ‌ها» هم برای بلوک پربازدیدترین‌هاست.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'homes',
'class'=>'ab-h'
),

array(
'id'=>'index_hj',
'label'=>'جدیدترین مطالب',
'desc'=>'باکس جدیدترین مطالب به همراه صفحه بندی در ایندکس نمایش داده شود ؟ (پیش‌فرض: روشن — با رفتار واقعی سایت یکی است)',
'std'=>'on',
'type'=>'on-off',
'section'=>'homes',
),
array(
'id'=>'index_ct',
'label'=>'عنوان جدیدترین ها',
'desc'=>'عنوان باکس جدیدترین مطالب را وارد کنید',
'type'=>'text',
'std'=>'جدیدترین مطالب',
'section'=>'homes'
),
array(
'id'=>'index_pv',
'label'=>'پربازدیدترین مطالب ماه',
'desc'=>'باکس پربازدیدترین مطالب ماه به همراه صفحه بندی در ایندکس نمایش داده شود ؟ (پیش‌فرض: روشن — با رفتار واقعی سایت یکی است)',
'std'=>'on',
'type'=>'on-off',
'section'=>'homes',
),
array(
'id'=>'index_pt',
'label'=>'عنوان پربازدیدترین ها',
'desc'=>'عنوان باکس پربازدیدترین مطالب را وارد کنید',
'type'=>'text',
'std'=>'پربازدیدترین مطالب ماه',
'section'=>'homes'
),
array(
'id'=>'index_nm',
'label'=>'تعداد آهنگ‌های پربازدیدترین‌ها',
'desc'=>'چند آهنگ در باکس «پربازدیدترین مطالب ماه» چاپ شود.',
'type'=>'numeric-slider',
'min_max_step'=>'1,60,1',
'std'=>'8',
'section'=>'homes'
),
array(
'id'=>'home_meta_title',
'label'=>'عنوان متا صفحه اصلی',
'desc'=>'عنوانی که در تب مرورگر و نتیجهٔ گوگل دیده می‌شود.<span>اگر خالی باشد، فقط «نام سایت» چاپ می‌شود (مثل قبل). صفحهٔ اصلی این قالب یک برگه نیست، پس این تنها جای ویرایش عنوان آن است. اگر افزونهٔ سئو (Yoast / Rank Math / AIOSEO) فعال باشد، همان افزونه عنوان را می‌سازد و این فیلد کنار می‌رود.</span>',
'type'=>'text',
'std'=>'',
'section'=>'homes'
),
array(
'id'=>'home_meta_desc',
'label'=>'توضیحات متا صفحه اصلی',
'desc'=>'توضیح یک‌دو جمله‌ای (حدود ۱۵۰ نویسه) برای گوگل و اشتراک‌گذاری در تلگرام/شبکه‌ها.<span>اگر خالی باشد، از «تنظیمات → همگانی → توضیح کوتاه» استفاده می‌شود. تگ HTML لازم نیست.</span>',
'type'=>'textarea',
'std'=>'',
'section'=>'homes'
),
array(
'id'=>'hty',
'label'=>'باکس مطالب',
'desc'=>'باکس مطالب صفحه اصلی را بسازید',
'type'=>'list-item',
'section'=>'homes',
'choices'=>array(),
'settings'=>array(
array(
'id'=>'hty-cat',
'label'=>'دسته بندی',
'desc'=>'دسته بندی قابل نمایش در باکس را انتخاب کنید.',
'std'=>'5',
'type'=>'category-select',
),
array(
'id'=>'hty-t',
'label'=>'تعداد مطالب',
'desc'=>'تعداد مطالب قابل نمایش را وارد کنید.',
'std'=> '',
'type'=>'text',
'choices'=>array()
)
)),
array(
'id'=>'homes_h_artists',
'label'=>'',
'desc'=>'ردیف هنرمندان و هنرمندان<span>«نمایش اسلایدر» = ردیف کاورهای هنرمند در صفحۀ اصلی؛ «صفحۀ هنرمندان» = آدرسی که لینک «همهٔ هنرمندان» به آن می‌رود.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'homes',
'class'=>'ab-h'
),

array(
'id'=>'arti_off',
'label'=>'نمایش اسلایدر',
'desc'=>'اسلایدر محبوبترین هنرمندان نمایش داده شود؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'homes',
),
array(
'id'=>'arti_title',
'label'=>'عنوان باکس',
'desc'=>'عنوان باکس اسلایدر هنرمندان را وارد کنید.',
'type'=>'text',
'std'=>'محبوبترین هنرمندان',
'section'=>'homes'
),
array(
'id'=>'arti_url',
'label'=>'صفحه هنرمندان',
'desc'=>'آدرس صفحه هنرمندان بیشتر را  به همراه http:// وارد کنید.',
'type'=>'text',
'std'=>'',
'section'=>'homes'
),
array(
'id'=>'siing_t',
'label'=>'هنرمندان',
'desc'=>'لیست هنرمندان را ایجاد کنید.',
'type'=>'list-item',
'section'=>'homes',
'choices'=>array(),
'settings'=>array(
array(
'id'=>'siing_u',
'label'=>'صفحه هنرمند',
'desc'=>'آدرس صفحه هنرمند را به همراه http:// را وارد کنید.',
'std'=> '',
'type'=>'text',
'choices'=>array()
),
array(
'id'=>'siing_img',
'label'=>'تصویر',
'desc'=>'پیشنهاد می‌شود اندازه‌ی  تصویر  150px × 150px باشد.',
'std'=> get_stylesheet_directory_uri().'/images/nomusics.jpg',
'type'=>'upload'
))),
//socials
array(
'id'=>'socials_guide',
'label'=>'',
'desc'=>'هر ردیف = یک شبکه. آدرس را کامل (با https://) بنویسید؛ هر کدام که خالی بماند، دکمۀ آن شبکه در پانوشت و صفحۀ پست نمایش داده نمی‌شود.',
'type'=>'textblock',
'std'=>'',
'section'=>'socials',
'class'=>'ab-guide'
),

array(
'id'=>'fixbtn',
'label'=>'باکس شبکه‌های اجتماعی در صفحهٔ پست',
'desc'=>'بالای لینک‌های دانلود هر آهنگ، دکمه‌های تلگرام/اینستاگرام/بله/روبیکا نمایش داده شود ؟ (در نسخهٔ 3.2.0 این گزینه جای «لینک‌های ثابتِ پایین صفحه» را گرفته)',
'old_label'=>'لینکهای ثابت',
'std'=>'on',
'type'=>'on-off',
'section'=>'socials',
),
array(
'id'=>'socials_h_links',
'label'=>'',
'desc'=>'آدرس و متن هر شبکه<span>هر ردیفِ «متن …» فقط نوشتهٔ دکمۀ همان شبکه را در صفحۀ پست عوض می‌کند.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'socials',
'class'=>'ab-h'
),

array(
'id'=>'telegram',
'label'=>'تلگرام',
'desc'=>'لینک کانال تلگرام را وارد کنید',
'type'=>'text',
'std'=>'',
'section'=>'socials'
),
array(
'id'=>'teltxt',
'label'=>'متن تلگرام',
'desc'=>'متن لینک ثابت تلگرام را وارد کنید',
'type'=>'text',
'std'=>'کانال تلگرام ',
'section'=>'socials'
),
array(
'id'=>'instagram',
'label'=>'اینستاگرام',
'desc'=>'لینک  صفحه  اینستاگرام را وارد کنید',
'type'=>'text',
'std'=>'',
'section'=>'socials'
),
array(
'id'=>'instxt',
'label'=>'متن  اینستاگرام',
'desc'=>'متن لینک ثابت  اینستاگرام را وارد کنید',
'type'=>'text',
'std'=>'صفحه اینستاگرام ',
'section'=>'socials'
),
array(
'id'=>'bale',
'label'=>'بله',
'desc'=>'لینک کانال/صفحهٔ بله را وارد کنید (https://bale.ai/...)',
'type'=>'text',
'std'=>'',
'section'=>'socials'
),
array(
'id'=>'baletxt',
'label'=>'متن بله',
'desc'=>'متن دکمهٔ بله در باکس صفحهٔ پست',
'type'=>'text',
'std'=>'کانال بله ',
'section'=>'socials'
),
array(
'id'=>'rubika',
'label'=>'روبیکا',
'desc'=>'لینک کانال/صفحهٔ روبیکا را وارد کنید',
'type'=>'text',
'std'=>'',
'section'=>'socials'
),
array(
'id'=>'rubikatxt',
'label'=>'متن روبیکا',
'desc'=>'متن دکمهٔ روبیکا در باکس صفحهٔ پست',
'type'=>'text',
'std'=>'کانال روبیکا ',
'section'=>'socials'
),
array(
'id'=>'facebook',
'label'=>'فیسبوک',
'desc'=>'لینک صفحه فیسبوک را وارد کنید',
'type'=>'text',
'std'=>'',
'section'=>'socials'
),
array(
'id'=>'twitter',
'label'=>'توئیتر',
'desc'=>'لینک صفحه توئیتر را وارد کنید',
'type'=>'text',
'std'=>'',
'section'=>'socials'
),
array(
'id'=>'youtube',
'label'=>'یوتیوب',
'desc'=>'لینک صفحه  یوتیوب  را وارد کنید',
'type'=>'text',
'std'=>'',
'section'=>'socials'
),
array(
'id'=>'aparat',
'label'=>'آپارات',
'desc'=>'لینک صفحه  آپارات  را وارد کنید',
'type'=>'text',
'std'=>'',
'section'=>'socials'
),
//footer
array(
'id'=>'footer_guide',
'label'=>'',
'desc'=>'پانوشت چهار ستون است: درباره ما · لینک‌های مفید · دسته‌های پرطرفدار · ما را دنبال کنید. دو ردیف اولِ همین تب قدیمیِ قالب‌اند؛ برای کم‌وزیادکردن خودِ ستون‌ها به پایین همین تب نگاه کنید.',
'type'=>'textblock',
'std'=>'',
'section'=>'footer',
'class'=>'ab-guide'
),

array(
'id'=>'upb',
'label'=>' دکمه بالارونده',
'desc'=>'دکمه بالارونده نمایش داده شود ؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'footer',
),
array(
'id'=>'copyright',
'label'=>'کپی رایت',
'desc'=>'متن کپی رایت را وارد کنید.',
'type'=>'text',
'std'=>'کلیه حقوق برای مدیران سایت محفوظ است.',
'section'=>'footer'
),
//ads1
array(
'id'=>'ads_guide',
'label'=>'',
'desc'=>'دو جای متفاوت برای تبلیغ: «صفحۀ دانلود» = پایینِ باکس لینک‌ها، «بالای لینک دانلود» = درست قبل از دکمه‌ها. ردیف‌های «جاوا» برای کدی است که داخل اسکریپت می‌رود (تگ باز و بسته‌اش را ننویسید). برای حذف یک تبلیغ، محتوا را کامل پاک کنید و ذخیره کنید.',
'type'=>'textblock',
'std'=>'',
'section'=>'ads1',
'class'=>'ab-guide'
),

array(
'id'=>'ads_bt',
'label'=>'تبلیغات صفحه دانلود',
'desc'=>'کد تبلیغاتی قابل نمایش در بالای صفحه دانلود آهنگ را وارد کنید.',
'type'=>'textarea',
'std'=>'',
'section'=>'ads1'
),
array(
'id'=>'adsjs_bt',
'label'=>'تبلیغات جاوا صفحه دانلود',
'desc'=>'کد تبلیغاتی جاوا قابل نمایش در بالای صفحه دانلود آهنگ را وارد کنید.',
'type'=>'javascript',
'std'=>'',
'section'=>'ads1'
),
array(
'id'=>'ads_sg',
'label'=>'تبلیغات بالای لینک دانلود',
'desc'=>'کد تبلیغاتی قابل نمایش در بالای لینکهای دانلود را وارد کنید.',
'type'=>'textarea',
'std'=>'',
'section'=>'ads1'
),
array(
'id'=>'adsjs_sg',
'label'=>'تبلیغات جاوا بالای لینک دانلود',
'desc'=>'کد تبلیغاتی جاوا قابل نمایش در بالای لینکهای دانلود را وارد کنید.',
'type'=>'javascript',
'std'=>'',
'section'=>'ads1'
),
// dl_pages
array(
'id'=>'dl_pages_guide',
'label'=>'',
'desc'=>'این تب فقط باکس‌های زیرِ پلیرِ صفحۀ دانلود آهنگ را کم‌وزیاد می‌کند؛ فایل و لینک دانلود دست‌نخورده می‌مانند.',
'type'=>'textblock',
'std'=>'',
'section'=>'dl_pages',
'class'=>'ab-guide'
),

array(
'id'=>'pppf',
'label'=>'مطالب هنرمند',
'desc'=>'تعداد «سایر آهنگ‌های هنرمند» در صفحۀ دانلود. پیش‌فرض ۱۲.',
'type'=>'numeric-slider',
'min_max_step'=>'1,60,1',
'std'=>'12',
'section'=>'dl_pages'
),
array(
'id'=>'share_off',
'label'=>'نمایش اشتراک گذاری',
'desc'=>'باکس اشتراک گذاری نمایش داده  شود؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'dl_pages',
),
array(
'id'=>'tag_off',
'label'=>'نمایش برچسبها',
'desc'=>'باکس برچسبها نمایش داده شوند؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'dl_pages',
),
array(
'id'=>'cm_off',
'label'=>'نمایش دیدگاه ها',
'desc'=>'باکس دیدگاه ها نمایش داده شوند؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'dl_pages',
),
array(
'id'=>'dl_pages_h_extra',
'label'=>'',
'desc'=>'باکس‌های افزودۀ این نسخه<span>این پنج ردیف در قالب مادر نبود؛ اضافه شدند تا شناسنامۀ آهنگ و متن ترانه جای درست خودشان بنشینند.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'dl_pages',
'class'=>'ab-h'
),

array(
'id'=>'credits_box',
'label'=>'شناسنامۀ آهنگ (تهیه‌کنندگان)',
'desc'=>'ردیف‌های هنرمند · آهنگساز · ترانه‌سرا · تنظیم · میکس و مستر · تولید در صفحۀ پست نمایش داده شود؟ (داده از فیلدهای artist/composer/lyricist/arranger/mixer/producer)',
'std'=>'on',
'type'=>'on-off',
'section'=>'dl_pages'
),
array(
'id'=>'credits_title',
'label'=>'تیتر باکس شناسنامۀ آهنگ',
'desc'=>'اگر خالی بماند، «شناسنامۀ آهنگ» چاپ می‌شود',
'type'=>'text',
'std'=>'',
'section'=>'dl_pages'
),
array(
'id'=>'credits_before_player',
'label'=>'شناسنامه بالای پلیر',
'desc'=>'روشن = بالای باکس پلیر؛ خاموش = بعد از لینک‌های دانلود (حالت پیش‌فرض)',
'std'=>'off',
'type'=>'on-off',
'section'=>'dl_pages'
),
array(
'id'=>'lyrics_after_download',
'label'=>'متن ترانه زیر لینک دانلود',
'desc'=>'روشن = متن آهنگ بعد از لینک‌های دانلود چاپ شود (حالت پیش‌فرض 3.3.2)؛ خاموش = بالای پلیر و زیر توضیحات',
'std'=>'on',
'type'=>'on-off',
'section'=>'dl_pages'
),

/* ---------------------------------------------------------------------------
   v3.4.1 — «کنترل رابط»: هر ردیف اینجا یک کلید واقعی است که در کد مصرف می‌شود
   (سورس: inc/ab-controls.php).  v3.4.3 — همان کلیدها با جداکنندهٔ بخش‌بخش،
   بلوک راهنما، اسلایدر عددی و ردیف‌های شرطی (فقط در حالت مربوطه دیده می‌شوند).
   --------------------------------------------------------------------------- */
array(
'id'=>'ab_ui_guide',
'label'=>'',
'desc'=>'<strong>هر ردیف یک کلید واقعی است</strong> — یعنی همان گزینه در فایل قالب مصرف می‌شود، چیزی برای «امتحان کردن» نیست. بعد از «ذخیرهٔ تغییرات» کافی است صفحۀ سایت را یک بار تازه کنید (روی کیبورد: Ctrl + Shift + R). خاموش‌کردن هر کدام، سایت را دقیقاً به حالت قبل برمی‌گرداند.',
'type'=>'textblock',
'std'=>'',
'section'=>'ab_ui',
'class'=>'ab-guide'
),
array(
'id'=>'ab_admin_ui',
'label'=>'پوستۀ همین صفحه',
'desc'=>'چیدمانِ صفحۀ تنظیمات: تب‌های بالا، ردیف‌های کارتی، اسلایدرهای عددی و همین متن‌های راهنما (css/ab-admin.css). با خاموش‌کردنش صفحه به ظاهر پیش‌فرض Option Tree برمی‌گردد و هیچ گزینه‌ای عوض نمی‌شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'dev'
),
array(
'id'=>'ab_ui_h_layers',
'label'=>'',
'desc'=>'لایه‌های ظاهری<span>هر سه مورد فقط فایل CSS را عوض می‌کنند؛ تغییری در محتوا و ساختار صفحۀ شما نمی‌دهند.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'ab_ui',
'class'=>'ab-h'
),
array(
'id'=>'ab_glass_ui',
'label'=>'لایۀ شیشه‌ایِ کارت‌ها',
'desc'=>'کارت/سایه/بلور روی باکس‌ها (css/ab-glass.css). خاموش = فقط استایل خودِ قالب.',
'std'=>'on',
'type'=>'on-off',
'section'=>'dev'
),
array(
'id'=>'ab_ui_guard',
'label'=>'گاردِ لایۀ CSS',
'desc'=>'اگر روزی style.css بازنویسی شود و بلوک انتهایی‌اش برود، کپیِ همان استایل از css/ab-ui.css لود می‌شود. پیشنهاد: روشن.',
'std'=>'on',
'type'=>'on-off',
'section'=>'dev'
),
array(
'id'=>'ab_card_stats',
'label'=>'بازدید و رأی روی کاور',
'desc'=>'عدد بازدید و میانگین رأی روی کارتِ آهنگ‌ها در صفحۀ اصلی و فهرست‌ها.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_ui_h_comments',
'label'=>'',
'desc'=>'دیدگاه‌ها<span>اگر زیر هر آهنگ ده‌ها دیدگاه ثبت شود، صفحۀ نوشته بی‌نهایت بلند می‌شود. با روشن‌بودنِ این دو گزینه، دیدگاه‌ها مثل آرشیو صفحه‌بندی می‌شوند: شمارۀ صفحه، «قدیمی‌تر/جدیدتر» و شمارشِ «نمایش ۲۱ تا ۴۰ از ۱۲۳».</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'ab_ui',
'class'=>'ab-h'
),
array(
'id'=>'ab_comments_paging',
'label'=>'صفحه‌بندی دیدگاه‌ها',
'desc'=>'دیدگاه‌های هر نوشته در چند صفحه نمایش داده شوند (پیشنهاد: روشن). خاموش = همۀ دیدگاه‌ها در یک صفحه، مثل قبل.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_comments_per_page',
'label'=>'تعداد دیدگاه در هر صفحه',
'desc'=>'بین ۵ تا ۱۰۰. پیش‌فرض ۲۰. این عدد جای «تنظیمات → گفتگو → دیدگاه‌ها در هر صفحه» را در نمای سایت می‌گیرد (تنظیمات وردپرس دست‌نخورده می‌ماند).',
'std'=>'20',
'type'=>'numeric-slider',
'min_max_step'=>'5,100,5',
'section'=>'ab_ui'
),
array(
'id'=>'ab_ui_h_chrome',
'label'=>'',
'desc'=>'عناصر اضافهٔ صفحه<span>خاموش = آن عنصر اصلاً در HTML صفحۀ شما چاپ نمی‌شود (نه اینکه فقط مخفی شود).</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'ab_ui',
'class'=>'ab-h'
),
array(
'id'=>'ab_sticky_player',
'label'=>'نوار چسبان پخش (نسخهٔ قدیمی)',
'desc'=>'پخش‌کنندهٔ قدیمیِ قالب که به پایین صفحه می‌چسبید. از ۳.۷.۷ به بعد پلیر تازهٔ ترانه یار همه‌جا هست و این نوار فقط یک پلیر دوم می‌سازد؛ پس پیش‌فرض «خاموش» است. اگر لازم شد، روشنش کنید.',
'std'=>'off',
'type'=>'on-off',
'section'=>'dev'
),
array(
'id'=>'ab_skip_link',
'label'=>'لینک «پرش به محتوا»',
'desc'=>'نخستین لینکِ صفحه برای کیبورد و صفحۀ‌خوان. با ماوس دیده نمی‌شود، پس خاموش‌کردنش ظاهر را عوض نمی‌کند.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_quickbar',
'label'=>'نوار دسترسی سریع',
'desc'=>'نوار زیر هدرِ صفحۀ اصلی (تازه‌ترین آهنگ‌ها · همهٔ هنرمندان · پربازدیدترین‌ها / اخبار روز). اگر اخبار ۴۰۴ باشد، خودش لینک اخبار حذف می‌شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_ui_h_player',
'label'=>'',
'desc'=>'پلیرِ صفحۀ اصلی<span>اگر پلیر شلوغ یا بیرون‌زده به‌نظر رسید، اول «شکل پلیر» را روی «ساده» بگذارید: همان پلیر قالب مادر، بدون کادر و سایه.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'ab_ui',
'class'=>'ab-h'
),
array(
'id'=>'ab_player_mode',
'label'=>'شکل پلیرِ صفحۀ اصلی',
'desc'=>'«کارت» = حالت فعلی (کادر، گوشۀ گرد، فهرست در پایین کارت). «فشرده» = همان کارت با ارتفاع کمتر. «ساده» = بدون کادر و سایه، مثل قالب مادر.',
'std'=>'card',
'type'=>'select',
'choices'=>array(
array('value'=>'card','label'=>'کارت'),
array('value'=>'compact','label'=>'فشرده'),
array('value'=>'legacy','label'=>'ساده (قالب مادر)'),
),
'section'=>'dev'
),
array(
'id'=>'ab_player_list',
'label'=>'فهرست آلبوم در پلیر',
'desc'=>'«دکمه» = لینک «نمایش آلبوم» زیر پلیر. «باز» = فهرست همیشه نمایش داده می‌شود. «مخفی» = فهرست و دکمه حذف می‌شوند.',
'std'=>'btn',
'type'=>'select',
'choices'=>array(
array('value'=>'btn','label'=>'دکمه'),
array('value'=>'open','label'=>'باز'),
array('value'=>'hide','label'=>'مخفی'),
),
'section'=>'ab_ui',
'condition'=>'ab_player_mode:is(card),ab_player_mode:is(compact)',
'operator'=>'or',
'class'=>'ab-sub'
),
array(
'id'=>'ab_player_volume',
'label'=>'دکمۀ صدا در پلیر',
'desc'=>'اسلایدر تنظیم صدا.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui',
'condition'=>'ab_player_mode:is(card),ab_player_mode:is(compact)',
'operator'=>'or',
'class'=>'ab-sub'
),
array(
'id'=>'ab_player_gap',
'label'=>'فاصلۀ پایین پلیر',
'desc'=>'فاصلۀ میان پلیر تا محتوای زیر آن، بر حسب پیکسل. پیش‌فرض ۳۰؛ ۰ = چسبیده به محتوای زیر.',
'std'=>'30',
'type'=>'numeric-slider',
'min_max_step'=>'0,96,1',
'section'=>'dev',
'condition'=>'ab_player_mode:is(card),ab_player_mode:is(compact)',
'operator'=>'or',
'class'=>'ab-sub'
),
array(
'id'=>'ab_player_radius',
'label'=>'گردی گوشۀ پلیر',
'desc'=>'گردی کارت پلیر بر حسب پیکسل. ۰ = گوشۀ تیز، پیش‌فرض ۲۰.',
'std'=>'20',
'type'=>'numeric-slider',
'min_max_step'=>'0,40,1',
'section'=>'dev',
'condition'=>'ab_player_mode:is(card),ab_player_mode:is(compact)',
'operator'=>'or',
'class'=>'ab-sub'
),
array(
'id'=>'ab_player_border',
'label'=>'کادر پلیر',
'desc'=>'خط دور کارت پلیر.',
'std'=>'on',
'type'=>'on-off',
'section'=>'dev',
'condition'=>'ab_player_mode:is(card),ab_player_mode:is(compact)',
'operator'=>'or',
'class'=>'ab-sub'
),
array(
'id'=>'ab_player_shadow',
'label'=>'سایۀ پلیر',
'desc'=>'سایۀ نرم زیر کارت پلیر.',
'std'=>'on',
'type'=>'on-off',
'section'=>'dev',
'condition'=>'ab_player_mode:is(card),ab_player_mode:is(compact)',
'operator'=>'or',
'class'=>'ab-sub'
),
array(
'id'=>'ab_ui_h_cover',
'label'=>'',
'desc'=>'تصویر کاور پست<span>کاورِ بالای صفحۀ هر آهنگ/نوشته. این دو ردیف فایلِ عکس را عوض نمی‌کنند؛ فقط همان را در اندازهٔ دلخواه نشان می‌دهند.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'ab_ui',
'class'=>'ab-h'
),
array(
'id'=>'ab_post_img_size',
'label'=>'اندازۀ کاور (px)',
'desc'=>'اندازۀ کادر کاور در صفحۀ پست، بر حسب پیکسل. پیش‌فرض ۵۰۰ یعنی ۵۰۰×۵۰۰. در موبایل و ستون باریک خودش کوچک‌تر می‌شود و از کادر نمی‌زند.',
'std'=>'500',
'type'=>'numeric-slider',
'min_max_step'=>'240,900,20',
'section'=>'dev'
),
array(
'id'=>'ab_post_img_crop',
'label'=>'کات مربعی کاور',
'desc'=>'روشن = عکس در همان کادر مربع از وسط کات می‌شود. خاموش = عرض همان عدد می‌ماند و ارتفاع نسبت اصلیِ عکس را می‌گیرد.',
'std'=>'on',
'type'=>'on-off',
'section'=>'dev',
'class'=>'ab-sub'
),
/* ---- هنرمند: نامکِ تاکسونومی و آدرسِ آرشیو (v3.4.7) ---- */
array(
'id'=>'ab_ui_h_artist',
'label'=>'',
'desc'=>'نامکِ «هنرمند»<span>در قالب مادر این تاکسونومی singer است، ولی بقیۀ پروژه artist را به کار می‌برد (فیلد ACFِ artist، کلاس‌ها، فایلِ فهرست هنرمندان). این دو ردیف همان دوگانگی را یکی می‌کند: نامک فعال + آدرسِ آرشیو. مهاجرت فقط یک ستونِ دیتابیس است و با خاموش‌کردن همین کلید برمی‌گردد.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'dev',
'class'=>'ab-h'
),
array(
'id'=>'ab_artist_tax',
'label'=>'نامک artist فعال باشد',
'desc'=>'روشن = فهرست‌ها، لینک‌ها و اصطلاحات زیر نامک artist (مهاجرت خودکارِ یک‌بار). خاموش = همان singer همیشگی؛ هیچ داده‌ای از دست نمی‌رود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'dev'
),
array(
'id'=>'ab_artist_slug',
'label'=>'آدرسِ آرشیو هنرمند',
'desc'=>'بخشِ اولِ آدرس، بدون اسلش. پیش‌فرض artist ⇒ /artist/mohsen-yeganeh. اگر singer بگذارید آدرس‌های قبلی برمی‌گردند. آدرس‌های قدیمی همیشه با ۳۰۱ به آدرسِ تازه می‌روند. صفحۀ فهرست هم با همین ردیف‌ها آدرسش را از arti_url یا اسلاگِ برگه (artists) می‌گیرد',
'std'=>'artist',
'type'=>'text',
'section'=>'dev',
'class'=>'ab-sub small-text'
),
array(
'id'=>'ab_artists_title',
'label'=>'تیترِ صفحۀ هنرمندان',
'desc'=>'اگر پر شود، تیتر و H1ِ همان صفحه را می‌پوشاند (مثلاً «هنرمندان») — تا لازم نباشد عنوانِ خودِ برگه را در پیشخوان عوض کنید.',
'std'=>'',
'type'=>'text',
'section'=>'ab_ui',
'class'=>'ab-sub small-text'
),
/* ---- کنترل پانوشت (این ردیف‌ها در تب «پانوشت» دیده می‌شوند) ---- */
array(
'id'=>'footer_h_ctrl',
'label'=>'',
'desc'=>'کنترل خودِ پانوشت<span>ستون‌ها را یکی‌یکی خاموش/روشن کنید؛ هر ستون که تیک نخورد اصلاً چاپ نمی‌شود و سه ستون دیگر عرض را پر می‌کنند.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'footer',
'class'=>'ab-h'
),

array(
'id'=>'ab_footer_cols',
'label'=>'ستون‌های فوتر',
'desc'=>'هر ستون که اینجا تیک نخورد اصلاً چاپ نمی‌شود و ستون‌های باقی‌مانده عرض را پر می‌کنند.',
'std'=>array('about','links','cats','social'),
'type'=>'checkbox',
'choices'=>array(
array('value'=>'about','label'=>'درباره ما'),
array('value'=>'links','label'=>'لینک‌های مفید'),
array('value'=>'cats','label'=>'دسته‌های پرطرفدار'),
array('value'=>'social','label'=>'ما را دنبال کنید'),
),
'section'=>'footer'
),
array(
'id'=>'ab_footer_pinned',
'label'=>'دو لینک اصلی فوتر',
'desc'=>'«همهٔ هنرمندان» و «تازه‌ترین آهنگ‌ها» در ستون لینک‌های مفید.',
'std'=>'on',
'type'=>'on-off',
'section'=>'footer'
),
array(
'id'=>'ab_footer_links',
'label'=>'لینک‌های دستی فوتر',
'desc'=>'هر خط یک لینک به شکل: عنوان|آدرس — مثلاً «قوانین|https://ahangban.ir/rules». خطی که با # شروع شود نادیده گرفته می‌شود.',
'std'=>'',
'type'=>'textarea',
'section'=>'footer'
),
array(
'id'=>'ab_footer_search',
'label'=>'جست‌وجو در فوتر',
'desc'=>'فرم جست‌وجوی پایین صفحه (زیر لینک‌های شبکهٔ اجتماعی).',
'std'=>'on',
'type'=>'on-off',
'section'=>'footer'
),
array(
'id'=>'ab_footer_cats_num',
'label'=>'تعداد رده‌های فوتر',
'desc'=>'چند «دستهٔ پرطرفدار» در ستون سوم پانوشت چاپ شود. ۰ = همه‌شان.',
'std'=>'8',
'type'=>'numeric-slider',
'min_max_step'=>'0,20,1',
'section'=>'footer'
),

/* ------------------------------------------------------------------
   ۳.۷.۰ — «درباره سایت»: یک متن واحد که هم در پانوشت می‌آید و هم در
   صفحات هنرمند. قبلاً این متن سه جای مختلف hard-code بود و برای عوض‌کردنش
   باید فایل ویرایش می‌شد.
   ------------------------------------------------------------------ */
array(
'id'=>'about_guide',
'label'=>'',
'desc'=>'<strong>یک متن، چند جا.</strong> این متن در ستون «درباره ما»ی پانوشت و در کارت «درباره سایت» صفحهٔ هنرمند نمایش داده می‌شود. خالی بگذارید تا همان رفتار قدیمی (توضیح سایت) بماند.',
'type'=>'textblock',
'std'=>'',
'section'=>'about'
),
array(
'id'=>'about_site_title',
'label'=>'تیتر بخش',
'desc'=>'عنوانی که بالای متن می‌نشیند. پیش‌فرض: «درباره سایت».',
'std'=>'',
'type'=>'text',
'section'=>'about'
),
array(
'id'=>'about_site_text',
'label'=>'متن دربارهٔ سایت',
'desc'=>'متن آزاد (خط جدید = پاراگراف تازه). اگر خالی باشد، توضیح سایت از «تنظیمات ← همگانی» استفاده می‌شود.',
'std'=>'',
'type'=>'textarea',
'section'=>'about'
),
array(
'id'=>'about_show_footer',
'label'=>'نمایش در پانوشت',
'desc'=>'ستون «درباره ما»ی پانوشت از این متن پر شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'about'
),
array(
'id'=>'about_show_artist',
'label'=>'نمایش در صفحهٔ هنرمند',
'desc'=>'کارت «درباره سایت» در صفحهٔ هر هنرمند نمایش داده شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'about'
),
array(
'id'=>'about_style',
'label'=>'چیدمان کارت درباره',
'desc'=>'نحوهٔ نمایش متن در صفحهٔ هنرمند.',
'std'=>'card',
'type'=>'select',
'choices'=>array(
array('value'=>'card','label'=>'کارت کنار محتوا'),
array('value'=>'wide','label'=>'تمام‌عرض، بالای هنرمندان مشابه'),
),
'section'=>'about'
),


/* ---------------------------------------------------------------------------
   v3.7.7 — «توسعه دهنده»: تنظیمات فنی/ظاهریِ ریز که کاربر عادی لازم ندارد.
   همه همان کلیدهای قبلی هستند (هیچ داده‌ای جابه‌جا نمی‌شود)؛ فقط از شلوغی
   بخش «ظاهر و رفتار» کم شده‌اند.
   ------------------------------------------------------------------------- */
array(
'id'=>'dev_guide',
'label'=>'',
'desc'=>'<strong>این بخش برای کاربر فنی است.</strong> هر گزینه همان گزینهٔ قبلی است، فقط این‌جا جمع شده تا بخش‌های روزمره ساده بمانند. اگر نمی‌دانید یک مورد چه می‌کند، همان پیش‌فرض را نگه دارید. وضعیت فعلی: PHP نسخهٔ ' . PHP_VERSION . ' · وردپرس نسخهٔ ' . ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '—' ) . ' · قالب نسخهٔ ' . ( defined( 'TARANEHYAR_VERSION' ) ? TARANEHYAR_VERSION : '—' ) . '.',
'type'=>'textblock',
'std'=>'',
'section'=>'dev',
'class'=>'ab-guide'
),

/* ---------------------------------------------------------------------------
   v3.9.0 — «رادیو و دی‌جی»: ایستگاهِ همیشه‌روشن + دی‌جیِ خودکارِ صفحهٔ ژانر/حال‌وهوا
   هیچ‌کدام از این‌ها داده‌ای را پاک نمی‌کند و خاموش‌کردنشان فقط همان بخش را
   از صفحه برمی‌دارد. طولِ میکس و افکت‌ها را کاربر روی خودِ کارت هم می‌تواند
   عوض کند (همان لحظه، بدونِ رفتن به تنظیمات).
   ------------------------------------------------------------------------- */
array(
'id'=>'radio_guide',
'label'=>'',
'desc'=>'<strong>رادیو ترانه یار چیست؟</strong> یک پخش‌کنندهٔ پیوسته که آهنگ‌ها را پشتِ سرِ هم و با <em>میکسِ خودکار</em> (کراس‌فید، هم‌ترازیِ سرعت و افکتِ باس) پخش می‌کند و بینِ آهنگ‌ها <em>معرفیِ صوتیِ سایت</em> می‌گذارد. در صفحهٔ اصلی، صفحهٔ هر ژانر/حال‌وهوا و هر جای دیگری با شورت‌کد <code>[tara_radio]</code> ظاهر می‌شود. همهٔ پردازش در مرورگرِ شنونده انجام می‌شود؛ هیچ پهنای‌باندِ اضافه‌ای از سرورِ شما مصرف نمی‌شود.<br><strong>صفحهٔ رادیو:</strong> یک برگهٔ تازه بسازید و در «ویژگی‌های برگه» قالبِ «رادیو ترانه یار» را انتخاب کنید؛ یا همان شورت‌کد <code>[tara_radio]</code> را در هر برگه/نوشته بگذارید. با ساخته‌شدنِ این برگه، دکمهٔ «صفحهٔ رادیو» در بخشِ صفحهٔ اصلی هم خودکار ظاهر می‌شود.',
'type'=>'textblock',
'std'=>'',
'section'=>'radio',
'class'=>'ab-guide'
),
array(
'id'=>'radio_on',
'label'=>'رادیو روشن باشد',
'desc'=>'کلیدِ اصلی. اگر خاموش باشد، هیچ کارتِ رادیو/دی‌جی در سایت چاپ نمی‌شود و فایل‌های CSS/JS هم بار نمی‌شوند.',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_home',
'label'=>'بخشِ رادیو در صفحهٔ اصلی',
'desc'=>'کارتِ رادیو زیرِ اسلایدرِ ویژه و بالای «تازه‌ترین آهنگ‌ها» نمایش داده می‌شود (فشرده و کم‌جا).',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_home_featured',
'label'=>'در صفحهٔ اصلی فقط آهنگ‌های «بخش ویژه»',
'desc'=>'روشن = رادیوی خانه فقط از آهنگ‌هایی ساخته می‌شود که در «اسلایدر ویژه» (فیلد vip_song) انتخاب کرده‌اید؛ دقیقاً همان چیزی که خودتان دست‌چین کرده‌اید. اگر هیچ آهنگِ ویژه‌ای ثبت نشده باشد، رادیو به‌جای خالی‌ماندن از کل آهنگ‌های سایت استفاده می‌کند. خاموش = رادیوی خانه از همهٔ آهنگ‌ها.',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_archives',
'label'=>'دی‌جی در صفحهٔ ژانر و حال‌وهوا',
'desc'=>'در سربرگِ هر ژانر/حال‌وهوا یک کارتِ رادیو با آهنگ‌های همان بخش چاپ می‌شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_user_add',
'label'=>'دکمهٔ «افزودن به رادیو» در صفحهٔ آهنگ و فهرست‌ها',
'desc'=>'خاموش (پیش‌فرض) = کاربر هیچ دکمه‌ای نمی‌بیند؛ صفحهٔ آهنگ فقط پلیر، دانلود و اطلاعات اثر را دارد. رادیو با همین حالِ خاموش، صف را از قواعدِ بالا (منبع، طول، میکس) می‌سازد و دست‌نخورده کار می‌کند. روشن = روی صفحهٔ هر آهنگ و روی هر ردیفِ فهرست، دکمهٔ «افزودن به رادیو» و تبِ «فهرست من» برمی‌گردد.',
'std'=>'off',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_mode',
'label'=>'منبعِ پیش‌فرضِ آهنگ‌ها',
'desc'=>'«ترکیبیِ هوشمند» جدیدترین‌ها و پربازدیدترین‌ها را یک‌درمیان می‌چیند. کاربر روی خودِ کارت هم می‌تواند منبع را عوض کند (در صفحهٔ رادیو).',
'std'=>'mixed',
'type'=>'select',
'choices'=>array(
array('value'=>'mixed','label'=>'ترکیبیِ هوشمند'),
array('value'=>'new','label'=>'جدیدترین‌ها'),
array('value'=>'views','label'=>'پربازدیدترین‌ها'),
array('value'=>'hot','label'=>'داغِ این فصل (۶ ماه اخیر)'),
array('value'=>'random','label'=>'تصادفی'),
),
'section'=>'radio'
),
array(
'id'=>'radio_limit',
'label'=>'تعداد آهنگ در هر نوبت',
'desc'=>'صفِ اولیهٔ رادیو چند آهنگ باشد. وقتی صف تمام شود، خودکار آهنگ‌های تازه اضافه می‌شوند.',
'std'=>'20',
'type'=>'numeric-slider',
'min_max_step'=>'4,40,1',
'section'=>'radio'
),
array(
'id'=>'radio_h_mix',
'label'=>'',
'desc'=>'میکس و افکت‌ها<span>این اعداد را می‌توانید همان لحظه روی کارتِ رادیو هم تغییر دهید؛ مقدارِ این‌جا فقط «شروعِ پیش‌فرض» است.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'radio',
'class'=>'ab-h'
),
array(
'id'=>'radio_xfade',
'label'=>'طولِ میکس (ثانیه)',
'desc'=>'کراس‌فیدِ پیش‌فرض. ۰ = تعویضِ فوری، ۳ = آرام، ۶ = حسِ دی‌جی، ۱۲ = باشگاهی. بیش از ۱۶ ثانیه در کارت هم اجازه داده نمی‌شود.',
'std'=>'6',
'type'=>'numeric-slider',
'min_max_step'=>'0,16,1',
'section'=>'radio'
),
array(
'id'=>'radio_tempo',
'label'=>'هم‌ترازیِ سرعت',
'desc'=>'آهنگِ تازه با سرعتِ کمی کمتر (۹۶٪) وارد می‌شود و در طولِ میکس به ۱۰۰٪ می‌رسد؛ همان کاری که دی‌جی با پیچِ صفحه می‌کند.',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_fx',
'label'=>'افکتِ فیلتر و باس',
'desc'=>'باسِ آهنگِ ورودی اول کم می‌شود و فیلترِ آهنگِ خروجی بسته می‌شود. <strong>فقط</strong> برای فایل‌هایی که روی همین دامنه هستند فعال می‌شود؛ برای فایلِ دامنهٔ دیگر خودکار کنار می‌رود تا صدا قطع نشود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_cue',
'label'=>'پرش از ابتدای آهنگ (ثانیه)',
'desc'=>'برای آهنگ‌هایی که مقدمهٔ طولانی دارند. ۰ = از اول. فقط وقتی اعمال می‌شود که آهنگ حداقل ۳۰ ثانیه بیشتر از این عدد باشد.',
'std'=>'0',
'type'=>'numeric-slider',
'min_max_step'=>'0,30,1',
'section'=>'radio'
),
array(
'id'=>'radio_h_station',
'label'=>'',
'desc'=>'معرفیِ صوتیِ سایت<span>وقتی موسیقی پخش است، صدای معرفی روی آن می‌نشیند و موسیقی خودکار کم می‌شود.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'radio',
'class'=>'ab-h'
),
array(
'id'=>'radio_station',
'label'=>'معرفیِ صوتی پخش شود',
'desc'=>'اگر خاموش باشد، فقط موسیقی می‌آید و هیچ صدای معرفی‌ای پخش نمی‌شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_station_file',
'label'=>'فایلِ صوتیِ معرفی (اختیاری)',
'desc'=>'بهترین گزینه: یک فایل MP3 کوتاه (۱۰ تا ۱۵ ثانیه) که خودتان ضبط کرده‌اید. اگر خالی بماند، مرورگرِ شنونده متنِ پایین را با صدای گفتارِ فارسی می‌خواند (اگر مرورگر صدای فارسی نداشته باشد، این بخش بی‌صدا رد می‌شود).',
'std'=>'',
'type'=>'upload',
'section'=>'radio'
),
array(
'id'=>'radio_station_text',
'label'=>'متنِ معرفیِ سایت',
'desc'=>'این جمله با گفتارِ مرورگر خوانده می‌شود. جای‌نگهدارها: <code>{site}</code> نام سایت، <code>{title}</code> نام آهنگ، <code>{artist}</code> نام خواننده.',
'type'=>'textarea',
'std'=>'رادیو {site}؛ پخشِ پیوستهٔ آهنگ‌های فارسی. گوش کنید.',
'section'=>'radio'
),
array(
'id'=>'radio_station_names',
'label'=>'نام‌بردن از آهنگِ بعدی',
'desc'=>'به‌جای تکرارِ معرفی، گاهی نامِ آهنگ و خوانندهٔ بعدی گفته می‌شود (مثلِ دی‌جیِ رادیو).',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_station_next',
'label'=>'متنِ معرفیِ آهنگِ بعدی',
'desc'=>'فقط وقتی «نام‌بردن از آهنگِ بعدی» روشن است استفاده می‌شود. اگر <code>{site}</code> را ننویسید، خودکار با «رادیو {site}؛» شروع می‌شود؛ پس نامِ سایت همیشه گفته می‌شود. مثال: «بعدی: {title} از {artist}.»',
'type'=>'text',
'std'=>'بعدی: {title} از {artist}؛ رادیو {site}.',
'section'=>'radio'
),
array(
'id'=>'radio_station_every',
'label'=>'هر چند آهنگ یک‌بار',
'desc'=>'فاصلهٔ معرفی‌ها. ۳ = هر سه آهنگ یک‌بار (پیشنهاد می‌شود).',
'std'=>'3',
'type'=>'numeric-slider',
'min_max_step'=>'1,12,1',
'section'=>'radio'
),
array(
'id'=>'radio_duck',
'label'=>'صدای موسیقی هنگامِ معرفی (درصد)',
'desc'=>'۳۵ = موسیقی به ۳۵٪ می‌رسد و وکال واضح شنیده می‌شود. ۰ = موسیقی کاملاً ساکت. ۱۰۰ = بدونِ کم‌کردن.',
'std'=>'35',
'type'=>'numeric-slider',
'min_max_step'=>'0,100,5',
'section'=>'radio'
),
array(
'id'=>'radio_chime',
'label'=>'نوای کوتاهِ ایستگاه پیش از معرفی',
'desc'=>'سه نتِ کوتاه پیش از هر معرفی پخش می‌شود (مثلِ امضای صوتیِ رادیو). بدونِ فایل و بدونِ درخواستِ اضافه، در خودِ مرورگر ساخته می‌شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'radio'
),
array(
'id'=>'radio_autostart',
'label'=>'پخشِ خودکارِ رادیو',
'desc'=>'مرورگرها پخشِ خودکار با صدا را معمولاً اجازه نمی‌دهند؛ روشن‌کردنش فقط «تلاش» می‌کند و اگر مرورگر جلویش را بگیرد، دکمهٔ پخش نشان داده می‌شود. پیشنهاد: خاموش.',
'std'=>'off',
'type'=>'on-off',
'section'=>'radio'
),

/* ---------------------------------------------------------------------------
   v3.10.0 — «حساب کاربری و ورود»: ورودِ پیامکی (OTP)، پنلِ کاربری و ربات
   ------------------------------------------------------------------------ */
array(
'id'=>'account_guide',
'label'=>'',
'desc'=>'<strong>ورود بدونِ رمز.</strong> کاربر فقط شمارهٔ موبایل را می‌دهد، یک کدِ ۶رقمی پیامک می‌شود و با همان وارد می‌شود؛ حساب اگر نباشد خودکار ساخته می‌شود. برای پیامک، حسابِ <a href="https://melipayamak.com" target="_blank" rel="noopener">ملی‌پیامک</a> یا هر پنلِ دیگر (از راهِ وب‌هوک) کافی است. تا وقتی «حالتِ آزمایشی» روشن است، کد روی صفحه نشان داده می‌شود و پیامکی نمی‌رود — برای تست قبل از خریدِ پنل.',
'type'=>'textblock',
'std'=>'',
'section'=>'account',
'class'=>'ab-guide'
),
array(
'id'=>'account_on',
'label'=>'حسابِ کاربری روشن باشد',
'desc'=>'خاموش = دکمهٔ ورود، پنلِ کاربری و همهٔ قابلیت‌های آن (علاقه‌مندی، پلی‌لیست، دنبال‌کردن) از سایت برداشته می‌شود. هیچ داده‌ای پاک نمی‌شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_allow_password',
'label'=>'ورود با نام‌کاربری و رمزِ وردپرس هم ممکن باشد',
'desc'=>'برای حسابِ مدیران و کاربرانِ قدیمی خوب است. کاربرانِ عادیِ تازه فقط با پیامک وارد می‌شوند.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_code_ttl',
'label'=>'عمرِ کدِ ورود (ثانیه)',
'desc'=>'پیش‌فرض ۱۸۰ = سه دقیقه. کمتر = امن‌تر، ولی اگر پیامک دیر برسد کاربر باید دوباره بگیرد.',
'std'=>'180',
'type'=>'numeric-slider',
'min_max_step'=>'60,600,30',
'section'=>'account'
),
array(
'id'=>'account_resend',
'label'=>'فاصلهٔ ارسالِ دوبارهٔ کد (ثانیه)',
'desc'=>'جلوگیری از هزینهٔ پیامکِ تکراری. در فرم، شمارندهٔ معکوس نشان داده می‌شود.',
'std'=>'60',
'type'=>'numeric-slider',
'min_max_step'=>'20,300,10',
'section'=>'account'
),
array(
'id'=>'account_tries',
'label'=>'حداکثر تلاشِ اشتباه برای هر کد',
'desc'=>'بعد از این تعداد، کد باطل می‌شود و کاربر باید کدِ تازه بگیرد.',
'std'=>'6',
'type'=>'numeric-slider',
'min_max_step'=>'3,10,1',
'section'=>'account'
),
array(
'id'=>'account_per_phone',
'label'=>'حداکثر پیامک در ساعت برای هر شماره',
'desc'=>'اگر کسی پشتِ سرِ هم کد بخواهد، جلویش گرفته می‌شود.',
'std'=>'5',
'type'=>'numeric-slider',
'min_max_step'=>'1,20,1',
'section'=>'account'
),
array(
'id'=>'account_per_ip',
'label'=>'حداکثر پیامک در ساعت از هر IP',
'desc'=>'محافظت در برابرِ سوءاستفادهٔ گروهی. برای سایت‌های پشتِ پراکسی عدد را کمی بالاتر بگذارید.',
'std'=>'12',
'type'=>'numeric-slider',
'min_max_step'=>'3,60,1',
'section'=>'account'
),
array(
'id'=>'account_sms',
'label'=>'سرویسِ پیامک',
'desc'=>'«ملی‌پیامک» مستقیم به RESTِ رسمی وصل می‌شود. «وب‌هوک» برای هر پنلِ دیگر (کاوه‌نگار، SMS.ir، اسکریپتِ اختصاصی). «آزمایشی» پیامک نمی‌فرستد و کد را روی همان صفحه نشان می‌دهد.',
'std'=>'dev',
'type'=>'select',
'choices'=>array(
array('value'=>'dev','label'=>'آزمایشی (بدونِ پیامک)'),
array('value'=>'melipayamak','label'=>'ملی‌پیامک (REST)'),
array('value'=>'webhook','label'=>'وب‌هوکِ دلخواه'),
),
'section'=>'account'
),
array(
'id'=>'account_sms_user',
'label'=>'ملی‌پیامک — نام‌کاربری',
'desc'=>'همان نام‌کاربریِ ورود به پنلِ ملی‌پیامک.',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_sms_pass',
'label'=>'ملی‌پیامک — رمزِ عبور',
'desc'=>'رمزِ پنل. فقط در همین تنظیمات ذخیره می‌شود و هیچ‌جا نمایش داده نمی‌شود (مگر همین کادر برای مدیر).',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_sms_from',
'label'=>'ملی‌پیامک — شمارهٔ فرستنده',
'desc'=>'مثلاً ۳۰۰۰۶۸ یا شمارهٔ اختصاصیِ شما، بدونِ صفرِ اضافه.',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_sms_pattern',
'label'=>'ملی‌پیامک — کدِ پترن (اختیاری)',
'desc'=>'اگر پترن دارید، فقط شمارهٔ پترن را بگذارید؛ ارسال سریع‌تر و ارزان‌تر است و «متنِ پیامک» نادیده گرفته می‌شود. خالی = ارسالِ متنیِ معمولی.',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_sms_hook',
'label'=>'وب‌هوک — آدرس',
'desc'=>'یک POSTِ JSON با این بدنه به آدرس می‌رود: <code>{phone, code, text, site, name}</code>. فقط https پذیرفته می‌شود و پاسخِ 2xx یعنی «ارسال شد».',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_sms_hook_auth',
'label'=>'وب‌هوک — هدرِ احراز (اختیاری)',
'desc'=>'مثلاً <code>Bearer xxxxx</code>. اگر خالی باشد هدری فرستاده نمی‌شود.',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_sms_text',
'label'=>'متنِ پیامک',
'desc'=>'از <code>{code}</code> برای کد و از <code>{site}</code> برای نامِ سایت استفاده کنید.',
'std'=>'کدِ ورود شما به {site}: {code}
این کد را به هیچ‌کس ندهید.',
'type'=>'textarea',
'section'=>'account'
),
array(
'id'=>'account_dev_show',
'label'=>'در حالتِ آزمایشی کد روی صفحه دیده شود',
'desc'=>'فقط برای تست. در سایتِ واقعی که پیامک روشن است، این گزینه هیچ اثری ندارد.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_features',
'label'=>'',
'desc'=>'<strong>قابلیت‌های پنلِ کاربری.</strong> هر کدام را خاموش کنید، هم از پنل و هم از صفحهٔ آهنگ/خواننده برداشته می‌شود؛ داده‌های قبلی می‌مانند.',
'type'=>'textblock',
'std'=>'',
'section'=>'account',
'class'=>'ab-guide'
),
array(
'id'=>'account_likes',
'label'=>'ذخیره در علاقه‌مندی‌ها (قلب)',
'desc'=>'دکمهٔ قلب روی صفحهٔ آهنگ و ردیف‌های فهرست.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_playlists',
'label'=>'پلی‌لیست‌ها',
'desc'=>'ساختِ پلی‌لیست، افزودن/برداشتنِ آهنگ و لینکِ اشتراکی.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_follows',
'label'=>'دنبال‌کردنِ خواننده‌ها',
'desc'=>'دکمه روی صفحهٔ خواننده + فیدِ آهنگ‌های تازه در پنل.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_history',
'label'=>'تاریخچهٔ شنیدن',
'desc'=>'آهنگ‌هایی که کاربر پخش می‌کند در پنلِ خودش ثبت می‌شود. برای حریمِ خصوصی، هیچ‌جا عمومی نیست.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_notifs',
'label'=>'پیام‌ها و اعلان‌ها',
'desc'=>'پیامِ خوش‌آمد، اتصالِ ربات و آهنگ‌های تازهٔ خواننده‌های دنبال‌شده.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_avatar',
'label'=>'تصویرِ پروفایل',
'desc'=>'کاربر می‌تواند آدرسِ تصویر بدهد (یا تصویرِ گراواتار داشته باشد).',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_welcome',
'label'=>'پیامِ خوش‌آمد برای کاربرِ تازه',
'desc'=>'بلافاصله بعد از اولین ورود، یک پیام در پنل می‌نشیند.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_max_likes',
'label'=>'سقفِ علاقه‌مندی‌ها برای هر کاربر',
'desc'=>'پیش‌فرض ۵۰۰ آهنگ.',
'std'=>'500',
'type'=>'numeric-slider',
'min_max_step'=>'50,2000,50',
'section'=>'account'
),
array(
'id'=>'account_max_playlists',
'label'=>'سقفِ پلی‌لیست برای هر کاربر',
'std'=>'30',
'type'=>'numeric-slider',
'min_max_step'=>'3,100,1',
'section'=>'account'
),
array(
'id'=>'account_max_tracks',
'label'=>'سقفِ آهنگ در هر پلی‌لیست',
'std'=>'300',
'type'=>'numeric-slider',
'min_max_step'=>'20,1000,20',
'section'=>'account'
),
array(
'id'=>'account_max_follows',
'label'=>'سقفِ خوانندهٔ دنبال‌شده',
'std'=>'500',
'type'=>'numeric-slider',
'min_max_step'=>'20,2000,20',
'section'=>'account'
),
array(
'id'=>'account_max_history',
'label'=>'سقفِ تاریخچهٔ شنیدن',
'std'=>'100',
'type'=>'numeric-slider',
'min_max_step'=>'20,500,20',
'section'=>'account'
),
array(
'id'=>'account_redirect',
'label'=>'بعد از ورود کجا برود؟',
'desc'=>'خالی = پنلِ «حساب من». می‌توانید آدرسِ یک برگهٔ دلخواه بگذارید.',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_bot_guide',
'label'=>'',
'desc'=>'<strong>اتصال به رباتِ تلگرام.</strong> اگر رباتِ «آهنگ‌بان» را دارید، نامِ کاربریِ ربات و همان توکنی که در فایلِ <code>.env</code> ربات گذاشته‌اید (<code>AHANGBAN_API_TOKEN</code>) را این‌جا بگذارید. کاربر در پنل کدِ ۸حرفی می‌گیرد، در ربات می‌فرستد و حسابش وصل می‌شود؛ از آن به بعد می‌تواند از تلگرام آهنگ ذخیره کند، خواننده دنبال کند و اعلان بگیرد. اگر توکن را این‌جا نگذارید، همین قالب همان توکنِ افزونهٔ «ahangban-connector» را می‌خواند و بدونِ تنظیمِ تازه کار می‌کند.',
'type'=>'textblock',
'std'=>'',
'section'=>'account',
'class'=>'ab-guide'
),
array(
'id'=>'account_tg_song',
'label'=>'دکمهٔ «مشاهده در ربات تلگرام» زیرِ لینک‌های دانلود',
'desc'=>'روشن (پیش‌فرض) = در صفحهٔ هر آهنگ، زیرِ لینک‌های دانلود یک دکمه می‌آید که همان آهنگ را در ربات باز می‌کند: <code>https://t.me/&lt;نامِ ربات&gt;?start=song_&lt;شناسهٔ آهنگ&gt;</code>. کاربر همان‌جا پخش، دانلود و ذخیره در علاقه‌مندی‌ها را دارد و با همان حسابِ تلگرامی که به سایت وصل است شناخته می‌شود. خاموش = دکمه چاپ نمی‌شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'account'
),
array(
'id'=>'account_tg_bot',
'label'=>'نامِ کاربریِ ربات',
'desc'=>'بدونِ @؛ مثلاً <code>ahangbanbot</code>. برای دکمهٔ «مشاهده در ربات تلگرام»، لینکِ اتصالِ پنل و دیپ‌لینک‌ها همین نام به کار می‌رود. خالی = <code>ahangbanbot</code>.',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_tg_token',
'label'=>'توکنِ RESTِ ربات (۶۴ کاراکترِ هگز)',
'desc'=>'همان مقدارِ <code>AHANGBAN_API_TOKEN</code> در ربات. خالی = خواندنِ توکنِ افزونهٔ ربات. با این توکن، ربات می‌تواند پروفایل، دنبال‌شده‌ها، فیدِ تازه‌ها و پلی‌لیست‌ها را برای کاربر بخواند.',
'std'=>'',
'type'=>'text',
'section'=>'account'
),
array(
'id'=>'account_tg_code_ttl',
'label'=>'عمرِ کدِ اتصال به ربات (ثانیه)',
'std'=>'600',
'type'=>'numeric-slider',
'min_max_step'=>'120,1800,60',
'section'=>'account'
),
));
  /* allow settings to be filtered before saving */
  $custom_settings = apply_filters( 'option_tree_settings_args', $custom_settings );
  /* settings are not the same update the DB */
  if ( $saved_settings !== $custom_settings ) {
    update_option( 'option_tree_settings', $custom_settings ); 
  }
}
