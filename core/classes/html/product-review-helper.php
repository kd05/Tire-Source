<?php

/**
 * Takes in user input and returns possible DB_Product or DB_Review objects.
 * This does not validate a user or their authorization to add or edit a review,
 * it simply looks at user data (ie. $_GET) and lets you know if it corresponds
 * to an existing product (model...) and/or an existing review.
 *
 * Class Product_Review_Helper
 */
Class Product_Review_Helper{

	/** @var bool  */
	public $is_tire;

	/** @var null|string  */
	public $is_rim;

	/** @var false|null|DB_Rim|DB_Tire */
	public $product;

	/**
	 * is $userdata['review'] is not set, then this belongs to
	 * the logged in user. If it is set, it could belong to any user.
	 *
	 * @var false|null|DB_Review
	 */
	public $review;

	/** @var  DB_User|null  */
	public $logged_in_user;

	/**
	 * Product_Review_Helper constructor.
	 */
	public function __construct( $userdata ){

		// setup defaults
		$this->is_tire = false;
		$this->is_rim = false;
		$this->product = false;
		$this->review = false;

		$this->logged_in_user = cw_get_logged_in_user();

		$review_id = get_user_input_singular_value( $userdata, 'review' );

		// By Review ID, ie. $_GET['review']
		if ( $review_id ) {

			$this->review = DB_Review::create_instance_via_primary_key( $review_id );

			if ( ! $this->review ) {
				return;
			}

			$brand = $this->review->get( 'review_brand' );
			$model = $this->review->get( 'review_model' );
			$color_1 = $this->review->get( 'review_color_1' );
			$color_2 = $this->review->get( 'review_color_2' );
			$finish = $this->review->get( 'review_finish' );
			$product_type = $this->review->get( 'review_product_type' );

			$this->is_tire = $product_type === 'tire';
			$this->is_rim = $product_type === 'rim';

		} else {

			$user = cw_get_logged_in_user();
			$user_id = $user ? $user->get_primary_key_value() : false;

			$brand = get_user_input_singular_value( $userdata, 'brand' );
			$model = get_user_input_singular_value( $userdata, 'model' );
			$color_1 = get_user_input_singular_value( $userdata, 'color_1' );
			$color_2 = get_user_input_singular_value( $userdata, 'color_2' );
			$finish  = get_user_input_singular_value( $userdata, 'finish' );

			$this->is_rim = get_user_input_singular_value( $userdata, 'is_rim' ) ? true : false;
			$this->is_tire = ! ( $this->is_rim );

			// find existing review from product data (which may or may not be found)
			// both are valid.. a user could be editing an existing one, or adding a new one.
			if ( $this->is_rim ) {
				$this->review = DB_Review::get_rim_review_via_user_id( $user_id, $brand, $model, $color_1, $color_2, $finish );
			} else {
				$this->review = DB_Review::get_tire_review_via_user_id( $user_id, $brand, $model );
			}
		}

		// still no guarantee the product exists, and that is viable because products can be deleted, where reviews
		// are not (.. as in .. not deleted when products are)
		if ( $this->is_tire ) {
			$this->product = DB_Tire::get_partial_product( $brand, $model );
		} else if ( $this->is_rim ) {
			$this->product = DB_Rim::get_partial_product( $brand, $model, $color_1, $color_2, $finish );
		} else {
			$this->product = false;
		}
	}

	/**
	 * @return string
	 */
	public function get_brand_name(){
		$v = $this->product ? $this->product->brand->get( 'name' ) : $this->review->get( 'review_brand' );
		return gp_test_input( $v );
	}

	/**
	 * @return string
	 */
	public function get_model_name(){
		$v = $this->product ? $this->product->model->get( 'name' ) : $this->review->get( 'review_model' );
		return gp_test_input( $v );
	}

	/**
	 *
	 */
	public function get_color_1_name(){
		$v = $this->product && $this->product->is_rim() ? $this->product->finish->get( 'color_1_name' ) : $this->review->get( 'review_color_1' );
		return gp_test_input( $v );
	}

	/**
	 *
	 */
	public function get_color_2_name(){
		$v = $this->product && $this->product->is_rim() ? $this->product->finish->get( 'color_2_name' ) : $this->review->get( 'review_color_2' );
		return gp_test_input( $v );
	}

	/**
	 *
	 */
	public function get_finish_name(){
		$v = $this->product && $this->product->is_rim() ? $this->product->finish->get( 'finish_name' ) : $this->review->get( 'review_finish' );
		return gp_test_input( $v );
	}

	/**
	 *
	 */
	public function get_product_image_url(){
		$v = $this->product ? $this->product->get_image_url() : image_not_available();
		return $v;
	}
}
