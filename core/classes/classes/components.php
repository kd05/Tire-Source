<?php

Class Components{

	public function __construct(){}

    /**
     * @param array $args
     * @return string
     */
	public static function get_split_row( $args = array() ) {
        $image1 = get_image_src( @$args['image1'] );
        $image2 = get_image_src( @$args['image2'] );
		$content = gp_if_set( $args, 'content', '' );
		$title_content = gp_if_set( $args, 'title_content', '' );

		$cls = array();
		$cls[] = 'split-row-section';
		$cls[] = $image2 ? 'has-image-2' : 'no-image-2';

		ob_start();

		?>

        <div class="split-row-section">
            <div class="sr-flex">
                <div class="sr-left">
                    <div class="sr-left-inner">
                        <div class="bg-wrap">
                            <div class="img-tag-cover inherit-size">
                                <img src="<?= $image1; ?>" alt="<?= gp_test_input( @$args['image1alt'] ); ?>">
                                <span class="overlay" style="opacity: .75"></span>
                            </div>
                        </div>
                        <div class="img-wrap">
                            <img src="<?= $image2; ?>" alt="<?= gp_test_input( @$args['image2alt'] ); ?>">
                        </div>
                    </div>
                </div>
                <div class="sr-right">
                    <div class="sr-right-inner">
                        <div class="section-title general-content">
                            <?= $title_content; ?>
                        </div>
                        <div class="section-main general-content">
                            <?= $content; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
	}

	/**
	 * little grey bar thing above some sections...
	 */
	public static function grey_bar( $args = array() ){

		$cls = array( 'grey-bar' );
		$cls[] = gp_if_set( $args, 'add_class', '' );

		$op = '';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<div class="wide-container"><div class="wide-container-inner"></div></div>';
		$op .= '</div>';
		return $op;
	}
}