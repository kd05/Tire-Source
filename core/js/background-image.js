
function background_image_img_tags(){

    function run(firstRun){

        $.each($('.img-tag-contain, .img-tag-cover'), function(){

            var el = $(this);
            var img = el.find('> img');

            if ( img.length < 1 ) {
                el.addClass('-img-error');
                if ( firstRun ) {
                    console.error('img tag not found', el);
                }
                return;
            }

            var elWidth = el.outerWidth();
            var elHeight = el.outerHeight();
            var imgWidth = img[0].naturalWidth;
            var imgHeight = img[0].naturalHeight;

            if ( parseInt( elHeight ) === 0 || parseInt( imgHeight ) === 0 ) {
                if ( firstRun ) {
                    console.error('el or img height is zero', elHeight, imgHeight );
                }
                return;
            }

            var elRatio = elWidth / ( elHeight > 0 ? elHeight : 1 );
            var imgRatio = imgWidth / ( imgHeight > 0 ? imgHeight : 1 );

            setTimeout( function(){
                el.addClass('loaded').removeClass('img-tall img-wide');
                el.addClass(elRatio >= imgRatio ? 'img-tall' : 'img-wide');
            }, 0);
        });

    }

    run(true);

    // temporary fix. Need to ensure we run after images loaded.
    var count = 0;
    setInterval(function(){
        count++;
        if ( count < 20) {
            run(false);
        }
    }, 500);

    $(window).on('resize', function(){
        run(false);
    });
}