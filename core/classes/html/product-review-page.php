<?php

/**
 *
 * Class Product_Review_Page
 */
Class Product_Review_Page {

	/**
	 * $_GET or _POST probably
	 *
	 * @var
	 */
	public $userdata;

	/**
	 * @var bool
	 */
	public $is_valid;

	/**
	 * @var string
	 */
	public $invalid_reason;

	/**
	 * This class takes in raw user input, and finds whether or not it is
	 * related to a DB_Product, and/or an existing DB_Review. Note that all combinations
	 * of product/review are possible. We can have both a product and a review, meaning a user
	 * is updating a review. We can have just a product, meaning they are adding a new one. Or we
	 * can have just a review meaning they are editing an existing review but the product no longer
	 * exists in the database. What's more fun, is that (outside of the helper class) we have to do
	 * a bunch of user logic, including whether or not an admin is trying to edit a review, or if its a user.
	 *
	 * @var Product_Review_Helper
	 */
	public $helper;
    /**
     * @var false
     */
    private $valid;

    /**
	 * Product_Review_Page constructor.
	 */
	public function __construct( $userdata ) {

		// setup defaults
		$this->valid = false;
		$this->invalid_reason = 'invalid';
		$user = cw_get_logged_in_user();
		$user_id = $user ? $user->get_primary_key_value() : false;

		$this->helper = new Product_Review_Helper( $userdata );

		if ( isset( $userdata['review'] ) && ! $this->helper->review ) {
			$this->is_valid = false;
			$this->invalid_reason = 'review_not_found';
			return;
		}

		// Must be logged in
		if ( ! $user ) {
			$this->is_valid = false;
			$this->invalid_reason = 'not_logged_in';
			return;
		}

		// Review Exists (it means action should be to update)
		if ( $this->helper->review ) {

			// Not admin? Then you can only edit your own.
			if ( ! $user->is_administrator() && (int) $user_id !== (int) $this->helper->review->get( 'user_id' ) ) {
				$this->is_valid = false;
				$this->invalid_reason = 'review_does_not_belong_to_user';
				return;
			}

			$this->is_valid = true;

		} else {

			// No review found, so action should be inserting a new one.

			// we require a product to insert new reviews
			if ( ! $this->helper->product ) {
				$this->is_valid = false;
				$this->invalid_reason = 'product_not_found';
				return;
			}

			// if any user is logged in (admin or not), and the product exists, then we're good to go.
			$this->is_valid = true;
		}

		// still need to store all raw userdata in the object.
		$this->userdata = $userdata;
	}

	/**
	 *
	 */
	public function get_invalid_user_response( $render = true, $df = 'Error.' ){

		$map = array();

		$map['not_logged_in'] = 'You must be logged in to post reviews.';
		$map['review_not_found'] = 'Your review was not found.';

		// rendering the form, or handling a form submission?
		if ( $render ) {

		} else {

		}

		return gp_if_set( $map, $this->invalid_reason, $df );
	}

	/**
	 *
	 */
	public function render_sidebar_content() {

		$cls   = [ 'product-card' ];
		$cls[] = $this->helper->is_tire ? 'type-tire' : '';
		$cls[] = $this->helper->is_rim ? 'type-rim' : '';

		$op = '';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<div class="pc-inner">';
		$op .= '<div class="product-titles">';
		$op .= '<h2 class="brand">' . $this->helper->get_brand_name() . '</h2>';
		$op .= '<p class="model">' . $this->helper->get_model_name() . '</p>';

		if ( $this->helper->is_rim ) {
			$op .= '<p class="finish">' . get_rim_finish_string( $this->helper->get_color_1_name(), $this->helper->get_color_2_name(), $this->helper->get_finish_name() ) . '</p>';
		}

		$op .= '</div>'; // product titles

		$img_url = $this->helper->get_product_image_url();

		if ( $img_url ) {
			$op .= '<div class="img-wrap">';
			$op .= '<div class="img-wrap-2">';
			$op .= '<div class="background-image contain" style="' . gp_get_img_style( $img_url ) . '"></div>';
			$op .= '</div>'; // img-wrap-2
			$op .= '</div>'; // img-wrap
		}

		$op .= '</div>'; // pc-inner

		if ( $this->helper->product ) {

            $url = $this->helper->product->get_url();

			$op .= '<div class="buttons">';
			$op .= '<div class="button-1 color-black"><a target="" href="' . $url . '">Details</a></div>';
			$op .= '</div>';
		} else {
			$op .= '<div class="buttons">';
			$op .= '<div class="button-1 color-black"><button disabled class="css-reset disabled">Product Not Found</button></div>';
			$op .= '</div>';
		}

		$op .= '</div>'; // product-card

		return $op;
	}

	/**
	 *
	 */
	public function render_main_content() {

		if ( ! $this->is_valid ) {
			return '';
		}

		$disabled = false;

		if ( $this->helper->review ) {
			$nickname = gp_test_input( $this->helper->review->get( 'nickname' ) );
			$rating = gp_test_input( $this->helper->review->get( 'rating' ) );
			$message = gp_sanitize_textarea( $this->helper->review->get( 'message' ) );

			$approved = $this->helper->review->is_approved();

			if ( $this->helper->logged_in_user && ! $this->helper->logged_in_user->is_administrator() && $approved ){
				$disabled = true;
			}

		} else {
			$nickname = $rating = $message = '';
		}

		$cls = ['review-product form-style-1 on-white-bg'];
		if ( $disabled ) {
			$cls[] = 'disabled';
		}

		$op = '';
		$op .= '<form id="review-product" action="' . AJAX_URL . '" class="' . gp_parse_css_classes( $cls ) . '">';

		$op .= get_ajax_hidden_inputs( 'review_product' );

		// print all user data (after sanitizing) so that our ajax script
		// has access to exactly what we in $_GET when it receives user input via ajax/$_POST
		$op .= get_hidden_inputs_from_array( $this->userdata, true );

		$op .= '<div class="form-items">';
		$op .= '';


		$op .= get_form_input( array(
			'name' => 'nickname',
			'label' => 'Nickname (optional)',
			// 'disabled' => $disabled,
			'value' => $nickname,
		));

		$op .= get_form_select( array(
			'name' => 'rating',
			'label' => 'Overall Satisfaction',
			'add_class_2' => 'on-white',
			'select_2' => true,
			// 'disabled' => $disabled,
			'placeholder' => 'Choose One', // empty placeholder is necessary
		), array(
			'current_value' => $rating,
			'items' => array(
				'1' => '1 out of 5',
				'2' => '2 out of 5',
				'3' => '3 out of 5',
				'4' => '4 out of 5',
				'5' => '5 out of 5',
			)
		));

		$op .= get_form_textarea( array(
			'name' => 'message',
			'label' => 'Message',
			// 'disabled' => $disabled,
			'value' => $message,
		));

		$op .= get_form_submit( [
			'text' => 'Submit',
		]);

		$op .= '</div>'; // form-items

		$op .= '</form>';
		return $op;
	}

	/**
	 *
	 */
	public function render_sidebar_and_content() {

		if ( ! $this->is_valid ) {
			return '';
		}

		$op = '';
		$op .= '<div class="review-product-container">';

		$op .= '<div class="rp-title">';

		if ( $this->helper->review ) {

			if ( $this->helper->logged_in_user->is_administrator() ) {
				$op .= '<h1 class="like-h1-lg">Edit Review</h1>';

				if ( $this->helper->review->get( 'user_id' ) == $this->helper->logged_in_user->get( 'user_id' ) ) {
					$op .= '<p>You are logged in as an administrator. This is your own review.</p>';
				} else {
					$op .= '<p>You are logged in as an administrator. This is someone else\'s review that you can edit.</p>';
				}

			} else {

				if ( $this->helper->review->get( 'approved' ) ) {
					$op .= '<h1 class="like-h1-lg">Rate This Product</h1>';
					$op .= '<p>Thank you for leaving a review on this product already. It has been approved, so you cannot make further changes to it.</p>';
				} else {
					$op .= '<h1 class="like-h1-lg">Rate This Product</h1>';
					$op .= '<p>Thank you for leaving a review on this product already. Once your review is approved, it will show up on the site, and you will no longer be able to make changes to it.</p>';
				}
			}

		} else {
			$op .= '<h1 class="like-h1-lg">Rate This Product</h1>';
		}
		$op .= '</div>';

		$op .= '<div class="rp-left">';
		$op .= '<div class="rp-left-2">';
		$op .= '';
		$op .= $this->render_sidebar_content();
		$op .= '';
		$op .= '</div>';
		$op .= '</div>';
		$op .= '<div class="rp-right">';
		$op .= '<div class="rp-right-2">';
		$op .= '';
		$op .= $this->render_main_content();
		$op .= '';
		$op .= '</div>';
		$op .= '</div>';
		$op .= '</div>';

		return $op;
	}
}


