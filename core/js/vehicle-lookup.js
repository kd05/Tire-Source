/**
 * Vehicle lookup form. Make/Year/Model/Trim/Fitment etc.
 *
 * Note: order matters. Make, then Year, then Model, etc.
 *
 * @param form
 * @constructor
 */
var Vehicle_Lookup = function (form) {
    this.form = form;
    this.cache = {};
};

/**
 * send a data object to server. the response should indicate what action is taking place so
 * we know what to do with the data based on the response only. Ie. tell the server to "get models",
 * and the server responds not just with a list of models, but also with "here are the models", then we'll
 * be able to handle the response independent of the function that originally sent it. This makes it easier
 * to channel all request through the same ajax function.
 *
 * @param data
 */
Vehicle_Lookup.prototype.send_ajax = function (data) {

    _console_log_('vl send ajax');

    var self = this;
    var arr = self.form.serializeArray();
    var d = {}; // data object (not array) to pass to ajax
    $.each(arr, function () {
        d[this.name] = this.value;
    });

    // data points to add, they will override data points from serializeArray()
    if (data !== undefined) {
        $.each(data, function (k, v) {
            d[k] = v;
        });
    }

    send_ajax( self.form, {
        data: d,
        beforeSend: function () {
            _console_log_('vl before send');
            empty_form_response_text( self.form );
            gp_body_loading_start();
            self.make_button_( 'disabled' );
        },
        success: function (response) {

            gp_body_loading_end();

            _console_log_('vl handle response');
            _console_log_(response);

            // var item_submit = self.form.find('.item-submit');

            var response_type = response.response_type || '';
            var response_text = response.response_text || '';
            var options = response.options || undefined;
            var cache_args = response.cache_args || {};
            var sub_sizes = response.sub_sizes || false;

            if ( response_text !== '' ) {
                set_form_response_text( self.form, response_text );
            }

            _console_log_('response type', response_type );
            _console_log_('options', options );

            var url = response.set_url || false;
            if ( url !== false ) {
                _console_log_('set URL: ' + url);
                self.set_url(url);
            }

            // response type is the same as request type. So if request/response type is models, that means
            // we're asking for models (which means years was changed). When fitments select changes
            // the request/response type is get_url, which updates the button url.
            switch (response_type) {
                case 'years':
                    self.set_years(options);
                    self.set_cache('years', cache_args, options);
                    break;
                case 'models':
                    self.set_models(options);
                    self.set_cache('models', cache_args, options);
                    break;
                case 'trims':
                    self.set_trims(options);
                    self.set_cache('trims', cache_args, options);
                    break;
                case 'fitments':
                    self.set_fitments(options);
                    self.set_cache('fitments', cache_args, options);
                    break;
                case 'get_url':
                    // if response.set_url is set, we'll set the URL, regardless of response_type
                    break;
                default:
                    _console_log_('default switch');
                    break;
            }

            // may need to do this last-ish... in case we check if the fitments list is populated... not totally sure though.
            if ( sub_sizes !== false ) {
                self.set_sub_sizes(sub_sizes);
            }
        }
    });

};

/**
 *
 * @param ele
 * @param html
 * @param animate
 */
Vehicle_Lookup.prototype.set_select = function (ele, html, animate ) {

    var self = this;

    _console_log_('set_select', html );

    // default to false (although... we normally set it to true)
    animate = animate === undefined ? false : animate;

    if ( ele.length > 0 ) {

        var wrap = ele.closest('.select-2-wrapper');
        if ( wrap.length > 0 ) {
            if ( animate ) {
                ele.addClass('updated');
                setTimeout(function(){
                    ele.removeClass('updated');
                }, 300);
            }
        }

        ele.empty().append(html);

        // mark "empty" items..
        setTimeout(function(){
            self.identify_select_empty( ele );
        }, 1);
    }
};


/**
 *
 * @param data
 * @param reset
 */
