


function gp_lightbox_close( lb ){

    _console_log_('close');

    if ( lb.length > 0 ) {
        lb.removeClass('active').addClass('not-active');
    }

    // sometimes we get two open at once
    setTimeout(function(){
        var one_open = gp_lightbox_is_one_open();
        if ( ! one_open ) {
            $('body').removeClass('gp-lightbox-active');
        }
    }, 1);
}

function gp_lightbox_get_all(){
    return $('.gp-lightbox');
}

/**
 *
 * @returns {boolean}
 */
function gp_lightbox_is_one_open(){

    var v = false;
    var all = gp_lightbox_get_all();
    $.each(all, function(e){
        if ( $(this).hasClass('active') ) {
            v = true;
            return false;
        }
    });

    _console_log_('one open', v);
    return v;
}

/**
 *
 * @param lb
 */
function gp_lightbox_open( lb ){

    if ( lb.length > 0 ) {

        lb.removeClass('not-active').addClass('active').addClass('most-recently-opened');
        $('body').addClass('gp-lightbox-active');

        // The most recently cloned lightbox shows up highest in the DOM and therefore
        // it shows up on top of other lightboxes with the same z-index.
        // However, sometime we open a lightbox that contains a trigger to open a different lightbox
        // that was already cloned into the DOM by the time we hit the trigger...
        // And so now, the most recently opened lightbox is no longer the
        // first one in the DOM. So, we'll add a higher z-index for these via CSS.
        var all = gp_lightbox_get_all();
        $.each(all, function(){
            if ( ! $(this).is(lb) ) {
                $(this).removeClass('most-recently-opened');
            }
        });

        gp_focus_first_visible_input( lb, [ '.lb-close'] );
    }
}

/**
 *
 * @param args
 */
function gp_lightbox_create( args ) {

    _console_log_('lightbox create', args );

    window.lightbox_count = window.lightbox_count ? window.lightbox_count : 0;
    window.lightbox_count++;

    var add_class = gp_if_set( args, 'add_class', '' );
    var lightbox_id = gp_if_set( args, 'lightbox_id', window.lightbox_count );
    var content = gp_if_set( args, 'content', '' );
    var close_btn = gp_if_set( args, 'close_btn', false );
    var add_lb_content = gp_if_set( args, 'add_lb_content', false );

    var op = '';

    op += '<div class="gp-lightbox active" data-lightbox-id="' + lightbox_id + '">';
    op += '<div class="lb-inner">';
    op += '<div class="lb-inner-2">';

    // .lb-content is often the target of a clone, but when lightboxes are added without being cloned, we sometimes
    // don't care to add this div to the html, so we can optionally add it in js so styles are more consistent.
    // an example is if you return just a <p> tag from the response of an ajax request... we're not going to put .lb-content
    // into the html of the response since now the response would be pretty useless anywhere outside of a lightbox since
    // .lb-content should be hidden by default
    if ( add_lb_content ) {
        op += '<div class="lb-content">';
        op += '</div>';
    }

    op += '</div>';
    op += '</div>';
    op += '</div>';

    var lightbox = $(op);

    if ( add_class ) {
        lightbox.addClass(add_class);
    }

    if ( lightbox.find('.lb-content').length > 0 ) {
        lightbox.find('.lb-content').prepend(content);
    } else if ( lightbox.find('.lb-inner-2').length > 0 ) {
        lightbox.find('.lb-inner-2').prepend(content);
    } else {
        _console_log_('lightbox could not find target');
    }

    // do this after content so it gets prepended near the top
    if ( close_btn ) {
        var close_btn_parent = lightbox.find('.lb-content');
        if ( close_btn_parent.length < 1 ) {
            close_btn_parent = lightbox.find('.lb-inner-2');
        }
        if ( close_btn_parent.length > 0 ) {
            close_btn_parent.prepend('<button class="close-btn lb-close css-reset"><i class="fa fa-times"></i></button>');
        }
    }

    return lightbox;
}

