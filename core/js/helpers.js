

var gp_body = $('body');

/**
 *
 * @param trigger
 */
function gp_trigger ( trigger ) {
    gp_body.trigger( trigger );
}

/**
 *
 * @param obj
 * @param index
 * @param df
 */
function gp_if_set(obj, index, df) {

    // object
    if ($.type(obj) === 'object') {
        var ret;
        ret = obj.hasOwnProperty( index ) ? obj[index] : df;
        //ret = ( index in obj ) ? obj[index] : df;
        //ret =  ( typeof obj[index] !== undefined ) ? obj[index] : df;
        return ret;
    }

    // array
    if ($.type(obj) === 'array') {
        _console_log_('gp if set.. is array');
        // does this even work?
        ret = ( index in obj ) ? obj[index] : df;
        // ret = ( typeof obj[index] !== undefined ) ? obj[index] : df;
        return ret;
    }

    _console_log_('gp if set.. is nothing');

    return df;
}

/**
 *
 * @param form
 * @param name
 * @param df
 * @returns {*}
 */
function gp_get_form_value(form, name, df) {
    df = df || '';
    if (form.length > 0) {
        var ele = form.find('*[name="' + name + '"]');
        if (ele.length > 0) {
            return ele.val();
        }
    }
    return df;
}

/**
 * lets us write a few lines less code when submitted ajax without serializing a form
 *
 * values should be an array. object should be the form data which may get added to.
 *
 * @param form
 * @param names
 * @param object
 */
function gp_add_form_values_to_object(form, names, object) {

    if (form.length > 0) {
        $.each(names, function (k, v) {
            if (object.hasOwnProperty(v) === false) {
                var val = gp_get_form_value(form, v);
                if (val !== undefined) {
                    object[v] = gp_get_form_value(form, v);
                }
            }
        });
    }

    return object;
}

/**
 *
 * @param start
 * @param closest
 * @param find
 */
function find_closest_within(start, closest, find) {
    if (start !== undefined && start.length > 0) {
        if (closest === undefined) {
            closest_e = start;
        } else {
            closest_e = start.closest(closest);
        }
        if (closest_e.length > 0) {
            return closest_e.find(find);
        }
    }
    return undefined;
}

/**
 * Pass in jquery object or selector, get a jquery object... or undefined... not very useful actually.
 *
 * @param thing
 */
function make_element(thing) {

    if (typeof thing === 'string' && thing.length > 0) {
        return $(thing);
    }

    return thing;
}