Vehicle_Lookup.prototype.set_trims = function (data, reset ) {

    reset = reset || false;
    data = data || {};

    var html = '';
    var animate = false;
    html += '<option value="">Trim</option>';

    $.each(data, function (k, v) {
        animate = true;
        html += '<option value="' + k + '">' + v + '</option>';
    });

    this.set_select( this.inputs.trims, html, animate );
};

/**
 *
 * @param data
 * @param reset
 */
Vehicle_Lookup.prototype.set_fitments = function (data, reset) {

    reset = reset || false;
    data = data || {};

    var placeholder = 'OEM Fitments';
    var count = data ? Object.keys( data ).length : 0;
    var count_text = count ? count + ' ' : 'No ';
    var html = '';

    if ( reset ) {
        html += '<option value="">' + placeholder + '</option>';
        this.set_select( this.inputs.fitments, html, true );
        return;
    } else {
        // ie. "7 OEM Fitments"
        html += '<option value="">' + count_text + placeholder + '</option>';
    }

    // may or may not add anything
    $.each(data, function (k, v) {
        html += '<option value="' + k + '">' + v + '</option>';
    });

    this.set_select( this.inputs.fitments, html, true );
};

/**
 * Consider possible states:
 *
 * 1. Fitment is selected but no sub sizes exist.
 * 2. Fitment is selected and sub sizes exist.
 * 3. Fitment is not selected, therefore we can't show sub sizes.
 *
 * @param data
 * @param reset
 */
Vehicle_Lookup.prototype.set_sub_sizes = function (data, reset) {

    reset = reset || false;
    data = data || {};

    var placeholder = 'Aftermarket Fitments';
    var count = data ? Object.keys( data ).length : 0;
    var count_text = count ? count + ' ' : 'No ';
    var html = '';

    if ( reset ) {
        html += '<option value="">' + placeholder + '</option>';
        this.set_select( this.inputs.subs, html, true );
        return;
    } else {
        // ie. "7 Aftermarket Fitments"
        html += '<option value="">' + count_text + placeholder + '</option>';
    }

    // may or may not add anything
    $.each(data, function (k, v) {
        html += '<option value="' + k + '">' + v + '</option>';
    });

    this.set_select( this.inputs.subs, html, true );
};

/**
 *
 * @param data
 * @param reset
 */
Vehicle_Lookup.prototype.set_years = function (data, reset ) {

    reset = reset || false;
    data = data || {};

    _console_log_('set years............');
    _console_log_(data);

    var html = '';
    var animate = false;
    html += '<option value="">Year</option>';

    var arr = [];

    $.each(data, function(i,j){
        arr.push([i,j]);
    });

    arr.sort();
    arr.reverse();

    $.each(arr, function (k, v) {
        animate = true;
        html += '<option value="' + v[0] + '">' + v[1] + '</option>';
    });

    this.set_select( this.inputs.years, html, animate );
};

/**
 *
 * @param data
 * @param reset
 */
Vehicle_Lookup.prototype.set_models = function (data, reset ) {

    reset = reset || false;
    data = data || {};

    var html = '';
    var animate = false;
    html += '<option value="">Model</option>';

    $.each(data, function (k, v) {
        animate = true;
        html += '<option value="' + k + '">' + v + '</option>';
    });

    this.set_select( this.inputs.models, html, animate );
};

/**
 * Note: cache should be an optional optimization to prevent unnecessary hits to the server
 *
 * Other note: we may have to turn it off...
 *
 * @param type
 * @param args ie. {make: make, model: model...}, object keys might be ignored.
 */
Vehicle_Lookup.prototype.get_cache = function (type, args) {

    // turning this off because I didn't properly anticipate needing to also cache
    // error responses associated with certain states of the form.
    // must return undefined, not null.
    return undefined;

    var str = this.cache_string(args);
    var value = undefined;

    if (this.cache.hasOwnProperty(type)) {
        value = this.cache[type][str];
        return value; // might be undefined
    }

    return value;
};

/**
 *
 * @param type
 * @param args
 * @param value
 */
