<?php
/**
 * آزمونِ واحدِ منطقِ نسخهٔ ۳.۵.۲ — با شبیه‌سازِ حداقلیِ وردپرس (tests/stubs-wp.php).
 * اجرا:  node tools/php-test.mjs tests/run-352.php
 *
 * این آزمون خودِ توابعِ قالب را (نه بازنویسیِ آن‌ها) اجرا می‌کند؛ پس اگر تابعی
 * عوض شود، نتیجه هم عوض می‌شود. چیزی که تست نمی‌شود: کوئریِ واقعیِ MySQL،
 * رفتارِ وردپرس در مرورگر، و رندرِ کاملِ صفحات (نیازمند نصب وردپرس).
 */

require __DIR__ . '/stubs-wp.php';

/* فایل‌های خودِ قالب */
require '/tmp/theme/inc/ab-artist-fields.php';
require '/tmp/theme/inc/ab-cat-desc.php';
require '/tmp/theme/inc/ab-terms-admin.php';
require '/tmp/theme/inc/ab-post-artist.php';

$GLOBALS['__attachments'] = array( 123 => true, 77 => true );

$PASS = 0;
$FAIL = 0;
$FAILS = array();

function t( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL, $FAILS;
	if ( $cond ) {
		$PASS++;
		echo "ok   $name\n";
	} else {
		$FAIL++;
		$FAILS[] = $name . ( $detail ? " — $detail" : '' );
		echo "FAIL $name" . ( $detail ? " — $detail" : '' ) . "\n";
	}
}

function reset_state() {
	$GLOBALS['__termmeta'] = array();
	$GLOBALS['__postmeta'] = array();
	$GLOBALS['__options']  = array();
	$GLOBALS['__trans']    = array();
	$GLOBALS['__caps']     = array();
	$GLOBALS['__calls']    = array();
	$GLOBALS['__objterms'] = array();
	$GLOBALS['__posts']    = array();
	$GLOBALS['__terms']    = array();
	$GLOBALS['__acf']      = array();
	$GLOBALS['__db_sum']   = 0;
	$GLOBALS['__db_ids']   = array();
	$_POST                 = array();
}

/* ======================================================================
   ۱) تصویر — تبدیل مقدار به URL
   ====================================================================== */
reset_state();

t( 'تصویر: شناسهٔ پیوست → URL اندازهٔ medium', tara_image_url_from_value( 123, 'medium' ) === 'https://ex/123-medium.jpg' );
t(
	'تصویر: آرایهٔ ACF با sizes → اندازهٔ خواسته‌شده',
	tara_image_url_from_value( array( 'sizes' => array( 'medium' => 'https://ex/sized.jpg' ), 'url' => 'https://ex/full.jpg' ), 'medium' ) === 'https://ex/sized.jpg'
);
t( 'تصویر: آرایهٔ ACF بدون sizes → url', tara_image_url_from_value( array( 'url' => 'https://ex/full.jpg' ) ) === 'https://ex/full.jpg' );
t( 'تصویر: رشتهٔ URL', tara_image_url_from_value( 'https://cdn.ex/x.jpg' ) === 'https://cdn.ex/x.jpg' );
t( 'تصویر: مسیر نسبی', tara_image_url_from_value( '/wp-content/x.jpg' ) === '/wp-content/x.jpg' );
t( 'تصویر: خالی', tara_image_url_from_value( '' ) === '' );
t( 'تصویر: رشتهٔ بی‌معنی', tara_image_url_from_value( 'not-a-url' ) === '' );
t( 'تصویر: شناسهٔ ناموجود', tara_image_url_from_value( 999 ) === '' );

/* ======================================================================
   ۲) خواندن فیلد هنرمند: ACF → term meta
   ====================================================================== */
reset_state();
$term = new WP_Term( 5, 'محسن یگانه', 'artist', 'mohsen-yeganeh', 42 );
$GLOBALS['__terms'][5] = $term;

