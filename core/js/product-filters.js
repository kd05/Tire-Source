
var Product_Filters = function( form ) {
    this.form = form;
};

/**
 *
 */
Product_Filters.prototype.init = function(){

    var self = this;

    /**
     * disable auto close, and add number to show how many items are checked which we'll
     * probably use css to only show when the item is closed manually.
     */
    self.form.on('change', '.sidebar-accordion-item .ai-body input', function(){

        // now that we show counts beside each filter, we'll hide the apply button and
        // just submit the form when a value changes.
        _console_log_('sidebar accordion item change');

        if ($(this).is(':visible')) {
            _console_log_('is visible, submitting form');
            self.form.submit();
        }
    });

    self.form.on('submit', function(e){
        self.clean_duplicate_form_values();

        // not doing ajax anymore
        // e.preventDefault();
        // self.send_ajax();
    });

};

/**
 * clean up duplicate values otherwise they end up in the URL
 */
Product_Filters.prototype.clean_duplicate_form_values = function(){

    var self = this;
    // $_GET is printed (... after being sanitized) to hidden form values
    var to_check = self.form.find('.page-load-inputs input');

    $.each(to_check, function(){
        $(this).addClass('checking');
        var name = $(this).attr('name');
        var remove = false;
        var with_same_name = self.form.find('[name="' + name + '"]');
        $.each(with_same_name, function(){
            if ( ! $(this).hasClass('checking') ) {
                remove = true;
            }
        });
        if ( remove ) {
            $(this).remove();
        }
    });

};

/**
 * filters now use $_GET, not ajax
 */
// Product_Filters.prototype.send_ajax = function(){
//
//     var self = this;
//     var loop = $('.product-loop-wrapper');
//     var data = self.form.serializeArray();
//     var url = self.form.attr('action');
//
//     _console_log_('data');
//     _console_log_(data);
//
//     _console_log_('action');
//     _console_log_(url);
//
//     send_ajax( self.form, {
//         beforeSend: function () {
//             loop.addClass('ajax-loading');
//         },
//         success: function (response) {
//             _console_log_(response);
//             loop.removeClass('ajax-loading');
//             var replace = response.hasOwnProperty( 'loop_pagination' ) ? response.loop_pagination : '';
//             if ( loop.length > 0 ) {
//                 loop.after(replace).remove();
//                 setTimeout( function(){
//                     $(window).trigger('gp_ajax_complete');
//                 });
//             }
//
//         }
//     });
// };

/**
 * These are the product filters on rims/tires/packages pages
 *
 * @param div
 * @param parent
 * @constructor
 */
var Sidebar_Accordion_Item = function(div, parent){
    this.div = div;
    if ( parent.length > 0 ) {
        this.parent = parent;
    } else {
        this.parent = $('body');
    }
};

/**
 *
 * @returns {number}
 */
Sidebar_Accordion_Item.prototype.count_checked = function(){

    var items = this.div.find('.ai-body input[type="checkbox"], .ai-body input[type="radio"]');

    var count_checked = 0;
    $.each(items, function(){
        if ( $(this).prop('checked') === true ) {
            count_checked++;
        }
    });

    return count_checked;
};

/**
 *
 * @param ele
 */
Sidebar_Accordion_Item.prototype.open = function( ele ){

    ele = ele === undefined ? this.div : ele;
    body = ele.find('.ai-body');

    if ( body.length > 0 ) {
        body.slideDown({
            duration: 250
        });

        ele.addClass('visible').removeClass('not-visible');
    }
};

/**
 *
 * @param ele
 */
Sidebar_Accordion_Item.prototype.close = function( ele ){

    ele = ele === undefined ? this.div : ele;
    body = ele.find('.ai-body');

    if ( body.length > 0 ) {
        body.slideUp({
            duration: 250
        });

        ele.addClass('not-visible').removeClass('visible');
    }
};

/**
 * label the title with the number of checkboxes checked..
 */
