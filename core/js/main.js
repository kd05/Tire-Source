/**
 *
 * @param thing
 */
function _console_log_(thing) {
    if ($('body').hasClass('do-console-logs')) {
        // console.log(thing);
    }
}

/**
 *
 */
function add_admin_table_controls() {

    var body = $('body');
    var tables = $('body.is-admin .table-style-1 table');

    var c = 0;

    // if table is not .table-style-1, can always just add the trigger into the html instead
    $.each(tables, function () {

        c++;
        $(this).addClass('export-csv-target').attr('data-csv', c);

        var btn1 = '<button type="button" style="margin-right: 15px;" class="export-csv-trigger" data-csv="' + c + '">Export CSV</button>';
        var btn2 = '<button type="button" class="admin-toggle-scroll-bars" data-csv="' + c + '">Toggle Scroll Bars</button>';
        $(this).before('<div class="csv-controls">' + btn1 + btn2 + '</div>');
    });


    body.on('click', '.admin-toggle-scroll-bars', function(e){
        e.preventDefault();

        if ( $('body').hasClass('admin-tables-overflow-visible') ) {
            $('body').removeClass('admin-tables-overflow-visible');
            document.cookie = "admin_tables_overflow_visible=0";
        } else {
            $('body').addClass('admin-tables-overflow-visible');
            document.cookie = "admin_tables_overflow_visible=1";
        }
    });

    body.on('click', '.export-csv-trigger', function (e) {

        e.preventDefault();

        var count = $(this).attr('data-csv');
        var target = $('.export-csv-target[data-csv="' + count + '"]');

        if (target.length > 0) {

            var filename = target.attr('data-csv-name') || string_to_slug(document.title);

            var csv = new GP_Table2CSV(target, filename);
            csv.export();

            // this one sucks
            // target.table2excel({
            //     filename: filename
            // });
        }

    });
}

/**
 *
 * @param str
 * @returns {string|*}
 */
function string_to_slug(str) {
    str = str.replace(/^\s+|\s+$/g, ''); // trim
    str = str.toLowerCase();

    // remove accents, swap ñ for n, etc
    var from = "àáäâèéëêìíïîòóöôùúüûñç·/_,:;";
    var to = "aaaaeeeeiiiioooouuuunc------";
    for (var i = 0, l = from.length; i < l; i++) {
        str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }

    str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
        .replace(/\s+/g, '-') // collapse whitespace and replace by -
        .replace(/-+/g, '-'); // collapse dashes

    return str;
}


/**
 *
 */
function test_checkout() {

    var body = $('body');
    if (body.hasClass('in-development')) {

        var id = Math.floor(Date.now() / 1000); // timestamp apparently

        // easier than auto-fill ... maybe...
        var map = {
            email: id + '@checkout.com',
            first_name: 'first',
            last_name: 'last',
            company: 'company',
            phone: 'phone',
            street_number: '123',
            street_name: 'fake street',
            street_extra: 'apt 23',
            city: 'city',
            postal: 'postal',
            card: '4111111111111111',
            cvv: '123'
        };

        body.on('dblclick', '.cart-title', function (e) {
            $.each(map, function (k, v) {
                var input = $('input#' + k);
                if (input.length > 0) {
                    input.val(v);
                }
            });
        });
    }
}


/**
 * @param table
 * @param file_name
 * @constructor
 */
function GP_Table2CSV(table, file_name) {

    this.ts = new Date().getTime();
    this.table = table;
    this.file_name = file_name || this.ts;

    var clean_text = function (text) {
        text = text.replace(/"/g, '""');
        return '"' + text + '"';
    };

    this.export = function () {

        //console.log('exporting...');
        var table = this.table;
        var caption = $(this).find('caption').text();
        var title = [];
        var rows = [];

        table.find('tr').each(function () {
            if ($(this).hasClass('cm-dont-export')) {
                return false;
            }
            var data = [];
            jQuery(this).find('th').each(function () {
                var text = clean_text(jQuery(this).text());
                title.push(text);
            });
            jQuery(this).find('td').each(function () {
                var text = clean_text(jQuery(this).text());
                data.push(text);
            });
            data = data.join(",");
            rows.push(data);
        });
        title = title.join(",");
        rows = rows.join("\n");

        var csv = title + rows;

        var uri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);

        var download_link = document.createElement('a');
        download_link.href = uri;

        if (caption == "") {
            download_link.download = file_name + ".csv";
        } else {
            download_link.download = caption + "-" + file_name + ".csv";
        }
        document.body.appendChild(download_link);
        download_link.click();
        document.body.removeChild(download_link);

    };

}

/**
 * $.ajax beforeSend()
 */
function gp_body_loading_start() {
    var body = $('body');
    body.addClass('state-loading');
}

/**
 * @returns {*}
 */
function gp_body_is_loading() {
    var body = $('body');
    return body.hasClass('state-loading');
}

/**
 *
 */
function gp_ajax_encountered_error(xhr) {
    gp_body_loading_end();
    var err = 'An unexpected error occurred.';
    // will help with debugging
    if ($('body').hasClass('admin-logged-in')) {
        err = 'Dumping all data since you are logged in as an admin (rather than showing a generic error message) ........';
        console.log(xhr);
        err += xhr ? xhr.responseText : '';
    }
    alert(err);
}