Vehicle_Lookup.prototype.set_cache = function (type, args, value) {
    // string doesn't have to look pretty we just need to ensure its unique
    // and can be replicated easily when we use the get_cache() method
    var str = this.cache_string(args);
    if (this.cache.hasOwnProperty(type) === false) {
        this.cache[type] = {};
    }
    this.cache[type][str] = value;
};

/**
 *
 * @param args
 */
Vehicle_Lookup.prototype.cache_string = function (args) {
    var str = '';
    $.each(args, function (k, v) {
        // args are likely to have some empty values
        v = v || '';
        if (v !== '') {
            str += '_' + v + '_';
        }
    });
    return str;
};

/**
 *
 * @param make
 * @param year
 */
Vehicle_Lookup.prototype.get_models_by_year = function (make, year) {

    var self = this;

    // reset the options
    self.set_models(false, true);

    // check browser cache
    var cache = self.get_cache('models', [make, year]);
    if (cache !== undefined) {
        _console_log_('cache results found, setting models to a cached result');
        self.set_models(cache);
        return;
    }

    // send ajax
    var data = {
        request_type: 'models'
    };
    self.send_ajax(data);
};

/**
 *
 * @param make
 */
// Vehicle_Lookup.prototype.get_models = function (make) {
//
//     var self = this;
//
//     // reset the options
//     self.set_models(false, true );
//
//     // check browser cache
//     var cache = self.get_cache('models', [make]);
//     if (cache !== undefined) {
//         _console_log_('cache results found, setting models to a cached result');
//         self.set_models(cache);
//         return;
//     }
//
//     // send ajax
//     var data = {
//         request_type: 'models',
//     };
//     self.send_ajax(data);
// };

/**
 *
 * @param make
 */
Vehicle_Lookup.prototype.get_years = function (make) {

    var self = this;

    // reset the options
    self.set_years(false, true);

    // check browser cache
    var cache = self.get_cache('years', [make]);
    if (cache !== undefined) {
        self.set_years(cache);
        return;
    }

    // send ajax
    var data = {
        request_type: 'years'
    };
    self.send_ajax(data);
};

/**
 *
 * @param make
 * @param model
 * @param year
 */
Vehicle_Lookup.prototype.get_trims = function (make, model, year) {

    var self = this;

    // reset the options
    self.set_trims(false, true);

    // check browser cache
    var cache = self.get_cache('trims', [make, model, year]);
    if (cache !== undefined) {
        _console_log_('found cache for trims');
        self.set_trims(cache);
        return;
    }

    // send ajax
    var data = {
        request_type: 'trims'
    };
    self.send_ajax(data);
};

/**
 *
 * @param make
 * @param model
 * @param year
 * @param trim
 */
Vehicle_Lookup.prototype.get_fitments = function (make, model, year, trim) {

    var self = this;

    // reset the options
    self.set_fitments(false, true);

    // check browser cache
    var cache = self.get_cache('fitments', [make, model, year, trim]);
    if (cache !== undefined) {
        _console_log_('found fitment cache');
        self.set_fitments(cache);
        return;
    }

    // send ajax
    var data = {
        request_type: 'fitments',
    };

    self.send_ajax(data);
};

/**
 * By default all of our <select> elements have the first option
 * which acts as a label/placeholder. So... we'll call the
 * <select> element "empty" if none of the <options> have non-false
 * like values.
 *
 * @param ele
 */
Vehicle_Lookup.prototype.identify_select_empty = function( ele ) {

    if ( ele.length > 0 ) {

        var opt = ele.find('option');
        empty = true;
        $.each(opt, function(x,y){
            if ( $(this).val() ) {
                empty = false;
            }
        });

        add_class_to = this.get_element_to_add_empty_class( ele );
        if ( empty ) {
            ele.prop('disabled', true );
            add_class_to.addClass(this.empty_class).removeClass(this.not_empty_class);
        } else {
            ele.prop('disabled', false );
            add_class_to.addClass(this.not_empty_class).removeClass(this.empty_class);
        }
    }
};

/**
 *
 */
