<?php

$response = array();
$errors = array();

$name = get_user_input_singular_value( $_POST, 'name' );
$email = get_user_input_singular_value( $_POST, 'email' );
$phone = get_user_input_singular_value( $_POST, 'phone' );

// this function will call htmlspecialchars...
// i dont konw if we'll store into DB, or if we will display on page, or whatever
// but id rather just be safe.
$message = get_user_input_singular_value( $_POST, 'message' );

if ( ! $name ) {
	$errors[] = '<p>Please provide your name.</p>';
}

if ( ! $email ) {
	$errors[] = '<p>Please provide your email.</p>';
} else if ( ! ( $email = filter_var( $email, FILTER_VALIDATE_EMAIL ) ) ) {
	$errors[] = '<p>Please enter a valid email address.</p>';
}

if ( ! $message ) {
	$errors[] = '<p>Please provide a message.</p>';
}

if ( $errors ) {
	$response['success'] = false;
	$response['output'] = implode( "\r\n", $errors );
	Ajax::echo_response( $response );
	exit;
}

// this is just ajax.php all the time which doesn't do us much good
//$page = gp_if_set( $_SERVER, 'REQUEST_URI' );
//$page = gp_test_input( $page ); // maybe unsanitary data from within $_GET ??

$body = '';
$body .= '<p>You have a new contact form submission.</p>';
// $body .= '<p><strong>Page: </strong> ' . $page;
$body .= '<p><strong>Name: </strong> ' . $name;
$body .= '<p><strong>Email: </strong> ' . $email;
$body .= '<p><strong>Phone: </strong> ' . $phone;
$body .= '<br><br>';
$body .= $message;

try {

	/** @var \PHPMailer\PHPMailer\PHPMailer $mail */
	$mail = get_php_mailer_instance();

	$mail->setFrom( get_admin_email_from() );
	$mail->addReplyTo( $email, $name );

	//Recipients
	$mail->Subject = 'Contact Form';
	$mail->Body    = $body;

	$mail->addAddress( get_admin_email_to() );

	$send = php_mailer_send( $mail );

	if ( $send ) {
		$response['success'] = true;
		$response['output'] = '<p>Thank you for your message, we will be in touch with you soon.</p>';
	} else {
		// ... caught down below
		throw new Exception( 'mail not sent');
	}

} catch (Exception $e) {

	if ( IN_PRODUCTION ) {
		$response['success'] = false;
		$response['output'] = '<p>An error has occurred, please contact us directly.</p>';
	} else {
		$response['success'] = false;
		$response['output'] = get_pre_print_r( $e );
	}
}

Ajax::echo_response( $response );
exit;