<?php $arti_of = ot_get_option('arti_off'); if('off' != $arti_of){ ?>
<div class="song_boxs">
<div class="songs_title_box">
<h3><?php echo esc_html( ot_get_option('arti_title') ); ?></h3>
<?php if(ot_get_option('arti_url') !=""){ ?>
<a href="<?php echo esc_url( ot_get_option('arti_url') ); ?>">مشاهده بیشتر</a><?php } ?>
<div class="clear"></div><div class="liner"></div>
</div>
<div class="artist_boxs">
<div class="artists_slider owl-carousel owl-theme">
<?php $artista = ot_get_option('siing_t',array()); foreach($artista as $artise){ ?>
<a rel="noreferrer noopener" href="<?php echo esc_url( $artise['siing_u'] ); ?>"><img src="<?php echo esc_url( $artise['siing_img'] ); ?>" width="150" height="150" alt="<?php echo esc_attr( $artise['title'] ); ?>"><span><?php echo $artise['title']; ?></span></a>
<?php } ?>
</div>
<div class="clear"></div>
</div>
</div>
<?php } ?>