var Ajax_Add_To_Cart = function (btn) {
    this.btn = btn;
};

/**
 * Add item(s) to the cart via ajax. data should be all req. form values
 * plus data.url which is the form action. Note: add to cart normally
 * occurs from a button with a json encoded data attribute as form values
 * rather than a <form>
 *
 * @param data
 */
Ajax_Add_To_Cart.prototype.send_request = function (data) {

    var self = this;

    if (data.hasOwnProperty('url') === false) {
        self.handle_response({});
        return;
    }

    send_ajax(null, {
        url: data.url,
        data: data,
        beforeSend: function () {
        },
        success: function (response) {

            // if cart count is undefined, function should do nothing
            var cart_count = gp_if_set(response, 'cart_count', undefined);
            update_cart_count(cart_count);

            // dont call this variable "alert"
            var alert_msg = response.alert || '';
            if (alert_msg !== '') {
                alert(alert_msg);
            }

            var actions = gp_if_set(response, 'actions', []);
            if (actions && actions.length > 0) {
                process_actions_from_objects(response.actions);
            }
        }
    });
};

/**
 * Call on page load or after page load if needed
 */
function ajax_add_to_cart_init() {

    $('body').on('click', '.ajax-add-to-cart', function (e) {

        // bit of a hacky way to prevent double submission..
        // double submission isn't a huge deal here, but this can prevent most or all of them.
        // this corrects an issue if enter is held down or pressed again after lightbox is open
        // and focus remains on the button that added items to the cart
        var ex_response = $('.gp-lightbox.active.add-to-cart-response.success');
        if ( ex_response.length > 0 ) {
            return;
        }

        e.preventDefault();
        var obj = new Ajax_Add_To_Cart($(this));
        var data = $(this).attr('data-cart');
        if (data !== undefined) {
            data = JSON.parse(data);
            obj.send_request(data);
        }
    });

    // var triggers = $('.ajax-add-to-cart');
    // $.each(triggers, function(){
    //     if ( $(this).hasClass('aatc-init') === false ) {
    //         $(this).addClass('aatc-init');
    //         var obj = new Ajax_Add_To_Cart( $(this) );
    //         obj.init();
    //     }
    // });
}