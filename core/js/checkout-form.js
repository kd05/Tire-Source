/**
 *
 * @param form
 * @param html
 */
function checkout_update_confirm_total(form, html) {

    // allow empty string however
    if (html === undefined) {

        return;
    }

    if (form.length < 1) {

        return;
    }

    // the server side logic should handle printing a new checkbox to the page
    // that is already checked, if it was checked last time and the price did not change
    var ex = form.find('.item-confirm_total');

    if (ex.length > 0) {
        ex.remove();
    }

    var item_before = form.find('.item-submit').last();

    // once again do this even if html is empty
    if (item_before.length > 0) {
        item_before.before(html);
    }
}

/**
 *
 * @param form
 * @param response
 */
function checkout_page_update_from_ajax_response(form, response) {

    // Note: run this exact same thing when submitting the form
    checkout_update_confirm_total(form, response.confirm_total);

    // run this upon trying to submit payment as well
    var receipt_html = response.receipt_html || '';
    checkout_page_update_receipt_html(receipt_html);

    var items_html = response.items_html || '';
    checkout_page_update_items_html(items_html);

    var cart_count = response.cart_count || undefined;
    if (cart_count !== undefined) {
        update_cart_count(cart_count);
    }
}

/**
 * Note: this needs to work on both the checkout page and the cart page.
 */
function checkout_page_update_receipt_html(v) {

    if (v) {
        var ele = $('.cart-section.order-summary, .cart-section.order-summary-mobile');

        $.each(ele, function(){
            _console_log_( ele );
            ele.empty().append(v);
        });
    }
}

/**
 * Set html for .cart-summary
 */
function checkout_page_update_items_html(v) {
    if (v) {
        var ele = $('.cart-section.cart-summary');

        if (ele.length > 0) {
            ele.empty().append(v);
        }
    }
}


/**
 * Callback to update the cart receipt.
 */
function checkout_form_update_receipt(form) {

    if (!form || !form.length > 0) {

        return;
    }


    var t = form.find('input, select');

    // $.each(t, function(){
    //     $('.main-content').append( $(this).attr('name') + '<br>' );
    // });

    var data = form.serializeArray();

    var cart_box = $('.cart-section.order-summary .cart-box');

    data.push({
        name: 'update_receipt',
        value: 1
    });

    send_ajax(form, {
        data: data,
        beforeSend: function () {
            gp_body_loading_start();
            if (cart_box.length > 0) {
                cart_box.addClass('loading');
            }
        },
        success: function (response) {

            gp_body_loading_end();

            if (cart_box.length > 0) {
                cart_box.removeClass('loading');
            }

            checkout_page_update_from_ajax_response(form, response);
        }
    });
}

/**
 * updates province <select> based on value of country <select>
 *
 * @param country
 * @param province
 */
function update_province_list(country, province) {


    if (!country || !country.length > 0) {

        return;
    }

    if (!province || !province.length > 0) {

        return;
    }

    var data = province.attr('data-provinces');
    data = data ? jQuery.parseJSON(data) : {};

    var current_country = country.val();

    // I think if we need a placeholder value like <option>Select Province</option>
    // then it should just be inserted into each set of province lists
    // partially because its just complicated to decide whether or not to keep the null value
    // and also because one might say select state etc.
    var options = gp_if_set(data, current_country, options);
    options = options || {};

    var opt = '';

    $.each(options, function (k, v) {
        opt += '<option value="' + k + '">' + v + '</option>';
    });

    province.empty().append(opt);
}

/**
 * Pretty much all event handlers for the checkout form.
 */
