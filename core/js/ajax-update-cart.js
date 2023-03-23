

// my attempt at javascript object inheritance...
var Ajax_Update_Cart = function() {};
var Ajax_Update_Cart_Button = function(){};
var Ajax_Update_Cart_Form = function(){};

/**
 *
 * @param data
 */
Ajax_Update_Cart.prototype.handle_submit = function( data ){

    var self = this;
    self.data = data; // make this available to other methods in case we need it

    var url;
    if ( self.type === 'form' ) {
        url = self.form.attr('action');
    } else {
        url = data.url;
    }

    // when submitting from the form with the province input in it, this isn't needed
    // but when using the update cart buttons, we need to add the province so that the
    // cart receipt reflects the latest shipping choice
    var province = $('form#cart-shipping select[name="province"]');

    if ( province.length > 0 ) {
        if ( $.isArray( data ) ) {
            data.push({
                name: 'province',
                value: province.val()
            });
        } else {
            // for buttons with json encoded data attributes ie. "Remove"
            data.province = province.val();
        }
    }

    var order_summary = $('.cart-section.order-summary .cart-box');

    if ( gp_body_is_loading() ) {
        alert( 'Please wait for your last request to finish.' );
        return false;
    }

    send_ajax( null, {
        data: data,
        url : url,
        beforeSend: function () {
            gp_body_loading_start();

            if ( order_summary.length > 0 ) {
                order_summary.addClass('loading');
            }
        },
        success: function (response) {
            gp_body_loading_end();

            if ( order_summary.length > 0 ) {
                order_summary.removeClass('loading');
            }

            process_actions_from_objects( response.actions );

            // run this upon trying to submit payment as well
            var receipt_html = response.receipt_html || '';
            checkout_page_update_receipt_html( receipt_html );

            var response_text = response.response_text || '';

            // this is most likely not a common thing, so just throw an alert
            if ( response_text !== '' ) {
                alert( response_text );
            }

            var success = response.hasOwnProperty( 'success' ) ? response.success : false;
            success = success === "0" || success === "false" ? false : success;

            // empty string is valid
            var cart_items_html = response.hasOwnProperty('cart_items_html') ? response.cart_items_html : undefined;

            if ( success && cart_items_html !== undefined ) {
                var cart_items = $('#cart-items');
                if ( cart_items.length > 0 ) {
                    cart_items.empty().append(cart_items_html);
                }
            }

            // if cart count is undefined, function should do nothing
            var cart_count = gp_if_set( response, 'cart_count', undefined );
            update_cart_count( cart_count );

            // attach event handlers on html that was just appended
            // set timeout because i'm not sure if we'll get async issues with trigger happening too early
            setTimeout(function(){
                $(window).trigger('updateCartComplete');
                $(window).trigger('gp_ajax_complete');
            }, 0 );
        }
    });
};

/**
 * @returns {boolean}
 */
Ajax_Update_Cart_Button.prototype.init = function( button ){
    var self = this;
    self.button = button;
    self.type = 'button';

    self.button.on('click', function(e){
        e.preventDefault();
        var data = $(this).attr('data-update-cart');
        data = JSON.parse( data );

        // var cart_shipping_postal = $('form#cart-shipping');
        // if ( cart_shipping_postal.length > 0 ) {
        //     var data_2 = cart_shipping_postal.serializeObject();
        //     console.log(data_2);
        // }

        self.handle_submit( data );
    });
};


// need separate init method
Ajax_Update_Cart_Form.prototype.init = function( form ){
    var self = this;
    self.form = form;
    self.type = 'form';

    self.form.on('submit', function(e){
        e.preventDefault();
        var data = $(this).serializeArray();
        self.handle_submit( data );
    });
};

// theres maybe a better way to do this but i can't seem to figure it out
Ajax_Update_Cart_Button.prototype.handle_submit = Ajax_Update_Cart.prototype.handle_submit;
Ajax_Update_Cart_Form.prototype.handle_submit = Ajax_Update_Cart.prototype.handle_submit;

/**
 *
 */
function ajax_update_cart_add_handlers(){

    _console_log_('ajax_update_cart_add_handlers');

    var btn = $('.ajax-update-cart-btn');
    $.each(btn, function(){

        if ( $(this).hasClass('auc-init') ) {
            return false;
        }

        $(this).addClass('auc-init');

        var obj = new Ajax_Update_Cart_Button();
        obj.init($(this));
    });

    var form = $('.ajax-update-cart-form');
    $.each(form, function(){

        if ( $(this).hasClass('auc-init') ) {
            return false;
        }

        $(this).addClass('auc-init');

        var obj = new Ajax_Update_Cart_Form();
        obj.init($(this));
    });

}

function cart_quantity_select_form(){

    var input = $('form.cart-qty-select input[name="quantity"]');
    if ( input.length > 0 ) {
        if ( input.hasClass('cqs-init') === false ) {
            input.addClass('cqs-init');
            input.on('change', function(){

                console.log('change qty select....');

                // prevent accidental changing quantities but not properly updating before checking out
                var msg = $('.cart-checkout-quantity-message');
                if ( msg.length > 0 ) {
                    msg.removeClass('hidden');
                }

                var checkout_button = $('.cart-section.order-summary .cart-checkout');
                if ( checkout_button.length > 0 ) {
                    checkout_button.addClass('hidden');
                }

                var form = $(this).closest('form');
                var wrap = form.find('.update-wrap');
                if ( wrap.length > 0 ) {
                    wrap.addClass('active');
                }
            });
        }
    }

}

/**
 *
 */
function init_ajax_update_cart_handlers(){
    ajax_update_cart_add_handlers();
    $(window).on('updateCartComplete', function(){
        _console_log_('updateCartComplete triggered');
        var enabled = window.update_cart_complete_handlers_enabled;
        if ( ! enabled ) {
            ajax_update_cart_add_handlers();
        }
    });
}
