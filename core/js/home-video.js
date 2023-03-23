

var Home_Video = function( wrapper ){

    var self = this;
    self.wrapper = wrapper;

    this.fix_ratio = function(){

        self.w_height = parseFloat( self.wrapper.css('height') );
        self.w_width = parseFloat( self.wrapper.css('width') );
        self.w_ratio = parseFloat( self.w_height / self.w_width );

        self.v_height = parseFloat( self.video.css('height') );
        self.v_width = parseFloat( self.video.css('width') );
        self.v_ratio = parseFloat( self.v_height / self.v_width );

        if ( self.w_ratio <= self.v_ratio ) {

            self.video.css({
                width: '100%',
                height: 'auto'
            });

        } else {

            self.video.css({
                width: 'auto',
                height: '100%'
            });

        }
    };

    this.construct = function(){

        self.video = wrapper.find('video');

        if ( self.video.length < 1 ) {
            return;
        }

        self.fix_ratio();

        $(window).on('resize', function(){
            self.fix_ratio();
        });

        $(window).on('load', function(){
            _console_log_('load');
            self.fix_ratio();
        });

        self.wrapper.addClass('js-init');
    };

    this.construct();
};

function init_home_video(){

    var wrapper = $('.home-top .video-wrapper');

    if ( wrapper.length > 0 ) {
        new Home_Video( wrapper );
    }
}