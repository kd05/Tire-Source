<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Edit FAQ' );

$postback_response = '';
$post_action = gp_if_set( $_POST, 'post_action' );
$faq_content = gp_if_set( $_POST, 'faq_content' );

if ( $post_action === 'set_faq_content' ) {

	$updated = cw_set_option( 'faq_content', $faq_content );

	if ( $updated ) {
		$postback_response = 'Updated.';
	} else {
		$postback_response = 'No update made.';
	}
}

cw_get_header();
Admin_Sidebar::html_before();

?>

	<div class="admin-section general-content">
		<h1>Frequently Asked Questions</h1>
        <p>See the [shortcode] instructions on the "edit gallery" page. Use shortcodes here: [faq][/faq], [q][/q], and [a][/a]</p>
		<form class="form-style-basic" method="post" id="gallery-edit">

			<input type="hidden" name="post_action" value="set_faq_content">

			<div class="form-items">

				<?php

				if ( $postback_response ) {
					echo get_form_response_text( $postback_response );
				}
				?>

				<?php
				// might include html, which in the case of script i think won't execute in a textarea
				$option = DB_Option::get_instance_via_option_key( 'faq_content' );
				?>

				<?php echo get_form_textarea( array(
					'add_class' => 'size-lg',
					'label' => 'FAQ Content',
					'name' => 'faq_content',
					'value' => $option ? $option->get( 'option_value' ) : '',
				));
				?>

				<?php echo get_form_submit(); ?>
			</div>
		</form>


	</div>

<?php
Admin_Sidebar::html_after();
cw_get_footer();