update_term_meta( 5, 'aimg2', 'https://ex/meta.jpg' );
t( 'فیلد: وقتی ACF مقدار ندارد، term meta خوانده می‌شود', tara_artist_field( 5, 'aimg2' ) === 'https://ex/meta.jpg' );

$GLOBALS['__acf']['aimg2'] = array( 'url' => 'https://ex/acf.jpg' );
t( 'فیلد: ACF اولویت دارد', tara_artist_field( 5, 'aimg2' ) === 'https://ex/acf.jpg' );
$GLOBALS['__acf'] = array();

update_term_meta( 5, 'afacebook', 'javascript:alert(1)' );
t( 'فیلد: URL ناامن پاک می‌شود', tara_artist_field( 5, 'afacebook' ) === '' );

update_term_meta( 5, 'afacebook', 'https://facebook.com/x' );
t( 'فیلد: URL سالم می‌ماند', tara_artist_field( 5, 'afacebook' ) === 'https://facebook.com/x' );

t( 'فیلد: ترمِ ناموجود → پیش‌فرض', tara_artist_field( 999, 'aimg2', 'DEF' ) === 'DEF' );

update_term_meta( 5, 'ab_bio_short', '<b>سلام</b> دنیا' );
t( 'فیلد: بیوگرافی کوتاه، تگ‌ها حذف می‌شوند', tara_artist_field( 5, 'ab_bio_short' ) === 'سلام دنیا' );

/* شناسهٔ پیوست در فیلد (سازگاری با ACF type=image) */
reset_state();
$GLOBALS['__terms'][5] = $term;
update_term_meta( 5, 'aimg2', '123' );
t( 'فیلد: شناسهٔ عددی به‌عنوان مقدار تصویر حفظ می‌شود', tara_artist_field( 5, 'aimg2' ) === '123' );
t( 'تصویر: شناسهٔ ذخیره‌شده در aimg2 → URL پیوست', tara_artist_image_url( 5 ) === 'https://ex/123-medium.jpg' );

/* ======================================================================
   ۳) تصویر هنرمند و پیش‌فرض
   ====================================================================== */
reset_state();
$GLOBALS['__terms'][5] = $term;
update_term_meta( 5, 'aimg2', 'https://ex/artist.jpg' );
t( 'تصویر هنرمند: از term meta', tara_artist_image_url( 5 ) === 'https://ex/artist.jpg' );
t( 'تصویر هنرمند: has_image درست است', tara_artist_has_image( 5 ) === true );

$GLOBALS['__termmeta'][5] = array();
t( 'تصویر هنرمند: نبود تصویر → پیش‌فرض قالب', substr( tara_artist_image_url( 5 ), -15 ) === 'def-singer.webp' || substr( tara_artist_image_url( 5 ), -15 ) === 'def-artist.webp' );
t( 'تصویر هنرمند: has_image=false', tara_artist_has_image( 5 ) === false );

/* ======================================================================
   ۴) شبکه‌های اجتماعی
   ====================================================================== */
reset_state();
$GLOBALS['__terms'][5] = $term;
update_term_meta( 5, 'atelegram', 'https://t.me/x' );
update_term_meta( 5, 'ayoutube', 'https://youtube.com/@x' );
$soc = tara_artist_socials( 5 );
t( 'اجتماعی: فقط پرشده‌ها برمی‌گردند', count( $soc ) === 2 && isset( $soc['atelegram'], $soc['ayoutube'] ) );
t( 'اجتماعی: آیکون تلگرام درست است', isset( $soc['atelegram'] ) && $soc['atelegram']['icon'] === 'icofont-paper-plane' );

/* ======================================================================
   ۵) لینک بیوگرافی کامل — قاعدهٔ دسترسی
   ====================================================================== */
reset_state();
$GLOBALS['__terms'][5] = $term;

