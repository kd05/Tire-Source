<?php

Header::$title = "Login";
Header::$meta_robots = 'noindex';
has_no_top_image();

cw_get_header();

?>
	<div class="page-wrap interior-page page-login">
		<div class="main-content">
			<div class="container general-content">
                <?php
                if ( cw_is_user_logged_in() ) {
                    echo '<div class="general-content">';
                    echo '<h2>You are already logged in.</h2>';
                    echo '<p>' . get_ajax_logout_anchor_tag() . '</p>';
                    echo '</div>';
                } else {
	                echo get_sign_in_form([
		                'redirect' => 'account',
	                ]);
                }
                ?>
			</div>
		</div>
	</div>
<?php

cw_get_footer();

