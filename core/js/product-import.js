/**
 *
 */
function init_product_import(){

    var body = $('body');
    var product_import = {};

    /**
     *
     * @param form
     * @param response
     */
    product_import.maybe_re_submit = function( form, response ){

        // we'll maybe add a 'stop' button somewhere that adds this class to the form
        if ( form.hasClass('do-not-continue') ) {
            return;
        }

        var cont = response.continue || false;

        var new_nonce = response.new_nonce || '';
        if ( new_nonce ) {
            var ex = form.find('input[name="nonce"]');
            if ( ex.length > 0 ) {
                ex.val(new_nonce);
            }
        }

        // info to send to the next ajax request
        var persist = response.persist || {};
        $.each(persist, function(k,v){
            var ex = form.find('input[name="' + k + '"]');
            if ( ex.length > 0 ) {
                ex.val(v);
            } else {
                form.prepend( '<input type="hidden" name="' + k + '" value="' + v + '">' );
            }
        });

        if ( cont ) {
            _console_log_('continue...');
            form.submit();
        } else {
            _console_log_('finished.');
        }
    };

    /**
     *
     * @param form
     * @param response
     */
    product_import.set_status_response = function( form, response ) {
        var status = response.status || '';
        if ( status !== '' ) {
            var div = $('.product-import-status');
            if ( div.length > 0 ) {
                div.removeClass('empty');
                div.empty().append(status);
            }
        }
    };

    /**
     *
     * @param form
     * @param response
     */
    product_import.add_update_response = function( form, response ) {
        var _append = response._append || '';
        if ( _append  !== '' ) {
            var div = $('.product-import-updates');
            if ( div.length > 0 ) {
                div.removeClass('empty');
                div.append('<div class="update">' + _append + '</div>');
            }
        }
    };

    body.on('click', '.do-not-continue-trigger', function(e){
        e.preventDefault();
        var form = $(this).closest('form');
        if ( form.length > 0 ) {
            form.addClass('do-not-continue');
            $(this).parent().after('<div class="form-item"><p>Submission will stop once the current process is complete. Please re-load the page to import again.</p></div>');
        }
    });

    body.on('submit', '#cw-import-rims, #cw-import-tires', function(e){

        e.preventDefault();

        var form = $(this);
        var import_action = get_form_value( form, 'import_action' );

        if ( import_action === 'init' ) {

            var things = $('.product-import-updates, .product-import-status');
            $.each(things, function(){
                $(this).empty().addClass('empty');
            });

            // remove the button to deter user from submitting without reloading the page afterwards
            // ideally id like to just disable the form from submitting but we can't because we trigger form
            // submit to re-send the next ajax requests
            var btn = form.find('.item-wrap.item-submit');
            if ( btn.length > 0 ) {
                // dont remove the button just hide it because the response message needs the button
                var abort = '<div class="item-wrap"><button class="do-not-continue-trigger">Abort</button></div>';
                btn.before(abort).hide();
            }

        } else if ( import_action === 'continue' ) {

        }

        send_ajax( form, {
            beforeSend: function(){
                gp_body_loading_start();
                empty_form_response_text( form );
            },
            success: function( response ){
                gp_body_loading_end();

                _console_log_(response);

                // general error messages
                set_form_response_text( form, response.output, response.success );

                // updates the current status
                product_import.set_status_response( form, response );

                // appends to a list of updates
                product_import.add_update_response( form, response );

                // maybe re-submit the form again (but after adding a bit of data)
                product_import.maybe_re_submit( form, response );
            }
        });

    });

}