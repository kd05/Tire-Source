<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Upload Images' );

cw_get_header();
Admin_Sidebar::html_before();

$form_action = gp_if_set( $_POST, 'form_action' );

$image_url = gp_if_set( $_POST, 'image_url' );
$image_file = gp_if_set( $_FILES, 'image_file' );
$file_name = get_user_input_singular_value( $_POST, 'file_name' );

$override = (bool) gp_if_set( $_POST, 'override' );

// careful, an empty array is provided when form is submitted without a file chosen
$image_file_provided = (bool) gp_if_set( $image_file, 'name' );

$post_back_response = [];

// handle post back
if ( $form_action === 'image_upload' ) {

	$post_back_response[] = 'Form submitted...';

	while (true) {

		if ( ! validate_nonce_value( 'image_upload', gp_if_set( $_POST, 'nonce' ), true ) ) {
			$post_back_response[] = 'Nonce error.';
			break;
		}

		if ( $image_file_provided && $image_url ) {
			$post_back_response[] = 'Use a URL or a file, but not both.';
			break;
		}

		// Upload from URL
		if ( $image_url ) {

			$post_back_response[] = 'Upload via URL...';

			if ( $override ) {
				$post_back_response[] = 'Will attempt to override existing file.';
            }

			try {
			    // user provided file name or default to getting the name from the url provided
				$file_name = $file_name ? $file_name : get_path_info( $image_url, 'filename' );
				$upload_url_url = localize_image( $image_url, $file_name, $override );
				$post_back_response[] = 'Success : <a target="_blank" href="' . $upload_url_url . '">' . $upload_url_url . '</a>';
			} catch( Exception $e ) {
				$post_back_response[] = $e->getMessage();
			}

		} else if ( $image_file_provided ) {

			$post_back_response[] = 'Upload via file...';

			if ( $override ) {
				$post_back_response[] = 'Will attempt to override existing file.';
			}

		    // Upload from file

			try{

				$file = gp_if_set( $_FILES, 'image_file' );
				$upload_file_url = upload_image_from_file_with_admin_user( $file, $file_name, $override );

				if ( $upload_file_url ) {
					$post_back_response[] = 'Success: <a target="_blank" href="' . $upload_file_url . '">' . $upload_file_url . '</a>';
				} else {
				    $post_back_response[] = 'File not uploaded.';
                }

			} catch( Exception $e ) {
				$post_back_response[] = get_pre_print_r( $e->getMessage() );
			}
		} else {
			$post_back_response[] = 'Image URL or file is required.';
			break;
        }

		break;
	} // while true
}

// wrap <p> around array elements, or around a string
$post_back_response = gp_array_to_paragraphs( $post_back_response );


?>
    <div class="admin-section general-content">
        <p><a href="<?php echo get_admin_page_url( 'images' ); ?>">Uploaded Images</a></p>
    </div>
    <div class="admin-section general-content">

        <h2>Upload an Image</h2>
        <form class="form-style-basic" method="post" id="" enctype="multipart/form-data">

            <input type="hidden" name="form_action" value="image_upload">

			<?php

            echo get_nonce_input( 'image_upload', true );

			if ( $post_back_response ) {
				echo get_form_response_text( $post_back_response );
			}

			?>

            <div class="form-items">
				<?php

				echo get_form_input( array(
					'name' => 'image_url',
					'value' => get_user_input_singular_value( $_POST, 'image_url' ),
					'label' => 'Image URL',
				));

				echo get_form_input( array(
					'name' => 'image_file',
					// 'value' => get_user_input_singular_value( $_POST, 'image_file' ),
					'label' => 'OR: Upload File',
					'type' => 'file',
				));

				echo get_form_input( array(
					'name' => 'file_name',
					'value' => get_user_input_singular_value( $_POST, 'file_name' ),
					'label' => 'Image Name (optional) (.jpg or .png is ignored if provided)',
				));

				echo get_form_checkbox( array(
					'name' => 'override',
					'checked' => (int) gp_if_set( $_POST, 'override' ) === 1,
					'value' => 1,
					'label' => 'Override target file if it already exists (recommended to not use this by default).',
				));

				echo get_form_submit();

				?>
            </div>
        </form>
    </div>

<?php
Admin_Sidebar::html_after();
cw_get_footer();