<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( have_posts() ) {
	the_post();
}

$post = get_post();

get_header();

?>
	<div class="page-wrap interior-page page-single">
		<?php echo get_top_image( array(
			'title' => get_the_title(),
			'img' => gp_get_img_url( get_post_thumbnail_id(), 'full' ),
            'overlay_opacity' => 99,
		) ); ?>
		<?php echo Components::grey_bar();?>
		<div class="main-content">
			<div class="container general-content">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		//centering H1 - Richard Agama
		document.getElementsByClassName('y-mid')[0].childNodes[0].childNodes[0].style.textAlign = 'center';
		document.getElementsByClassName('y-mid')[0].childNodes[0].childNodes[0].style.maxWidth = 'none';
	</script>
<?php

get_footer();