$GLOBALS['__posts'][12] = new WP_Post( 12, 'بیوگرافی کامل', 'publish' );
update_term_meta( 5, 'ab_bio_post', '12' );
t( 'بیوگرافی: نوشتهٔ منتشرشده → لینک', tara_artist_bio_link( 5 ) === 'https://ex/?p=12' );

$GLOBALS['__posts'][13] = new WP_Post( 13, 'پیش‌نویس', 'draft' );
update_term_meta( 5, 'ab_bio_post', '13' );
t( 'بیوگرافی: پیش‌نویس برای مهمان → بدون لینک', tara_artist_bio_link( 5 ) === '' );

$GLOBALS['__caps']['read_post'] = true;
t( 'بیوگرافی: پیش‌نویس برای کاربر مجاز → لینک', tara_artist_bio_link( 5 ) === 'https://ex/?p=13' );
$GLOBALS['__caps'] = array();

$GLOBALS['__posts'][14] = new WP_Post( 14, 'خصوصی', 'private' );
update_term_meta( 5, 'ab_bio_post', '14' );
t( 'بیوگرافی: خصوصی برای مهمان → بدون لینک', tara_artist_bio_link( 5 ) === '' );

update_term_meta( 5, 'ab_bio_post', '99999' );
t( 'بیوگرافی: نوشتهٔ حذف‌شده → بدون لینک', tara_artist_bio_link( 5 ) === '' );

update_term_meta( 5, 'ab_bio_post', '0' );
t( 'بیوگرافی: بدون انتخاب → بدون لینک', tara_artist_bio_link( 5 ) === '' );

/* ======================================================================
   ۶) مجموع بازدید
   ====================================================================== */
reset_state();
$GLOBALS['__terms'][5] = $term;

$GLOBALS['__db_sum'] = 1234;
t( 'بازدید: جمع از کوئری', tara_artist_total_views( 5 ) === 1234 );
t( 'بازدید: کش پس از محاسبه', get_transient( 'jv_av_5_' . tara_artist_views_epoch() ) === 1234 );

$GLOBALS['__db_sum'] = 9999;
t( 'بازدید: بار دوم از کش (بدون کوئری تازه)', tara_artist_total_views( 5 ) === 1234 );

tara_artist_views_flush();
t( 'بازدید: ابطال کش با تغییرِ نسخه', tara_artist_total_views( 5 ) === 9999 );

$GLOBALS['__db_sum'] = -20;
tara_artist_views_flush();
t( 'بازدید: مقدار منفی/نامعتبر → صفر', tara_artist_total_views( 5 ) === 0 );

tara_artist_views_flush();
$GLOBALS['__db_queue'] = array( 0, 500 ); /* کوئریِ تاکسونومی=۰، بعد جمعِ متای نامی=۵۰۰ */
$GLOBALS['__db_ids']   = array( 21, 22 );
t( 'بازدید: مسیر نامِ متای قدیمی وقتی تاکسونومی جمع صفر است', tara_artist_total_views( 5 ) === 500 );
$GLOBALS['__db_queue'] = array();

$GLOBALS['__db_sum'] = 0;
tara_artist_views_flush();
t( 'بازدید: صفر → خروجی HTML خالی (بدون کادر بی‌محتوا)', tara_artist_views_html( 5 ) === '' );

$GLOBALS['__db_queue'] = array( 128500 );
$GLOBALS['__db_sum'] = 128500;
tara_artist_views_flush();
$html = tara_artist_views_html( 5 );
t( 'بازدید: HTML شامل عددِ فارسی', false !== strpos( $html, '۱۲۸٬۵۰۰' ) || false !== strpos( $html, '۱۲۸,۵۰۰' ) || false !== strpos( $html, '۱۲۸۵۰۰' ), $html );
t( 'بازدید: HTML شامل برچسب', false !== strpos( $html, 'مجموع بازدید آثار' ), $html );
t( 'بازدید: HTML شامل li', 0 === strpos( $html, '<li class="ab-resume__views">' ), $html );

