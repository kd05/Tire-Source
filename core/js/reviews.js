
function init_reviews_page(){

    var body = $('body');

    body.on('submit', 'form#review-product', function(e){

        _console_log_('submit');
        var form = $(this);

        e.preventDefault();

        send_ajax( form, {
            beforeSend: function(){
                gp_body_loading_start();
                empty_form_response_text( form );
                _console_log_('before send');
            },
            success: function(response){
                gp_body_loading_end();
                _console_log_('after send');

                var response_text = response.response_text || '';
                if ( response_text ) {
                    set_form_response_text( form, response_text );
                }
            }
        });

    });


}