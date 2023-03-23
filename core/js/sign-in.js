/**
 * Sign In / Sign Up forms
 */
function init_sign_in_form(){

    var body = $('body');

    body.on('submit', 'form.sign-in-ajax, form.sign-up-ajax', function(e){

        e.preventDefault();

        var form = $(this);
        var type = form.hasClass('sign-in-ajax') ? 'sign_in' : form.hasClass('sign-up-ajax') ? 'sign_up' : '';
        _console_log_('sign in type:', type );

        send_ajax( form, {
            beforeSend: function () {
                gp_body_loading_start();
                empty_form_response_text( form );
            },
            success: function (response) {

                gp_body_loading_end();

                var success = response.success || false;
                var response_text = response.response_text || '';

                var redirect = response.location || '';
                var reload = response.reload || false;

                if ( success ) {
                    if ( reload ) {
                        window.location.reload(true);
                    } else if ( redirect ) {
                        window.location.href = redirect;
                    } else if ( response_text ) {
                        set_form_response_text( form, response_text );
                    }
                } else {
                    response_text = response_text || 'Please try again.';
                    set_form_response_text( form, response_text );
                }
            }
        });
    });
}