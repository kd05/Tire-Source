var Per_Page_Ajax = function (form) {
    this.form = form;
    // css class for when ajax is running. dont use .ajax-loading, might be css attributed to it.
    this.loading_class = 'ajax_load';
};

/**
 *
 */
Per_Page_Ajax.prototype.handle_submit = function () {

    var self = this;

    send_ajax( self.form, {
        beforeSend: function () {
            gp_body_loading_start();
            self.form.find('select').prop('disabled', true);
        },
        success: function (response) {
            gp_body_loading_end();
            self.form.find('select').prop('disabled', false);

            // default to true here. all we do is submit the filters form anyways.
            // or default to false, it really shouldn't matter...
            var success = response.hasOwnProperty('success') ? response.success : true;

            if (success) {
                // this is normally a form located elsewhere on the page
                var to_submit_selector = self.form.attr('data-submit');
                if (to_submit_selector) {
                    var to_submit = $(to_submit_selector);
                    if (to_submit.length > 0) {

                        // remove input for page number if changing the number of items showing per page
                        var page = to_submit.find('input[name="page"]');
                        if ( page.length > 0 ) {
                            page.remove();
                        }
                        to_submit.submit();
                    }
                } else {
                    var reload = self.form.attr('data-reload');
                    if ( reload ) {
                        window.location.reload(true);
                    }
                }
            }
        }
    });
};

/**
 *
 */
Per_Page_Ajax.prototype.init = function () {

    if (this.form.length < 1) {
        return;
    }

    if (this.form.hasClass('ppa-init')) {
        return;
    }

    var self = this;
    self.form.addClass('ppa-init');

    self.form.on('submit', function (e) {
        e.preventDefault();
        self.handle_submit();
    });

    self.form.on('change', 'select', function () {
        self.form.submit();
    });

};

/**
 *
 */
function init_per_page_ajax() {

    var forms = $('form.per-page-ajax');
    $.each(forms, function () {
        var obj = new Per_Page_Ajax($(this));
        obj.init();
    });

}