Sidebar_Accordion_Item.prototype.check_state = function( allow_open ){

    _console_log_('input change 2');

    allow_open = allow_open === undefined ? false : allow_open; // default false

    var self = this;
    var item = self.div; // $('.sidebar-accordion-item');
    var body = item.find('.ai-body');

    var count = self.count_checked();

    // disable auto close (when other accordion items in the same group are opened)
    if ( count > 0 ) {
        item.addClass('no-auto-close');

        // maybe open on page load only
        if ( allow_open ) {
            self.open();
        }

    } else {
        item.removeClass('no-auto-close');
    }

    if ( item.hasClass('no-auto-count') === false ) {
        // show count of items checked if an item is closed while filters are being applied
        var title = item.find('.ai-header .title');
        if ( title.length > 0 ) {
            var after_title = '(' + count + ')';
            var ex_span = title.find('.checked-count');
            if ( ex_span.length > 0 ) {

                if ( count === 0 ){
                    ex_span.remove();
                } else {
                    ex_span.empty().append(after_title);
                }

            } else {
                if ( count > 0 ){
                    title.append('<span class="checked-count">' + after_title + '</span>');
                }
            }
        }
    }
};

/**
 *
 */

Sidebar_Accordion_Item.prototype.init = function(){

    var self = this;

    if ( self.div.length < 1 ) {
        return;
    }

    if ( self.div.hasClass('sbai-init') ) {
        return;
    }

    self.div.addClass('sbai-init');

    // on page load
    var body = this.div.find('.ai-body');
    if ( body.length > 0 ) {
        if ( body.is(':visible' ) ) {
            $(this).addClass('visible').removeClass('not-visible');
        } else {
            $(this).removeClass('visible').addClass('not-visible');
        }
    }

    // pass in true to open the items, but i think its better to not and just
    // show numbers beside title if some boxes are checked. opening is bad with large numbers of checkboxes.
    self.check_state( false );

    // on change..
    self.div.on('change', 'input', function(){
        self.check_state( false );
    });

    // on click (toggle self, maybe close others)
    self.div.on('click', '.ai-header', function(){

        var item_clicked = $(this).closest('.sidebar-accordion-item');

        if ( item_clicked.length > 0 && item_clicked.hasClass('always-open') ){
            return false;
        }

        var items = self.parent.find('.sidebar-accordion-item');

        /**
         * close all, maybe open one
         */
        $.each(items, function(){
            var body = $(this).find('.ai-body');
            var this_item = $(this);
            if ( body.length < 1 ) {
                return true; // continue
            }
            if ( this_item[0] === item_clicked[0] ) {
                _console_log_('this item.... item clicked....');
                if ( ! body.is(':visible') ) {
                    self.open( this_item );
                } else {
                    self.close( this_item );
                }
            } else {
                // might have this class if items are checked
                if ( ! this_item.hasClass('no-auto-close') ) {
                    self.close( this_item );
                }
            }
        });

    });
};

/**
 *
 */
function init_sidebar_accordion_items( wrapper ){

    if ( wrapper.length < 1 ) {
        wrapper = $('body');
    }

    var items = wrapper.find('.sidebar-accordion-item');

    /**
     * initialize items on page load. Add/remove class to reflect whether or not the body div
     * is visible based on css. Also, override visibility if some items are checked.
     */
    $.each(items, function(){
        var obj = new Sidebar_Accordion_Item( $(this), wrapper );
        obj.init();
    });

}

/**
 *
 */
function init_product_filters(){

    if ( ! window.init_product_filters_radio_buttons ) {
        window.init_product_filters_radio_buttons = true;
        var body = $('body');
        var selector = 'input.allow-uncheck[type="radio"]';
        make_radio_buttons_un_checkable( body, selector );
    }

    var rims_by_size = $('#rims-by-size');
    if ( rims_by_size.length > 0 ) {
        init_sidebar_accordion_items( rims_by_size );
    }

    var form = $('#product-filters');
    if ( form.length > 0 ) {

        init_sidebar_accordion_items( form );

        $.each(form, function(){
            if ( $(this).hasClass('pf-init') === false ) {
                $(this).addClass('pf-init');
                var obj = new Product_Filters( $(this) );
                obj.init();
            }
        });
    }
}

