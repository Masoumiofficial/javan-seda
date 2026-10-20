<?php get_header(''); ?>
<?php get_template_part('inc/breadcrumbs'); ?>
<?php $term = get_queried_object(); ?>

<div class="tarlanweb_center">
<div class="artist_box_tarlanweb">
<?php if(get_field('aimg2', $term) || (get_field('afacebook',$term)) || (get_field('atelegram',$term)) || (get_field('ainstagram',$term)) || (get_field('atwitter',$term))){ ?>
<div class="artists_imsf">
<?php $image = get_field('aimg2', $term); if ($image !=""){ ?> 
<img src="<?php echo $image; ?>" width="200" height="200" alt="<?php echo $term->name; ?>" title="<?php echo $term->name; ?>">
<?php } ?>
<div class="socials_box">
<span>صفحات مجازی خواننده</span>
<?php if(get_field('afacebook',$term)){ ?><a href="<?php echo get_field('afacebook', $term); ?>" class="icofont-facebook"></a><?php } if(get_field('atelegram',$term)){ ?><a href="<?php echo get_field('atelegram', $term); ?>" class="icofont-paper-plane"></a><?php } if(get_field('ainstagram',$term)){ ?><a href="<?php echo get_field('ainstagram', $term); ?>" class="icofont-instagram"></a><?php } if(get_field('atwitter',$term)){ ?><a href="<?php echo get_field('atwitter', $term); ?>" class="icofont-twitter"></a><?php } if(get_field('ayoutube',$term)){ ?><a href="<?php echo get_field('ayoutube', $term); ?>" class="icofont-youtube-play"></a><?php } ?>
<div class="clear"></div>
</div><div class="clear"></div>
</div>
<?php } ?>
<div class="artist_box_detss">
<h1 class="artist_name_head"><?php single_term_title(); ?></h1>	
<div class="clear"></div>
<?php if (! empty(the_archive_description(''))) { ?> 
<div class="artist_txt_box">
<?php the_archive_description(''); ?>
<div class="clear"></div>
</div>
<?php } ?>
</div>

<div class="clear"></div>
</div>
</div>

<div id="artist_box">

<!-- centers_box -->
<div class="tarlanweb_center">
<div id="centers_box">

<!-- song_boxs -->
<div class="song_boxs">
<?php if(have_posts()) : ?>
<div class="song_boxs_posts">
<ul>
<?php while(have_posts()) : the_post(); ?>
<li><?php get_template_part('inc/songs_post'); ?></li>
<?php endwhile; ?>
</ul>
<div class="clear"></div>
</div>
<?php else: get_template_part('inc/no-content'); endif; ?>
<div class="clear"></div>
</div>
<!-- song_boxs -->	
	
	
	
<?php get_template_part('inc/pagination'); ?>
<div class="clear"></div>
</div>
</div>
<!-- centers_box -->
</div>

<?php get_footer(''); ?>