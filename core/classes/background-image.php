<?php

Class Background_Image{

	/**
	 * @param string $filename
	 * @param array  $args
	 *
	 * @return string
	 */
	public static function get( $filename = '', $args = array() ){

		$img = gp_if_set( $args, 'img', $filename );
		$img_url = get_image_src( $img );

		$contain = gp_if_set( $args, 'contain' );

		$cls = array();

		$cls[] = gp_if_set( $args, 'base_class', 'background-image' );
        $cls[] = $img_url ? '' : 'img-not-found';

		if ( $contain ) {
			$cls[] = 'contain';
		} else {
			$cls[] = gp_if_set( $args, 'standard', true ) ? 'standard' : '';
		}

		$cls[] = gp_if_set( $args, 'add_class', '' );
		$overlay = gp_if_set( $args, 'overlay', false );
		$overlay_opacity = gp_if_set( $args,'overlay_opacity' );

		$op = '';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '" style="' . gp_get_img_style( $img_url ) . '">';

		if ( $overlay || $overlay_opacity ) {
			$op .= '<span class="overlay" style="' . gp_shortcode_overlay_style( $overlay_opacity ) . '"></span>';
		}

		$op .= gp_if_set( $args, 'inner_html', '' );
		$op .= '</div>';
		$op .= '';
		return $op;

	}

}