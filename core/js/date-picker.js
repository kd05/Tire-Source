jQuery( document ).ready(function($) {

    if($('.date-picker-field').length){
        $( ".date-picker-field" ).datepicker({
            dateFormat: "yy-mm-dd"
        });
    }

});