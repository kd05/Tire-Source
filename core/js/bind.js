
/**
 * JS_Bind object
 *
 * @param ele
 * @constructor
 */
var JS_Bind = function (ele) {
    var self = this;
    self.ele = ele;
    self.data_bind = self.ele.attr('data-bind');
    self.bindings = ( self.data_bind !== undefined && self.data_bind ) ? JSON.parse(self.data_bind) : {};
};

/**
 * JS_Bind INIT
 */
JS_Bind.prototype.init = function () {
    var self = this;

    // although its tempting to put the events in the array indexes, this prevents us
    // from attaching 2 actions to the same event.
    $.each(self.bindings, function (count, args) {

        var bind = gp_if_set(args, 'bind');
        var action = gp_if_set(args, 'action');

        args.ele = gp_if_set(args, 'ele', self.ele);

        if (!bind)
            return true;

        // not sure if we'll use this, but will fire something on page load essentially
        if (bind === 'immediate') {
            var execute = new JS_Action(action, args);
            execute.init();
            return true;
        }

        // fire the action when event is triggered
        self.ele.on(bind, function () {
            _console_log_('JS_Bind: ' + bind);
            // pass in the thing we clicked on if args.ele is not set
            var execute = new JS_Action(action, args);
            execute.init();
        });

    });

};

/**
 * Will run on document ready
 */
function js_bind_init() {
    var bb = $('.js-bind');
    $.each(bb, function () {
        if ($(this).hasClass('js-bind-init') === false) {
            $(this).addClass('js-bind-init');
            var obj = new JS_Bind($(this));
            obj.init();
            _console_log_(obj);
        }
    });
}