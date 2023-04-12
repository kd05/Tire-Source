<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Insert Coupon' );

cw_get_header();
Admin_Sidebar::html_before();

?>

<div class="admin-section general-content">


	<?php echo get_add_coupon_form(
		['title' => 'Add Coupon' ]
	); ?>

</div>


<?php

Admin_Sidebar::html_after();
cw_get_footer();