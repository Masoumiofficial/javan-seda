<?php /* v3.4.9 — ردیفِ «محبوبترین هنرمندانِ صفحۀ اصلی»
   ---------------------------------------------------
   دو حالت، هر دو با همۀ تنظیم‌های قبلی (arti_off / arti_title / arti_url / siing_t):

   ۱) دستی — همان ردیف‌های «تنظیمات پوسته ← صفحۀ اصلی ← هنرمندان». از این نسخه هر
      ردیف می‌تواند فقط «نام هنرمند» را داشته باشد (siing_n) و لینکش را خودش از
      ترم می‌گیرد؛ اگر cogsِ ردیف خالی باشد، عکسِ همان هنرمند به کار می‌رود.
      ⇒ دیگر لازم نیست یک عکس دو بار آپلود شود.
   ۲) خودکار — «ردیف خانه از خودِ هنرمندان» روشن باشد: ترتیب با
      «پربازدیدترین / پرکارترین / تازه‌ترین / الفبایی»، به‌علاوهٔ «ثابت در بالا»
      (تیکِ «ثابت در ردیف خانه» در صفحۀ ویرایش هنرمند، یا لیستِ اسامی در تنظیمات).

   اگر لایۀ «هنرمند» (inc/ab-tax-artist.php) روی هاست نباشد، دقیقاً همان حلقۀ
   دستیِ قبل اجرا می‌شود؛ این فایل تنها نمی‌شکند.
*/
$arti_of = ot_get_option('arti_off'); if('off' != $arti_of){
	$ab_art_rows = function_exists( 'javan_seda_artist_home_rows' ) ? (array) javan_seda_artist_home_rows() : array();
	if ( empty( $ab_art_rows ) ) {
		$ab_art_rows = array();
		foreach ( (array) ot_get_option( 'siing_t', array() ) as $ab_art_r ) {
			if ( ! is_array( $ab_art_r ) || empty( $ab_art_r['siing_img'] ) ) {
				continue;
			}
			$ab_art_rows[] = array(
				'name'  => ! empty( $ab_art_r['siing_n'] ) ? $ab_art_r['siing_n'] : '',
				'url'   => ! empty( $ab_art_r['siing_u'] ) ? $ab_art_r['siing_u'] : '',
				'img'   => (string) $ab_art_r['siing_img'],
				'views' => 0,
			);
		}
	}
	if ( $ab_art_rows ) {
?>
<div class="song_boxs">
<div class="songs_title_box">
<h3><?php echo esc_html( ot_get_option('arti_title') ); ?></h3>
<?php if(ot_get_option('arti_url') !=""){ ?>
<a href="<?php echo esc_url( ot_get_option( 'arti_url' ) ); ?>">مشاهده بیشتر</a><?php } ?>
<div class="clear"></div><div class="liner"></div>
</div>
<div class="artist_boxs">
<div class="artists_slider owl-carousel owl-theme">
<?php foreach ( $ab_art_rows as $ab_art ) :
	$ab_art_name = isset( $ab_art['name'] ) ? (string) $ab_art['name'] : '';
	$ab_art_url  = ! empty( $ab_art['url'] ) ? (string) $ab_art['url'] : '';
	$ab_art_img  = ! empty( $ab_art['img'] ) ? (string) $ab_art['img'] : '';
	if ( '' === $ab_art_img && '' === $ab_art_url ) {
		continue;
	}
	if ( '' === $ab_art_url ) {
		$ab_art_url = function_exists( 'home_url' ) ? home_url( '/' ) : '';
	}
	?>
<a rel="noreferrer noopener" href="<?php echo esc_url( $ab_art_url ); ?>" title="<?php echo esc_attr( $ab_art_name ); ?>"<?php echo ( ! empty( $ab_art['views'] ) ) ? ' data-ab-views="' . esc_attr( (string) (int) $ab_art['views'] ) . '"' : ''; ?>><img src="<?php echo esc_url( $ab_art_img ); ?>" width="150" height="150" alt="<?php echo esc_attr( $ab_art_name ); ?>" title="<?php echo esc_attr( $ab_art_name ); ?>" loading="lazy" decoding="async"></a>
<?php endforeach; ?>
</div>
<div class="clear"></div>
</div>
</div>
<?php
	}
}
