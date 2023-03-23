<?php

Header::$title = "404 Not Found";

$msg = gp_get_global( '404_msg', 'It looks like the page you were looking for does not exist.' );

cw_get_header();

?>
	<div class="page-wrap page-404">
		<?php echo get_top_image( array(
			'title' => gp_get_global( '404_title', '404 not found' ),
			'img' => get_image_src( 'iStock-513017767-wide-lg.jpg' ),
            'overlay_opacity' => 77,
		)); ?>
		<?php echo Components::grey_bar(); ?>
		<div class="main-content">
			<div class="container">
				<div class="general-content">
					<h2><?= $msg; ?></h2>
				</div>
			</div>
		</div>
	</div>
<?php

cw_get_footer();