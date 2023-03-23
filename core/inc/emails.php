<?php


/**
 *
 */
function get_admin_email_from_name() {
	return '';
}

/**
 * @param string $context
 *
 * @return string
 */
function get_admin_email_to( $context = '' ) {

	if ( ! IN_PRODUCTION ) {
	    return SITE_DEVELOPER_EMAIL;
	}

	switch( $context ) {
		case 'supplier_fallback':
			// sales
			return 'sales@email_removed.com';
			break;
		case 'contact':
			// info
			return 'info@email_removed.com';
			break;
		case 'checkout':
			// sales
			return 'sales@email_removed.com';
			break;
		default:
			// info
			return 'info@email_removed.com';
			break;
	}
}

/**
 * Used for a few automated emails sent to site users, ie.
 * on forgot password, etc.
 */
function get_email_from_address(){
    if ( IN_PRODUCTION ) {
        return 'donotreply@email_removed.com';
    } else {
        return 'dev-env@email_removed.com';
    }
}

/**
 * @return string
 */
function get_email_from_name(){
	// will this just automatically flag shit for spam?
	return 'tiresource.COM';
}

/**
 * @return string
 */
function get_email_reply_to_address( $context = '' ){

	switch( $context ) {
		case 'password_reset':
			$ret = 'info@email_removed.com';
			break;
		case 'suppliers':
			$ret = 'orders@email_removed.com';
			break;
		// probably most emails will end up using the default here
		default:
			$ret = 'sales@email_removed.com';

	}

	return $ret;
}

/**
 * @return string
 */
function get_email_reply_to_name(){
	return get_email_from_name();
}

/**
 * may or may not be the same as get_non_admin_email_from()
 *
 * For example, we may send emails to clients from sales@email_removed.com, or from
 * donotreply@email_removed.com. but i prefer not to send from sales@email_removed.com to
 * sales@email_removed.com, maybe it doesn't matter but i thought i remember issues when doing
 * that one time.
 *
 * @return mixed|string
 */
function get_admin_email_from() {

    if ( IN_PRODUCTION ) {
        return 'donotreply@email_removed.com';
    } else {
        return 'dev-env@email_removed.com';
    }
}