/**
 * Might use this sometimes with ajax.
 */
function gp_body_loading_end() {
    var body = $('body');
    body.removeClass('state-loading');
}

// /**
//  * fix select 2 form reset ?
//  */
// function gp_form_reset( form ){
//
//     console.log('gp form reset');
//
//     if ( ! form && ! form.length > 0 ) {
//         console.log('no form to reset');
//         return;
//     }
//
//     form.addClass('test');
//
//     console.log(form);
//
//     console.log('gp form reset2');
//
//     var s2 = form.find('.select2-hidden-accessible');
//     console.log(s2);
//     $.each(s2, function(e){
//         var s = $(this);
//         s.addClass('testing');
//         s.val('');
//     });
//
// }

/**
 *
 * @param wrapper
 * @param input_name
 */
function get_form_value(wrapper, input_name) {
    wrapper = wrapper || {};
    if (wrapper.length > 0) {
        var input = wrapper.find('input[name="' + input_name + '"]');
        if (input.length > 0) {
            return input.val();
        }
    }
    return undefined;
}

/**
 *
 * @param form - ie. <form>
 * @param options - options passed into $.ajax
 * @param args - additional args, that are not passed directly into $.ajax
 */
function send_ajax(form, options, args) {

    stop_if_loading = gp_if_set(args, 'stop_if_loading', true);

    if (stop_if_loading) {
        if (gp_body_is_loading()) {
            alert('An action is already being processed. Please wait until it is complete.');
            return false;
        }
    }

    // set defaults
    if (form && form.length > 0) {
        options.url = gp_if_set(options, 'url', form.attr('action'));
        options.data = gp_if_set(options, 'data', form.serializeArray());
    }

    // set defaults
    options.type = gp_if_set(options, 'type', 'POST');
    options.dataType = gp_if_set(options, 'dataType', 'json');

    options.error = gp_if_set(options, 'error', function (xhr, status, error) {
        gp_ajax_encountered_error(xhr);
    });

    // add options.beforeSend = function(), and options.success = function( response )
    $.ajax(options);
}

/**
 * @param wrap - should just be $('body') or an element that doesn't get removed/added via ajax etc.
 *
 * @param selector - a string selector like '#form-id input[type="radio"]'
 */
function make_radio_buttons_un_checkable(wrap, selector) {

    wrap = wrap && wrap.length > 0 ? wrap : $('body');
    selector = selector && selector.length > 0 ? selector : 'input[type="radio"]';

    /**
     *
     * @param wrap - probably body, but you could do one radio button group or form if you prefer.
     */
    function update_radio_button_state(wrap) {
        wrap = wrap && wrap.length > 0 ? wrap : $('body');
        var items = wrap.find('input[type="radio"]');
        $.each(items, function () {
            if (this.checked) {
                $(this).attr('data-was-checked', 1);
            } else {
                // dont use true/false because then "false" becomes true
                $(this).attr('data-was-checked', '');
            }
        });
    }

    // on page load
    update_radio_button_state();

    // after ajax
    $(window).on('gp_ajax_complete', function () {
        update_radio_button_state();
    });

    // on clone (ie. lightbox) - technically not needed
    // $(window).on('gp_clone_complete', function () {
    //     $.each($(selector),function(){
    //         $(this).trigger('gp_radio_button_track_state');
    //     });
    // });

    // product filter radio button on click
    wrap.on('click', selector, function (e) {

        var was_checked = $(this).attr('data-was-checked') || false;

        // un check self
        if (was_checked) {
            this.checked = false;
            // if we don't do this, the event doesn't fire, which isn't good if we
            // want to submit a form when an input changes for example
            $(this).trigger('change');
        }

        // now update the state for the next time
        // not sure if the timeout really does anything
        setTimeout(function () {
            update_radio_button_state();
        }, 1);
    });
}

/**
 * make a response div inside a form for multiple forms using similar markup.
 *
 * @param form
 */
function make_form_response_element(form) {

    if (form.length < 1) {
        return;
    }

    var response_text_ele = form.find('.response-text');
    if (response_text_ele.length < 1) {
        var item_submit = form.find('.item-submit');
        if (item_submit.length > 0) {
            item_submit.before('<div class="response-text empty"></div>');
        }
    }
}

/**
 * Set a forms (ajax) response text, for multiple forms using similar markup.
 *
 * @param form
 * @param text
 * @param success
 */
function set_form_response_text(form, text, success) {

    if (form.length < 1) {
        return;
    }

    // ensure element exists
    make_form_response_element(form);

    text = text || '';

    var add_class = '';

    if (success === 1 || success === true) {
        add_class = 'success';
    } else if (success === 0 || success === false || success === "false") {
        // trying to avoid throwing error when success is simply not defined.
        add_class = 'error';
    }

    var response_text_ele = form.find('.response-text');
    if (response_text_ele.length > 0) {
        if (text) {
            response_text_ele.empty().append(text).addClass('not-empty').addClass(add_class).removeClass('empty').slideDown();
        } else {
            // possibly equivalent to doing nothing..
            response_text_ele.empty().addClass('empty').addClass(add_class).removeClass('not-empty');
        }
    }
}

