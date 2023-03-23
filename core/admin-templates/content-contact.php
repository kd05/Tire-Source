<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

print_simple_admin_page_to_edit_option_textarea( 'Edit Contact Us', 'content_contact' );

