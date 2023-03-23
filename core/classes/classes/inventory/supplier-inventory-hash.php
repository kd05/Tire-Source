<?php

/**
 * When we run a supplier inventory import, we'll hash the array that was
 * used as input data, and store that value in the "options" table.
 *
 * The next time we run the import, we can compare the hash to the last value used,
 * and if they are the same, we'll probably want to skip the import.
 *
 * Class Supplier_Inventory_Hash
 */
Class Supplier_Inventory_Hash{

	public static $option_key_prefix = 'supp_inv_hash_';

	const KEY_DT_TIRE_TIRES_CA = 'dt_tire_tires_ca';

	const KEY_VISION_RIMS_CA = 'vision_rims_ca';
	const KEY_VISION_RIMS_US = 'vision_rims_us';

	const KEY_CDA_TIRE_TIRES_CA = 'cda_tire_tires_ca';
	const KEY_CDA_TIRE_RIMS_CA = 'cda_tire_rims_ca';
	const KEY_ROBERT_THIBERT_RIMS_CA = 'robert_thibert_rims_ca';
	const KEY_ROBERT_THIBERT_RIMS_US = 'robert_thibert_rims_us';

	const KEY_TWG_RIMS_CA = 'wheel_1_rims_ca';
	const KEY_TWG_RIMS_US = 'wheel_1_rims_us';

	const KEY_DAI_TIRES_CA = 'dai_tires_ca';
	const KEY_DAI_TIRES_US = 'dai_tires_us';
	const KEY_DAI_RIMS_CA = 'dai_rims_ca';
	const KEY_DAI_RIMS_US = 'dai_rims_us';

    const KEY_FASTCO_TIRES_CA = 'fastco_tires_ca';
    const KEY_FASTCO_RIMS_CA = 'fastco_rims_ca';

    const KEY_WHEELPROS_TIRES_CA = 'wheelpros_tires_ca';
    const KEY_WHEELPROS_RIMS_CA = 'wheelpros_rims_ca';

    const KEY_DYNAMIC_TIRE_TIRES_CA = 'dynamic_tire_tires_ca';

    const KEY_AMAZON_ROBERT_THIBERT_CA = 'amazon_robert_thibert_ca';

	/**
	 * @return array
	 */
	public static function get_all_keys(){

		// get all defined constants of this class ...
		$oClass = new ReflectionClass(__CLASS__);
		$constants = $oClass->getConstants();

		$ret = array();

		if ( is_array( $constants ) ) {
			foreach ( $constants as $k=>$v ) {
				if ( strpos( $k, 'KEY_' ) !== FALSE ) {
					$ret[$k] = $v;
				}
			}
		}

		if ( ! $ret ) {
			throw_dev_error( 'probable error in getting all hash keys' );
		}

		return $ret;
	}

    /**
     * Returns true if the previously stored hash value is the same as the hash
     * value computed from $new_data.
     *
     * @param $key
     * @param $new_data
     * @return bool
     */
	public static function cmp_prev( $key, $new_data ) {
		$ex_hash = self::get_hash( $key );
		return $ex_hash && $ex_hash === self::make_hash( $new_data );
	}

	/**
	 *
	 */
	public static function delete_all_hashes(){
		foreach ( self::get_all_keys() as $key ) {
			self::delete_hash( $key );
		}
	}

	/**
	 * @param $key
	 */
	public static function delete_hash( $key ) {
		$_key = self::prefix_key( $key );
		$opt = DB_Option::get_instance_via_option_key( $_key );
		if ( $opt ) {
			self::update_hash( $_key, '' );
		}
	}

	/**
	 * in: "cda_tire_ca", out: "supp_inv_hash_cda_tire_ca"
	 *
	 * @param $hash_key
	 *
	 * @return string
	 */
	public static function prefix_key( $key ) {
		if ( strpos( $key, self::$option_key_prefix ) !== 0 ) {
			return self::$option_key_prefix . $key;
		}
		return $key;
	}

    /**
     * Not sure what happens if for example, $data was a resource or file handle
     * pointing to a file. I think for obvious reasons, we don't want to do this.
     * For example, when you print a resource doesn't it just say "Resource #5" for example?
     * so what then if we pass the handle into md5()? an error, or does it hash "Resource #5".
     * Obviously, i'm not so familiar with file handles and resources and how they really work.
     *
     * @param $data
     * @return false|string
     */
	public static function make_hash( $data ) {

		if ( is_array( $data ) ) {

		    // using only stock and not part number is good enough,
            // it can produce false positives in very rare cases, but
            // a false positive is not a problem.
		    return md5( implode( "", array_map( function( $item ){
		        return $item['stock'];
            }, $data ) ) );

		    // old
			// return md5( json_encode( $data ) );
		}

		// perhaps filename could be passed in.
		// note: if we download a file from FTP and localize it, will the time modified
		// change, and will this effect the result of md5_file() ?
		if ( gp_is_singular( $data ) && file_exists( $data ) ) {
			return md5_file( $data );
		}

		throw_dev_error( 'Invalid input type to hash generation function (' . gettype( $data ) . ').');
	}

	/**
	 *
	 * @see self:delete_hash_key()
	 *
	 * @param $hash_key
	 * @param $hash_value
	 */
	public static function update_hash( $key, $hash ) {
		$key = self::prefix_key( $key );
		cw_set_option( $key, $hash );
	}

	/**
	 * @param $hash_key
	 */
	public static function get_hash( $key ) {
		return cw_get_option( self::prefix_key( $key ), null );
	}
}


