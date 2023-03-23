<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Edit Gallery' );

$postback_response = '';
$post_action = gp_if_set( $_POST, 'post_action' );
$gallery_content = gp_if_set( $_POST, 'gallery_content' );

if ( $post_action === 'set_gallery_content' ) {

    $updated = cw_set_option( 'gallery_content', $gallery_content );

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
        <h1>Gallery</h1>
        <p>To add a gallery item, use the following layout and replace the text inside {}. Use as many or as few gallery items as you would like. Open each "item" without a slash, ie [caption], and don't forget the forward slash when you close it [/caption]. If the image exists in the images folder, you can simply put the name of the image (ie. wheels.jpg) in between [image][/image].</p>
        <p>Spaces and new lines around [shortcodes] are ignored, so you can do it in whichever way is easiest.</p>
        <?php
        $br = '<br>';
        echo '<p>';

        echo '[gallery_item]';
        echo $br;
        echo $br;
        echo '[image] {image_url_here} [/image]';
        echo $br;
        echo $br;
        echo '[caption] {image_caption_here} [/caption]';
        echo $br;
        echo $br;
        echo '[/gallery_item]';
        echo $br;
        echo $br;
        echo '</p>';
        ?>
        <form class="form-style-basic" method="post" id="gallery-edit">

            <input type="hidden" name="post_action" value="set_gallery_content">

            <div class="form-items">

                <?php

                if ( $postback_response ) {
                    echo get_form_response_text( $postback_response );
                }
                ?>

                <?php
                // might include html, which in the case of script i think won't execute in a textarea
                $option = DB_Option::get_instance_via_option_key( 'gallery_content' );
                ?>

                <?php echo get_form_textarea( array(
                    'add_class' => 'size-lg',
                   'label' => 'Gallery Content',
                   'name' => 'gallery_content',
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