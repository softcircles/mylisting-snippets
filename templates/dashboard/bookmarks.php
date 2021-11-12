<?php

// print_r( $bookmarks );exit();
$_page = isset( $_GET['_page'] ) ? (int) $_GET['_page'] : 1;
$bookmark_ids = MyListing\Src\Bookmarks::get_by_user( get_current_user_id() );
$endpoint_url = wc_get_endpoint_url( \MyListing\bookmarks_endpoint_slug() );

if ( ! $bookmark_ids ) {
	$bookmark_ids = [0];
}

$bookmarks = new WP_Query( [
	'post_type' => 'job_listing',
	'posts_per_page' => 10,
	'post_status' => 'publish',
	'paged' => $_page,
	'post__in' => $bookmark_ids,
] );
?>
<?php if ( ! $bookmarks->posts ) : ?>
	<div class="no-listings">
		<i class="no-results-icon material-icons">mood_bad</i>
		<?php _e( 'No bookmarks yet.', 'my-listing' ) ?>
	</div>
<?php else : ?>
	<section class="i-section listing-feed">
		<div class="container-fluid">
			<div class="row section-body grid">
				<?php while ( $bookmarks->have_posts() ):
					$bookmarks->the_post();
					$listing = \MyListing\Src\Listing::get( get_the_ID() );
				?>
					<?php printf(
						'<div class="%s">%s</div>',
						'col-lg-4 col-md-3 col-sm-6 col-xs-12 grid-item',
						\MyListing\get_preview_card( $listing->get_id() )
					) ?>
				<?php endwhile ?>
			</div>
		</div>
	</section>

	<div class="pagination center-button">
		<?php echo paginate_links( [
			'format'  => '?_page=%#%',
			'current'   => ! empty( $_GET['_page'] ) ? absint( $_GET['_page'] ) : 1,
			'total'   => $bookmarks->max_num_pages,
			'type'      => 'list',
			'end_size'  => 3,
			'mid_size'  => 3
		 ] ) ?>
	</div>
<?php endif ?>
