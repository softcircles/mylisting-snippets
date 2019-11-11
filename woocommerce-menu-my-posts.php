function ml_new_account_menu_items( $items ) {
    $items['my-posts'] = esc_html__( 'My Posts', 'my-listing' );
    return $items;
}

add_filter( 'woocommerce_account_menu_items', 'ml_new_account_menu_items', 99, 1 );

function ml_add_my_account_endpoint() {
    add_rewrite_endpoint( 'my-posts', EP_PAGES );
}
 
add_action( 'init', 'ml_add_my_account_endpoint' );

function ml_my_posts_endpoint_content() {

	$query_args = [
		'post_type' => 'post',
		'post_status' => 'publish',
		'ignore_sticky_posts' => 1,
		'posts_per_page' => 12,
		'orderby' => 'date',
		'order' => 'DESC',
		'paged'	=> 1,
		'author' => get_current_user_id(),
	];

	global $post;
	$the_post = $post;

	$query = new WP_Query( $query_args );
    
    ?>
    	<div id="job-manager-job-dashboard">
			<?php if ($query->have_posts()): ?>

			<div class="row section-body grid">

				<?php while ( $query->have_posts() ): $query->the_post();
					c27()->get_partial( 'post-preview', [
						'wrap_in' => 'col-md-4 col-sm-6 col-xs-12',
					] ) ?>
				<?php endwhile ?>

			</div>

			<div class="blog-footer">

				<div class="row project-changer">
					<div class="text-center">
						<?php echo paginate_links([
							'total'   => $query->max_num_pages,
							'format'  => '?paged=%#%',
							'current' => 0,
							'current' => $query_args['paged'],
						]) ?>
					</div>
				</div>

			</div>

			<?php wp_reset_postdata() ?>
			<?php /* Temporary fix: */ $GLOBALS['post'] = $the_post; ?>
		<?php endif ?>
		</div>
   	<?php
}

add_action( 'woocommerce_account_my-posts_endpoint', 'ml_my_posts_endpoint_content' );