/**
 * Remove a forms (ajax) response text, for multiple forms using similar markup.
 *
 * @param form
 */
function empty_form_response_text(form) {

    form = form || {};

    if (form.length < 1) {
        return;
    }

    var response_text_ele = form.find('.response-text');
    if (response_text_ele.length > 0) {
        if (response_text_ele.hasClass('not-empty')) {
            response_text_ele.empty().addClass('empty').removeClass('not-empty').slideUp();
        } else {
            response_text_ele.addClass('empty');
        }
    }
}

/**
 * Try to focus on first visible input within any wrapper element.
 *
 * Works (almost always?)
 *
 * @param wrapper
 * @param skip_selectors
 * @param fallback_to_wrapper
 */
function gp_focus_first_visible_input(wrapper, skip_selectors, fallback_to_wrapper) {

    fallback_to_wrapper = fallback_to_wrapper || true;
    skip_selectors = ( skip_selectors !== undefined ) ? skip_selectors : [];

    if (wrapper.length < 1) {
        return;
    }

    // focus on the first input.. or button
    var visible_inputs = wrapper.find('*').filter(':input:visible, :button:visible');

    // note... :visible is true for elements with visibility: hidden
    // loop through items until we find a visible one, focus on it, and then exit the loop
    var focussed = false;
    $.each(visible_inputs, function () {

        var vis = $(this);

        if (focussed) {
            return false;
        }

        if (( $(this).css('visibility') === 'hidden' ) === false) {
            if ($(this).prop('disabled') === false) {

                var cont = false;

                if (skip_selectors.length > 0) {
                    $.each(skip_selectors, function (kk, vv) {
                        // vv could be.. '.lb-close', in other words we dont focus on an element if it has that class
                        if (vis.is(vv)) {
                            cont = true;
                        }
                    });
                }

                // continue to next iteration of .each ?
                if (cont === true) {
                    return true;
                }

                $(this).focus();
                focussed = true;
            }
        }
    });

    if (!focussed) {
        if (fallback_to_wrapper) {
            var ti = wrapper.attr('tab-index');
            if (ti === undefined) {
                wrapper.attr('tab-index', 0).focus();
            } else {
                wrapper.focus();
            }
        }
    }
}


/**
 * Overwrites obj1's values with obj2's and adds obj2's if non existent in obj1
 * @param obj1
 * @param obj2
 * @returns obj3 a new object based on obj1 and obj2
 */
function merge_objects(obj1, obj2) {
    var obj3 = {};
    for (var attrname in obj1) {
        obj3[attrname] = obj1[attrname];
    }
    for (var attrname in obj2) {
        obj3[attrname] = obj2[attrname];
    }
    return obj3;
}

/**
 * @param value
 */
function update_cart_count(value) {

    if (value === undefined || value === null) {
        return;
    }

    // probably just one of these in header, but in theory it could be anywhere
    var ele = $('.cart-count-indicator');

    if (ele.length > 0) {
        value = parseInt(value);
        var html = '(' + value + ')';
        ele.empty();
        if (value) {
            ele.addClass('not-empty').removeClass('empty').append(html);
        } else {
            ele.addClass('empty').removeClass('not-empty').append(html);
        }
    }

}

$.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

