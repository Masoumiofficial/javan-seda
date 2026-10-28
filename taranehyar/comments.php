<div id="comments">

<?php if ( post_password_required() ) : ?>

<p class="nopassword"> این مطلب محافظت شده است. برای نمایش نظرات رمز عبور را وارد نمائید.</p>

</div>

<?php return; endif; ?>

<?php
/* v3.4.6 — دو دروازۀ اضافی که فهرست دیدگاه‌ها را می‌بستند باز شد:
   1) اگر «کاربر باید ثبت‌نام/وارد شود» روشن باشد، این فایل وسط کار return می‌کرد و
      نظراتِ تاییدشدۀ همان نوشته هم چاپ نمی‌شد. حالا همان پیام فقط جای «فرم» را می‌گیرد.
   2) have_comments() به کوئری اصلی وابسته است؛ اگر آن کوئری خالی بوده باشد ولی
      نوشته کامنت داشته باشد، فهرست بی‌محتوا می‌شد ⇒ یک کوئری پشتیبان با همان
      wp_list_comments() و همان callback قالب اجرا می‌شود (DOM یکسان، CSS تازه لازم نیست).
   هیچ چیزی از فرستادن دیدگاه/بازدید تغییر نکرده است. */
$jv_comments_id = get_queried_object_id();
$jv_have        = have_comments();
$jv_fallback    = array();
if ( ! $jv_have && $jv_comments_id && (int) get_comments_number() > 0 ) {
	$jv_args = array(
		'post_id' => $jv_comments_id,
		'status'  => 'approve',
		'order'   => 'ASC',
	);
	if ( get_option( 'page_comments' ) ) {
		$jv_per = (int) get_option( 'comments_per_page' );
		if ( $jv_per > 0 ) {
			/* v3.5.4 — پارامترِ `page` در WP_Comment_Query وجود ندارد؛ با `offset`
			   دقیقاً همان بازهٔ صفحۀ جاری خوانده می‌شود (هم‌راستا با مسیرِ اصلی). */
			$jv_page           = max( 1, (int) get_query_var( 'cpage' ) );
			$jv_args['number'] = $jv_per;
			$jv_args['offset'] = ( $jv_page - 1 ) * $jv_per;
		}
	}
	$jv_fallback = get_comments( $jv_args );
}
$jv_need_login = ( get_option( 'comment_registration' ) && ! is_user_logged_in() );
?>

<?php if ( $jv_need_login ) : ?>

<p class="nopassword">لطفاً براي ارسال دیدگاه، ابتدا وارد حساب كاربري خود بشويد</p>

<?php elseif ( function_exists( 'comment_form_cd' ) ) : comment_form_cd(); ?>

<?php endif; ?>

<?php if ( $jv_have || $jv_fallback ) : ?>

<?php
/* v3.5.4 — سرتیتر + ناوبریِ صفحه‌بندی (بالا و پایینِ فهرست).
   اگر لایهٔ تازه روی سرور نبود، همان ناوبریِ سادهٔ قبلی چاپ می‌شود (سازگاری کامل). */
$jv_pager = function_exists( 'javan_seda_comments_pager' );
if ( $jv_pager ) {
	echo javan_seda_comments_heading(); /* html امن؛ داخل تابع escape شده است */
	echo javan_seda_comments_pager( array( 'position' => 'top' ) );
} elseif ( $jv_have && get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) {
	?>
<nav id="comment-nav-above">

<div class="nav-previous"><?php previous_comments_link( 'دیدگاه‌های قبلی' ); ?></div>

<div class="nav-next"><?php next_comments_link( 'دیدگاه‌های جدید' ); ?></div>

</nav>
	<?php
}
?>

<ol class="commentlist"><?php if ( $jv_have ) { wp_list_comments( array( 'callback' => 'comment_loop_cd' ) ); } else { wp_list_comments( array( 'callback' => 'comment_loop_cd' ), $jv_fallback ); } ?></ol>

<?php if ( $jv_pager ) { echo javan_seda_comments_pager( array( 'position' => 'bottom' ) ); } ?>

<?php elseif ( ! comments_open() && ! is_page() && post_type_supports( get_post_type(), 'comments' ) ) : ?>

<p class="nocomments">مجوز ارسال دیدگاه داده نشده است!</p>

<?php endif; ?>

<div class="clear"></div>

</div>
