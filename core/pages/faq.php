<?php

Header::$title = "FAQ";
Header::$canonical = get_url( 'faq' );

// from database
$items = get_faq_items_array();

cw_get_header();

?>
    <div class="page-wrap interior-page page-faq">
		<?php echo get_top_image( array(
			'title' => 'Frequently Asked Questions',
			'img' => get_image_src( 'iStock-884106776-wide-lg.jpg' ),
            'overlay_opacity' => 53,
		) ); ?>
		<?php echo Components::grey_bar(); ?>
        <div class="main-content">
            <div class="container">
                <div class="general-content">
                    <div class="faq-items">
                        <div class="faq-controls all-hidden">
                            <button class="css-reset expand-all">[Expand All]</button>
                            <button class="css-reset collapse-all">[Collapse All]</button>
                        </div>
						<?php
						foreach ( $items as $item ) {

							$q = gp_if_set( $item, 'question' );
							$a = gp_if_set( $item, 'answer' );

							echo '<div class="faq-item hidden">';

							echo '<div class="question">';
							echo '<h3 class="question-2">' . $q . '</h3>';
							echo '</div>';

							echo '<div class="answer">';
							echo '<div class="answer-2 general-content">';
							echo gp_render_textarea_content( $a );
							echo '</div>';
							echo '</div>';

							echo '</div>';
						}
						?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php

cw_get_footer();