Vehicle_Lookup.prototype.get_element_to_add_empty_class = function( ele ) {
    var wrap = ele.closest('.select-2-wrapper');
    return wrap.length > 0 ? wrap : ele;
};

/**
 *
 */
Vehicle_Lookup.prototype.init = function () {

    var self = this;
    _console_log_('vl init');

    self.inputs = {};
    self.inputs.shop_for = self.form.find('#vl-shop_for');
    self.inputs.makes = self.form.find('#vl-makes');
    self.inputs.models = self.form.find('#vl-models');
    self.inputs.years = self.form.find('#vl-years');
    self.inputs.fitments = self.form.find('#vl-fitments');
    self.inputs.trims = self.form.find('#vl-trims');
    self.inputs.subs = self.form.find('#vl-subs');
    self.form.button = self.form.find('.vl-submit');
    self.empty_class = 'empty';
    self.not_empty_class = 'not-empty';

    $.each(self.inputs, function(k,v){

        _console_log_( 'init select element' );
        _console_log_( v );

        if ( ! v ) {
            return;
        }

        if ( ! v.length ) {
            return;
        }

        // add a class to empty <select> elements - on page load
        self.identify_select_empty( v );

        // when ajax is running in the background and returns with a response, the
        // <select> element, if open, does not get its items updated.
        // so, prevent the click action if its empty/disabled
        // doesnt work with select2 enabled
        // v.on('click', function(e){
        //     _console_log_( 'ele clicked' );
        //     var ele = self.get_element_to_add_empty_class( v );
        //     if ( ele.hasClass(self.empty_class) ) {
        //         _console_log_( 'ele has empty class' );
        //         e.preventDefault();
        //     }
        // });
    });

    if (self.form.button.length > 0) {
        self.form.button.on('click', function (e) {
            var href = $(this).attr('href');
            href = href || '';
            if (href === '') {
                e.preventDefault();
            }
        });
    }

    // "shop_for" changes, maybe update button URL
    self.inputs.shop_for.on('change', function () {
        empty_form_response_text( self.form );

        // server should give back empty response if req. fields are missing
        // but lets prevent additional ajax requests if we know it will return nothing
        var make = self.inputs.makes.val();
        var model = self.inputs.models.val();
        var year = self.inputs.years.val();
        var trim = self.inputs.trims.val();
        var fitment = self.inputs.fitments.val();
        if (make && model && year && trim && fitment) {
            self.send_ajax({
                request_type: 'get_url'
            });
        } else {
            self.set_url('');
            self.make_button_('disabled');
        }
    });

    // makes change, get years
    self.inputs.makes.on('change', function () {

        console.log('change makes --->', self.inputs, self.inputs.makes.val());

        empty_form_response_text( self.form );
        var make = self.inputs.makes.val();
        self.get_years(make);
        self.set_models(false, true);
        self.set_trims(false, true);
        self.set_fitments(false, true);
        self.set_sub_sizes(false, true );
    });

    // years change, get models
    self.inputs.years.on('change', function () {

        console.log('change years --->', self.inputs, self.inputs.years.val());

        empty_form_response_text( self.form );
        var make = self.inputs.makes.val();
        var year = self.inputs.years.val();
        self.get_models_by_year(make, year);

        self.set_trims(false, true);
        self.set_fitments(false, true);
        self.set_sub_sizes(false, true );
    });

    // models change, get trims
    self.inputs.models.on('change', function () {

        console.log('change models --->', self.inputs, self.inputs.models.val());

        empty_form_response_text( self.form );
        var make = self.inputs.makes.val();
        var model = self.inputs.models.val();
        var year = self.inputs.years.val();
        self.get_trims(make, model, year);

        self.set_fitments(false, true);
        self.set_sub_sizes(false, true );
    });

    // trims change, get fitments
    self.inputs.trims.on('change', function () {

        console.log('change trims --->', self.inputs, self.inputs.trims.val());

        empty_form_response_text( self.form );
        var make = self.inputs.makes.val();
        var model = self.inputs.models.val();
        var year = self.inputs.years.val();
        var trim = self.inputs.trims.val();

        if ( self.inputs.fitments.length < 1 ) {
            if ( ! trim ) {
                self.set_url('');
            }
        }

        self.set_sub_sizes(false, true );
        self.get_fitments(make, model, year, trim);
    });

    /**
     * Update:
     *
     * We are adding a field for substitution/aftermarket sizes, below fitments.
     * This field is a bit annoying because its optional. When a user selects a fitment,
     * we need to both set the URL of the button so its clickable, and then if sub sizes
     * are found, update the list of substitution sizes (and if not - not sure how to handle this yet).
     *
     * In addition, when a user selects a sub size we need to update the URL, but even when they select
     * no sub size, we still need to update the URL. Vehicles are "complete" if they have either
     * a fitment size, or a fitment size AND a sub size.
     *
     * The javascript was built around the idea of one request_type and one response_type,
     * but now the response_type optionally needs to say two things. The plan is to leave the response_type
     * the way it currently is, and we'll add another response index to deal with the sub sizes.
     *
     */
    // fitments change - get the URL and/or get Sub Sizes
    self.inputs.fitments.on('change', function () {

        console.log('change fitments --->', self.inputs, self.inputs.fitments.val());

        var fitment = self.inputs.fitments.val();

        // reset sub sizes list regardless of fitment value
        // it may be repopulated on ajax request
        self.set_sub_sizes(false, true );

        if (fitment) {
            // button enabled after ajax gets back to us..
            self.send_ajax({
                request_type: 'get_url'
            });
        } else {
            self.make_button_('disabled');
            self.set_url('');
        }
    });

    // Sub sizes change - get the URL - which may or may not have a sub size applied.
    self.inputs.subs.on('change', function () {

        console.log('change self.inputs.subs --->', self.inputs, self.inputs.subs.val());

        // just tell the server to get the URL.
        // if a sub size is present, it will include it in the URL.
        // if a sub size is not present, it will make the URL using only the fitment size.
        // when a sub size is not present, it could be because a user just selected their fitment size,
        // or because they unselected their selected sub size. In either case, the plan is to return the
        // same data: a URL and a list of sub sizes. When a user unselected their sub size, we'll just update the
        // list again which is less efficient but keeps things simple.
        self.send_ajax({
            request_type: 'get_url'
        });
    });

};