/**
 * Open if after via gp_lightbox_open()
 *
 * @param ele
 */
function gp_lightbox_add_to_dom( ele ){
    if ( ele.length > 0 ) {

        var body = $('body');
        var ex = body.find('.gp-lightbox').last();

        _console_log_(ex);

        // allow multiple lightboxes to stack.. (newest ones need to go lowest in DOM)
        if ( ex.length > 0 ) {
            ex.after(ele);
        } else {
            body.prepend(ele);
        }

        // some content for lightboxes are cloned, so upon adding them to the DOM
        // we should trigger this event.
        $(window).trigger('gp_clone_complete');
    }
}

/**
 *
 */
function init_lightboxes(){

    var body = $('body');

    body.on('click', '.lb-close', function(e){
        var lightbox = $(this).closest('.gp-lightbox');
        gp_lightbox_close( lightbox );
    });

    if ( body.hasClass('gp-lightbox-esc') === false ) {
        body.addClass('gp-lightbox-esc');
        body.on('keydown', function(e){
            if (e.keyCode === 27) {
                _console_log_('escape... close lb');
                var open = body.find('.gp-lightbox.active');
                var most_recently_opened = body.find('.gp-lightbox.active.most-recently-opened');
                // Close the most recently opened lightbox
                // I think we'll always have just 1 "most recently opened" lightbox, at least, we should.
                // but... do some fallback logic just in case. The fallback is to close all open lightboxes.
                if ( most_recently_opened.length > 0 ) {
                    $.each(most_recently_opened, function(){
                        gp_lightbox_close($(this));
                    });
                } else {
                    $.each(open, function(){
                        gp_lightbox_close($(this));
                    });
                }
            }
        });
    }

    body.on('click', '.gp-lightbox', function(e){
        var target = $(e.target);

        // this div only present sometimes
        if ( $(this).find('.lb-content').length > 0 ) {
            var content = target.closest('.lb-content');
            if ( content.length < 1 ) {
                gp_lightbox_close($(this));
            }
        }

        // this div is always present, if its not then we won't be able
        // to click anywhere without closing the lightbox
        if ( $(this).find('.lb-inner-2').length > 0 ) {
            var inner_2 = target.closest('.lb-inner-2');
            if ( inner_2.length < 1 ) {
                gp_lightbox_close($(this));
            }
        }

    });

    body.on('click', '.lb-trigger', function(e){
        e.preventDefault();

        // this is kind of like how a label is linked to an input via the "for" attribute
        // except multiple triggers can be for the same lightbox
        // so when one is clicked, we'll see if the lightbox already exists
        // triggers without this attribute won't work, and each attribute must be unique on the page.
        // also don't be confused with html ID attributes.. those are not used here.
        var lightbox_id = $(this).attr('data-for');
        _console_log_(lightbox_id);
        if ( lightbox_id !== undefined ) {
            var ex = body.find('.gp-lightbox[data-lightbox-id="' + lightbox_id + '"]');
            if ( ex.length > 0 ) {
                gp_lightbox_open(ex);

            } else {

                var content_div = $('.lb-content[data-lightbox-id="' + lightbox_id + '"]');

                // destroy select2 before cloning. attempting to destroy after causes error.
                if ( content_div.length > 0 ) {
                    _console_log_('content div 1');
                    var select2 = content_div.find('.select2-hidden-accessible');
                    if ( select2.length > 0 ) {
                        $.each(select2, function(){
                            $(this).select2('destroy');
                        });
                    }
                }

                var lightbox = gp_lightbox_create( {
                    'lightbox_id' : lightbox_id,
                    'add_class' : content_div.attr('data-lightbox-class'),
                    'content' : content_div.clone(),
                    'close_btn' : content_div.attr('data-close-btn')
                });

                gp_lightbox_add_to_dom( lightbox );
                gp_lightbox_open(lightbox);
            }
        }
    });

    var open = $('.lb-trigger.open-on-page-load');
    $.each(open, function(){
        $(this).trigger('click');
    });
}