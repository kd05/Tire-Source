/**
 *
 * @param form
 * @param options
 * @constructor
 */
var CW_Ajax = function (form, options) {

    var self = this;
    self.form = form;
    self.id = self.form.attr('id');
    self.options = options;

    // call construct manually... to allow modifying prototypes beforehand
    // this.construct();
};

/**
 * this looks like its not being used but apparently it is...
 * see JS_Bind or something like that.
 */
CW_Ajax.prototype.handle_before_send = function () {
    var self = this;
    self.form.addClass('ajax-loading');
};

/**
 *
 * @param response
 */
CW_Ajax.prototype.handle_delete_within = function (response) {

    var self = this;

    var delete_within = response.delete_within || false;
    var type = $.type(delete_within);

    if (type === 'array' || type === 'object') {
        $.each(delete_within, function (k, v) {
            var ele = self.form.find(v);
            if (ele.length > 0) {
                ele.remove();
            }
        });
    }

};

/**
 *
 * @param response
 */
CW_Ajax.prototype.handle_response_message = function (response) {

    _console_log_('handle msg');

    var self = this;
    var response_div = self.form.find('.ajax-response');
    if (response_div.length < 1) {
        return;
    }
    // output should have p tags
    var output = response.output || '';

    // plain text might not have p tags
    if (output === '') {
        var plain_text = response.plain_text || '';
        if (plain_text !== '') {
            output = '<p>' + plain_text + '</p>';
        }
    }

    var success = response.success || '';
    var cls;
    if (success === true) {
        cls = 'success';
    } else if (success === false) {
        cls = 'error';
    } else {
        cls = 'notice';
    }

    if (output === '') {
        cls += ' empty';
    } else {
        cls += ' not-empty';
    }

    response_div.empty().removeClass('error success notice empty not-empty').append(output).addClass(cls);
};

/**
 *
 */
CW_Ajax.prototype.handle_submit = function () {

    var self = this;

    if (self.form.hasClass('ajax-loading')) {
        return;
    }

    var url = self.form.attr('action');
    var data = self.form.serializeArray();
    var fd = new FormData();

    send_ajax( self.form, {
        beforeSend: function () {

            // although it may look like this is not in use, it is used via
            // json encoded data attributes that perform actions on events, such as this one.
            self.form.trigger('beforeSend');

            self.form.addClass('ajax-loading');

            // this only applies to some of the forms using this code
            empty_form_response_text( self.form );

            gp_body_loading_start();
        },
        success: function (response) {

            gp_body_loading_end();

            // although it may look like this is not in use, it is used via
            // json encoded data attributes that perform actions on events, such as this one.
            self.form.trigger('afterSend');

            self.form.removeClass('ajax-loading');

            var success = gp_if_set( response, 'success', false );

            // this function is a newer way of updating form response
            // (ie. it uses updated general styles)
            // that's why we have both set_form_response_text() and handle_response_message()
            // generally one or the other is used.
            // also, set_form_response_text() should do nothing if response_text is empty
            var response_text = response.response_text || '';
            set_form_response_text( self.form, response_text );

            // response message
            self.handle_response_message(response);

            // response message
            self.handle_delete_within(response);

            if ( success && self.form.attr('data-reset-success' ) ) {
                self.form[0].reset();
            }

            // this is used only a few times. it can be a large amount of html printed to the page, but
            // it doesn't get removed upon submitting again (because we can't target it if we don't know what it is)
            var html_after_form = response.html_after_form || '';
            if (html_after_form !== '') {
                self.form.after(html_after_form);
            }

            // in case other ajax forms are in the response
            $(window).trigger('gp_ajax_complete');

            _console_log_(response);
        }
    });
};

/**
 *
 */
CW_Ajax.prototype.construct = function () {

    var self = this;

    if (self.form.hasClass('js-init')) {
        return false;
    }

    self.form.addClass('js-init');


    self.form.on('submit', function (e) {
        e.preventDefault();
        self.handle_submit();
    });
};

function cw_ajax_init(){
    var forms = $('form.cw-ajax');
    $.each(forms, function () {

        var form = $(this);

        if (form.hasClass('js-init')) {
            return true; // continue
        }

        var opt = form.attr('data-options');
        options = opt !== undefined ? opt : {};

        var fr = new CW_Ajax(form, options);
        fr.construct();
    });
}
