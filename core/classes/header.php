<?php

class Header {

    /**
     * ie. Meta title, a.k.a. <title>
     *
     * @var
     */
    public static $title;
    public static $meta_description;
    public static $meta_robots;

    /**
     * No need to sanitize when setting the property.
     *
     * If empty, won't print a tag.
     *
     * @var string
     */
    public static $canonical = '';

    /**
     * Array of raw html to print in <head>
     *
     * Meta tags (except those with their own prop), schema, etc. might go here.
     *
     * @var array
     */
    public static $head_arr = [];

    /**
     * @var array
     */
    static $body_classes = [];

    /**
     * @return string
     */
    public static function get_meta_description(){

        $desc = Header::$meta_description;

        // check value stored in DB for current page. If found, it takes precedence.
        if ( Router::$current_db_page ) {
            $id = Router::$current_db_page->get_id();
            $meta_desc = get_page_meta( $id, 'meta_desc', false );
            $meta_desc = $meta_desc ?? '';
            $_desc = trim( $meta_desc );

            if ( $_desc ) {
                $desc = $_desc;
            }
        }

        return htmlspecialchars_but_allow_ampersand( $desc );
    }

    /**
     * <title></title>
     *
     * @return string
     */
    public static function get_title() {

        // at /blog
        if ( CW_IS_WP_BLOG ) {
            ob_start();
            wp_title();
            return ob_get_clean();
        } else {

            $title = Header::$title;

            if ( Router::$current_db_page ) {
                $id = Router::$current_db_page->get_id();
                $meta_title = get_page_meta( $id, 'meta_title', false );
                $meta_title = $meta_title ?? '';
                $_title = trim( $meta_title );

                if ( $_title ) {
                    $title = $_title;
                }
            }

            $title = htmlspecialchars_but_allow_ampersand( $title );

            // might be unlikely fallback, not sure.
            return $title ? $title : "Click It Wheels";
        }
    }

    /**
     * Returns a <meta name=""> tag or ""
     *
     * @param $name
     * @param string $content
     * @param bool $render_empty - if true and $content is empty, returns ""
     * @return string
     */
    public static function render_meta( $name, $content = '', $render_empty = false ) {

        if ( ! $content && ! $render_empty ) {
            return '';
        }

        return '<meta name="' . addslashes( $name ) . '" content="' . addslashes( $content ) . '" />';
    }

    /**
     *
     */
    public static function primary_navigation() {

        ?>
        <ul>
            <?php if ( ! IN_PRODUCTION ): ?>
                <li>
                    <a style="color: #03bec4; font-weight: 700;" href="#">[DEV]</a>
                </li>
            <?php endif; ?>
            <li class="page-tires"><a href="<?php echo get_url( 'tires' ); ?>">Tires</a></li>
            <li class="page-wheels"><a href="<?php echo get_url( 'wheels' ); ?>">Wheels</a></li>
            <li class="page-packages"><a href="<?php echo get_url( 'packages' ); ?>">Packages</a></li>
            <li class="page-faq"><a href="<?php echo get_url( 'faq' ) ?>">FAQ</a></li>
            <li class="page-blog"><a href="<?php echo get_url( 'blog' ); ?>">Blog</a></li>
            <li class="mobile-only page-account"><a href="<?php echo get_url( 'account' ); ?>">Account</a></li>
            <li class="mobile-only page-cart"><a href="<?php echo get_url( 'cart' ); ?>">Cart</a></li>
            <li class="mobile-only page-contact"><a href="<?php echo get_url( 'contact' ); ?>">Contact</a></li>
        </ul>
        <?php
    }

    /**
     *
     */
    public static function account_navigation() {

        $cart_icon = '<i class="fa fa-shopping-cart"></i>';
        $account_icon = '<i class="fa fa-user-circle"></i>';
        $count_html = get_cart_count_indicator();
        ?>
        <ul>
            <li class="page-account"><a href="<?php echo get_url( 'account' ); ?>"><?php echo $account_icon; ?>
                    Account</a></li>
            <li class="page-cart"><a href="<?php echo get_url( 'cart' ); ?>"><?php echo $cart_icon; ?>
                    Cart<?php echo $count_html; ?></a></li>
            <li class="page-contact"><a href="<?php echo get_url( 'contact' ); ?>">Contact</a></li>
        </ul>
        <?php
    }

    /**
     * Returns a string of css classes for <body>
     *
     * @return string
     */
    static function get_body_classes() {

        $ret = [];

        $ret[] = IN_PRODUCTION ? 'in-production' : 'in-development';
        $ret[] = get_global( 'is_admin' ) ? 'is-admin' : '';
        $ret[] = get_global( 'has_top_image', true ) ? 'has-top-image' : 'no-top-image';

        $ret[] = cw_is_user_logged_in() ? 'logged-in' : 'not-logged-in';
        $ret[] = cw_is_admin_logged_in() ? 'admin-logged-in' : '';

        $ret[] = CW_IS_WP_BLOG ? "is--blog" : "not--blog";

        // turn on javascript console logs
        if ( ! IN_PRODUCTION ) {
            $ret[] = 'do-console-logs';
        }

        $ret[] = 'page_' . Router::$current_page;

        return gp_parse_css_classes( array_merge( $ret, Header::$body_classes ) );
    }

    public static function add_schema( array $schema ) {

        Header::$head_arr[] = gp_capture_output( function () use ( $schema ) {
            echo '<script type="application/ld+json">';
            echo "\r\n";
            echo json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
            echo "\r\n";
            echo '</script>';
        } );
    }
}
