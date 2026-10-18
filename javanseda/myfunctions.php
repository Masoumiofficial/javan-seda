<?php
/* ---------------------------------------------------------------------------
   لایهٔ ایمنی «سایدبار / ابزارک نوشته‌ها» (v3.0.2)
   اگر این include حذف شود، ناحیه‌های left_side / left_side2 / left_side1
   فقط به فایل رمزنگاری‌شدهٔ functions.php وابسته می‌مانند.
   ------------------------------------------------------------------------- */
$_javan_seda_sidebars = trailingslashit( get_template_directory() ) . 'inc/ab-sidebars.php';
if ( is_readable( $_javan_seda_sidebars ) ) {
	require_once $_javan_seda_sidebars;
}
unset( $_javan_seda_sidebars );
/* v3.1.2 — لایهٔ «اخبار روز»: فقط تابع/شورت‌کد [ab_news] را تعریف می‌کند (بدون هوکِ رندر) */
$_javan_seda_news = trailingslashit( get_template_directory() ) . 'inc/ab-news-ticker.php';
if ( is_readable( $_javan_seda_news ) ) {
	require_once $_javan_seda_news;
}
unset( $_javan_seda_news );
/* v3.1.4 — لایۀ «دیده‌شدن» (meta description + og:/twitter: + BreadcrumbList + wp_robots)
   برای خاموش‌کردن همه:  define( 'JAVANSEDA_SEO', false );  در wp-config.php */
$_javan_seda_seo = trailingslashit( get_template_directory() ) . 'inc/ab-seo.php';
if ( is_readable( $_javan_seda_seo ) ) {
	require_once $_javan_seda_seo;
}
unset( $_javan_seda_seo );
/* v3.1.3 — لایۀ «بایتِ سرعت»: defer / preload فونت / حذف بارِ <head> / lazy تصاویر + گزارش perf.
   برای خاموش‌کردن همه:  define( 'JAVANSEDA_PERF', false );  در wp-config.php */
$_javan_seda_perf = trailingslashit( get_template_directory() ) . 'inc/ab-perf.php';
if ( is_readable( $_javan_seda_perf ) ) {
	require_once $_javan_seda_perf;
}
unset( $_javan_seda_perf );
/* v3.2.0 — لایۀ «باکس اجتماعیِ صفحهٔ پست + نوارِ چسبانِ پخش» (inc/ab-post-extras.php).
   خاموشی کامل:  define( 'JAVANSEDA_POST_EXTRAS', false );  ·  فقط نوارِ پخش:  define( 'JAVANSEDA_STICKY_PLAYER', false ); */
$_javan_seda_extras = trailingslashit( get_template_directory() ) . 'inc/ab-post-extras.php';
if ( is_readable( $_javan_seda_extras ) ) {
	require_once $_javan_seda_extras;
}
unset( $_javan_seda_extras );
/* v3.2.1 — لایۀ «شناسنامۀ آهنگ» (inc/ab-credits.php) — ردیف‌های آهنگساز/ترانه‌سرا/تنظیم/میکس/تولید.
   خاموشی:  define( 'JAVANSEDA_CREDITS', false );  ·  فقط کادر ویرایش:  define( 'JAVANSEDA_CREDITS_METABOX', false ); */
$_javan_seda_credits = trailingslashit( get_template_directory() ) . 'inc/ab-credits.php';
if ( is_readable( $_javan_seda_credits ) ) {
	require_once $_javan_seda_credits;
}
unset( $_javan_seda_credits );


/* v3.1.0 — لایهٔ سازگاری ACF: فقط وقتی ACF فعال نباشد توابع را تعریف می‌کند
   و از خطای کشندهٔ «Call to undefined function get_field()» در single.php جلوگیری می‌کند. */