( function ($) {

    /**
     *
     */
    function dev_alert() {

        $('body').on('click', '.dev-alert .trigger', function () {

            var p = $(this).closest('.dev-alert');
            var c = p.find('.alert-content');
            if (p.hasClass('active') === false) {
                c.show();
                p.addClass('active');
            } else {
                c.hide();
                p.removeClass('active');
            }
        });

    }

    /**
     * Run on form.submit() to clean up URL with method="get"
     *
     * @param form
     */
    function disable_empty_form_values(form) {
        if (form.length > 0) {

            var inputs = form.find('*[name]');

            if (inputs.length > 0) {
                $.each(inputs, function () {
                    var val = $(this).val();
                    if (val === "") {
                        $(this).attr('disabled', 'disabled');
                    }
                });
            }
        }
    }

    function rims_by_size_form(){

        $('body').on('submit', 'form#rims-by-size', function (e) {
            e.preventDefault();
            disable_empty_form_values($(this));

            var form = $(this);
            var obj = form.serializeObject();
            var query = [];
            var action = form.attr('action');
            var fields = [ 'diameter', 'width', 'bolt_pattern', 'offset_min', 'offset_max', 'hub_bore_min', 'hub_bore_max' ];

            $.each( fields, function(index,field){

                // width and diameter
                var multiField = field + '[]';
                var val = obj[field];
                var multiVal = obj[multiField];
                var multiValStr = $.isArray(multiVal) ? multiVal.join('-') : multiVal;

                if ( multiVal !== undefined ) {
                    query.push('&' + field + '=' + multiValStr);
                } else if ( val !== undefined ) {
                    query.push('&' + field + '=' + val);
                }
            });

            window.location = action + '?' + query.join('');
        });

    }

    function init_disable_empty_form_values() {

        var body = $('body');

        body.on('submit', '.js-remove-empty-on-submit', function (e) {
            disable_empty_form_values($(this));
        });

        body.on('submit', 'form#product-filters', function (e) {
            disable_empty_form_values($(this));
        });
    }

    /**
     *
     */
    function init_product_pagination() {

        var div = $('.gp-pagination.link-to-input');

        if (div.length < 1) {
            return;
        }

        var form_selector = div.attr('data-form');
        var input_name = div.attr('data-input-name');

        input_name = input_name === null ? 'page' : input_name;

        if (form_selector) {
            var form = $(form_selector);
            if (form.length < 1) {
                return;
            }
        } else {
            return;
        }

        // change hidden input field located elsewhere on the page then submit
        $('body').on('click', '.gp-pagination.link-to-input button', function (e) {
            e.preventDefault();

            if ($(this).hasClass('is-current')) {
                return;
            }

            // get the input on click, this is important...
            var input = form.find('input[name="' + input_name + '"]');
            var num = $(this).attr('data-num');
            num = num === undefined ? 1 : num;
            var remove_input = num === 1 || num === "1";

            if (remove_input) {
                if (input.length > 0) {
                    input.remove();
                }
                form.submit();
            } else {

                if (input.length < 1) {
                    form.append('<input type="hidden" name="' + input_name + '" value="' + num + '">');
                } else {
                    input.val(num);
                }

                form.submit();
            }
        });
    }

    /**
     * This is at the top right of the header (2 flags)
     */
    function init_country_select() {

        var body = $('body');

        // don't allow submitting the current item
        body.on('click', 'form#set-country .current-item button', function (e) {
            e.preventDefault();
        });

        // manually add hidden input on button click since form serialize doesn't seem to
        // take into account buttons with name and value
        body.on('click', 'form#set-country button', function (e) {

            var b = $(this);
            var form = b.closest('form');
            var value = b.attr('value');

            var ex = form.find('input[name="_country"]');
            if (ex.length > 0) {
                ex.val(value);
            } else {
                form.prepend('<input type="hidden" name="_country" value="' + value + '">');
            }
        });

        // Form Submit
        body.on('submit', 'form#set-country', function (e) {

            e.preventDefault();

            var form = $(this);

            send_ajax(form, {
                beforeSend: function () {
                },
                success: function (response) {
                    var success = response.success || false;
                    if (success) {
                        // hard re-load
                        window.location.reload(true);
                    }
                }
            });

        });
    }

    /**
     *
     */
    function init_select_2(wrapper) {

        wrapper = wrapper && wrapper.length > 0 && typeof wrapper === 'object' ? wrapper : $('body');

        var selects = wrapper.find($('select.make-select-2, .select-2-wrapper select'));

        $.each(selects, function () {

            var ele = $(this);

            window.select_2_indicator = false;
            ele.trigger('indicate_select_2_active');

            if (!window.select_2_indicator) {

                ele.on('indicate_select_2_active', function () {
                    window.select_2_indicator = true;
                });

                var args = ele.attr('data-select-args');
                args = args === undefined ? {} : jQuery.parseJSON(args);

                // add custom args (will override)
                // args = merge_objects(args, {});

                if (typeof ele.select2 !== "undefined") {
                    ele.select2(args);
                } else {
                    _console_log_('skipping select 2 - func undefined');
                }
            }

        });

    }

    /**
     *
     */
    function anchors_disabled() {
        $('body').on('click', 'a.disabled, button.disabled', function (e) {
            e.preventDefault();
        });
    }

    /**
     *
     */
    function init_product_sort_by() {

        $('body').on('change', 'select#product-sort', function (e) {

            var s = $(this);
            var form = $('#product-filters');

            if (form.length < 1) {
                return;
            }

            var val = s.val();
            var input = form.find('input[name="sort"]');

            if (val) {
                if (input.length > 0) {
                    input.val(val);
                } else {
                    form.append('<input type="hidden" name="sort" value="' + val + '">');
                }
            } else {
                if (input.length > 0) {
                    input.remove();
                }
            }

            form.submit();
        });

    }

    /**
     *
     */
    function ajax_reset_password_form() {
        $('body').on('submit', 'form#reset-password', function (e) {
            e.preventDefault();
            var form = $(this);
            send_ajax(form, {
                beforeSend: function () {
                    gp_body_loading_start();
                    empty_form_response_text(form);
                },
                success: function (response) {
                    gp_body_loading_end();

                    var success = response.success || false;
                    var text = response.response_text || '';

                    if (text) {
                        set_form_response_text(form, response.response_text);
                    } else if (!success) {
                        // fallback error message
                        set_form_response_text(form, 'An error occurred');
                    }

                }
            });

        });
    }

    /**
     *
     */
    function ajax_forgot_password_form() {

        $('body').on('submit', 'form#forgot-password', function (e) {

            e.preventDefault();

            var form = $(this);

            send_ajax(form, {
                beforeSend: function () {
                    gp_body_loading_start();
                    empty_form_response_text(form);
                },
                success: function (response) {
                    gp_body_loading_end();
                    set_form_response_text(form, response.response_text);
                }
            });

        });

    }

    /**
     *
     */
    function ajax_logout_buttons() {

        $('body').on('click', '.ajax-logout', function (e) {

            e.preventDefault();

            var btn = $(this);
            var data = btn.attr('data-ajax');
            data = data ? jQuery.parseJSON(data) : {};

            var ajax = {
                data: data,
                url: gp_if_set(data, 'url'),
                beforeSend: function () {
                    gp_body_loading_start();
                },
                error: function () {
                    gp_ajax_encountered_error();
                },
                success: function (response) {
                    gp_body_loading_end();

                    var aa = response.alert || '';
                    var success = response.success || false;

                    if (success) {
                        window.location.reload(true);
                    }

                    if (aa) {
                        alert(aa);
                    }

                    if (!success && !aa) {

                        var logout_url = gp_if_set(data, 'logout_url');
                        if (logout_url) {
                            window.location = logout_url;
                        } else {
                            // this shouldn't happen ever, but doesn't hurt to have a fallback I suppose.
                            // the user should know this in case they are on a public computer.
                            var msg = 'There may have been an error while trying to log you out. Please re-load the page and/or clear your browser cache to ensure you are logged out.';
                            alert(msg);
                        }
                    }
                }
            };

            send_ajax(null, ajax);
        });
    }

    /**
     *
     */
    function init_compare_products_ajax() {

        var body = $('body');
        var counter = 0;

        // body.on('change', '.gp-lightbox.lb-compare', function () {
        //     console.log('lb change21222');
        // });

        // hover image show title
        body.on('mouseenter', '.compare-queue .cq-item .background-image', function () {

            var img = $(this);
            var wrap = img.closest('.compare-queue');
            var part_number = img.attr('data-part-number');

            var item = img.closest('.cq-item');
            var title = wrap.find('.cq-hover-titles .item[data-part-number="' + part_number + '"]');

            var titles = wrap.find('.cq-hover-titles .item');

            var items = wrap.find('.cq-item');
            $.each(items, function () {
                var i = $(this);
                if (i.is(item)) {
                    i.addClass('is-current');
                } else {
                    i.removeClass('is-current');
                }
            });

            $.each(titles, function () {
                var t = $(this);
                if (t.is(title)) {
                    t.addClass('is-current');
                } else {
                    t.removeClass('is-current');
                }
            });
        });

        // no mouseleave needed. we mark last item current when lightbox opens..
        // last item hovered is always the one that shows the title
        // body.on('mouseleave', '.compare-queue .cq-item .background-image', function(){
        //     console.log('cq mouseleave');
        //
        //     var img = $(this);
        //     var wrap = img.closest('.compare-queue');
        //
        //     var titles = wrap.find('.cq-hover-titles .item');
        //     var items = wrap.find('.cq-item');
        //
        //     setTimeout(function(){});
        //
        //     var c1 = 0;
        //     $.each(titles, function(){
        //         c1++;
        //         var t = $(this);
        //         if ( c1 === items.length ) {
        //             t.addClass('is-current');
        //         } else {
        //             t.removeClass('is-current');
        //         }
        //     });
        //
        //     var c2 = 0;
        //     $.each(titles, function(){
        //         c2++;
        //         var t = $(this);
        //         if ( c2 === titles.length ) {
        //             t.addClass('is-current');
        //         } else {
        //             t.removeClass('is-current');
        //         }
        //     });
        // });

        // ajax submit
        body.on('click', '.compare-products', function (e) {

            e.preventDefault();

            var btn = $(this);
            var data = btn.attr('data-ajax');
            data = data ? jQuery.parseJSON(data) : {};

            var _lb = btn.closest('.gp-lightbox');
            var in_lightbox = _lb.length > 0;

            var args = {
                data: data,
                url: gp_if_set(data, 'url'),
                beforeSend: function () {
                    gp_body_loading_start();
                },
                success: function (response) {

                    counter++;
                    var lightbox_id = 'compare-lightbox-' + counter;

                    gp_body_loading_end();

                    var lightbox = response.lightbox || '';

                    // if the button clicked was inside a lightbox, then just replace the content from
                    // within the same lightbox
                    if (in_lightbox) {
                        gp_lightbox_close(_lb);
                        _lb.remove();
                    }

                    if (lightbox !== '') {
                        var lb = gp_lightbox_create({
                            add_class: 'lb-compare',
                            lightbox_id: lightbox_id,
                            content: lightbox,
                            close_btn: 1,
                            add_lb_content: 1
                        });
                        gp_lightbox_add_to_dom(lb);
                        gp_lightbox_open(lb);
                    }

                }
            };

            send_ajax(null, args)

        });

    }

    /**
     *
     */
    function home_shop_nav() {

        var body = $('body');

        // .shop-nav is in 2 places, one is for mobile
        body.on('click', '.shop-nav button', function (e) {

            e.preventDefault();

            var b = $(this);
            var p = b.parent();

            var shop_v = $('.home-top .shop-right .by-vehicle');
            var shop_t = $('.home-top .shop-right .by-tire');
            var btn_v = $('.shop-nav .shop-vehicle');
            var btn_t = $('.shop-nav .shop-tire');

            if (p.hasClass('shop-vehicle')) {

                if (btn_v.length > 0) {
                    btn_v.addClass('active');
                }

                if (btn_t.length > 0) {
                    btn_t.removeClass('active');
                }

                if (shop_v.length > 0) {
                    shop_v.removeClass('hidden');
                }

                if (shop_t.length > 0) {
                    shop_t.addClass('hidden');
                }

            } else if (p.hasClass('shop-tire')) {

                if (btn_t.length > 0) {
                    btn_t.addClass('active');
                }

                if (btn_v.length > 0) {
                    btn_v.removeClass('active');
                }

                if (shop_t.length > 0) {
                    shop_t.removeClass('hidden');
                }

                if (shop_v.length > 0) {
                    shop_v.addClass('hidden');
                }

            }

            home_top_responsive_fix();

        });

    }

    /**
     * background image in the home top section needs to
     * go halfway down to the bottom of the shop nav when on mobile
     */
    function home_top_responsive_fix() {

        var mob = $('.home-top .mobile-indicator');
        var bg = $('.home-top .background-image');

        if (mob.length > 0 && bg.length > 0) {

            if (mob.length > 0 && mob.is(':visible')) {

                var btm = $('.home-top').find('.shop-right');

                if (btm.length > 0) {

                    var height = parseInt(parseInt(btm.css('height')) / 2);
                    bg.css('bottom', height + 'px');
                }
            } else {
                bg.css('bottom', 0);
            }
        }
    }

    /**
     *
     * @param div
     * @param anim_time
     */
    function scroll_to_div(div, anim_time) {

        anim_time = anim_time || 300;
        if (div.length > 0) {
            var offset = parseInt(div.offset().top);

            $('body,html').stop().animate({
                scrollTop: offset,
                duration: anim_time,
                complete: function () {
                }
            });
        }
    }

    /**
     *
     */
    function fixed_header_add_class_for_top_container() {

    }

    function init_fixed_header() {

        var header = $('.site-header');

        if (header.length < 1) {
            return;
        }

        var header_top = header.find('.header-top');

        if (header_top.length < 1) {
            return;
        }

        var ht_height = parseInt(header_top.css('height'));


    }

    /**
     * If we keep the top image on the product archive pages,
     * then we could scroll down to the main content on doc ready.
     * I think the delay might be annoying, but using filters often is also
     * annoying when you are brought up to the top image every time.
     */
    function product_page_scroll_down() {

        var page = $('.products-archive-page');

        if (page.length > 0) {

            var div = $('.main-content');
            if (div.length > 0) {
                scroll_to_div(div);
            }
        }
    }

    /**
     *
     * @param trigger_selector - selector inside of an item
     * @param content_selector - selector inside of an item
     * @param item_selector
     */
    var init_accordion = function (trigger_selector, content_selector, item_selector) {

        var body = $('body');
        // var items = $(item_selector);
        // var triggers = $(trigger_selector);
        // var contents = $(content_selector);

        var self = this;

        self.init_items = function () {
            var items = body.find(item_selector);

            $.each(items, function () {
                var item = $(this);
                var content = item.find(content_selector);
                if (content.length > 0) {

                    if (content.is(':visible')) {
                        item.addClass('visible').removeClass('hidden');
                    } else {
                        item.addClass('hidden').removeClass('visible');
                    }
                }
            });
        };

        self.init_items();

        self.is_open = function (item) {
            return item.length > 0 && item.hasClass('visible');
        };

        self.toggle = function (item) {
            if (self.is_open(item)) {
                self.close(item);
            } else {
                self.open(item);
            }
        };

        self.open = function (item) {
            if (item.length > 0) {
                var content = item.find(content_selector);

                if (content.length > 0) {
                    content.slideDown({
                        duration: 250,
                        queue: false
                    });
                }

                item.addClass('visible').removeClass('hidden');
            }
        };

        self.close = function (item) {
            if (item.length > 0) {

                var content = item.find(content_selector);

                if (content.length > 0) {
                    content.slideUp({
                        duration: 200,
                        queue: false
                    });
                }

                item.addClass('hidden').removeClass('visible');
            }
        };

        body.on('click', trigger_selector, function (e) {
            var item = $(this).closest(item_selector);
            if (item.length > 0) {
                e.preventDefault();
                self.toggle(item);
            }
        });

    }

    function faq_init() {

        var body = $('body');

        // individual items
        var accordion = new init_accordion('.question', '.answer', '.faq-item');

        // expand all
        body.on('click', '.faq-controls .expand-all', function (e) {
            var items = $('.faq-item');
            $.each(items, function (e) {
                accordion.open($(this));
            });

            $(this).closest('.faq-controls').addClass('all-visible').removeClass('all-hidden');
        });

        // collapse all
        body.on('click', '.faq-controls .collapse-all', function (e) {
            var items = $('.faq-item');
            $.each(items, function (e) {
                accordion.close($(this));
            });

            $(this).closest('.faq-controls').addClass('all-hidden').removeClass('all-visible');
        });

    }

    /**
     *
     */
    function fancybox_init() {

        // $().fancybox({
        //     selector : '[data-fancybox="gallery"]',
        //     loop     : true
        // });

    }

    /**
     *
     */
    function reviews_list_show_more() {

        var body = $('body');

        body.on('click', '.pr-more-trigger', function (e) {

            var wrap = $(this).closest('.product-reviews');
            if (wrap.length > 0) {
                var hidden = $('.pr-item.hidden');
                $.each(hidden, function (e) {
                    var item = $(this);
                    item.removeClass('hidden');
                });

                // hide button, wrap should be .parent()
                var btn_wrap = $(this).closest('.pr-more');
                if (btn_wrap.length > 0) {
                    btn_wrap.hide();
                }

            }

        });

    }

    /**
     * due to added hidden columns in some tables, our col span
     * calculations no longer work, so the col span has to be equal
     * to the number of visible columns.
     *
     * may as well just generalized this into all td.js-full-width-col-span or something like that.
     */
    function fix_product_table_no_results_col_span(){

        var self = this;

        var rows = $('.product-table table .no-results-row');

        if ( rows.length < 1 ) {
            return;
        }

        self.fix_col_span = function(){

            $.each(rows, function(){

                var count = 0;

                $.each($(this).closest('table').find('tr.type-header th'), function(){
                    if ( $(this).is(':visible') ) {

                        // dont include the button column which shows on desktop only and has
                        // no label in the header row
                        if ( $(this).is('.cell-add_to_cart') === false ) {
                            count++;
                        }
                    }
                });

                var cell = $(this).find('> td');

                if ( cell.length > 0 ) {
                    cell.attr('colspan', count );
                }

            });
        };

        self.fix_col_span();

        $(window).on('resize', function(){
            self.fix_col_span();
        });
    }

    /**
     * add a class to a wrapper on single product pages
     * when table has more width than one if its parents,
     * so we can show an indicator to the user to scroll right
     */
    function product_table_overflow_indicator(){

        var self = this;

        var wrap = $('.product-table');

        if ( wrap.length > 0 ) {

            this.check_overflowed = function( wrap ){
                var table = wrap.find('.table-overflow table');

                if ( table.length > 0 ) {

                    var outside = parseInt( wrap.css('width') );
                    var inside = parseInt( table.css('width') );
                    var cls = 'table-is-overflowed';

                    // give it some padding otherwise rounding errors may cause issues
                    if ( ( inside - 2 ) > outside ) {
                        wrap.addClass(cls);
                    } else {
                        wrap.removeClass(cls);
                    }
                }
            };

            $.each(wrap, function(){
                self.check_overflowed( wrap );
            });

            $(window).on('resize', function(){
                $.each(wrap, function(){
                    self.check_overflowed( wrap );
                });
            });
        }
    }

    /**
     * Only works if the set country form is loaded into the header. Works by
     * trigger a click on one of those buttons, which submits nonce data etc
     */
    function init_select_country_from_anywhere(){

        $('body').on('click', '.js-set-country-btn', function(e){

            e.preventDefault();
            var toClick;
            var country = $(this).attr('data-country');
            country = country && country.toLowerCase();

            switch( country ){
                case 'ca':
                    toClick = $('#set-country-trigger-ca');
                    break;
                case 'us':
                    toClick = $('#set-country-trigger-us');
                    break;
                default:
                    console.error( "Cannot switch to country", country );
            }

            if ( toClick && toClick.length > 0 ) {
                toClick.click();
            } else {
                console.error( "Switch to country button not found." );
            }
        });
    }

    function top_image_vehicle_or_size(){

        var parent = $('.top-image-vehicle-lookup');

        if ( parent.length > 0 && parent.find('form.tire-size-select').length > 0 ) {

            var set_by_vehicle = function(e){
                parent.find('.heading .by-vehicle').addClass('active');
                parent.find('.heading .by-size').removeClass('active');
                parent.find('form.vehicle-lookup').show();
                parent.find('form.tire-size-select').hide();
            };

            var set_by_size = function(e){
                parent.find('.heading .by-vehicle').removeClass('active');
                parent.find('.heading .by-size').addClass('active');
                parent.find('form.vehicle-lookup').hide();
                parent.find('form.tire-size-select').show();
            };

            parent.find('.heading .by-vehicle').on('click', set_by_vehicle);
            parent.find('.heading .by-size').on('click', set_by_size);
        }
    }

    /**
     * DOC READY
     */
    jQuery(document).ready(function ($) {

        background_image_img_tags();

        top_image_vehicle_or_size();

        init_select_country_from_anywhere();

        /**
         * javascript select boxes to allow for better
         * styling, multi-select, and search if we want it
         */
        init_select_2();

        /**
         * when some tables have overflow, indicate that they can be scrolled
         */
        product_table_overflow_indicator();

        /**
         * A col span needs to adjust dynamically in some product tables.
         */
        fix_product_table_no_results_col_span();

        /**
         * Simple accordion thing just for printing test data
         */
        dev_alert();

        /**
         * send ajax and re-load page when clicking flag at top right
         */
        init_country_select();

        /**
         * optional video on homepage.. make it fit parent container like a background image.
         */
        init_home_video();

        /**
         * mobile menu clone, open, close
         */
        init_mobile_menu();

        /**
         * nothing to see here..
         */
        init_product_import();

        /**
         * Sort by option on product archive pages
         */
        init_product_sort_by();

        /**
         *
         */
        init_fixed_header();

        /**
         * reviews form ajax
         */
        init_reviews_page();

        /**
         * Bind lets us add json encoded data attributes
         * to certain elements, and control what they do
         * upon click or other events, from outside of javascript.
         */
        js_bind_init();

        /**
         * Some code for general ajax forms.
         */
        cw_ajax_init();

        /**
         * some ajax code for general forms that don't need any highly specific operations
         * for sending/receiving ajax. this is kind of what cw_ajax_init() was built for but..
         * things got kind of convoluted, and our general form html changed a bit, therefore, we're just
         * going to leave that in place, because it works fine for what its meant to do. This should be
         * more light weight, and work with more of our forms.
         */
        new_ajax_general();

        /**
         * Handles (probably) all Add to Cart Buttons/Forms.
         */
        ajax_add_to_cart_init();

        /**
         * <a> tags usually
         */
        ajax_logout_buttons();

        /**
         *
         */
        ajax_forgot_password_form();

        /**
         *
         */
        ajax_reset_password_form();

        /**
         * Select a vehicle based on Make, Model, Year, Trim
         * and Fitment, and then select a page: Tires/Rims/Packages.
         * This is the primary vehicle select form on the homepage
         * and often shown on other pages from within a lightbox.
         */
        vehicle_lookup_init();

        /**
         * The fitment/size <select> that is shown with vehicles sometimes.
         */
        init_select_vehicle_size();

        /**
         * Product sidebar filters on Tires/Rims/Packages
         */
        init_product_filters();

        /**
         *
         */
        // product_page_scroll_down();

        /**
         * Remove empty form values from some forms with method="get" so we don't
         * clutter the URL
         */
        init_disable_empty_form_values();

        rims_by_size_form();

        /**
         * Rims/Tires/Packages product loop
         */
        init_product_pagination();

        /**
         * Set the number of items per page for product
         * archive pages: Rims/Tires/Packages
         */
        init_per_page_ajax();

        /**
         *
         */
        init_compare_products_ajax();

        /**
         * Change the quantity of a cart item
         * on the Cart page.
         */
        cart_quantity_select_form();

        /**
         * On the Packages product archive page,
         * show products details in a popup when clicking
         * on "Details"
         */
        init_lightboxes();

        /**
         * On Tires Landing page users can select a
         * tire Width/Profile/Diameter and then search. This
         * javascript prevents users from selecting tire sizes
         * where no result would be found.
         */
        init_tire_size_select();

        /**
         * Update cart buttons (mainly on the cart page) .. like quantity,
         * and add/remove some items, but not "add to cart" buttons on products.
         */
        init_ajax_update_cart_handlers();

        /**
         * Select Tires or Rims by Vehicle and Size (tires/rims landing page)
         */
        init_vehicle_tabs();

        /**
         * Tabs.. click a trigger, show one item, hide the rest.
         */
        init_tabs();

        /**
         * prevent default action on some anchor tags
         */
        anchors_disabled();

        /**
         * ajax handler, and some javascript stuff
         */
        checkout_form();

        /**
         *
         */
        init_sign_in_form();


        /**
         *
         */
        init_add_coupon_form();


        /**
         *
         */
        reviews_list_show_more();

        /**
         * image gallery stuff (lightbox)
         */
        fancybox_init();

        /**
         * image gallery stuff (lightbox)
         */
        faq_init();

        /**
         * simple class toggle on homepage, show 'shop by vehicles' or 'tires by size'
         */
        home_shop_nav();

        /**
         * adds button to some tables to export to CSV
         */
        add_admin_table_controls();

        /**
         * taking care of something not easily done with css
         */
        home_top_responsive_fix();

        $(window).on('resize', function () {
            home_top_responsive_fix();
        });

        $(window).on('scroll', function () {
            home_top_responsive_fix();
        });

        // We clone for lightboxes..
        $(window).on('gp_clone_complete', function () {
            vehicle_lookup_init();
            init_tire_size_select();
            init_select_2();
        });

        // Lots of things return content after Ajax
        // some functions bind their events to the body, others
        // need to be called again once items may have been introduced to the page.
        $(window).on('gp_ajax_complete', function () {
            init_per_page_ajax();
            js_bind_init();
            cw_ajax_init();
            cart_quantity_select_form();
            init_select_2();
        });

        // button type reset not working well with select 2 elements..
        // $('body').on('click', 'form .item-reset-form button', function (e) {
        //     console.log('reset');
        //     e.preventDefault();
        //     var form = $(this).closest('form');
        //     gp_form_reset( form );
        // });

    });

}(jQuery));




