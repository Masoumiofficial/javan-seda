<?php if(function_exists('yoast_breadcrumb')){ yoast_breadcrumb('<div id="breadcrumbs_container">
<div class="tarlanweb_center"><p id="breadcrumbs">','</p><div class="clear"></div></div></div>'); } ?>
<?php if(function_exists('rank_math_the_breadcrumbs')){ ?>
<div id="breadcrumbs_container"><div class="tarlanweb_center">
<?php rank_math_the_breadcrumbs(); ?>
<div class="clear"></div></div></div>
<?php } ?>