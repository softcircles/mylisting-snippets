<?php

$_page = isset( $_GET['_page'] ) ? (int) $_GET['_page'] : 1;
$bookmark_ids = MyListing\Src\Bookmarks::get_by_user( get_current_user_id() );
$endpoint_url = wc_get_endpoint_url( 'my-bookmarks' );

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

<?php if ( $bookmarks->have_posts() ) : ?>
	<div class="row">
		<div class="col-md-12">
			<table class="job-manager-jobs c27-bookmarks-table shop_table">
				<thead>
					<tr>
						<th class="bookmark-photo"><i class="mi photo"></i></th>
						<th class="bookmark-title"><?php _e( 'Name', 'my-listing' ) ?></th>
						<th class="bookmark-actions"><?php _e( 'Actions', 'my-listing' ) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php while ( $bookmarks->have_posts() ):
					$bookmarks->the_post();
					$listing = \MyListing\Src\Listing::get( get_the_ID() );
					?>
					<tr>
						<td class="bookmark-photo">
							<?php if ( $gallery = $listing->get_field('gallery') ) : ?>
								<img src="<?php echo esc_url( $gallery[0] ); ?>">
							<?php else : ?>
								<img src="<?php echo $listing->get_logo('thumbnail') ?: c27()->image( 'marker.jpg' ) ?>">
							<?php endif; ?>
						</td>
						<td class="bookmark-title">
							<h5>
								<a href="<?php echo esc_url( $listing->get_link() ) ?>">
									<?php echo esc_html( $listing->get_name() ) ?>
								</a>
							</h5>
						</td>
						<td class="listing-actions">
							<ul class="job-dashboard-actions">
								<li><a href="<?php echo esc_url( $listing->get_link() ) ?>" class="view-action"><?php _e( 'View Listing', 'my-listing' ) ?></a></li>
								<li>
									<a href="<?php echo esc_url( add_query_arg( [ 'listing_id' => $listing->get_id(), 'action' => 'remove_bookmark' ], $endpoint_url ) ) ?>"
										class="delete-action"><?php _e( 'Remove Bookmark', 'my-listing' ) ?></a>
									</li>
								</ul>


							</td>
						</tr>
					<?php endwhile ?>
				</tbody>
			</table>

		</div>
	</div>
	<div class="pagination center-button">
		<?php echo paginate_links([
			'format'  => '?_page=%#%',
			'current' => $_page,
			'total'   => $bookmarks->max_num_pages,
			]);
			wp_reset_postdata(); ?>
	</div>
<?php else: ?>
	<div class="no-listings">
		<i class="no-results-icon material-icons">mood_bad</i>
		<?php _e( 'No bookmarks yet.', 'my-listing' ) ?>
	</div>
<?php endif ?>
