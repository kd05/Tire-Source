<?php

$img_url = get_image_src( '4-tires.jpg' );

?>

<div class="contact-banner">
    <div class="background-image standard">
        <div class="img-tag-cover inherit-size">
            <img src="<?= get_image_src( '4-tires.jpg' ); ?>" alt="Tires Online at Click It Wheels">
        </div>
    </div>
	<div class="container">
        <div class="content-top general-content color-white">
            <p class="like-h1-xxl">Let's Get In Touch</p>
            <div class="line-under"></div>
        </div>
        <form action="<?php echo AJAX_URL; ?>" class="contact-1 cw-ajax" data-reset-success="1">
            <?php echo get_ajax_hidden_inputs( 'contact_1' ); ?>
            <div class="form-col left">
                <div class="form-col-inner">
                    <div class="form-field-type-1 item-name">
                        <input type="text" placeholder="Name" id="c1-name" name="name">
                        <label for="c1-name"><i class="fa fa-pencil-alt"></i></label>
                    </div>
                    <div class="form-field-type-1 item-email">
                        <input type="text" placeholder="Email" id="c1-email" name="email">
                        <label for="c1-email"><i class="fa fa-at"></i></label>
                    </div>
                    <div class="form-field-type-1 item-phone">
                        <input type="text" placeholder="Phone" id="c1-phone" name="phone">
                        <label for="c1-name"><i class="fa fa-phone"></i></label>
                    </div>
                </div>
            </div>
            <div class="form-col right">
                <div class="form-col-inner">
                    <div class="form-field-type-1 field-textarea item-message">
                        <textarea name="message" placeholder="Message" id="c1-message" cols="30" rows="10"></textarea>
                        <label for="c1-message"><i class="fa fa-pencil-alt"></i></label>
                    </div>
                </div>
            </div>
            <div class="ajax-response empty"></div>
            <div class="submit-wrap">
                <div class="button-1">
                    <button type="submit">Submit</button>
                </div>
            </div>
        </form>
	</div>
</div>
