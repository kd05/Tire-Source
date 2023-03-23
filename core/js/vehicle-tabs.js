

/**
 * todo: this was generalized into init_tabs(), maybe remove duplicate code at some point (can't do that right now though)
 */
function init_vehicle_tabs(){

    var body = $('body');

    body.on('click', '.vt-trigger', function(){

        var trigger = $(this);
        var wrap = trigger.closest('.vehicle-tabs');
        var data_for = trigger.attr('data-for');

        var triggers = wrap.find('.vt-trigger');
        $.each(triggers, function(k,v){
            if ( trigger.is($(this)) ) {
                $(this).addClass('active').removeClass('not-active');
            } else {
                $(this).addClass('not-active').removeClass('active');
            }
        });


        $.each(wrap.find('.vt-item'), function(k,v){

            if ( $(this).attr('id') === data_for ) {
                $(this).addClass('active').removeClass('not-active');
            } else {
                $(this).addClass('not-active').removeClass('active');
            }
        });

    });
}

/**
 * Tabs.. click a trigger, show one item, hide the rest.
 */
function init_tabs(){

    var body = $('body');

    body.on('click', '.tab-trigger', function(){

        _console_log_('tab trigger');

        var trigger = $(this);
        var wrap = trigger.closest('.tabs-wrapper');

        // generic selector.. #thing or .thing, inside of .tabs-wrapper
        var data_for = trigger.attr('data-for');

        _console_log_(data_for);

        // mark triggers
        var triggers = wrap.find('.tab-trigger');
        $.each(triggers, function(k,v){
            if ( trigger.is($(this)) ) {
                $(this).addClass('active').removeClass('not-active');
            } else {
                $(this).addClass('not-active').removeClass('active');
            }
        });

        var item = wrap.find(data_for);
        var items = wrap.find('.tab-item');

        // mark items
        if ( item.length > 0 ) {
            $.each(items, function(k,v){
                var loop_item = $(this);
                if ( loop_item.is( item ) ) {
                    loop_item.addClass('active').removeClass('not-active');
                } else {
                    loop_item.addClass('not-active').removeClass('active');
                }
            });
        }
    });
}