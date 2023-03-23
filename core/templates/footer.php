<?php

?>

    </main>

    <footer class="site-footer">
        <?php maintenance_mode_print_possible_warning(); ?>

        <?php
        global $skip_footer_contact;
        if ( ! $skip_footer_contact ) {

            // skip the contact form for admin section always
            if ( ! get_global( 'is_admin', false ) ) {
                include TEMPLATES_DIR . '/contact-banner.php';
            }
        }
        ?>

        <div class="footer-main">
            <div class="container wide-container">
                <div class="top">
                    <div class="logo invert-logo">
                        <?php
                        // when getting the icon 'logo_invert' you don't need the .invert-logo class above
                        // but for logo_dot_com or logo_dot_com_caps, include the class to adjust the colours via css
                        //						echo gp_get_icon( 'logo_invert' );
                        //						echo gp_get_icon( 'logo_dot_com' );
                        echo gp_get_icon( 'logo_dot_com_caps' );
                        ?>
                    </div>
                    <nav class="main-nav">
                        <?php Header::primary_navigation(); ?>
                    </nav>
                    <nav class="account-nav">
                        <?php Header::account_navigation(); ?>
                    </nav>
                </div>
                <div class="bottom">
                    <div class="copyright">
                        <div class="payment">
                            <?php echo get_credit_card_icons_html( [ 'transparent' => false ] ); ?>
                        </div>
                        <div class="copy-main">
                            <p class="copy">&copy; <?php echo date( 'Y' ); ?> <?php echo get_operating_as_name(); ?> All
                                Rights Reserved</p>
                            <p class="dev">Website by GeekPower <a href="http://in-toronto-web-design.ca"
                                                                   target="_blank"
                                                                   title="Web Design in Toronto">Web Design in
                                    Toronto</a>
                            </p>
                        </div>
                    </div>
                    <div class="contact">
                        <div class="contact-1">
                            <p class="terms"><a href="<?php echo get_url( 'privacy_policy' ); ?>">Privacy Policy</a></p>
                            <p class="email"><a href="mailto:info@email_removed.com">info@email_removed.com</a></p>
                            <p class="contact">North York, Ontario, M3N 1V9</a></p>
                        </div>
                        <!--                        <div class="payment">-->
                        <?php // echo get_credit_card_icons_html(); ?>
                        <!--                        </div>-->
                    </div>
                </div>
            </div>
        </div>
    </footer>

<?php
$lb = get_queued_lightbox_html();
echo ! $lb ? '' : html_element( $lb, 'div', 'hidden-lightbox-content', [ 'style' => 'display: none;' ] );

?>

    <div class="session-alerts-hidden">
        <?php
        // put this code at the bottom of the footer so that if the code fails before now, we don't
        // remove the alert from session and then not show it..
        $alerts = get_session_alerts_html_and_remove();
        if ( $alerts ) {
            $lb_id = 'session-alerts';
            echo get_general_lightbox_content( $lb_id, $alerts, [
                'add_class' => 'session-alerts general-lightbox',
            ] );
            echo '<button class="lb-trigger open-on-page-load" data-for="' . $lb_id . '">';
        }
        ?>
    </div>

    </body>

<?php cw_print_wp_blog_filter( 'cw_footer' ); ?>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>

<?php
if ( gp_get_global( 'require_fancybox', false ) ) {
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.3.5/jquery.fancybox.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.3.5/jquery.fancybox.min.css"/>
    <?php
}
?>
    <script src="<?= BASE_URL; ?>/build/main.min.js?v=<?= _SCRIPTS_VERSION; ?>"></script>
<?php

if ( Footer::$raw_html ) {
    echo implode( "\r\n", Footer::$raw_html );
}

if ( ! IN_PRODUCTION ) {

    $v = get_primary_vehicle_instance( $_GET );
    queue_dev_alert( 'Vehicle Fitment', get_pre_print_r( $v->fitment_object ) );
    queue_dev_alert( 'Vehicle Summary', get_pre_print_r( $v->complete_vehicle_summary_array() ) );
    queue_dev_alert( 'User', cw_get_logged_in_user_array() );
    queue_dev_alert( 'Component_Builder::get_return() counter', Component_Builder::get_recursion_counter() );
    queue_dev_alert( 'History', Vehicle::render_session_history_urls() );
    queue_dev_alert( 'Debug', Debug::render() );
    queue_dev_alert( 'Cart', get_pre_print_r( get_session_cart() ) );

    queue_dev_alert( "Mem: " . implode( " --- real:  ", get_mem_formatted() ) );
    queue_dev_alert( "Peak Mem: " . implode( " --- real: ", get_peak_mem_formatted() ) );

    echo render_dev_alerts();
}

?>
    <div class="footer-hidden" style="display: none;">
        <div data-json="<?= gp_json_encode( Footer::$print_hidden ); ?>"></div>
    </div>
<?php

Debug::log_time( 'script_end_footer' );

