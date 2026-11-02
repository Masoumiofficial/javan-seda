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
array('id'=>'homes','title'=>'صفحه اصلی'),
array('id'=>'dl_pages','title'=>'صفحه دانلود آهنگ'),
array('id'=>'ab_ui','title'=>'کنترل رابط'),
array('id'=>'footer','title'=>'پانوشت'),
array('id'=>'socials','title'=>'شبکه های اجتماعی'),
array('id'=>'ads1','title'=>'تبلیغات'),
),
'settings'=>array( 
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
'label'=>'لوگو',
'desc'=>'پیشنهاد می‌شود اندازه‌ی لوگو 150px × 40px باشد.',
'std'=> get_stylesheet_directory_uri().'/images/logo.png',
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
'desc'=>'اگر از برچسب ها برای ساخت آرشیو هنرمند استفاده کرده اید این بخش را بر روی on یا بله قرار دهید. <br> در غیر اینصورت اگر از بخش هنرمند که زیر برچسب ها اضافه شده و مخصوص این قالب است استفاده کرده اید این گزینه را بر روی off یا خیر قرار دهید. پیشنهاد ما استفاده از فیلد مخصوص هنرمند که در زیر برچسب اضافه شده است. هستَ!<br> برای سفارش <a target="_blank" href="http://tarlanweb.ir">طراحی قالب وردپرس اختصاصی</a> و یا ویرایش قالب فعلی با شماره 09158856205 تماس بگیرید.',
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
'label'=>'تعداد آهنگ‌های پربازدیدترین‌ها',
'desc'=>'چند آهنگ در باکس «پربازدیدترین مطالب ماه» چاپ شود.',
'type'=>'numeric-slider',
'min_max_step'=>'1,60,1',
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
'desc'=>'هر ردیف = یک هنرمند در اسلایدرِ صفحۀ اصلی. از این نسخ' + Z + 'ه اگر فقط ' + Z + 'نام هنرمند' + Z + ' را بنویسید، عکس و لینک از خودِ صفحۀ هنرمند خوانده می‌شود (یک بار آپلود، همه‌جا). برای ردیف‌های خودکار، ردیفِ «' + Z + 'ساخت خودکارِ ردیف خانه' + Z + '» را روشن کنید.',
'type'=>'list-item',
'section'=>'homes',
'choices'=>array(),
'settings'=>array(
array(
'id'=>'siing_n',
'label'=>'نام هنرمند',
'desc'=>'نامِ دقیق' + Z + 'هنرمند هم' + Z + 'ان در پیشخوان. اگر پر شود، لینک و عکس از خودِ هنرمند می‌آید و لازم نیست دو فیلد بعدی را پر کنید.',
'std'=> '',
'type'=>'text',
'choices'=>array()
),
array(
'id'=>'siing_u',
'label'=>'صفحه هنرمند',
'desc'=>'اختیاری — اگر خالی بماند، آدرس از همان «نام هنرمند' + Z + '» ساخته می‌شود.',
'std'=> '',
'type'=>'text',
'choices'=>array()
),
array(
'id'=>'siing_img',
'label'=>'تصویر',
'desc'=>'اختیاری — خالی = عکسِ پروفایلِ همان هنرمند. اگر پر شود، فقط در این ردیف جا ' + Z + 'م را می‌گیرد. (اندازۀ پیشنهادی 150×150)',
'std'=> get_stylesheet_directory_uri().'/images/nomusics.jpg',
'type'=>'upload'
))),
array(
'id'=>'ab_home_artists_auto',
'label'=>'ساخت خودکارِ ردیف خانه',
'desc'=>'روشن = ردیفِ «' + Z + 'محبوبترین هنرمندان' + Z + '» از خودِ ترم‌های هنرمند ساخته می‌شود (با ترتیبِ پایین و ثابت‌های بالا) و ردیف‌های دستی نادیده گرفته می‌شوند. خاموش = مثل قبل، فقط ردیف‌های دستی.',
'std'=>'off',
'type'=>'on-off',
'section'=>'homes'
),
array(
'id'=>'ab_home_artists_n',
'label'=>'چند هنرمند؟',
'desc'=>'عددِ ۱ تا ۳۰.',
'std'=>'12',
'type'=>'text',
'section'=>'homes',
'class'=>'small-text'
),
array(
'id'=>'ab_home_artists_sort',
'label'=>'ترتیبِ ردیف خانه',
'desc'=>'«پربازدیدترین» مجموعِ بازدیدِ آثار هر هنرمند را جمع می‌زند (همان شمارندۀ بازدیدِ پست‌ها؛ فقط خوانده می‌شود، چیزی نوشته نمی‌شود) و شش ساعت کش می‌کند.',
'std'=>'views',
'type'=>'select',
'choices'=>array(
array('value'=>'views','label'=>'پربازدیدترین'),
array('value'=>'count','label'=>'پرکارترین (بیشترین اثر)'),
array('value'=>'new','label'=>'تازه‌ترین'),
array('value'=>'name','label'=>'الفبایی'),
),
'section'=>'homes'
),
array(
'id'=>'ab_home_artists_pin',
'label'=>'ثابت در بالا (با نام)',
'desc'=>'نامِ هنرمند' + Z + 'ها را با کاما جدا کنید: «محسن یگانه، سیروان خسروی». این‌ها همیشه اول می‌آیند. (گزینه' + Z + 'ی دیگر: تیکِ «ثابت در ردیف خانه» در صفحۀ ویرایش هر هنرمند)',
'std'=>'',
'type'=>'text',
'section'=>'homes'
),
array(
'id'=>'ab_home_artists_views',
'label'=>'محاسبۀ بازدید برای ردیف خانه',
'desc'=>'خاموش = سریع‌تر؛ ترتیبِ «پربازدیدترین» آن‌وقت به «پرکارترین» برمی‌گردد.',
'std'=>'on',
'type'=>'on-off',
'section'=>'homes'
),
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
'section'=>'ab_ui'
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
'label'=>'نوار چسبان پخش',
'desc'=>'نوار پایینِ صفحه در صفحۀ تکیِ آهنگ.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
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
'section'=>'ab_ui'
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
'section'=>'ab_ui',
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
'section'=>'ab_ui',
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
'section'=>'ab_ui',
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
'section'=>'ab_ui',
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
'section'=>'ab_ui'
),
array(
'id'=>'ab_post_img_crop',
'label'=>'کات مربعی کاور',
'desc'=>'روشن = عکس در همان کادر مربع از وسط کات می‌شود. خاموش = عرض همان عدد می‌ماند و ارتفاع نسبت اصلیِ عکس را می‌گیرد.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui',
'class'=>'ab-sub'
),
/* ---- هنرمند: نامکِ تاکسونومی و آدرسِ آرشیو (v3.4.7) ---- */
array(
'id'=>'ab_ui_h_artist',
'label'=>'',
'desc'=>'نامکِ «هنرمند»<span>در قالب مادر این تاکسونومی singer است، ولی بقیۀ پروژه artist را به کار می‌برد (فیلد ACFِ artist، کلاس‌ها، فایلِ فهرست هنرمندان). این دو ردیف همان دوگانگی را یکی می‌کند: نامک فعال + آدرسِ آرشیو. مهاجرت فقط یک ستونِ دیتابیس است و با خاموش‌کردن همین کلید برمی‌گردد.</span>',
'type'=>'textblock',
'std'=>'',
'section'=>'ab_ui',
'class'=>'ab-h'
),
array(
'id'=>'ab_artist_tax',
'label'=>'نامک artist فعال باشد',
'desc'=>'روشن = فهرست‌ها، لینک‌ها و اصطلاحات زیر نامک artist (مهاجرت خودکارِ یک‌بار). خاموش = همان singer همیشگی؛ هیچ داده‌ای از دست نمی‌رود.',
'std'=>'on',
'type'=>'on-off',
'section'=>'ab_ui'
),
array(
'id'=>'ab_artist_slug',
'label'=>'آدرسِ آرشیو هنرمند',
'desc'=>'بخشِ اولِ آدرس، بدون اسلش. پیش‌فرض artist ⇒ /artist/mohsen-yeganeh. اگر singer بگذارید آدرس‌های قبلی برمی‌گردند. آدرس‌های قدیمی همیشه با ۳۰۱ به آدرسِ تازه می‌روند. صفحۀ فهرست هم با همین ردیف‌ها آدرسش را از arti_url یا اسلاگِ برگه (artists) می‌گیرد',
'std'=>'artist',
'type'=>'text',
'section'=>'ab_ui',
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

));
  /* allow settings to be filtered before saving */
  $custom_settings = apply_filters( 'option_tree_settings_args', $custom_settings );
  /* settings are not the same update the DB */
  if ( $saved_settings !== $custom_settings ) {
    update_option( 'option_tree_settings', $custom_settings ); 
  }
}
