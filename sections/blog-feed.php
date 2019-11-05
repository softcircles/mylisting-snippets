<?php
$data = c27()->merge_options([
	'template' => 'col3',
	'infinite_scroll' => false,
	'posts_per_page' => 9,
	'category' => [],
	'include' => [],
	'paged' => 1,
	'query' => false,
], $data);

/**
 * On some sites, the custom post query is conflicting with Elementor,
 * causing the Elementor editor to stop working on pages with this widget.
 * To bypass this issue, we have to save the current global $post to a temporary variable,
 * then reassign it after the custom query loop. This should be done automatically
 * when wp_reset_postdata() is called, but that's not working in this case.
 */
global $post;
$the_post = $post;

$templates = [
	'col3' => ['wrap_in' => 'col-md-4 col-sm-6 col-xs-12'],
	'col2' => ['wrap_in' => 'col-md-6 col-sm-6 col-xs-12'],
];

$args = [];
$args['paged'] = $data['paged'];
$args['posts_per_page'] = $data['posts_per_page'];

// Filter by category.
if ($data['category']) $args['category__in'] = $data['category'];

// Only display the selected listings.
if ($data['include']) $args['post__in'] = $data['include'];

if ( is_singular( 'post' ) ) {
	$args['post__not_in'] = [$the_post->ID];
	unset($args['post__in']);
}

// WP Query.
$query = $data['query'] ? : new WP_Query($args);
?>

<section class="i-section">
	<div class="container-fluid">

		<?php if ($query->have_posts()): ?>

			<div class="row section-body grid">

				<?php while ( $query->have_posts() ): $query->the_post();
					c27()->get_partial( 'post-preview', [
						'wrap_in' => (isset($templates[$data['template']]) ? $templates[$data['template']]['wrap_in'] : $templates['col3']['wrap_in']) . (is_sticky() ? ' sticky ' : ''),
					] ) ?>
				<?php endwhile ?>

			</div>

			<div class="blog-footer">

				<?php if (!$data['infinite_scroll']): ?>

					<div class="row project-changer">
						<div class="text-center">
							<?php echo paginate_links([
								'total'   => $query->max_num_pages,
								'format'  => '?paged=%#%',
								'current' => 0,
								'current' => $data['paged'],
								]) ?>
							</div>
						</div>

				<?php else: ?>

					<?php if (get_option('posts_per_page') < $query->found_posts): ?>

						<div class="load-more row">
							<div class="col-md-12">
								<a href="#" class="buttons button-2" id="get-post-items"><?php _e( 'Load More', 'my-listing' ) ?></a>
							</div>
						</div>

					<?php endif ?>

				<?php endif ?>

			</div>

			<?php wp_reset_postdata() ?>
			<?php /* Temporary fix: */ $GLOBALS['post'] = $the_post; ?>
		<?php endif ?>

	</div>
</section>
