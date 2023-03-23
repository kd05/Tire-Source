<?php

/**
 * Class Single_Products_Page_Table
 */
Abstract Class Single_Products_Page_Table {

	/** @var  'tire'|'rim' */
	protected $class_type;

	/** @var Vehicle|null */
	protected $vehicle;

	/** @var null  */
	protected $part_number;

	protected $rows;

	protected $columns;

	public $args;

	protected $row_count;

	/** @var array - Default arguments for add to cart buttons. $atc['items'] is added to this for each row. */
	protected $atc;

	/**
	 * html to show a horizontal line..
	 *
	 * Can use for staggered fitment rows where each cell contains 2 data points
	 *
	 * @var string
	 */
	public $cell_separator;

	/**
	 * You may want to override the default for this.
	 *
	 * @var string
	 */
	protected $no_results_html;
    /**
     * @var mixed|null
     */
    private $package_id;

    /**
	 * Single_Products_Page_Table constructor.
	 */
	public function __construct( $columns, $args = array(), $vehicle = null, $package_id = null, $part_number = null ) {
		$this->columns   = $columns;
		$this->args      = $args;
		$this->row_count = 0;

		$this->part_number = $part_number;
		$this->vehicle    = $vehicle;
		$this->package_id = $package_id;

		$this->atc = get_add_to_cart_partial_args( $vehicle, $package_id, array() );

		$this->cell_separator = '<p class="sep"></p>';
	}

	/**
     * Whatever is passed in here will get passed into $this->get_cell_data().
	 *
	 * @param array $data
	 */
	public function add_row( $data ) {
		// why gp_make_array ?
		$this->rows[] = gp_make_array( $data );
	}

	/**
	 *
	 */
	public function render() {

        $classes = [
            'gp-flex-table',
            'product-table',
            @$this->args['add_class']
        ];

        $op    = '';
        $op    .= '<div class="' . gp_parse_css_classes( $classes ) . '">';

        if ( @$this->args['title'] ) {
            $op .= '<div class="product-table-title">';
            $op .= '<h2 class="like-h2">' . $this->args['title'] . '</h2>';
            $op .= gp_if_set( $this->args, 'after_title' );
            $op .= '</div>';
        }

        $op .= '<p class="scroll-indicator">Scroll <i class="fas fa-long-arrow-alt-right"></i></p>';

        $op .= '<div class="table-overflow">';
        $op .= '<table>';
        $op .= $this->render_row( $this->columns, 'header' );

        if ( $this->rows && is_array( $this->rows ) ) {
            foreach ( $this->rows as $row ) {
                $data = array();
                if ( $this->columns && is_array( $this->columns ) ) {
                    foreach ( $this->columns as $key => $value ) {
                        $data[ $key ] = $this->get_cell_data( $key, $row );
                    }
                }
                $op .= $this->render_row( $data, 'body', $row );
            }
        } else {
            $op .= $this->get_no_results_row();
        }

        $op .= '</table>';
        $op .= '</div>'; // table-overflow

		$op .= '</div>'; // gp-flex-table

		return $op;
	}

	/**
	 * @param $html
	 */
	public function set_no_results_html( $html, $before = '<p>', $after = '</p>' ) {
		$this->no_results_html = $before . $html . $after;
	}

	/**
	 *
	 */
	protected function get_no_results_row(){

		$no_results_html = $this->no_results_html ? $this->no_results_html : '<p>No Results Found</p>';

//		$data = array(
//			'single' => array(
//				'cell_html' => $no_results_html,
//			)
//		);
//
//		$type = 'body';
//		$args = array(
//			'add_class' => 'no-results-row',
//			// 'col_span' => count( $this->columns ) - 1,
//		);

		// minus 2 just because. javascript will fix this when its needed
		$col_span = count( $this->columns ) - 2;

		$ret = '';
		$ret .= '<tr class="no-results-row">';
		$ret .= '<td class="cell" colspan="' . $col_span . '">';
		$ret .= '<div class="cell-inner">';
		$ret .= $no_results_html;
		$ret .= '</div>';
		$ret .= '</td>';
		$ret .= '</tr>';

		return $ret;

		// return $this->render_row_generic_alt( $data, $type, $args );
	}

    /**
     * @param $data - result of $this->get_cell_data()
     * @param $type - 'header' or 'body'
     * @param array $args
     * @return string
     */
	protected function render_row( $data, $type, $args = array() ) {

		$cls = array(
			'',
			'type-' . gp_test_input( $type ),
		);

		if ( $type === 'body' ) {
			$this->row_count ++;

			if ( $this->row_count === 1 ) {
				$cls[] = 'first';
			}

			if ( $this->row_count % 2 === 0 ) {
				$cls[] = 'even';
			} else {
				$cls[] = 'odd';
			}
		}

		// this element could be an array/string/nested array etc.
		$cls[] = gp_if_set( $args, 'add_class' );

		$op = '';
		$op .= '';

		$op .= '<tr class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= gp_if_set( $args, 'html_before' );

		if ( $data && is_array( $data ) ) {

			// sometimes $value needs to be an array
			foreach ( $data as $key => $value ) {

				$cell_cls = array(
					'cell',
					'cell-' . gp_esc_db_col( $key ),
				);

				if ( gp_is_singular( $value ) ) {
					$vv          = $value;
					$cell_before = '';
					$cell_after  = '';
				} else {
					$vv          = gp_if_set( $value, 'value' );
					$cell_cls[]  = gp_if_set( $value, 'add_class' );
					$cell_before = gp_if_set( $value, 'cell_before' );
					$cell_after  = gp_if_set( $value, 'cell_after' );
				}

				$vv = gp_make_singular( $vv );

				if ( $type === 'header' ) {
					$op .= '<th class="' . gp_parse_css_classes( $cell_cls ) . '">';
				} else {
					$op .= '<td class="' . gp_parse_css_classes( $cell_cls ) . '">';
				}

				// may pass in raw html for some of our no results rows
				if ( is_array( $value ) && isset( $value['cell_html'] ) ) {

					// using this method basically just allows us to not print the p tag in case we need
					// to print multiple p tags... and not worry about one inside of another.
					$op .= '<div class="cell-inner">';
					$op .= $value['cell_html'];
					$op .= '</div>';

				} else {
					$op .= $cell_before;
					$op .= '<div class="cell-inner">';

					// this is a crappy way to do it but not storing <p> tags in $this->columns..
					// and all body rows need their own p tags cuz some have more complicated html
					if ( $type === 'header' ) {
						$op .= '<p>';
						$op .= $vv;
						$op .= '</p>';
					} else {
						$op .= $vv;
					}

					$op .= '</div>';
					$op .= $cell_after;
				}

				if ( $type === 'header' ) {
					$op .= '</th>'; // cell
				} else {
					$op .= '</td>'; // cell
				}

			}
		}

		$op .= gp_if_set( $args, 'html_after' );
		$op .= '</div>';

		return $op;

	}

	/**
	 * @param $key
	 * @param $product
	 */
	abstract protected function get_cell_data( $key, $product );

	/**
	 * @param        $items - could be 2 products due to rows for staggered fitments
	 * @param string $text
	 * @param string $fitment_slug
	 * @param string $sub_slug
	 *
	 * @return string
	 */
	protected function get_add_to_cart_btn( $items, $text = 'Add To Cart', $fitment_slug = '', $sub_slug = '' ) {

		$atc            = $this->atc;
		$atc[ 'items' ] = $items;

		// example of $atc['items']
		// $atc['items'][0]['type'] = 'tire';
		// $atc['items'][0]['loc'] = 'universal';
		// $atc['items'][0]['quantity'] = 4;
		// $atc['items'][0]['part_number'] = 1231231234;

		// sometimes these values are in $this->atc, because all rows in the table are displaying
		// the exact same vehicle, but other times different rows in the table have different values
		// for these, therefore if they are not provided, then default to what is in $atc.
		$ss = $sub_slug ? $sub_slug : gp_if_set( $atc, 'sub_slug' );
		$fs = $fitment_slug ? $fitment_slug : gp_if_set( $atc, 'fitment_slug' );

		if ( $fs ) {
			$atc['fitment'] = $fs;
		}

		if ( $ss ) {
			$atc['sub'] = $ss;
		}

		// this comes from adding an item to the cart without a vehicle, and then adding a vehicle to that item.
		$replace = get_user_input_singular_value( $_GET, 'replace' );
		if ( $replace ) {
			$atc['replace'] = $replace;
		}

		$op = '';
		$op .= '<div class="add-to-cart-wrapper">';
		$op .= '<button class="ajax-add-to-cart css-reset" data-cart="' . gp_json_encode( $atc ) . '">' . $text . '</button>';
		$op .= '</div>';

		return $op;
	}
}