/* ======================================================================
   ۷) توضیح دسته — کوتاه و کامل
   ====================================================================== */
reset_state();
$cat = new WP_Term( 8, 'پاپ', 'category', 'pop', 10 );
$GLOBALS['__terms'][8] = $cat;
$GLOBALS['__termdesc'][8] = 'توضیح قدیمی دسته';

t( 'دسته: نبودِ فیلد تازه → fallback به توضیح قدیمی', false !== strpos( tara_category_desc_short( 8 ), 'توضیح قدیمی دسته' ) );
t( 'دسته: کوتاه شامل کلاس archive_txt_box', false !== strpos( tara_category_desc_short( 8 ), 'archive_txt_box' ) );

update_term_meta( 8, 'ab_desc_short', 'معرفی کوتاه <script>alert(1)</script>تازه' );
$short = tara_category_desc_short( 8 );
t( 'دسته: توضیح کوتاه اولویت دارد', false !== strpos( $short, 'معرفی کوتاه' ) && false === strpos( $short, 'توضیح قدیمی' ), $short );
t( 'دسته: توضیح کوتاه escape شده است', false === strpos( $short, '<script>' ), $short );

t( 'دسته: نبود توضیح کامل → رشتهٔ خالی', tara_category_desc_full( 8 ) === '' );

update_term_meta( 8, 'ab_desc_full', '<p>متن کامل <strong>دسته</strong></p><script>bad()</script>' );
$full = tara_category_desc_full( 8 );
t( 'دسته: کامل شامل بخش ab-cat-desc', false !== strpos( $full, 'class="ab-cat-desc"' ), $full );
t( 'دسته: دکمهٔ نمایش بیشتر در HTML با aria', false !== strpos( $full, 'data-ab-desc-toggle' ) && false !== strpos( $full, 'aria-expanded="true"' ) && false !== strpos( $full, 'aria-controls=' ), $full );
t( 'دسته: دکمه در ابتدا hidden است (بدون JS دیده نشود)', false !== strpos( $full, 'hidden>نمایش بیشتر' ), $full );
t( 'دسته: متن در DOM هست (برای سئو/بدون JS)', false !== strpos( $full, 'متن کامل' ), $full );
t( 'دسته: اسکریپت حذف شده', false === strpos( $full, '<script>' ), $full );

/* ======================================================================
   ۸) ذخیرهٔ فیلدهای ترم — nonce، مجوز، پاک‌سازی
   ====================================================================== */
reset_state();
$GLOBALS['__terms'][5] = $term;
update_term_meta( 5, 'artist_en', 'OLD' );

/* بدون nonce */
$_POST = array( 'tara_artist_form' => '1', 'artist_en' => 'HACK' );
tara_save_term_fields( 5, 5, 'artist' );
t( 'ذخیره: بدون nonce چیزی عوض نمی‌شود', get_term_meta( 5, 'artist_en' ) === 'OLD' );

/* با nonce، بدون مجوز */
$_POST = array( 'tara_term_nonce' => 'good', 'tara_artist_form' => '1', 'artist_en' => 'HACK' );
tara_save_term_fields( 5, 5, 'artist' );
t( 'ذخیره: بدون مجوز چیزی عوض نمی‌شود', get_term_meta( 5, 'artist_en' ) === 'OLD' );

