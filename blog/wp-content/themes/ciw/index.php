<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

get_header();

$queried_object = get_queried_object();

// exists on category archives
$cat_term = $queried_object instanceof WP_Term ? $queried_object : false;

// unlike on the "Page for Posts", the queried object is not the post type object
// when we're not on a category archive. Since the homepage shows latest posts,
// it means we can test for the "all categories" blog page by using is_home(),
$is_all_cat_page = is_home();
$all_cats_url = get_bloginfo( 'url' );

$is_search = isset( $_GET['s'] );

$sub_title = "";

if ( $is_all_cat_page ) {
    $archive_title = "Blog";
	$sub_title = "All Categories";
} else if ( $cat_term ) {
    $archive_title = strip_tags( $cat_term->name );
	$sub_title = "Blog Category Results";
} else if ( $is_search ){
    $archive_title = "Blog Search";
    $sub_title = "Showing results for: " . get_search_query( true );
} else {
	$archive_title = get_the_archive_title();
}

ob_start();

if ( have_posts() ) {

	echo '<div class="blog-posts-wrapper">';
	echo '<div class="blog-posts-flex">';

	while( have_posts() ) {

		the_post();
		$p = get_post();
		$e = gp_excerptize( get_the_excerpt( $p ), 40);

		$date = get_the_date( get_nice_date_format(), $p );
		$url = get_the_permalink( $p );

		echo '<div class="blog-single">';
		echo '<div class="blog-single-2">';

		echo '<a href="' . $url . '" class="top">';
		echo Background_Image::get( gp_get_img_url( get_post_thumbnail_id( $p->ID ) ), 'large' );

		echo '<div class="top-content">';
		echo '<h2 class="blog-title">' . get_the_title( $p  ) . '</h2>';
		echo '</div>';

		echo '</a>';

		echo '<div class="btm">';
		echo '<div class="excerpt general-content">';
		echo wpautop( $e );
		echo '</div>';
		echo '<div class="btn-wrapper">';
		echo '<p class="blog-date">' . $date . '</p>';
		echo '<a href="' . $url . '">[Read More]</a>';
		echo '</div>'; // btn-wrapper
		echo '</div>'; // btm

		echo '</div>'; // blog-single-2
		echo '</div>'; // blog-single
	}

	echo '</div>';
	echo '</div>';

} else {

	$st = get_search_query( true );
	$msg = $is_search ? "No posts found for your search terms: $st." : "No posts found. Please try another category.";

	echo html_element( html_element( $msg, 'p' ), 'div', 'general-content' );
}

$pagination = get_the_posts_pagination( [
	'prev_text' => 'Prev',
	'next_text' => 'Next',
	'before_page_number' => '',
] );

// pagination wrapper is required for proper vertical spacing, regardless of whether or not posts are found.
?>
    <div class="gp-pagination-wrapper<?php echo $pagination ? "" : " is-empty"; ?>">
		<?php echo $pagination; ?>
    </div>
<?php

$main_content = ob_get_clean();

?>
	<div class="page-wrap interior-page page-blog">
		<?php echo get_top_image( array(
			'title' => $archive_title,
			'html_tagline' => $sub_title ? html_element( $sub_title, 'h4' ) : '',
			'img' => get_image_src( 'iStock-883581726-wide-lg.jpg' ),
		) ); ?>
		<?php echo Components::grey_bar(); ?>
		<div class="main-content">
			<div class="blog-sidebar-wrapper">
				<div class="bs-content">
					<?php echo $main_content; ?>
				</div>
				<div class="bs-sidebar">

					<div class="bs-sidebar-section">
						<div class="top">
							<label class="title" for="search_blog">Search Blog</label>
						</div>
						<div class="btm">
							<form action="<?php echo $all_cats_url; ?>" method="get">
								<div class="gp-search-wrapper">
									<input placeholder="Search..." type="text" id="search_blog" name="s" value="<?php echo get_search_query( true ); ?>">
									<button class="css-reset" type="submit"><i class="fa fa-search"></i></button>
								</div>
							</form>
						</div>
					</div>

					<div class="bs-sidebar-section">
						<div class="top">
							<p class="title">Categories</p>
						</div>
						<div class="btm">
							<ul class="gp-category-list">
								<?php
								$_all = $is_all_cat_page ? "All Categories" : get_anchor_tag_simple( $all_cats_url, "All Categories" );
								$_all_cls = $is_all_cat_page ? "cat-item cat-item-all current-cat" : "cat-item cat-item-all";
								echo html_element( $_all, 'li', $_all_cls );
								wp_list_categories([
									'current_category' => $cat_term ? $cat_term->term_id : 0,
									'depth' => 1,
									'title_li' => false,
								]);
								?>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php

get_footer();