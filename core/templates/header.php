<?php

// print tracking codes (gtag, fb pixel, hotjar, etc) on live site for front-end only
$print_tracking_codes = IN_PRODUCTION && ! cw_is_admin();

?><!DOCTYPE html>

<html>

<head>
    <title><?php echo Header::get_title(); ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="msapplication-TileColor" content="#da532c" />
    <meta name="theme-color" content="#ffffff" />

    <?= Header::$canonical ? '<link rel="canonical" href="' . addslashes( Header::$canonical ) . '" />' : ''; ?>

    <?= Header::render_meta( 'robots', Header::$meta_robots, false ); ?>

    <?= Header::render_meta( 'description', Header::get_meta_description(), false ); ?>

    <?= implode( "\r\n", Header::$head_arr ); ?>

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.8/css/all.css"
          integrity="sha384-3AB7yXWz4OeoZcPbieVW64vVXEwADiYyAEhwilzWsLw+9FgqpyjjStpPnpBO8o8S"
          crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,600,700,800" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= BASE_URL; ?>/build/master.css?v=<?= _SCRIPTS_VERSION; ?>">

    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/favicon/site.webmanifest">
    <link rel="mask-icon" href="<?php echo BASE_URL; ?>/favicon/safari-pinned-tab.svg" color="#5bbad5">

    <?php

    // wp filters, for /blog only
    cw_print_wp_blog_filter( 'cw_head' );

    if ( get_global( 'print_kount_data_collector', false ) ) {
        // echo App_Kount::get_data_collector_html( IN_PRODUCTION, true );
    }
    ?>
</head>

<?php if ( CW_IS_WP_BLOG ) { ?>
<body <?php body_class( Header::get_body_classes() ); ?>>
<?php } else { ?>
<body class="<?= Header::get_body_classes() ?>">
<?php } ?>

<?php if ( $print_tracking_codes ) { ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-59P5DJ7"
                      height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php } ?>

<header class="site-header">
    <?php
    $locale = app_get_locale();

    $cls_1 = $locale == 'US' ? 'current-item' : '';
    $cls_2 = $locale == 'CA' ? 'current-item' : '';

    ?>
    <div class="header-top">
        <div class="top-container">
            <?php

            if ( CW_IS_WP_BLOG && current_user_can( 'administrator' ) ) {
                echo '<div class="pre-flags">';
                echo '<a href="' . get_url( 'blog' ) . '/wp-admin">WP-Admin</a>';
                echo '</div>';
            }

            if ( cw_is_admin_logged_in() ) {
                echo '<div class="pre-flags">';
                echo '<a href="' . get_admin_page_url( 'home' ) . '">Admin</a>';
                echo '</div>';
            }

            ?>
            <form action="<?php echo AJAX_URL; ?>" id="set-country" class="set-country">
                <?php echo get_ajax_hidden_inputs( 'set_country' ); ?>
                <nav class="country-nav" role="navigation" aria-label="Country Navigation">
                    <ul>
                        <?php if ( ! DISABLE_LOCALES ) { ?>
                            <li class="<?php echo $cls_1; ?>">
                                <button id="set-country-trigger-us" title="Shipping Region: United States"
                                        class="css-reset" type="submit"
                                        name="country" value="US"><span
                                            class="screen-reader-text">United States</span><img
                                            role="presentation"
                                            src="<?php echo get_image_src( 'us-flag.png' ); ?>"
                                            alt=""></button>
                            </li>
                        <?php } ?>
                        <li class="<?php echo $cls_2; ?>">
                            <button id="set-country-trigger-ca" title="Shipping Region: Canada" class="css-reset"
                                    type="submit" name="country"
                                    value="CA"><span
                                        class="screen-reader-text">Canada</span><img
                                        role="presentation" src="<?php echo get_image_src( 'canada-flag.png' ); ?>"
                                        alt=""></button>
                        </li>
                    </ul>
                </nav>
            </form>
        </div>
    </div>
    <?php maintenance_mode_print_possible_warning(); ?>
    <div class="header-mid">
        <div class="mid-container">
            <div class="logo">
                <div class="logo-inner">
                    <a href="<?php echo BASE_URL; ?>"><span class="screen-reader-text">Home</span></a>
                    <?= gp_get_icon( 'logo_dot_com_caps' ); ?>
                </div>
            </div>
            <div class="mid-right">
                <div class="wide-container">
                    <nav class="mini-nav" aria-label="Account Navigation">
                        <?php Header::account_navigation(); ?>
                    </nav>
                    <div class="mobile-button">
                        <button class="mobile-menu-trigger css-reset">
                            <span class="close"><i class="fa fa-times"></i></span>
                            <span class="bars">
                                <span class="screen-reader-text">Toggle Menu</span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="header-btm">
        <div class="wide-container">
            <div class="wide-container-inner">
                <nav class="main-nav mobile-menu-target" role="navigation" aria-label="Primary Navigation">
                    <?php Header::primary_navigation(); ?>
                </nav>
                <?php
                // might be home page only
                global $show_shop_nav;
                if ( $show_shop_nav ) {
                    ?>
                    <div class="shop-nav">
                        <div class="shop-btn shop-vehicle active">
                            <button class="css-reset">Vehicle</button>
                        </div>
                        <div class="shop-btn shop-tire">
                            <button class="css-reset">Tire</button>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</header>

<main id="main" class="site-main">

<?php

if ( ! IN_PRODUCTION ) {
    $meta = [ Header::$title, Header::$meta_description ];
    queue_dev_alert( implode( '...', $meta ), get_pre_print_r( $meta ) );
}