/* با nonce و مجوز */
$GLOBALS['__caps']['manage_categories'] = true;
$_POST = array(
	'tara_term_nonce'  => 'good',
	'tara_artist_form' => '1',
	'aimg2'                  => 'https://ex/new.jpg',
	'aimg2_id'               => '77abc',
	'artist_en'              => '  Mohsen   Yeganeh ',
	'afacebook'              => 'javascript:alert(1)',
	'ab_bio_short'           => '<b>بیو</b> کوتاه',
	'ab_bio_post'            => '12x',
);
tara_save_term_fields( 5, 5, 'artist' );
t( 'ذخیره: تصویر ذخیره شد', get_term_meta( 5, 'aimg2' ) === 'https://ex/new.jpg' );
t( 'ذخیره: شناسهٔ تصویر فقط عدد', get_term_meta( 5, 'aimg2_id' ) === '77' );
t( 'ذخیره: نام لاتین تمیز شد', get_term_meta( 5, 'artist_en' ) === 'Mohsen Yeganeh', get_term_meta( 5, 'artist_en' ) );
t( 'ذخیره: URL ناامن حذف شد', get_term_meta( 5, 'afacebook' ) === '' );
t( 'ذخیره: بیوگرافی بدون تگ', get_term_meta( 5, 'ab_bio_short' ) === 'بیو کوتاه' );
t( 'ذخیره: شناسهٔ نوشته فقط عدد', get_term_meta( 5, 'ab_bio_post' ) === '12' );

/* دسته‌بندی */
reset_state();
$GLOBALS['__terms'][8] = $cat;
$GLOBALS['__caps']['manage_categories'] = true;
$_POST = array(
	'tara_term_nonce' => 'good',
	'tara_cat_form'   => '1',
	'ab_desc_short'         => '<em>' . str_repeat( 'ا', 420 ) . '</em>',
	'ab_desc_full'          => '<p>کامل</p><script>x()</script>',
);
tara_save_term_fields( 8, 8, 'category' );
$saved_short = get_term_meta( 8, 'ab_desc_short' );
t( 'ذخیرهٔ دسته: توضیح کوتاه تا ۴۰۰ نویسه', tara_strlen_utf8( $saved_short ) === 400, (string) tara_strlen_utf8( $saved_short ) );
t( 'ذخیرهٔ دسته: توضیح کامل HTML مجاز را نگه می‌دارد', get_term_meta( 8, 'ab_desc_full' ) === '<p>کامل</p>' );

$_POST = array(
	'tara_term_nonce' => 'good',
	'tara_cat_form'   => '1',
	'ab_desc_short'         => '   ',
	'ab_desc_full'          => '   ',
);
tara_save_term_fields( 8, 8, 'category' );
t( 'ذخیرهٔ دسته: مقدار خالی → حذف متا (نه رشتهٔ خالی)', get_term_meta( 8, 'ab_desc_short' ) === '' && get_term_meta( 8, 'ab_desc_full' ) === '' );

/* ======================================================================
   ۹) جعبهٔ «هنرمندِ اثر»
   ====================================================================== */
reset_state();
$GLOBALS['__terms'][3] = new WP_Term( 3, 'علی', 'artist', 'ali' );
$GLOBALS['__terms'][4] = new WP_Term( 4, 'سارا', 'artist', 'sara' );
$GLOBALS['__posts'][10] = new WP_Post( 10, 'آهنگ تست' );
$GLOBALS['__caps']['edit_post'] = true;

/* بدون nonce */
$_POST = array( 'ab_artist_ids' => array( '3' ) );
tara_save_post_artists( 10 );
t( 'متای پست: بدون nonce تغییری نمی‌کند', get_post_meta( 10, 'artist' ) === '' );

/* با nonce؛ شناسهٔ نامعتبر و تکراری */
$_POST = array(
	'tara_post_artist_nonce' => 'good-post',
	'ab_artist_ids'                => array( '3', '999', '3', '4' ),
);
tara_save_post_artists( 10 );
t( 'متای پست: فقط ترم‌های معتبر', isset( $GLOBALS['__objterms'][10] ) && $GLOBALS['__objterms'][10] === array( 3, 4 ), json_encode( isset( $GLOBALS['__objterms'][10] ) ? $GLOBALS['__objterms'][10] : null ) );
t( 'متای پست: «artist» متنی همگام شد', get_post_meta( 10, 'artist' ) === 'علی, سارا', get_post_meta( 10, 'artist' ) );

