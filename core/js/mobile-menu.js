/**
 *
 */
function init_mobile_menu(){

    var body = $('body');

    // on click (open menu)
    body.on('click', '.mobile-menu-trigger', function(e){
        // clone if not found
        var m = mobile_menu_get();
        if ( m.length < 1 ) {
            m = mobile_menu_clone();
        }

        // toggle state
        if ( m.length > 0 ) {
            if ( m.hasClass('active') ) {
                mobile_menu_action( 'close' );
            } else {
                mobile_menu_action( 'open' );
            }
        }
    });

    // menu arrows on click (toggle sub-menu)
    // ON CLICK
    body.on('click', '.mobile-menu-clone .link-wrap .arrow', function(e){

        var arrow = $(this);
        var sub_menu = arrow.closest('li').find('> ul');

        var li = arrow.closest('li');
        var duration = 300;

        if ( li.length > 0 ) {
            var ul = li.find('> ul');
        }

        if ( ul.length > 0 ) {

            if ( li.hasClass('expanded') ) {

                li.removeClass('expanded').addClass('collapsed');
                ul.slideUp({
                    duration: duration,
                    queue: false
                });

            } else {

                li.addClass('expanded').removeClass('collapsed');
                ul.slideDown({
                    duration: duration,
                    queue: false
                });
            }

        }
    });


    // adjust height
    $(window).on('resize', function(){
        var menu = mobile_menu_get();

        if ( menu.length > 0 && menu.hasClass('active')) {
            mobile_menu_fix_height();
        }
    });

    // close menu on resize if visible trigger is not found
    $(window).on('resize', function(){
        var menu = mobile_menu_get();
        if ( menu.length > 0 && menu.hasClass('active')) {

            var trigger = $('.mobile-menu-trigger');

            var found = false;
            $.each(trigger, function(){
                if ( $(this).is(':visible') ) {
                    found = true;
                }
            });

            if ( ! found ) {
                mobile_menu_action( 'close' );
            }
        }
    });

    // close on 'escape'
    body.on('keydown', function(e){
        if (e.keyCode === 27) {
            mobile_menu_action( 'close' );
        }
    });
}

/**
 *
 * @param action
 */
function mobile_menu_action( action ) {

    var body = $('body');
    var menu = mobile_menu_get();
    var triggers = body.find('.mobile-menu-trigger');
    var overlay = $('body .mobile-menu-overlay');

    if ( body.length < 1 ) {
        return;
    }

    if ( menu.length < 1 ) {
        return;
    }

    if ( overlay.length < 1 ) {
        return;
    }

    if ( action === 'open' ) {

        menu.addClass('active');
        menu.slideDown();
        overlay.addClass('active');
        body.addClass('mobile-menu-active');


        if ( triggers.length > 0 ) {
            triggers.addClass('active');
        }

        mobile_menu_fix_height();

    }

    if ( action === 'close' ) {

        menu.removeClass('active');
        overlay.removeClass('active');
        body.removeClass('mobile-menu-active');

        if ( triggers.length > 0 ) {
            triggers.removeClass('active');
        }

    }
}

function mobile_menu_fix_height(){

    var menu = mobile_menu_get();
    var header = $('.site-header');
    var hh = header.length > 0 ? parseInt( header.css('height') ) : 0;

    var height = window.innerHeight - hh;

    if ( menu.length > 0 ) {

        menu.css('height', height );

    }
}

/**
 *
 * @returns {jQuery|HTMLElement}
 */
function mobile_menu_get(){
    return $('.site-header .mobile-menu-clone');
}

/**
 * @returns {jQuery|HTMLElement}
 */
function mobile_menu_clone(){

    // find clone target
    var target = $('.mobile-menu-target');
    var _target = target.clone(true, true);
    _target.addClass('cloned');

    // need a few divs for col flex with martin top/bottom auto, and overflow auto
    var wrappers = '';
    wrappers+= '<div class="mobile-menu-clone">';
    wrappers+= '<div class="mm-wrap">';
    wrappers+= '<div class="mm-wrap-2">';
    wrappers+= '</div>';
    wrappers+= '</div>';
    wrappers+= '</div>';

    var result = $(wrappers);
    result.find('.mm-wrap-2').append(_target);

    // mobile drop down arrows
    var nav = result.find('.main-nav');
    if ( nav.length > 0 ) {
        add_arrows_to_nav_menu( nav );
    }

    // insert into DOM
    $('.site-header').append(result);

    // add an overlay div
    var overlay = $('body .mobile-menu-overlay');
    if ( overlay.length < 1 ) {
        $('body').prepend('<div class="mobile-menu-overlay"></div>');
    }

    return mobile_menu_get();
}

/**
 * adds arrows to a div using a nested <ul> <li> structure
 *
 * <p> tags are added around the text for easy use of flex to position arrows to the right
 */
function add_arrows_to_nav_menu(nav_menu) {

    if ( nav_menu.length < 1 ) {
        return;
    }

    var list_items = nav_menu.find('li');

    $.each(list_items, function () {

        var anchor = $(this).find('> a');
        var sub_menu = $(this).find('> ul');

        var arrows = '';
        if (sub_menu.length > 0) {

            // add class to the <li> which contains both the button and the sub-menu
            $(this).addClass('collapsed');
            sub_menu.hide(); // maybe redundant but ensures that slideDown() will work properly

            arrows = '<button class="arrow css-reset"><i class="fa fa-caret-down" aria-hidden="true"></i><span class="screen-reader-text">Toggle sub-menu</span></button>';
        }

        anchor.wrap('<p class="link-wrap"></p>').after(arrows);
    });
};
