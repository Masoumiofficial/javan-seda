<?php 
function rkianosh_load_widget(){ register_widget( 'tarlanweb_ir_newp' ); }
add_action( 'widgets_init', 'rkianosh_load_widget' ); 
class tarlanweb_ir_newp extends WP_Widget {
function __construct() {
parent::__construct(
'tarlanweb_ir_newp', '(اختصاصی) جدیدترین مطالب', 
array('description' => 'ابزارک نمایش جدیدترین مطالب با تصویر شاخص') 
);
}
// Creating widget front-end
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title']);
$post_numbers = apply_filters( 'widget_numbers', $instance['post_numbers'] );
$targets = $instance[ 'targets' ] ? 'true' : 'false';
// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];
?>
<?php global $wp_query; 
$arms = array(
'post_type' => 'post',
'posts_per_page' => $post_numbers,'ignore_sticky_posts' => 1,
'orderby' => 'id',
'order' => 'DESC',
'post_status' => 'publish',
);
$wp_query = new WP_Query($arms); if($wp_query->have_posts()) : ?>
<ul>
<?php while ($wp_query->have_posts()) : $wp_query->the_post(); $post_id = $wp_query->post->ID; ?>
<li><a href="<?php the_permalink(); ?>">
<div class="tara-thumb-box"><?php if($current_post_id === $post->ID){ ?><i class="inactive_box_cir"></i><?php } ?>
<?php if(has_post_thumbnail()){
the_post_thumbnail('thumb3',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().'')); } else { ?>
<img src="<?php bloginfo('template_url');?>/images/nomusics.jpg" width="60" height="60" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } ?></div>
<?php $artist=get_post_meta(get_the_ID(),'artist',true); $track=get_post_meta(get_the_ID(),'song',true);
if(isset($artist) && !empty($artist)){?> <span class="song_artists"> <?php echo $artist; ?> </span> <span> <?php echo $track; ?> </span> <?php } else{ the_title(''); } ?>	
</a></li>
<?php endwhile; ?>
</ul>
<?php endif; wp_reset_postdata(); ?>
<?php
echo $args['after_widget'];
}
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'جدیدترین موسیقی ها', 'tarlanweb_ir_newp_domain' );
}
if ( isset( $instance[ 'post_numbers' ] ) ) {
$post_numbers = $instance[ 'post_numbers' ];
}
else {
$post_numbers = __( '7', 'tarlanweb_ir_newp_domain' );
}
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>">عنوان: </label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'post_numbers' ); ?>">تعداد مطالب قابل نمایش:</label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'post_numbers' ); ?>" name="<?php echo $this->get_field_name( 'post_numbers' ); ?>" type="text" value="<?php echo esc_attr( $post_numbers ); ?>" />
</p>
<p>
<input class="checkbox" type="checkbox" <?php checked( $instance[ 'targets' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'targets' ); ?>" name="<?php echo $this->get_field_name( 'targets' ); ?>"/> 
<label for="<?php echo $this->get_field_id( 'targets' ); ?>">باز شدن لینک در تب جدید</label>
</p>
<?php 
}
public function update($new_instance,$old_instance){
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
$instance['post_numbers'] = ( ! empty( $new_instance['post_numbers'] ) ) ? strip_tags( $new_instance['post_numbers'] ) : '';
$instance[ 'targets' ] = $new_instance[ 'targets' ];
return $instance;
}
}
// end ne post by thumbnail
function tarlanweb_load_widget(){register_widget('tarlanweb_ir_popular');}
add_action( 'widgets_init', 'tarlanweb_load_widget'); 
class tarlanweb_ir_popular extends WP_Widget {
function __construct(){
parent::__construct(
'tarlanweb_ir_popular','(اختصاصی) موسیقی محبوب (بازدید)', 
array('description' => 'ابزارک نمایش موسیقی های محبوب بر اساس بازدید') 
);
}
// Creating widget front-end
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title']);
$post_numbers = apply_filters( 'widget_numbers', $instance['post_numbers'] );
$targets = $instance[ 'targets' ] ? 'true' : 'false';
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];
?>
<ul>
<?php global $wp_query;
$arms = array(
'post_type' => 'post',
'posts_per_page' => $post_numbers,'ignore_sticky_posts' => 1,
'offset' => 0,
'meta_key'  => 'views',
'orderby' => 'meta_value_num',
'order' => 'DESC',
'post_status' => 'publish',
'date_query' => array(
array(
'column' => 'post_date_gmt',
'after' => '11 month ago',
)));
$wp_query = new WP_Query($arms);
if ($wp_query->have_posts()) : while ($wp_query->have_posts()) : $wp_query->the_post(); $post_id = $wp_query->post->ID; ?>
<li><a href="<?php the_permalink(); ?>">
<div class="tara-thumb-box"><?php if($current_post_id === $post->ID){ ?><i class="inactive_box_cir"></i><?php } ?>
<?php if(has_post_thumbnail()){
the_post_thumbnail('thumb3',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().'')); } else { ?>
<img src="<?php bloginfo('template_url');?>/images/nomusics.jpg" width="60" height="60" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } ?></div>
<?php $artist=get_post_meta(get_the_ID(),'artist',true); $track=get_post_meta(get_the_ID(),'song',true);
if(isset($artist) && !empty($artist)){?> <span class="song_artists"> <?php echo $artist; ?> </span> <span> <?php echo $track; ?> </span> <?php } else{ the_title(''); } ?>	
</a></li>
<?php endwhile; wp_reset_postdata(); else : ?>
<li><?php _e( 'چیزی برای نمایش وجود ندارد' ); ?></li>
<?php endif; wp_reset_postdata(); ?>
</ul>
<?php
echo $args['after_widget'];
}
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'پربازدیدترین موسیقی ها', 'tarlanweb_ir_popular_domain' );
}
if ( isset( $instance[ 'post_numbers' ] ) ) {
$post_numbers = $instance[ 'post_numbers' ];
}
else {
$post_numbers = __( '7', 'tarlanweb_ir_popular_domain' );
}
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>">عنوان :</label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'post_numbers' ); ?>">تعداد مطالب قابل نمایش:</label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'post_numbers' ); ?>" name="<?php echo $this->get_field_name( 'post_numbers' ); ?>" type="text" value="<?php echo esc_attr( $post_numbers ); ?>" />
</p>
<p>
<input class="checkbox" type="checkbox" <?php checked( $instance[ 'targets' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'targets' ); ?>" name="<?php echo $this->get_field_name( 'targets' ); ?>"/> 
<label for="<?php echo $this->get_field_id( 'targets' ); ?>">باز شدن لینک در تب جدید</label>
</p>
<?php 
}
public function update($new_instance,$old_instance){
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
$instance['post_numbers'] = ( ! empty( $new_instance['post_numbers'] ) ) ? strip_tags( $new_instance['post_numbers'] ) : '';
$instance[ 'targets' ] = $new_instance[ 'targets' ];
return $instance;
}
}
// end tabs1
function rknos_lod_wigt(){ register_widget( 'tarlanweb_ir_rand' ); }
add_action( 'widgets_init', 'rknos_lod_wigt' ); 
class tarlanweb_ir_rand extends WP_Widget {
function __construct() {
parent::__construct(
'tarlanweb_ir_rand', '(اختصاصی) مطالب تصادفی', 
array('description' => 'ابزارک نمایش مطالب تصادفی با تصویر شاخص') 
);
}
// Creating widget front-end
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title']);
$post_numbers = apply_filters( 'widget_numbers', $instance['post_numbers'] );
$targets = $instance[ 'targets' ] ? 'true' : 'false';
// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];
?>
<?php global $wp_query; 
$arms = array(
'post_type' => 'post',
'posts_per_page' => $post_numbers,'ignore_sticky_posts' => 1,
'orderby' => 'rand',
'order' => 'DESC',
'post_status' => 'publish',
);
$wp_query = new WP_Query($arms); if($wp_query->have_posts()) : ?>
<ul>
<?php while ($wp_query->have_posts()) : $wp_query->the_post(); $post_id = $wp_query->post->ID; ?>
<li><a href="<?php the_permalink(); ?>">
<div class="tara-thumb-box"><?php if($current_post_id === $post->ID){ ?><i class="inactive_box_cir"></i><?php } ?>
<?php if(has_post_thumbnail()){
the_post_thumbnail('thumb3',array('alt'=>''.get_the_title(),'title'=>''.get_the_title().'')); } else { ?>
<img src="<?php bloginfo('template_url');?>/images/nomusics.jpg" width="60" height="60" alt="تصویر پیدا نشد" title="تصویر پیدا نشد"><?php } ?></div>
<?php $artist=get_post_meta(get_the_ID(),'artist',true); $track=get_post_meta(get_the_ID(),'song',true);
if(isset($artist) && !empty($artist)){?> <span class="song_artists"> <?php echo $artist; ?> </span> <span> <?php echo $track; ?> </span> <?php } else{ the_title(''); } ?>	
</a></li>
<?php endwhile; ?>
</ul>
<?php endif; wp_reset_postdata(); ?>
<?php
echo $args['after_widget'];
}
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'مطالب تصادفی', 'tarlanweb_ir_rand_domain' );
}
if ( isset( $instance[ 'post_numbers' ] ) ) {
$post_numbers = $instance[ 'post_numbers' ];
}
else {
$post_numbers = __( '7', 'tarlanweb_ir_rand_domain' );
}
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>">عنوان: </label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'post_numbers' ); ?>">تعداد مطالب قابل نمایش:</label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'post_numbers' ); ?>" name="<?php echo $this->get_field_name( 'post_numbers' ); ?>" type="text" value="<?php echo esc_attr( $post_numbers ); ?>" />
</p>
<p>
<input class="checkbox" type="checkbox" <?php checked( $instance[ 'targets' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'targets' ); ?>" name="<?php echo $this->get_field_name( 'targets' ); ?>"/> 
<label for="<?php echo $this->get_field_id( 'targets' ); ?>">باز شدن لینک در تب جدید</label>
</p>
<?php 
}
public function update($new_instance,$old_instance){
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
$instance['post_numbers'] = ( ! empty( $new_instance['post_numbers'] ) ) ? strip_tags( $new_instance['post_numbers'] ) : '';
$instance[ 'targets' ] = $new_instance[ 'targets' ];
return $instance;
}
}
// end random post by thumbnail