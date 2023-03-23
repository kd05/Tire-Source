/**
 * see comment in main.js..
 */
function new_ajax_general(){

    var body = $('body');

    body.on('submit', 'form.ajax-general', function(e){

        e.preventDefault();
        var form = $(this);

        var confirm_before = form.attr('data-confirm-before');

        if ( confirm_before && ! confirm( confirm_before ) ) {
            return false;
        }

        send_ajax( form, {
            beforeSend: function(){
                gp_body_loading_start();
            },
            error: function(){
                gp_ajax_encountered_error();
                gp_body_loading_end();
            },
            success: function( response ){

                gp_body_loading_end();

                // most response text messages should be in here
                set_form_response_text( form, response.response_text );

                var auto = response._auto || {};
                var auto_alert = auto.alert || '';
                var auto_reload = auto.reload || false;

                if ( auto_alert !== '' ) {
                    alert( auto_alert );
                }

                if ( auto_reload ) {
                    window.location.reload();
                }

                var aa;
                if ( form.hasClass('alert-response-msg') ) {
                    aa = gp_if_set( response, 'msg', gp_if_set( response, 'alert', '' ) );
                } else {
                    aa = response.alert || '';
                }

                if ( aa ) {
                    alert( aa );
                }
            }
        })

    });

}