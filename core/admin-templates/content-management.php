<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( "Content Management" );
cw_get_header();
Admin_Sidebar::html_before();

?>

    <div class="admin-section general-content">
        <h1>Content Management</h1>

        <p>Most content management can be done through 2 main pages:</p>
        <p><strong>Edit Pages:</strong> Lets you edit meta titles, meta descriptions, and content for most static pages and many other types of pages (tires/wheels/packages landing pages, tire and rim brand archive pages, tires by type archive pages, etc.). Some pages have their meta tags hardcoded and they cannot be edited via the admin panel. After clicking the sidebar link, look for the edit button in the "page_id" column. Also click the "link" column to see which page it links to on the front-end.</p>
        <p><strong>Tables:</strong> Mostly for the product descriptions on single product pages (ie. Tire Model or Rim Model page). The way it works is that if a tire model contains a description it will display that, and if not it will check the tire brand. When tire models don't have a description, this usually means the tire brand description will get repeated on many tire model pages. Click the sidebar link for more info, or one of the links below to start editing.</p>
        <ul>
            <li><a href="<?= ADMIN_URL . '/?page=edit&table=tire_brands'; ?>">tire_brands</a></li>
            <li><a href="<?= ADMIN_URL . '/?page=edit&table=tire_models'; ?>">tire_models</a></li>
            <li><a href="<?= ADMIN_URL . '/?page=edit&table=rim_brands'; ?>">rim_brands</a></li>
            <li><a href="<?= ADMIN_URL . '/?page=edit&table=rim_models'; ?>">rim_models</a></li>
        </ul>

        <br>
        <h2>Other Content:</h2>
        <ul>
            <li><a href="<?php echo get_admin_page_url( 'content_home' ); ?>">Home Page</a></li>
            <li><a href="<?php echo get_admin_page_url( 'gallery' ); ?>">Gallery</a></li>
            <li><a href="<?php echo get_admin_page_url( 'faq' ); ?>">FAQ</a></li>
            <li><a href="<?php echo get_admin_page_url( 'content_contact' ); ?>">Contact</a></li>
            <?php if ( false ) {
                // there is just a lot of html in these pages, so i might hardcode them. if not, you can probably
                // link to these and they should more or less work, of if you know the url you can just visit it..
                ?>
                <li><a href="<?php echo get_admin_page_url( 'content_privacy_policy' ); ?>">Privacy Policy</a></li>
                <li><a href="<?php echo get_admin_page_url( 'content_refund_policy' ); ?>">Refund Policy</a></li>
            <?php } ?>
        </ul>
    </div>
<?php

Admin_Sidebar::html_after();
cw_get_footer();

?>
