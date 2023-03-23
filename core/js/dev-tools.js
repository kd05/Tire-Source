

( function ($) {

    /**
     *
     * @constructor
     */
    var GP_Dev_Tools = function () {

        /**
         *
         */
        function gpDev_remove_ajax_loading() {
            _console_log_('remove loading');
            $('.ajax-loading').removeClass('ajax-loading').find('disabled').removeClass('disabled');
        }

        /**
         *
         */
        function gpDev_toggle_outlines() {
            _console_log_('toggle outlines');
            var body = $('body');
            if (body.hasClass('dev-outlines')) {
                body.removeClass('dev-outlines');
                body.find('*').css({
                    'outline': ''
                });
            } else {
                body.addClass('dev-outlines');
                body.find('*').css({
                    'outline': '1px solid red'
                });
            }
        }

        /**
         *
         */
        function gpDev_listen_and_fire_events() {

            var keys_pressed = [];

            var keys_pressed_interval = setInterval(function () {
                keys_pressed = [];
                // _console_log_(keys_pressed);
            }, 3000);

            $(window).on('keypress', function (e) {
                clearInterval(keys_pressed_interval);

                keys_pressed_interval = setInterval(function () {
                    keys_pressed = [];
                    // _console_log_(keys_pressed);
                }, 3000);

            });

            var events = [];

            // min length of keys pressed should be 2
            events.push({
                'keys': [101, 114, 101, 114, 101, 114], // er er er
                'event': 'keyCombo1'
            });

            events.push({
                'keys': [101, 101, 101, 114, 101, 101, 101], // eeereee
                'event': 'keyCombo2'
            });

            // events.push({
            //     'keys' : [1,2,3,4,1],
            //     'event' : 'keyCombo2',
            // });

            _console_log_(events);

            // beware of changing keypress, then keycodes may be mapped differently
            $(window).on('keypress', function (e) {

                // add key to the array
                keys_pressed.push(e.which);

                // _console_log_('--keydown--');
                // _console_log_(e.which);
                // _console_log_(String.fromCharCode(e.which));  // returns "ABC");

                var len;
                var end;
                var kp_len = keys_pressed.length;

                $.each(events, function (key, value) {

                    len = value.keys.length;
                    end = keys_pressed.slice(Math.max(0, kp_len - len));

                    if (end.join('|') === value.keys.join('|') && len > 1) {
                        _console_log_(value.event);
                        _console_log_(end);
                        $(window).trigger(value.event);
                        keys_pressed = [];
                    }

                });
            });
        }

        gpDev_listen_and_fire_events();

        $(window).on('keyCombo1', function () {
            gpDev_toggle_outlines();
        });

        $(window).on('keyCombo2', function () {
            gpDev_remove_ajax_loading()
        });

    };

    jQuery(document).ready(function ($) {
        if ($('body').hasClass('in-development')) {
            new GP_Dev_Tools();
        }
    });

}(jQuery));
