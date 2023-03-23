<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Insert A User' );

cw_get_header();
Admin_Sidebar::html_before();

?>

<div class="admin-section general-content">

    

	<?php echo get_sign_up_form(
		['title' => 'Insert User' ]
	); ?>

</div>


<?php

Admin_Sidebar::html_after();
cw_get_footer();