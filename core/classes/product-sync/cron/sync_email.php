<?php

list( $items_with_changes, $content ) = \PS\Cron\get_email_content();

if ( count( $items_with_changes ) === 0 ) {
    log_data([], 'sync-no-changes' );
    exit;
}

if ( IS_WFL ) {
    $args = [
        'to' => IN_PRODUCTION ? 'email_removed@email_removed.com' : 'masci.joel@gmail.com',
        'from' => get_admin_email_from(),
        'content' => $content,
    ];

} else {
    $args = [
        'to' => IN_PRODUCTION ? 'tiresource@gmail.com' : 'masci.joel@gmail.com',
        'from' => get_admin_email_from(),
        'content' => $content,
    ];
}

Cron_Helper::$merge_into_log_after['to'] = $args['to'];
Cron_Helper::$merge_into_log_after['from'] = $args['from'];

try{

    // configure PHPMailer
    $mail = get_php_mailer_instance( true );
    $mail->isHTML( true );
    $mail->addAddress( $args['to' ] );
    $mail->setFrom( $args['from'] );
    $mail->Body = $content;
    $mail->Subject = 'Product Sync';

    ob_start();
    $sent = php_mailer_send( $mail );

    Cron_Helper::$merge_into_log_after['sent'] = $sent;

    // I think the send function sometimes just prints errors/warnings
    $output = ob_get_clean();

    if ( ! $sent ) {
        log_data( [
            'output' => $output,
            'args' => $args,
        ], 'sync-email-not-sent' );
    }

} catch ( Exception $e ) {

    log_data( [
        'e' => $e->getMessage(),
        'args' => $args,
    ], 'sync-email-exception' );

    // let global exception handler pick it up because I think it does
    // a better job at logging the exception.
    throw $e;
}