/**
 * Set the url of the button, and make it not disabled.
 */
Vehicle_Lookup.prototype.set_url = function (url) {

    var self = this;

    _console_log_('set_url');
    _console_log_(url);

    if ( ! url ) {
        self.make_button_( 'disabled' );
    } else {
        self.make_button_( 'enabled' );
    }

    if (this.form.button.length === 0) {
        return;
    }
    _console_log_('btn');
    this.form.button.attr({'href': url});
};

/**
 * Make button 'enabled' or 'disabled'
 *
 * @param what
 * @private
 */
Vehicle_Lookup.prototype.make_button_ = function (what) {

    if (this.form.button.length === 0) {
        return;
    }

    if (what === 'enabled') {
        this.form.button.addClass('not-disabled').removeClass('disabled');
    }

    if (what === 'disabled') {
        this.form.button.addClass('disabled').removeClass('not-disabled');
    }
};

/**
 * call this later on document.ready,
 * or after an ajax response that includes a vehicle
 * lookup form (if that will even be a thing)
 *
 * update: vehicle lookup forms are cloned into lightboxes,
 * which means we need to call this again after cloning the content.
 */
function vehicle_lookup_init() {
    var vl = $('#vehicle-lookup');
    $.each(vl, function () {

        // when im_alive is true, the lightbox should have event handlers attached
        // so we don't need to init again. This should make vehicle_lookup_init() idempotent.
        window.im_alive = false;
        $(this).trigger('am_i_alive');

        if ( ! window.im_alive ) {

            $(this).on('am_i_alive', function(){
                window.im_alive = true;
            });

            var v = new Vehicle_Lookup($(this));
            v.init();
        }
    });
}