/* v3.3.0 — لایۀ شیشه‌ای (css/ab-glass.css) : فقط پوسته؛ خاموشی:  define( 'JAVANSEDA_GLASS_UI', false ); */
$_javan_seda_glass = trailingslashit( get_template_directory() ) . 'inc/ab-glass.php';
if ( is_readable( $_javan_seda_glass ) ) {
	require_once $_javan_seda_glass;
}
unset( $_javan_seda_glass );
$_javan_seda_acf = trailingslashit( get_template_directory() ) . 'inc/acf-compat.php';
if ( is_readable( $_javan_seda_acf ) ) {
	require_once $_javan_seda_acf;
}
unset( $_javan_seda_acf );
/* v3.3.8 — گاردِ لایۀ UI (css/ab-ui.css را فقط اگر مارکر از style.css رفت enqueue می‌کند) */
$_javan_seda_uiguard = function_exists( 'locate_template' ) ? locate_template( 'inc/ab-ui-guard.php' ) : trailingslashit( get_template_directory() ) . 'inc/ab-ui-guard.php';
if ( $_javan_seda_uiguard && is_readable( $_javan_seda_uiguard ) ) {
	require_once $_javan_seda_uiguard;
}
unset( $_javan_seda_uiguard );
add_theme_support( 'post-thumbnails' );
if ( function_exists( 'add_theme_support' ) ) { /* v3.3.8 — مارکاپ مدرن برای فرم/گالر‌ی/کپشن و حذف type از enqueueها؛ title-tag عمداً افزوده نشد چون لایۀ ab-seo.php عنوان را خودش می‌سازد */
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
}
if (function_exists('add_image_size')){
add_image_size( 'thumb4', 150, 150, true);
add_image_size( 'thumb3', 60, 60, true);
add_image_size( 'thumb2', 190, 190, true);
add_image_size( 'thumb2x', 380, 380, true); /* v3.3.8 — برای srcset/دستگاه‌های DPR2 (کارت‌ها روی موبایل شارپ می‌شوند) */
add_image_size( 'thumb1', 640, 360, true);
} ?>
<?php
function shapeSpace_script_loader_tag($tag, $handle, $src) {
	if ($handle === 'my-plugin-javascript-handle') {
		if (false === stripos($tag, 'async')) {
			$tag = str_replace(' src', ' async="async" src', $tag);
		}
		if (false === stripos($tag, 'defer')) {
			$tag = str_replace('<script ', '<script defer ', $tag);
		}
	}
	return $tag;
}
add_filter('script_loader_tag', 'shapeSpace_script_loader_tag', 10, 3);
// end
function meks_disable_srcset( $sources ) {
    return false;
}
add_filter( 'wp_calculate_image_srcset', 'meks_disable_srcset' );
// end
?>
<?php
function leftsides(){
$args = array(
'id'            => 'left_side',
'class'         => 'leftside',
'name'          => 'سایدبار نوشته‌ها — بالا (single.php)',
'description'   => 'ابزارک‌های بالای ستون چپ صفحهٔ نوشته/آهنگ را اینجا بگذارید. شناسه: left_side',
'before_widget' => '<aside id="%1$s" class="related_posts %2$s">',
'before_title'  => '<h3 class="tarlanweb_side_titles">',
'after_title'   => '</h3><div class="liner clear"></div><div class="tarlanweb_side_box">',
'after_widget'  => '<div class="clear"></div></div></aside>',
);
register_sidebar($args);
}
add_action('widgets_init','leftsides');
function leftsides2(){
$args = array(
'id'            => 'left_side2',
'class'         => 'leftside2',
'name'          => 'سایدبار نوشته‌ها — پایین (single.php)',
'description'   => 'ابزارک‌های پایین ستون چپ صفحهٔ نوشته (بعد از نوشته‌های مرتبط). شناسه: left_side2',
'before_widget' => '<aside id="%1$s" class="related_posts %2$s">',
'before_title'  => '<h3 class="tarlanweb_side_titles">',
'after_title'   => '</h3><div class="liner clear"></div><div class="tarlanweb_side_box">',
'after_widget'  => '<div class="clear"></div></div></aside>',
);
register_sidebar($args);
}
add_action('widgets_init','leftsides2');
function leftsides1(){
$args = array(
'id'            => 'left_side1',
'class'         => 'leftside1',
'name'          => 'سایدبار برگه‌ها (page.php)',
'description'   => 'ستون چپ «برگه‌ها» — روی صفحهٔ نوشته/آهنگ نمایش داده نمی‌شود! شناسه: left_side1',
'before_widget' => '<aside id="%1$s" class="related_posts %2$s">',
'before_title'  => '<h3 class="tarlanweb_side_titles">',
'after_title'   => '</h3><div class="liner clear"></div><div class="tarlanweb_side_box">',
'after_widget'  => '<div class="clear"></div></div></aside>',
);
register_sidebar($args);
}
add_action('widgets_init','leftsides1');
?>
<?php add_action( 'template_redirect', function(){
ob_start( function( $buffer ){
$buffer = str_replace( array( 'type="text/javascript"', "type='text/javascript'" ), '', $buffer );
$buffer = str_replace( array( 'type="text/css"', "type='text/css'" ), '', $buffer );
$buffer = str_replace( array( 'frameborder="0"', "frameborder='0'" ), '', $buffer );
$buffer = str_replace( array( 'scrolling="no"', "scrolling='no'" ), '', $buffer );
return $buffer;
});
}); ?>
<?php add_filter('acf/settings/remove_wp_meta_box', '__return_false'); ?>
<?php function comment_loop_cd( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case 'pingback' :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p>بازتاب: <?php comment_author_link(); ?><?php edit_comment_link( 'ویرایش', '<span class="edit-link">', '</span>' ); ?></p>
	<?php
			break;
		default :
	?>
<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
<article id="comment-<?php comment_ID(); ?>" class="comment">
<div class="comment-content"> 
<footer>
<?php printf( '%1$s',sprintf( '<span class="fn">%s</span>', get_comment_author_link() ),
sprintf( '<a href="%1$s"><time datetime="%2$s">%3$s</time></a>',
esc_url( get_comment_link( $comment->comment_ID ) ),get_comment_time( 'c' ),
sprintf( '%1$s ، %2$s', get_comment_date("d M Y"), get_comment_time() ))); ?>
<div class="left_kianoosh_like"><div class="comment-date"><?php $d="d F y"; $comment_date = get_comment_date($d,''); echo $comment_date; ?></div>
<div class="clear"></div>
</div>
<div class="comment-replay">
<?php echo comment_reply_link(array('depth' => $depth, 'max_depth' => $args['max_depth'])); ?>
</div>
<div class="clear"></div>
</footer>
<div class="clear"></div>
<div class="rkianoosh_ir_com">
<?php edit_comment_link( '(ویرایش)', '<span class="edit-link">', '</span>' ); ?>
<?php comment_text(); ?>
<?php if ( $comment->comment_approved == '0' ) : ?>
<em class="comment-awaiting-moderation">نظر شما بعد از تائید نمایش داده میشود.</em>
<br>
<?php endif; ?>
<div class="clear"></div>
</div>
<div class="clear"></div>
</div>
<div class="clear"></div>
</article><!-- #comment-## -->
<?php
	break;
	endswitch;
}
function comment_form_cd( $args = array(), $post_id = null ) {
	global $id;
	if ( null === $post_id )
		$post_id = $id;
	else
		$id = $post_id;
	$commenter = wp_get_current_commenter();
	$user = wp_get_current_user();
	$user_identity = ! empty( $user->ID ) ? $user->display_name : '';
	$req = get_option( 'require_name_email' );
	$aria_req = ( $req ? " aria-required='true'" : '' );
	$fields =  array(
		'author' => '<div class="right-commentss"><p class="comment-form-author">' . '<input id="author" class="meta" name="author" type="text" placeholder="نام ( مورد نیاز )" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
		'email'  => '<p class="comment-form-email"><input id="email" class="meta" name="email" type="text" placeholder="پست الکترونیکی (مورد نیاز)" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
		'url'    => '<p class="comment-form-url">' . '<input id="url" class="meta" name="url" type="text" placeholder="وب سایت" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p></div>',
	);
	$required_text = sprintf( ' ' . __('Required fields are marked %s'), '<span class="required">*</span>' );
	$defaults = array(
		'fields'               => apply_filters( 'comment_form_default_fields', $fields ),
		'comment_field'        => '<p class="comment-form-comment"><textarea id="comment" name="comment" cols="45" rows="11" aria-required="true" placeholder="نظرتان را بنویسید"></textarea></p>',
		'must_log_in'          => '<p class="must-log-in">' .  sprintf( __( 'شما باید <a href="%s">وارد شوید</a> برای ارسال دیدگاه' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
		'logged_in_as'         => '<p class="logged-in-as">' . sprintf( __( 'وارد شده با نام <a href="%1$s">%2$s</a>. <a href="%3$s" title="بیرون رفتن از حساب کاربری">بیرون رفتن؟</a>' ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
		'comment_notes_before' => '',
		'id_form'              => 'commentform',
		'id_submit'            => 'submit',
		'title_reply'          => __( '' ),
		'title_reply_to'       => __( 'Leave a Reply to %s' ),
		'cancel_reply_link'    => __( 'Cancel reply' ),
		'label_submit'         => 'ارسال دیدگاه',
	);
	//'comment_notes_before' => '<p class="comment-notes">' . __( 'Your email address will not be published.' ) . ( $req ? $required_text : '' ) . '</p>',
$args = wp_parse_args( $args, apply_filters( 'comment_form_defaults', $defaults ) );
?>
<?php if ( comments_open() ) : ?>
			<?php do_action( 'comment_form_before' ); ?>
<div id="respond">
<h3 id="reply-title"><?php comment_form_title( $args['title_reply'], $args['title_reply_to'] ); ?> <small><?php cancel_comment_reply_link( $args['cancel_reply_link'] ); ?></small></h3>
				<?php if ( get_option( 'comment_registration' ) && !is_user_logged_in() ) : ?>
					<?php echo $args['must_log_in']; ?>
					<?php do_action( 'comment_form_must_log_in_after' ); ?>
				<?php else : ?>
					<form action="<?php echo site_url( '/wp-comments-post.php' ); ?>" method="post" id="<?php echo esc_attr( $args['id_form'] ); ?>">
<?php do_action( 'comment_form_top' ); ?>
<?php if ( is_user_logged_in() ) : ?>
<?php echo apply_filters( 'comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity ); ?>
<?php do_action( 'comment_form_logged_in_after', $commenter, $user_identity ); ?>
<?php else : ?>
<?php echo $args['comment_notes_before']; ?>
<?php
	do_action( 'comment_form_before_fields' );
	foreach ( (array) $args['fields'] as $name => $field ) {
	echo apply_filters( "comment_form_field_{$name}", $field ) . "\n";
}
do_action( 'comment_form_after_fields' ); ?>
<?php endif; ?>
<?php echo apply_filters( 'comment_form_field_comment', $args['comment_field'] ); ?>
<?php // echo $args['comment_notes_after']; ?>
<p class="form-submit">
<input name="submit" type="submit" id="<?php echo esc_attr( $args['id_submit'] ); ?>" value="<?php echo esc_attr( $args['label_submit'] ); ?>" />
<?php comment_id_fields( $post_id ); ?>
</p>
<?php do_action( 'comment_form', $post_id ); ?>
</form>
<?php endif; ?>
<div class="clear"></div>
</div><!-- #respond -->
<?php do_action( 'comment_form_after' ); ?>
<?php else : ?>
<?php do_action( 'comment_form_comments_closed' ); ?>
<?php endif; ?>
<?php
}


/* ---------------------------------------------------------------------------
   «کاور پست» = تصویر شاخص نوشته (Featured Image)
   فعال بودن تصویر شاخص (post-thumbnails) و اندازه‌های thumb1..thumb4 در بالای
   همین فایل ثبت شده است و نیازی به تغییر ندارند.

   به‌صورت پیش‌فرض، نمایش خودکار کاور در ابتدای بخش توضیحات توسط قالب
   single.php انجام می‌شود و کد پایین غیرفعال است.

   حالت اختیاری (سراسری): اگر می‌خواهید کاور خودکار به محتوای نوشته‌ها اضافه شود
   و لازم نباشد به قالب دست بزنید، فقط مقدار ثابت زیر را به true تغییر دهید
   و در آن صورت بلوک تصویر شاخص را از single.php بردارید
   (در غیر این صورت تصویر دو بار نمایش داده می‌شود).
--------------------------------------------------------------------------- */

if ( ! defined( 'JAVANSEDA_COVER_IN_CONTENT' ) ) {
	define( 'JAVANSEDA_COVER_IN_CONTENT', false ); // for global mode change to: true
}

if ( JAVANSEDA_COVER_IN_CONTENT ) {
	function javanseda_content_auto_cover( $content ) {
		if ( is_singular( 'post' ) && is_main_query() && in_the_loop() && has_post_thumbnail() ) {
			$cover = '<div class="single_featured_cover"><figure class="single_featured_image">' . get_the_post_thumbnail(
				get_the_ID(),
				'large',
				array( 'alt' => get_the_title(), 'title' => get_the_title() )
			) . '</figure></div>';
			return $cover . $content;
		}
		return $content;
	}
	add_filter( 'the_content', 'javanseda_content_auto_cover', 20 );
}


/* ==================================================================================
   «صفحهٔ خواننده» — تشخیص و ایمنی  (پیش‌فرض: خاموش)
   ----------------------------------------------------------------------------------
   • این بخش به‌صورت پیش‌فرض هیچ کاری نمی‌کند و رفتار سایت را تغییر نمی‌دهد.
   • اگر صفحهٔ /singer/... شما قبلاً کار می‌کرد، همان‌طور خاموش بماند؛ هیچ ثابتی هم
     لازم نیست تعریف شود. (پیش‌تر در نسخهٔ اول این فایل، همین بخش پیش‌فرض روشن بود و
     می‌توانست با ثبت قالب/افزونهٔ خودتان تداخل کند — عذرخواهی، اصلاح شد.)
   • فعال‌کردن (فقط وقتی گزارش تشخیصی می‌گوید تاکسونومی singer ثبت نشده است):
        ۱) خط پایین را true کن، یا
        ۲) در wp-config.php بنویس:  define( 'JAVANSEDA_SINGER_FIX', true );
   • در حالت فعال: اگر تاکسونومی singer ثبت نشده باشد و پست‌تایپی با همین نام وجود
     نداشته باشد، در اولویت ۹۹ (بعد از قالب و افزونه‌ها) یک ثبت جایگزین انجام می‌شود
     و قوانین پیوند یکتا یک‌بار (نسخه‌دار) بازسازی می‌شوند.
   • گزارش تشخیصی زیر همیشه فعال است، فقط برای مدیر و فقط خواندنی. بهترین کار: همان
     آدرسی که خطا می‌دهد را با ?ab_singer_diag=1 باز کن تا ببینی وردپرس آن درخواست را
     «آرشیو تاکسونومی» می‌بیند، «نوشتهٔ تک»، «آرشیو پست‌تایپ» یا «۴۰۴».
   ================================================================================== */

if ( ! defined( 'JAVANSEDA_SINGER_FIX' ) ) {
	define( 'JAVANSEDA_SINGER_FIX', false ); // برای فعال‌کردن: true
}

if ( JAVANSEDA_SINGER_FIX ) {

	/* ثبت جایگزین — فقط اگر تاکسونومی و پست‌تایپ هم‌نام وجود نداشته باشند */
	add_action(
		'init',
		function () {
			if ( taxonomy_exists( 'singer' ) ) {
				return; // قبلاً ثبت شده است؛ دست نزن
			}
			if ( post_type_exists( 'singer' ) ) {
				return; // پست‌تایپ هم‌نام وجود دارد؛ ثبت تاکسونومی هم‌نام باعث تداخل rewrite و ۴۰۴ می‌شود
			}
			register_taxonomy(
				'singer',
				array( 'post' ),
				array(
					'labels'             => array(
						'name'          => 'خواننده‌ها',
						'singular_name' => 'خواننده',
					),
					'public'             => true,
					'publicly_queryable' => true,
					'hierarchical'       => false,
					'show_ui'            => true,
					'show_admin_column'  => true,
					'show_in_rest'       => true,
					'rewrite'            => array(
						'slug'       => 'singer',
						'with_front' => false,
					),
				)
			);
		},
		99
	);

	/* بازسازی یک‌بارهٔ قوانین پیوند یکتا (soft، بدون دست‌زدن به .htaccess) */
	add_action(
		'admin_init',
		function () {
			if ( ! taxonomy_exists( 'singer' ) ) {
				return;
			}
			$version = '2.0.0';
			if ( get_option( 'javanseda_singer_rewrite_version' ) === $version ) {
				return;
			}
			flush_rewrite_rules( false );
			update_option( 'javanseda_singer_rewrite_version', $version );
		}
	);
}

/* ----------------------------------------------------------------------------------
   گزارش تشخیصی — خواندنی، فقط برای مدیر
---------------------------------------------------------------------------------- */
add_filter(
	'template_include',
	function ( $template ) {
		if ( empty( $_GET['ab_singer_diag'] ) || ! current_user_can( 'manage_options' ) ) {
			return $template;
		}

		global $wp;

		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );

		$lines   = array();
		$lines[] = '=== گزارش تشخیص «صفحهٔ خواننده» — ترانه‌یار / جوان‌صدا ===';
		$lines[] = 'WordPress: ' . get_bloginfo( 'version' ) . ' | PHP: ' . PHP_VERSION;
		$lines[] = 'permalink_structure: ' . (string) get_option( 'permalink_structure' );
		$lines[] = 'JAVANSEDA_SINGER_FIX: ' . ( JAVANSEDA_SINGER_FIX ? 'ON' : 'OFF (پیش‌فرض)' );

		$lines[] = '';
		$lines[] = '--- نوع ثبت «singer» ---';
		$lines[] = 'taxonomy_exists(singer): ' . ( taxonomy_exists( 'singer' ) ? 'YES' : 'NO' );
		$lines[] = 'post_type_exists(singer): ' . ( post_type_exists( 'singer' ) ? 'YES  <-- صفحهٔ خواننده در این سایت «پست‌تایپ» است، نه آرشیو تاکسونومی' : 'NO' );
		$lines[] = 'taxonomy_exists(post_tag): ' . ( taxonomy_exists( 'post_tag' ) ? 'YES' : 'NO' );
		if ( taxonomy_exists( 'singer' ) ) {
			$object  = get_taxonomy( 'singer' );
			$lines[] = 'public: ' . ( $object->public ? 'yes' : 'NO' ) . ' | publicly_queryable: ' . ( $object->publicly_queryable ? 'yes' : 'NO' );
			$lines[] = 'hierarchical: ' . ( $object->hierarchical ? 'yes' : 'no' ) . ' | show_in_rest: ' . ( $object->show_in_rest ? 'yes' : 'no' );
			$lines[] = 'object_type: ' . wp_json_encode( $object->object_type );
			$lines[] = 'rewrite: ' . wp_json_encode( $object->rewrite, JSON_UNESCAPED_UNICODE );
		}

		$lines[] = '';
		$lines[] = '--- این درخواست (همان آدرسی که باز کرده‌اید) ---';
		$lines[] = 'request_uri: ' . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		$lines[] = 'matched_rule: ' . ( isset( $wp->matched_rule ) && '' !== $wp->matched_rule ? $wp->matched_rule : '(هیچ قانونی مطابقت نکرد)' );
		$lines[] = 'matched_query: ' . ( isset( $wp->matched_query ) && '' !== $wp->matched_query ? $wp->matched_query : '(خالی)' );
		$lines[] = 'is_404: ' . ( is_404() ? 'YES  <-- همین باعث «بالا نیامدن» صفحه است' : 'no' );
		$lines[] = 'is_tax(singer): ' . ( is_tax( 'singer' ) ? 'YES (آرشیو تاکسونومی — حالت درست)' : 'no' );
		$lines[] = 'is_singular: ' . ( is_singular() ? 'YES (' . get_post_type() . ')' : 'no' );
		$lines[] = 'is_post_type_archive(singer): ' . ( is_post_type_archive( 'singer' ) ? 'YES' : 'no' );
		$lines[] = 'is_archive: ' . ( is_archive() ? 'yes' : 'no' ) . ' | is_home: ' . ( is_home() ? 'yes' : 'no' );

		$queried = get_queried_object();
		if ( $queried instanceof WP_Term ) {
			$lines[] = 'queried_object: WP_Term → ' . $queried->taxonomy . ' / ' . $queried->slug . ' (ID ' . $queried->term_id . ')';
		} elseif ( $queried instanceof WP_Post ) {
			$lines[] = 'queried_object: WP_Post → ' . $queried->post_type . ' / ' . $queried->post_name . ' (ID ' . $queried->ID . ')';
		} elseif ( $queried instanceof WP_Post_Type ) {
			$lines[] = 'queried_object: WP_Post_Type → ' . $queried->name;
		} elseif ( $queried instanceof WP_User ) {
			$lines[] = 'queried_object: WP_User → ' . $queried->user_login;
		} else {
			$lines[] = 'queried_object: ' . ( is_object( $queried ) ? get_class( $queried ) : gettype( $queried ) );
		}

		$lines[] = 'template (انتخاب‌شده): ' . (string) $template;

		$lines[] = '';
		$lines[] = '--- قوانین پیوند یکتا که «singer» دارند ---';
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) ) {
			$lines[] = '(قوانین ذخیره نشده‌اند — تنظیمات ← پیوندهای یکتا ← ذخیره)';
		} else {
			$found = array();
			foreach ( array_keys( $rules ) as $pattern ) {
				if ( false !== strpos( $pattern, 'singer' ) ) {
					$found[] = $pattern . '  →  ' . $rules[ $pattern ];
				}
			}
			$lines[] = $found ? implode( "\n", array_slice( $found, 0, 8 ) ) : '(هیچ قانونی برای singer ثبت نشده است)';
		}

		$total  = wp_count_terms( array( 'taxonomy' => 'singer', 'hide_empty' => false ) );
		$lines[] = '';
		$lines[] = '--- ترم‌های singer ---';
		$lines[] = 'تعداد کل: ' . ( is_wp_error( $total ) ? 'WP_Error: ' . $total->get_error_message() : $total );

		$sample = get_terms( array( 'taxonomy' => 'singer', 'hide_empty' => false, 'number' => 5 ) );
		if ( is_wp_error( $sample ) ) {
			$lines[] = 'get_terms خطا: ' . $sample->get_error_message();
		} elseif ( is_array( $sample ) ) {
			foreach ( $sample as $row ) {
				$link    = get_term_link( $row );
				$lines[] = '   - ' . $row->name . ' | slug: ' . $row->slug . ' | count: ' . $row->count . ' | ' . ( is_wp_error( $link ) ? 'لینک خطا: ' . $link->get_error_message() : $link );
			}
		}

		$needle = isset( $_GET['ab_singer_term'] ) ? sanitize_text_field( wp_unslash( $_GET['ab_singer_term'] ) ) : '';
		if ( '' !== $needle ) {
			$lines[] = '';
			$lines[] = '--- بررسی ترم: ' . $needle . ' ---';
			$term    = get_term_by( 'slug', sanitize_title( $needle ), 'singer' );
			if ( ! $term ) {
				$term = get_term_by( 'name', $needle, 'singer' );
			}
			if ( ! $term || is_wp_error( $term ) ) {
				$lines[] = 'در تاکسونومی singer ترمی با این نام/اسلاگ پیدا نشد.';
				foreach ( array( 'post_tag', 'category' ) as $fallback_tax ) {
					$alt = get_term_by( 'name', $needle, $fallback_tax );
					if ( $alt && ! is_wp_error( $alt ) ) {
						$lines[] = 'اما در «' . $fallback_tax . '» هست: ' . $alt->name . ' (' . $alt->count . ' نوشته) → ' . get_term_link( $alt );
					}
				}
				$cpt = get_page_by_path( sanitize_title( $needle ), OBJECT, 'singer' );
				if ( $cpt ) {
					$lines[] = 'و یک نوشته از پست‌تایپ singer با همین اسلاگ وجود دارد: ' . get_permalink( $cpt );
				}
			} else {
				$link    = get_term_link( $term );
				$lines[] = 'term_id: ' . $term->term_id . ' | name: ' . $term->name . ' | slug: ' . $term->slug . ' | count: ' . $term->count;
				$lines[] = 'term_link: ' . ( is_wp_error( $link ) ? 'WP_Error: ' . $link->get_error_message() : $link );

				$histogram = array();
				$posts     = get_posts(
					array(
						'post_type'      => 'post',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'no_found_rows'  => true,
						'tax_query'      => array(
							array(
								'taxonomy' => 'singer',
								'field'    => 'term_id',
								'terms'    => $term->term_id,
							),
						),
					)
				);
				$lines[] = 'تعداد نوشته‌های این ترم: ' . count( $posts );
				foreach ( $posts as $post_id ) {
					$raw = get_post_meta( $post_id, 'musics_type', true );
					$key = is_scalar( $raw ) ? (string) $raw : '';
					if ( '' === $key ) {
						$key = '(خالی)';
					}
					$histogram[ $key ] = isset( $histogram[ $key ] ) ? $histogram[ $key ] + 1 : 1;
				}
				$lines[] = 'پرتکرارترین مقدارهای musics_type: ' . ( $histogram ? wp_json_encode( $histogram, JSON_UNESCAPED_UNICODE ) : 'هیچ' );
			}
		}

		$lines[] = '';
		$lines[] = 'پایان گزارش.';
		echo esc_html( implode( "\\n", $lines ) );
		exit;
	},
	PHP_INT_MAX
);
/* ------------------------------------------------------------------------- v3.3.8 */
/* شمارندۀ پلیرها. JS قالب (js/scripts.js:88,135) با getElementById('audio') فقط اولین پلیر را
   bind می‌کند؛ اگر در یک نوشته هم «تک‌آهنگ» و هم «آلبوم» باشد، id تکراری و کنترل بی‌حرکت
   می‌ساخت. اولی همان `audio` می‌ماند (پس JS دست‌نخورده کار می‌کند) و بعدی‌ها `audio-2`،
   `audio-3` … می‌شوند + خصیصۀ data-ab-player که در CSS کنترل‌های مردهٔ پلیر دوم را پنهان می‌کند. */
if ( ! function_exists( 'javan_seda_player_uid' ) ) {
	function javan_seda_player_uid() {
		static $n = 0;
		$n++;
		return $n;
	}
}