function checkout_form() {

    var body = $('body');

    // link country to province selectors
    var cp = [
        [
            'form#checkout select[name="country"]',
            'form#checkout select[name="province"]'
        ],
        [
            'form#checkout select[name="sh_country"]',
            'form#checkout select[name="sh_province"]'
        ]
    ];

    // update province lists
    $.each(cp, function (k, v) {

        // on page load
        var c = $(v[0]);
        var p = $(v[1]);
        update_province_list(c, p);

        // on change
        body.on('change', v[0], function () {

            var p = $(v[1]);
            update_province_list($(this), p);
        });
    });

    // update order summary when clicking buttons
    body.on('change', '.page-checkout .update-order-summary, #form-checkout .update-order-summary', function (e) {
        var form = $('form#checkout');
        if (form.length > 0) {
            checkout_form_update_receipt(form);
        }
    });

    // form names that require order summary to be updated when changed
    var names = [
        'province',
        'country',
        'postal',
        'sh_province',
        'sh_country',
        'sh_postal',
        'ship_to',
        'shipping_is_billing'
    ];

    // update order summary on change
    body.on('change', 'form#checkout input, form#checkout select', function (e) {

        var name = $(this).attr('name');
        var needs_update = false;
        var form = $(this).closest('form#checkout');

        $.each(names, function (k, v) {
            if (name === v) {
                needs_update = true;
            }
        });

        if (needs_update) {
            checkout_form_update_receipt(form);
        }
    });

    // Show Shipping Fields only if a checkbox is not checked
    body.on('change', 'form#checkout input[name="shipping_is_billing"]', function (e) {
        var input = $(this);
        var checked = ( input.prop('checked') );
        var form = $(this).closest('form');
        var items = form.find('.shipping-items');
        if (items.length > 0) {
            if (checked) {
                items.addClass('hidden');
            } else {
                items.removeClass('hidden');
            }
        }
    });

    // Heard about - reveal input type text for 'heard_about_other'
    body.on('change', 'form#checkout select[name="heard_about"]', function (e) {
        var ele = $('form#checkout .item-heard_about_other');
        if (ele.length > 0) {
            if ($(this).val() === 'other') {
                ele.show();
            } else {
                ele.hide();
            }
        }
    });

    // Show password fields when 'register' is checked
    body.on('change', 'form#checkout input[name="register"]', function (e) {

        var form = $(this).closest('form');
        var p1 = form.find('.item-password_1');
        var p2 = form.find('.item-password_2');

        if (this.checked) {
            if (p1.length > 0) {
                p1.removeClass('hidden');
            }
            if (p2.length > 0) {
                p2.removeClass('hidden');
            }
        } else {
            if (p1.length > 0) {
                p1.addClass('hidden');
            }
            if (p2.length > 0) {
                p2.addClass('hidden');
            }
        }
    });

    // Toggle Shipping Sub Sections (ship to address / local pickup)
    body.on('change', 'form#checkout input[name="ship_to"]', function (e) {
        var input = $(this);
        var checked = ( input.prop('checked') );
        if (checked) {
            var sub_section = input.closest('.shipping-sub-section');
            if (sub_section.length > 0) {
                var sub_sections = sub_section.closest('.cart-box').find('.shipping-sub-section');
                $.each(sub_sections, function () {
                    var this_sub_section = $(this);
                    this_sub_section.addClass('test');
                    if (this_sub_section.is(sub_section)) {
                        this_sub_section.addClass('active').removeClass('not-active');
                    } else {
                        this_sub_section.addClass('not-active').removeClass('active');
                    }
                });
            }
        }


    });

    // Form Submit
    body.on('submit', 'form#checkout', function (e) {

        e.preventDefault();

        var form = $(this);

        if (form.hasClass('processing')) {
            alert('Your last submission is still waiting for a response, please wait for it to complete before submitting again.');
            e.preventDefault();
            return;
        }

        var data = form.serializeArray();

        // add this value when the form is submitted.
        // the reason is because we add a different value when we just want the receipt html
        // and we run that code when certain address fields change, which means that on any failures
        // or errors in the code, the fallback will be to attempt to process the payment based on the current
        // form values, which is not a good fallback to have. Therefore, server side won't attempt to process unless
        // this is set.
        data.push({
            name: 'process_payment',
            value: 1
        });

        send_ajax(form, {
            data: data,
            beforeSend: function () {
                gp_body_loading_start();
                form.addClass('processing');

                // just remove the div entirely, it causes issues with last child styles on submit button otherwise
                var response_div = form.find('.submit-response');
                if (response_div.length > 0) {
                    response_div.remove();
                }
            },
            error: function () {
                alert('An unknown error occurred. Please try again or contact us if the error persists.');
                gp_body_loading_end();
                form.removeClass('processing');
            },
            success: function (response) {

                gp_body_loading_end();
                form.removeClass('processing');

                checkout_page_update_from_ajax_response(form, response);

                var success = response.success || false;

                // create the div with each response, delete it before send.
                // also, we're specifically not going to delete existing response texts here
                // in case a user submits twice (which js does attempt to prevent), they may
                // get one message saying duplicate transaction, and another one saying transaction approved.
                // we don't want to show only the first of these messages..
                var response_text = response.response_text || '';
                if (response_text !== '') {
                    var submit = form.find('.item-submit').last();
                    if (submit.length > 0) {
                        var cls = success ? 'success' : 'error';
                        var rr = '<div class="submit-response not-empty ' + cls + '">' + response_text + '</div>';
                        submit.after(rr);
                    }
                }

                if (success) {
                    form[0].reset();
                    if ( window.fbq !== undefined ) {
                        // ie {currency: "USD", value: 30.00}
                        var purchase = response.fbq_purchase || {};
                        fbq('track', 'Purchase', purchase );
                    }

                    var gtag_fields = response.gtag_fields || {};
                    var _send = {
                        'send_to': 'AW-780794004/TFdJCP_J_L0DEJTxp_QC',
                        'value': gtag_fields.value,
                        'currency': gtag_fields.currency,
                        'transaction_id': gtag_fields.transaction_id,
                    };
                    console.log('gtag event conversion', _send);

                    if ( window.gtag !== undefined ) {
                        console.log('gtag sending...');
                        gtag('event', 'conversion', _send );
                    }
                }
            }
        });
    });
}
