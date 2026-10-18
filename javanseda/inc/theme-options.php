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
array('id'=>'socials','title'=>'شبکه های اجتماعی'),
array('id'=>'homes','title'=>'صفحه اصلی'),
array('id'=>'dl_pages','title'=>'صفحه دانلود آهنگ'),
array('id'=>'ads1','title'=>'تبلیغات'),
array('id'=>'footer','title'=>'پانوشت'),
array('id'=>'ab_ui','title'=>'کنترل رابط'),
),
'settings'=>array( 
// General
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
'label'=>'لوگو',
'desc'=>'پیشنهاد می‌شود اندازه‌ی لوگو 150px × 40px باشد.',
'std'=> get_stylesheet_directory_uri().'/images/logo.png',
'type'=>'upload',
'section'=>'general'
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
'label'=>'مطالب بیشتر خواننده',
'desc'=>'مطالب بیشتر خواننده بر اساس برچسب یا همان tag نمایش داده شود؟',
'std'=>'off',
'type'=>'on-off',
'section'=>'general',
),
array(
'id' => 'navar_txt',
'label'=>'توضیحات',
'desc'=>'اگر از برچسب ها برای ساخت آرشیو خواننده استفاده کرده اید این بخش را بر روی on یا بله قرار دهید. <br> در غیر اینصورت اگر از بخش خواننده که زیر برچسب ها اضافه شده و مخصوص این قالب است استفاده کرده اید این گزینه را بر روی off یا خیر قرار دهید. پیشنهاد ما استفاده از فیلد مخصوص خواننده که در زیر برچسب اضافه شده است. هستَ!<br> برای سفارش <a target="_blank" href="http://tarlanweb.ir">طراحی قالب وردپرس اختصاصی</a> و یا ویرایش قالب فعلی با شماره 09158856205 تماس بگیرید.',
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
'desc'=>'کتعداد اسلایدها قابل نمایش را وارد کنید.',
'type'=>'text',
'std'=>'4',
'section'=>'homes'
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
'label'=>'تعداد آهنگها',
'desc'=>'تعداد آهنگهای قابل نمایش را وارد کنید.',
'type'=>'text',
'std'=>'8',
'section'=>'homes'
),
array(
'id'=>'index_hj',
'label'=>'جدیدترین مطالب',
'desc'=>'باکس جدیدترین مطالب به همراه صفحه بندی در ایندکس نمایش داده شود ؟',
'std'=>'off',
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
'desc'=>'باکس پربازدیدترین مطالب ماه به همراه صفحه بندی در ایندکس نمایش داده شود ؟',
'std'=>'off',
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
'label'=>'تعداد آهنگها',
'desc'=>'تعداد آهنگهای قابل نمایش در باکس پربازدیدترین مطالب را وارد کنید.',
'type'=>'text',
'std'=>'8',
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
'id'=>'indpl',
'label'=>'پلیر ایندکس',
'desc'=>'پلیر ایندکس نمایش داده شود؟',
'std'=>'on',
'type'=>'on-off',
'section'=>'homes',
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
'desc'=>'آدرس صفحه خوانندگان بیشتر را  به همراه http:// وارد کنید.',
'type'=>'text',
'std'=>'',
'section'=>'homes'
),
array(
'id'=>'siing_t',
'label'=>'خواننده ها',
'desc'=>'لیست خواننده ها را ایجاد کنید.',
'type'=>'list-item',
'section'=>'homes',
'choices'=>array(),
'settings'=>array(
array(
'id'=>'siing_u',
'label'=>'صفحه خواننده',
'desc'=>'آدرس صفحه خواننده را به همراه http:// را وارد کنید.',
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
'id'=>'fixbtn',
'label'=>'باکس شبکه‌های اجتماعی در صفحهٔ پست',
'desc'=>'بالای لینک‌های دانلود هر آهنگ، دکمه‌های تلگرام/اینستاگرام/بله/روبیکا نمایش داده شود ؟ (در نسخهٔ 3.2.0 این گزینه جای «لینک‌های ثابتِ پایین صفحه» را گرفته)',
'old_label'=>'لینکهای ثابت',
'std'=>'on',
'type'=>'on-off',
'section'=>'socials',
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
'id'=>'pppf',
'label'=>'مطالب خواننده',
'desc'=>'تعداد سایر آهنگهای خواننده را وارد کنید.',
'type'=>'text',
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
'id'=>'credits_box',
'label'=>'شناسنامۀ آهنگ (تهیه‌کنندگان)',
'desc'=>'ردیف‌های خواننده · آهنگساز · ترانه‌سرا · تنظیم · میکس و مستر · تولید در صفحۀ پست نمایش داده شود؟ (داده از فیلدهای artist/composer/lyricist/arranger/mixer/producer)',
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

/* ---- v3.4.1 — «کنترل رابط» + کنترلرهای فوتر (سورس: inc/ab-controls.php) ---- */
array(
'id'=>'ab_glass_ui',
'label'=>'لایۀ شیشه‌ایِ کارت‌ها',
'desc'=>'کارت/سایه/بلور روی باکس‌ها (css/ab-glass.css). خاموش = فقط استایل خودِ قالب.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_ui_guard',
'label'=>'گاردِ لایۀ CSS',
'desc'=>'اگر روزی style.css بازنویسی شود و بلوک انتهایی‌اش برود، کپیِ همان استایل از css/ab-ui.css لود می‌شود. پیشنهاد: روشن.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
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
'id'=>'ab_sticky_player',
'label'=>'نوار چسبان پخش',
'desc'=>'نوار پایینِ صفحه در صفحۀ تکیِ آهنگ. خاموش = هیچ DOM یا اسکریپتی چاپ نمی‌شود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_skip_link',
'label'=>'لینک «پرش به محتوا»',
'desc'=>'نخستین لینکِ صفحه برای کیبورد و صفحه‌خوان.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_quickbar',
'label'=>'نوار دسترسی سریع',
'desc'=>'نوار زیر هدرِ صفحۀ اصلی (تازه‌ترین آهنگ‌ها · همهٔ خواننده‌ها · پربازدیدترین‌ها / اخبار روز).',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_player_mode',
'label'=>'شکل پلیرِ صفحۀ اصلی',
'desc'=>'«کارت» = حالت فعلی (کادر، گردی، فهرست در پایین کارت). «فشرده» = همان کارت با ارتفاع کمتر. «ساده» = بدون کادر و سایه، مثل قالب مادر.',
'std'=>'card',
'type'=>'select',
'choices'=>array(
array('value'=>'card','label'=>'کارت'),
array('value'=>'compact','label'=>'فشرده'),
array('value'=>'legacy','label'=>'ساده (قالب مادر)'),
),
'section'=>'ab_ui'
),
array(
'id'=>'ab_player_list',
'label'=>'فهرست آلبوم در پلیر',
'desc'=>'«دکمه» = لینک «نمایش آلبوم» زیر پلیر (حالت فعلی). «باز» = فهرست همیشه نمایش داده می‌شود. «مخفی» = فهرست و دکمه حذف می‌شوند.',
'std'=>'btn',
'type'=>'select',
'choices'=>array(
array('value'=>'btn','label'=>'دکمه'),
array('value'=>'open','label'=>'باز'),
array('value'=>'hide','label'=>'مخفی'),
),
'section'=>'ab_ui'
),
array(
'id'=>'ab_player_volume',
'label'=>'دکمۀ صدا در پلیر',
'desc'=>'اسلایدر تنظیم صدا.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_player_gap',
'label'=>'فاصلۀ پایین پلیر (px)',
'desc'=>'فاصلۀ میان پلیر تا محتوای زیر آن؛ فقط عدد، بدون px. پیش‌فرض ۳۰، بازۀ مجاز ۰ تا ۹۶.',
'std'=>'30',
'type'=>'text',
'section'=>'ab_ui'
),
array(
'id'=>'ab_player_radius',
'label'=>'گردی گوشۀ پلیر (px)',
'desc'=>'گردی کارت پلیر؛ فقط عدد. ۰ = گوشۀ تیز، پیش‌فرض ۲۰.',
'std'=>'20',
'type'=>'text',
'section'=>'ab_ui'
),
array(
'id'=>'ab_player_border',
'label'=>'کادر پلیر',
'desc'=>'خط دور کارت پلیر.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_player_shadow',
'label'=>'سایۀ پلیر',
'desc'=>'سایۀ نرم زیر کارت پلیر.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
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
'desc'=>'«همهٔ خواننده‌ها» و «تازه‌ترین آهنگ‌ها» در ستون لینک‌های مفید.',
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
'desc'=>'تعداد «دسته‌های پرطرفدار» در ستون سوم فوتر؛ فقط عدد. ۰ = همه، پیش‌فرض ۸.',
'std'=>'8',
'type'=>'text',
'section'=>'footer'
),

));
  /* allow settings to be filtered before saving */
  $custom_settings = apply_filters( 'option_tree_settings_args', $custom_settings );
  /* settings are not the same update the DB */
  if ( $saved_settings !== $custom_settings ) {
    update_option( 'option_tree_settings', $custom_settings ); 
  }
}
