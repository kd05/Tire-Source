
/**
 *
 * Performs one of many often repeatable actions based on a number of arguments.
 * Examples could be deleting all selectors from within another selector. See also: JS_Bind
 *
 * @param action
 * @param args
 * @constructor
 */
var JS_Action = function (action, args) {
    this.action = action;
    this.args = args;
};

/**
 * For example:
 *
 * $.ajax({ success: function(r){ process_actions_from_objects( r.actions ) }
 *
 * probably not the best name for a function...
 *
 * @param actions - array of objects
 */
function process_actions_from_objects( actions ){

    if ( ! actions || actions.length < 1  ) {
        return;
    }

    $.each(actions, function(k,v){
        var obj= new JS_Action( gp_if_set( v, 'action' ), v );
        obj.init();
    });
}

/**
 * JS_Action Init
 */
JS_Action.prototype.init = function () {

    var self = this;

    // could be a jquery object, or possibly a string selector
    var ele = gp_if_set(self.args, 'ele');
    if (typeof ele === 'string') {
        ele = $(ele);
        self.args.ele = ele;
    }

    switch (self.action) {
        case 'delete':
            self.do_delete();
            break;
        case 'check_all':
            self.do_check_all();
            break;
        case 'uncheck_all':
            self.do_uncheck_all();
            break;
        case 'lightbox':
            self.do_lightbox();
            break;
        default:
            console.log('JS_Action: no action');
    }

};

/**
 * Add a lightbox to the DOM and open it
 */
JS_Action.prototype.do_lightbox = function(){

    var self = this;

    var content = gp_if_set( self.args, 'content', '' );
    content = '<div class="lb-content">' + content + '</div>';

    // we could just pass in self.args... but I'm not totally
    // sure that would never cause issues.
    var lb = gp_lightbox_create( {
        'content' : content,
        'add_class' : gp_if_set( self.args, 'add_class', '' ),
        'close_btn' : gp_if_set( self.args, 'close_btn', false )
    });

    gp_lightbox_add_to_dom( lb );
    gp_lightbox_open( lb );
};

/**
 *
 */
JS_Action.prototype.do_delete = function () {

    var self = this;

    var ele = gp_if_set(self.args, 'ele');
    var targets = find_closest_within(ele, self.args.closest, self.args.find);
    $.each(targets, function () {
        $(this).remove();
    });
};

/**
 * Check all checkboxes
 */
JS_Action.prototype.do_check_all = function () {

    var self = this;

    var ele = gp_if_set(self.args, 'ele');
    var targets = find_closest_within(ele, self.args.closest, self.args.find);
    $.each(targets, function () {
        this.checked = true;
    });
};

/**
 * Uncheck all checkboxes
 */
JS_Action.prototype.do_uncheck_all = function () {

    var self = this;

    var ele = gp_if_set(self.args, 'ele');
    var targets = find_closest_within(ele, self.args.closest, self.args.find);
    $.each(targets, function () {
        this.checked = false;
    });
};