<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Edit Home Page' );

$postback_response = '';
$form_submitted = gp_if_set( $_POST, 'form_submitted' );

if ( $form_submitted ) {

	cw_set_option( 'home_middle_banner', gp_strip_script_tags( gp_if_set( $_POST, 'home_middle_banner' )));
	cw_set_option( 'home_bottom_title', gp_strip_script_tags( gp_if_set( $_POST, 'home_bottom_title' )));
	cw_set_option( 'home_bottom_content', gp_strip_script_tags( gp_if_set( $_POST, 'home_bottom_content' )));
	cw_set_option( 'home_top_title', gp_strip_script_tags( gp_if_set( $_POST, 'home_top_title' )));
	cw_set_option( 'home_top_image', gp_strip_script_tags( gp_if_set( $_POST, 'home_top_image' )));
	cw_set_option( 'home_top_video', gp_strip_script_tags( gp_if_set( $_POST, 'home_top_video' )));

	$postback_response = 'Home page updated.';
}

cw_get_header();
Admin_Sidebar::html_before();

?>

    <div class="admin-section general-content">
        <h1>Home</h1>

        <form action="" method="post" class="form-style-basic">

            <input type="hidden" name="form_submitted" value="1">

            <?php
            if ( $postback_response ) {
                echo get_form_response_text( $postback_response );
            }
            ?>

            <div class="form-items">

                <?php

                echo get_form_input( array(
	                'label' => 'Home Top Title. ie. It\'s About Style & Performance. This text is large in size. Keep it close in length to the original text.',
	                'name' => 'home_top_title',
	                'value' => gp_test_input( cw_get_option( 'home_top_title' ) ),
                ));

                echo get_form_input( array(
	                'label' => 'Home Top Image (shows on mobile devices always, and on desktop devices when a video is not selected)',
	                'name' => 'home_top_image',
	                'value' => gp_test_input( cw_get_option( 'home_top_image' ) ),
                ));

                echo get_form_input( array(
	                'label' => 'Home Top Video. Recommended maximum of 15 megabytes. You will have to use FTP to add new videos. The default video is found here: ' . VIDEOS_URL . '/home.mp4',
	                'name' => 'home_top_video',
	                'value' => gp_test_input( cw_get_option( 'home_top_video' ) ),
                ));

                echo get_form_input( array(
	                'label' => 'Middle Image Banner (ie. "ruffino-wheels.jpg" or a full image url with http://.. )',
	                'name' => 'home_middle_banner',
	                'value' => cw_get_option( 'home_middle_banner' ),
                ));

                echo get_form_input( array(
                    'label' => 'Home Bottom Title (ie. What We Do)',
                    'name' => 'home_bottom_title',
	                'value' => cw_get_option( 'home_bottom_title' ),
                ));

                // html allowed
                echo get_form_textarea( array(
	                'label' => 'Home Bottom Content',
	                'name' => 'home_bottom_content',
	                'value' => cw_get_option( 'home_bottom_content' ),
                ));

                echo get_form_submit();

                ?>

            </div>

        </form>

    </div>

<?php
Admin_Sidebar::html_after();
cw_get_footer();