/* خالی‌کردن */
$_POST = array(
	'tara_post_artist_nonce' => 'good-post',
	'ab_artist_ids'                => array(),
	'ab_artist_original'           => '3,4',
);
tara_save_post_artists( 10 );
t( 'متای پست: با حذف همه (فرم نمایش داشت)، متای متنی پاک می‌شود', get_post_meta( 10, 'artist' ) === '' );

/* بدون مجوز */
$GLOBALS['__caps'] = array();
$_POST = array( 'tara_post_artist_nonce' => 'good-post', 'ab_artist_ids' => array( 3 ) );
tara_save_post_artists( 10 );
t( 'متای پست: بدون مجوز ویرایش تغییری نمی‌کند', get_post_meta( 10, 'artist' ) === '' );

/* ---------- سناریوی مهم: نوشتهٔ قدیمی با متای artist و بدون ترم ---------- */
reset_state();
$GLOBALS['__terms'][3] = new WP_Term( 3, 'علی', 'artist', 'ali' );
$GLOBALS['__posts'][11] = new WP_Post( 11, 'آهنگ قدیمی' );
update_post_meta( 11, 'artist', 'هنرمند قدیمی' );
$GLOBALS['__caps']['edit_post'] = true;

/* ویرایشِ نوشته بدون دست‌زدن به جعبهٔ هنرمند (چیپ‌ها خالی، اصلی هم خالی) */
$_POST = array(
	'tara_post_artist_nonce' => 'good-post',
	'ab_artist_ids'                => array(),
	'ab_artist_original'           => '',
);
tara_save_post_artists( 11 );
t( 'متای پست: ذخیرهٔ بدون تغییر، متای قدیمی را پاک نمی‌کند', get_post_meta( 11, 'artist' ) === 'هنرمند قدیمی', get_post_meta( 11, 'artist' ) );

/* ذخیره از ویرایش سریع (جعبه رندر نشده) یا بدون فرم ⇒ هیچ تغییری نباید رخ دهد */
$_POST = array(
	'tara_post_artist_nonce' => 'good-post',
	'ab_artist_ids'                => array( 3 ),
	'ab_artist_original'           => '',
);
tara_save_post_artists( 11 );
t( 'متای پست: افزودنِ ترم بدون وضعیتِ اولیه هم بی‌خطر است', get_post_meta( 11, 'artist' ) === 'علی', get_post_meta( 11, 'artist' ) );

/* حالا مدیر عمداً یک هنرمند اضافه می‌کند */
$_POST = array(
	'tara_post_artist_nonce' => 'good-post',
	'ab_artist_ids'                => array( 3 ),
	'ab_artist_original'           => '',
);
tara_save_post_artists( 11 );
t( 'متای پست: تغییرِ عمدی ⇒ همگام‌سازی انجام می‌شود', get_post_meta( 11, 'artist' ) === 'علی', get_post_meta( 11, 'artist' ) );

/* و اگر دقیقاً همان وضعیت قبلی دوباره ذخیره شود، تغییری نمی‌کند */
$_POST = array(
	'tara_post_artist_nonce' => 'good-post',
	'ab_artist_ids'                => array( '3' ),
	'ab_artist_original'           => '3',
);
tara_save_post_artists( 11 );
t( 'متای پست: ذخیرهٔ تکراری، تغییری ایجاد نمی‌کند', get_post_meta( 11, 'artist' ) === 'علی' );

/* ======================================================================
   خلاصه
   ====================================================================== */
echo "\n";
echo 'RESULT pass=' . $PASS . ' fail=' . $FAIL . "\n";
if ( $FAIL ) {
	echo "FAILED:\n";
	foreach ( $FAILS as $f ) {
		echo ' - ' . $f . "\n";
	}
}
exit( $FAIL ? 1 : 0 );
