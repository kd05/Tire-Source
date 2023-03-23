<?php

$to = 'masci.joel@gmail.com';
$body = 'Test Content.';
$subject = 'Test Subject.';
$is_html = gp_if_set( $_POST, 'is_html' ) == "1";

if ( gp_if_set( $_POST, 'form_submit' ) == "1" ) {

	$mail = get_php_mailer_instance();

	$mail->setFrom( get_email_from_address() );
	$mail->addReplyTo( get_email_reply_to_address(), get_email_reply_to_name() );

	$mail->addAddress( $to );

	$mail->isHTML( $is_html );

	$mail->Subject = $subject;
	$mail->Body    = $body;

	$send = php_mailer_send( $mail );

	if ( $send ) {
		echo '************ SENT **************';
	} else {
		echo '************ NOT SENT **************';
	}
	echo '<br><br>';

	$mail2 = clone $mail;
	$mail2->Password = "********";

	echo '<pre>' . print_r( $mail2, true ) . '</pre>';

	$exception = false;
}


?>

<form action="" method="post">
	<input type="hidden" name="form_submit" value="1">
	<p>To:</p>
	<input type="text" value="<?php echo $to; ?>" disabled>
	<p>Content:</p>
	<textarea name="" id="" cols="30" rows="10" disabled><?php echo $body; ?></textarea>
	<p>Is Html</p>
	<p><input type="checkbox" name="is_html" value="1"></p>
	<p><input type="submit"></p>
</form>
