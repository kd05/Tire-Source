<?php

$ret              = [];
$ret[ 'success' ] = false;

// do this when email gets send to a valid user, or when user does not exist but email address
// is valid ... as in, its not something retarded, not as in .. it belongs to a valid user ..
// if you can't see the difference you need help.
$success_message = 'Please check your email for instructions on how to reset your password.';
$error_general   = 'There was an error sending your email. Please contact us directly.';

// i don't know if sanitizing this will cause issues with @ or other characters
// that may or may not be allowed in email addresses.
$email = get_array_value_force_singular( $_POST, 'email' );
$email = trim( $email );

if ( ! validate_email( $email ) ) {
	$ret[ 'success' ]       = false;
	$ret[ 'response_text' ] = 'Please enter a valid email address.';
	Ajax::echo_response( $ret );
	exit;
}

$user = DB_User::create_instance_via_email( $email );

// if no user with email, indicate SUCCESS
if ( ! $user ) {
	// wait for .1 seconds
	usleep( 100000 );
	$ret[ 'success' ]       = true;
	$ret[ 'response_text' ] = $success_message;
	Ajax::echo_response( $ret );
	exit;
}

if ( $user && $user->get_locked_status() > 1 ) {
	$ret[ 'success' ]       = false;
	$ret[ 'response_text' ] = $user->get_locked_message();
	Ajax::echo_response( $ret );
	exit;
}

$url = get_forgot_password_url_and_update_user( $user );

if ( ! $url ) {
	$ret[ 'success' ]       = false;
	$ret[ 'response_text' ] = '[1] ' . $error_general;
	Ajax::echo_response( $ret );
	exit;
}

//if ( ! IN_PRODUCTION ) {
// print link maybe..
//	$ret[ 'response_text' ] = $body;
//	$ret[ 'success' ]       = true;
//	Ajax::echo_response( $ret );
//	exit;
//}

// not for production.... this gives free access to all accounts

$body                   = '';
$body .= '<p>Someone has requested a password reset for your tiresource.COM account.</p>';
$body .= '<p>If you did not initiate this action, please ignore this email.</p>';
$body .= '<p>Otherwise, visit the link shown here: <a href="' . $url . '">' . $url . '</a>.</p>';
$body .= '<p>Your password reset link will expire after a short period of time.</p>';

$subject = 'Password Reset - Click It Wheels';

try {

	$mail = get_php_mailer_instance();

	$mail->setFrom( get_email_from_address(), get_email_from_name() );
	$mail->addReplyTo( get_email_reply_to_address( 'password_reset' ), get_email_reply_to_name() );

	$mail->addAddress( $email );

	$mail->isHTML( true );

	$mail->Subject = $subject;
	$mail->Body    = $body;

	$send = php_mailer_send( $mail );
	$exception = false;

} catch ( Exception $e ) {
	$send      = false;
	$exception = true;
}

if ( ! $send ) {
	$ret[ 'success' ]       = false;
	$ret[ 'response_text' ] = '[2] ' . $error_general;
	Ajax::echo_response( $ret );
	exit;
}

$ret[ 'success' ]       = false;
$ret[ 'response_text' ] = $success_message;
Ajax::echo_response( $ret );
exit;
