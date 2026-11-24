<?php $arti_of = function_exists( 'tara_opt_on' ) ? tara_opt_on( 'arti_off' ) : ( 'off' != ot_get_option( 'arti_off' ) ); if ( $arti_of ) { ?>
<div class="song_boxs">
<div class="songs_title_box">
<h3><?php echo esc_html( ot_get_option('arti_title') ); ?></h3>
<?php if(ot_get_option('arti_url') !=""){ ?>
<a href="<?php echo esc_url( ot_get_option('arti_url') ); ?>">مشاهده بیشتر</a><?php } ?>
<div class="clear"></div><div class="liner"></div>
</div>
<div class="artist_boxs">
<div class="artists_slider owl-carousel owl-theme">
<?php $artista = ot_get_option('siing_t',array()); foreach($artista as $artise){
	/* v3.5.3 — کارتِ یکدست با صفحهٔ اصلی: لایهٔ «مات» + مجموع بازدید روی عکس + نام زیرِ عکس.
	   اگر لایهٔ تازه روی سرور نبود، دقیقاً همان مارکاپِ قبلی چاپ می‌شود. */
	if ( function_exists( 'tara_artist_card' ) ) {
		echo tara_artist_card( $artise ); /* html امن؛ داخل تابع escape شده است */
	} else { ?>
<a rel="noreferrer noopener" href="<?php echo esc_url( $artise['siing_u'] ); ?>"><img src="<?php echo esc_url( $artise['siing_img'] ); ?>" width="150" height="150" alt="<?php echo esc_attr( $artise['title'] ); ?>"><span><?php echo esc_html( $artise['title'] ); ?></span></a>
<?php }
} ?>
</div>
<div class="clear"></div>
</div>
</div>
<?php } ?>