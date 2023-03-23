<?php

/**
 * Class Field_Set_Item_Presets
 */
Class Field_Set_Item_Presets {

    /**
     * @param string $add_to_label
     * @return Field_Set_Item
     */
	public static function meta_title( $add_to_label = "" ) {
		return self::generic_input( 'meta_title', [
			'allow_html' => false,
            'label' => "Meta Title" . $add_to_label,
			'meta_key' => 'meta_title'
		] );
	}

	/**
	 * @return Field_Set_Item
	 */
	public static function archive_desc() {
		return self::generic_textarea( 'archive_desc', [
			'allow_html' => true,
			'label' => "Archive Description (Text below the main title on product brand archives). HTML is allowed.",
			'meta_key' => 'archive_desc'
		] );
	}

	/**
	 * @return Field_Set_Item
	 */
	public static function html_textarea( $name, $label = "" ) {
		return self::generic_textarea( $name, [
			'allow_html' => true,
			'label' => $label ? $label : $name,
			'meta_key' => $name
		] );
	}

    /**
     * @param string $add_to_label
     * @return Field_Set_Item
     */
	public static function meta_desc( $add_to_label = '' ) {
		return self::generic_input( 'meta_desc', [
			'allow_html' => false,
			'label' => "Meta Description" . $add_to_label,
			'meta_key' => 'meta_desc'
		] );
	}

	/**
	 * @param       $name
	 * @param array $args
	 */
	//	public static function generic_textarea_tiny_mce( $name, $args = [] ) {
	//		$args[ 'tiny_mce' ]   = true;
	//		$args[ 'allow_html' ] = true;
	//
	//		return self::generic_textarea( $name, $args );
	//	}

	/**
	 * defaults $args['meta_key'] and $args['label'] to $name,
	 * and possibly does a few other things..
	 *
	 * @param array $args
	 * @param       $name
	 */
	public static function filter_args( $args, $name = '', $filter_type = '' ) {
		$args[ 'name' ]     = gp_if_set_and_not_empty( $args, 'name', $name );
		$args[ 'label' ]    = gp_if_set_and_not_empty( $args, 'label', $args[ 'name' ] );
		$args[ 'meta_key' ] = gp_if_set_and_not_empty( $args, 'meta_key', $args[ 'name' ] );

		// maybe 'select' or something else in the future..
		switch ( $filter_type ) {
			default:
				break;
		}

		return $args;
	}

	/**
	 * @param      $name
	 * @param null $label
	 *
	 * @return Field_Set_Item
	 */
	public static function generic_textarea( $name, $args = [] ) {

		$allow_html = gp_if_set( $args, 'allow_html', false );
		$args       = self::filter_args( $args, $name );

		$field_set_args        = $callback = [];
		$callbacks[ 'render' ] = function ( $field ) use ( $args, $allow_html ) {

			$page_id = $field->parent->page->get( 'page_id' );

			$value_raw = get_page_meta( $page_id, $args[ 'meta_key' ] );

			// allowing html in title field for possible anchor tags or inline styles
			return get_form_textarea( array(
				'tiny_mce' => gp_if_set( $args, 'tiny_mce', false ),
				'label' => @$args[ 'label' ],
				'name' => @$args[ 'name' ],
				'value' => htmlspecialchars_but_allow_ampersand( $value_raw ),
				// did our own sanitation already (maybe)
				'sanitize_value' => false,
			) );
		};

		$callbacks[ 'save' ] = function ( $field ) use ( $args, $allow_html ) {

			$page_id = $field->parent->page->get( 'page_id' );

			// allowing html in title field for possible anchor tags or inline styles
			$value = gp_if_set( $_POST, $args[ 'name' ] );

			if ( ! $allow_html ) {
			    // not always sufficient for sanitation but we'll also sanitize on printing
				$value = strip_tags( $value );
			}

			update_page_meta( $page_id, $args[ 'meta_key' ], $value );
		};

		return new Field_Set_Item( $field_set_args, $callbacks );
	}

	/**
	 * @param      $name
	 * @param null $label
	 *
	 * @return Field_Set_Item
	 */
	public static function generic_input( $name, $args = [] ) {

		// using default of true should also allow ampersands...
		$allow_html = gp_if_set( $args, 'allow_html', true );
		$args       = self::filter_args( $args, $name );

		$render = function ( $field ) use ( $args, $allow_html ) {

			$page_id = $field->parent->page->get( 'page_id' );

			$value_raw = get_page_meta( $page_id, $args[ 'meta_key' ] );

			// allowing html in title field for possible anchor tags or inline styles
			return get_form_input( array(
				'label' => @$args[ 'label' ],
				'name' => @$args[ 'name' ],
				'value' => htmlspecialchars_but_allow_ampersand( $value_raw ),
				// did our own sanitation already
				'sanitize_value' => false,
			) );
		};

		$save = function ( $field ) use ( $args, $allow_html ) {

			$page_id = $field->parent->page->get( 'page_id' );

			// allowing html in title field for possible anchor tags or inline styles
			$value = gp_if_set( $_POST, $args[ 'name' ] );

			if ( ! $allow_html ) {
				// strip tags not needed perhaps?
				$value = strip_tags( $value );
			}

			update_page_meta( $page_id, $args[ 'meta_key' ], $value );
		};

		return Field_Set_Item::instance_from_callbacks( $render, $save );
	}
}