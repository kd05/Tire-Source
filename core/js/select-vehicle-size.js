

function init_select_vehicle_size(){

    var body = $('body');

    // this might not be in use anymore
    body.on('change', 'select.select-vehicle-size', function(){
        var val = $(this).val();
        _console_log_(val);
        if ( val ) {
            if ( val.indexOf('http') !== -1 ) {
                window.location.href = $(this).val();
            }
        }
    });

    // this is meant for div.sub-size-select select, but we'll generalize it
    body.on('change', 'select.href-on-change', function(){
        var val = $(this).val();
        _console_log_(val);
        if ( val ) {
            if ( val.indexOf('http') !== -1 ) {
                window.location.href = $(this).val();
            }
        }
    });
}