<?php

Admin_Controller::with_header_footer_and_sidebar(function(){
    Product_Sync_Admin_UI::render();
});
