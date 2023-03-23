

var Tire_Size_Select = function( form ) {
    this.form = form;
};

/**
 *
 */
Tire_Size_Select.prototype.init = function(){

    var self = this;
    var sizes = self.form.attr('data-sizes');
    self.sizes = sizes === undefined ? [] : JSON.parse( sizes );

    self.inputs = {};
    self.inputs.width = self.form.find('#tss_width');
    self.inputs.profile = self.form.find('#tss_profile');
    self.inputs.diameter= self.form.find('#tss_diameter');
    self.button = self.form.find('.item-submit button');

    _console_log_(self);

    self.update();

    self.form.on('click', '.item-submit button', function(e){
        if ( $(this).hasClass('disabled' ) ) {
            e.preventDefault();
        }
    });

    // the default action of this button just doesn't work on this form.
    // I dont remember why but it either has to do with select2 or our own javascript..
    self.form.on('click', 'button[type="reset"]', function(e){
        e.preventDefault();
        $.each(self.inputs, function(){
            $(this).val('');
        });

        // update calls sync_select_2
        self.update();
        // self.sync_select_2();
    });

    self.form.on('change', '#tss_width, #tss_profile, #tss_diameter', function(e){
        _console_log_('change');

        // inside of self.update() we need to trigger change so that select2 will update accordingly
        if ( $(this).hasClass('do-nothing-on-next-change') ) {
            _console_log_('do nothing on next change...');
            $(this).removeClass('do-nothing-on-next-change');
            return;
        }

        self.update();
    });

    self.form.on('submit', function(e){

        e.preventDefault();

        var width = parseInt( self.inputs.width.val(), 10 );
        var profile = parseInt( self.inputs.profile.val(), 10 );
        var diameter = parseInt( self.inputs.diameter.val(), 10 );

        var sizeString = width + '-' + profile + 'R' + diameter;
        // prevent submit unless all fields are filled out
        if ( width && profile && diameter ) {
            window.location = self.form.attr('data-base-url') + '/' + sizeString;
        }
    });
};

/**
 *
 */
Tire_Size_Select.prototype.sync_button = function(){

    var self = this;
    var item_empty = false;

    if ( self.button.length < 1 ) {
        return;
    }

    $.each(self.inputs, function(){
        if ( ! $(this).val() ) {
            item_empty = true;
        }
    });

    if ( item_empty ) {
        self.button.addClass('disabled');
    } else {
        self.button.removeClass('disabled');
    }

};

/**
 * Select2 is not updating disabled inputs, so we'll have to run our own js
 * to force select 2 to get updated version of the form.
 */
Tire_Size_Select.prototype.sync_select_2 = function(){

    var self = this;

    setTimeout(function(){
        $.each(self.inputs, function(){

            // this is a stupid thing to do but unfortunately even triggering
            // change again on the <select> just flat out doesn't work for updating
            // the select2 mirror with regards to enabling previously disabled inputs.
            // its supposed to work, but it just doesn't. So.. destroy the select2 container and
            // re-initialize (losing all additional arguments if any were passed in the first time)
            if ( $(this).hasClass('select2-hidden-accessible') ) {
                $(this).select2('destroy').select2();
            }

        });

        // self.inputs.width.addClass('do-nothing-on-next-change').trigger('change');
        // self.inputs.profile.addClass('do-nothing-on-next-change').trigger('change');
        // self.inputs.diameter.addClass('do-nothing-on-next-change').trigger('change');

    }, 1);

};

Tire_Size_Select.prototype.update = function(){

    _console_log_(' ----------- update -----------');
    var self = this;

    var width = self.inputs.width.val();
    var profile = self.inputs.profile.val();
    var diameter = self.inputs.diameter.val();

    var allowed_sizes = self.filter_sizes( width, profile, diameter );

    _console_log_('allowed sizes: ');
    _console_log_(allowed_sizes);
    var widths_allowed = [];
    var profiles_allowed = [];
    var diameters_allowed = [];

    $.each(allowed_sizes, function(k,v){
        widths_allowed.push(v[0]);
        profiles_allowed.push(v[1]);
        diameters_allowed.push(v[2]);
    });

    _console_log_('----------------------');
    _console_log_(widths_allowed);
    _console_log_(profiles_allowed);
    _console_log_(diameters_allowed);

    widths_allowed = $.unique( widths_allowed );
    profiles_allowed = $.unique( profiles_allowed );
    diameters_allowed = $.unique( diameters_allowed );

    _console_log_('----------------------');
    _console_log_(widths_allowed);
    _console_log_(profiles_allowed);
    _console_log_(diameters_allowed);

    self.disable_select_options( self.inputs.width, widths_allowed );
    self.disable_select_options( self.inputs.profile, profiles_allowed );
    self.disable_select_options( self.inputs.diameter, diameters_allowed );

    _console_log_('re sync select 2');

    self.sync_select_2();

    self.sync_button();
};

/**
 *
 * @param select
 * @param allowed
 */
Tire_Size_Select.prototype.disable_select_options = function( select, allowed ) {

    var opt = select.find('option');

    _console_log_('disable/enable ---------');
    _console_log_(opt);
    _console_log_(allowed);

    $.each(opt, function(){

        var option = $(this);

        var v = option.val();
        var is_allowed = ( jQuery.inArray( v, allowed ) !== -1 );

        // continue loop. never disable options without values
        if ( ! v ) {
            return true;
        }

        _console_log_('The value of the option is: ' + v);
        _console_log_(is_allowed);

        if ( is_allowed ) {
            _console_log_('enabling', 1111111111111111 );

            // using this alone seems not to do the job.
            option.prop('disabled', false );
            option.removeAttr('disabled');

        } else {
            _console_log_('disabling', 9900000000000000000000000000000);
            option.prop('disabled', true );
        }

    });
};

/**
 * Gives a subset of sizes that are valid based on parameters
 */
Tire_Size_Select.prototype.filter_sizes = function ( width, profile, diameter ) {

    var self = this;
    var ret = [];

    _console_log_('filter');
    _console_log_(width);
    _console_log_(profile);
    _console_log_(diameter);

    var size_allowed, ww, pp, dd;

    _console_log_('each sizes');
    $.each(self.sizes, function(k,v){

        size_allowed = true;
        ww = v[0];
        pp = v[1];
        dd = v[2];

        if ( width && width !== ww ) {
            size_allowed = false;
        }

        if ( profile && profile !== pp ) {
            size_allowed = false;
        }

        if ( diameter && diameter !== dd ) {
            size_allowed = false;
        }

        if ( size_allowed ) {
            ret.push(v);
        }

    });

    return ret;
};

/**
 *
 */
function init_tire_size_select(){

    var tire_size_select = $('form#tire-size-select');

    $.each(tire_size_select, function(e){

        window.im_alive = false;
        $(this).trigger('am_i_alive');

        if ( ! window.im_alive ) {

            $(this).on('am_i_alive', function(){
                window.im_alive = true;
            });

            var obj = new Tire_Size_Select( $(this) );
            obj.init();
        }
    });
}