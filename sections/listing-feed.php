<?php
/**
 * Template for Listing Feed widget.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$data = c27()->merge_options( [
	'template' => 'grid',
	'posts_per_page' => 6,
	'category' => '',
	'tag' => '',
	'region' => '',
	'include' => '',
	'listing_types' => '',
    'is_edit_mode' => false,
    'columns' => ['lg' => 3, 'md' => 3, 'sm' => 2, 'xs' => 1],
    'order_by' => 'date',
    'order' => 'DESC',
    'order_by_priority' => true,
    'priority_levels' => [],
    'show_promoted_badge' => 'yes',
	'query_method' => 'filters',
	'query_string' => '',
], $data );

if ( $data['query_method'] === 'query_string' ) {
	if ( ! ( $query_string = parse_url( $data['query_string'], PHP_URL_QUERY ) ) ) {
		return;
	}

	if ( ! ( $query_args = wp_parse_args( $query_string ) ) ) {
		return;
	}

	if ( ! empty( $query_args['pg'] ) ) {
		$query_args['page'] = max( 0, absint( $query_args['pg'] ) - 1 );
	}

	$aliases = array_merge(
		\MyListing\Src\Listing::$aliases,
		[
			'date_from' => 'job_date_from',
			'date_to' => 'job_date_to',
			'lat' => 'search_location_lat',
			'lng' => 'search_location_lng',
		]
	);

	foreach ( $query_args as $key => $query_arg ) {
		if ( ! empty( $aliases[ $key ] ) ) {
			$query_args[ $aliases[ $key ] ] = $query_arg;
			unset( $query_args[ $key ] );
		}
	}

	$listings_query = \MyListing\Src\Queries\Explore_Listings::instance()->run( [
		'listing_type' => ! empty( $query_args['type'] ) ? $query_args['type'] : false,
		'form_data' => c27()->merge_options( [
			'per_page' => $data['posts_per_page'],
		], (array) $query_args ),
		'return_query' => true,
	] );

	if ( ! $listings_query instanceof \WP_Query ) {
		return false;
	}

	$listings = $listings_query->posts;
} else {
	// Query Method: Filters
	$args = [
		'post_type' => 'job_listing',
		'post_status' => 'publish',
		'posts_per_page' => $data['posts_per_page'],
		'ignore_sticky_posts' => false,
		'meta_query' => [],
		'tax_query' => [],
	];

	// Filter by 'job_listing_category' taxonomy.
	if ( $data['category'] ) {
		$args['tax_query'][] = [
			'taxonomy' => 'job_listing_category',
			'terms' => $data['category'],
			'field' => 'term_id',
		];
	}

	// Filter by 'region' taxonomy.
	if ( $data['region'] ) {
		$args['tax_query'][] = [
			'taxonomy' => 'region',
			'terms' => $data['region'],
			'field' => 'term_id',
		];
	}

	// Filter by 'case27_job_listing_tags' taxonomy.
	if ( $data['tag'] ) {
		$args['tax_query'][] = [
			'taxonomy' => 'case27_job_listing_tags',
			'terms' => $data['tag'],
			'field' => 'term_id',
		];
	}

	$taxonomy_list = mylisting_custom_taxonomies();

	foreach ( $taxonomy_list as $slug => $label ) {
		if ( empty( $data[ $slug ] ) ) {
			continue;
		}

		if ( $data[$slug ] ) {
			$args['tax_query'][] = [
				'taxonomy' => $slug,
				'terms' => $data[$slug],
				'field' => 'term_id',
			];
		}
	}

	// Only display the selected listings.
	if ( $data['include'] ) {
		$args['post__in'] = $data['include'];
	}

	// Filter by the listing type.
	if ( $data['listing_types'] ) {
		$args['meta_query']['c27_listing_type_clause'] = [
			'key' => '_case27_listing_type',
			'value' => $data['listing_types'],
			'compare' => 'IN',
		];
	}

	if ( $data['order_by'] ) {
		if ($data['order_by'][0] === '_') {
			// Order by meta key.
			$args['meta_query']['c27_orderby_clause'] = [
				'key' => $data['order_by'],
				'compare' => 'EXISTS',
				'type' => 'DECIMAL(10, 2)',
			];

			$args['orderby'] = 'c27_orderby_clause';
		} else {
			$args['orderby'] = $data['order_by'];
		}
	}

	if ( ! in_array( $data['order'], ['ASC', 'DESC'] ) ) {
		$data['order'] = 'DESC';
	}

	$args['order'] = $data['order'];

	if ( $data['show_promoted_badge'] !== 'yes' ) {
		remove_filter( 'mylisting/preview-card/show-badge', [ mylisting()->promotions(), 'show_promoted_badge' ], 30 );
	}

	// set which priority levels to include
	if ( count( $data['priority_levels'] ) === 0 || count( $data['priority_levels'] ) === 4 ) {
		// all priority levels are included
	}

	// order by priority
	if ( $data['order_by_priority'] === true ) {
		$args['suppress_filters'] = false;
		add_filter( 'posts_join', [ \MyListing\Src\Queries\Explore_Listings::instance(), 'priority_field_join' ], 30, 2 );
		add_filter( 'posts_orderby', [ \MyListing\Src\Queries\Explore_Listings::instance(), 'priority_field_orderby' ], 30, 2 );
	}

	// dump($args);
	$listings = get_posts( apply_filters( 'mylisting/sections/listing-feed/args', $args, $data ) );

	if ( $data['order_by_priority'] === true ) {
		remove_filter( 'posts_join', [ \MyListing\Src\Queries\Explore_Listings::instance(), 'priority_field_join' ], 30 );
		remove_filter( 'posts_orderby', [ \MyListing\Src\Queries\Explore_Listings::instance(), 'priority_field_orderby' ], 30 );
	}
}

?>

<?php if (!$data['template'] || in_array( $data['template'], ['grid', 'fluid-grid'] ) ): ?>
	<section class="i-section listing-feed">
		<div class="container-fluid">
			<div class="row section-body grid">
				<?php foreach ($listings as $listing): $listing->_c27_show_promoted_badge = $data['show_promoted_badge'] == true; ?>
					<?php c27()->get_partial('listing-preview', [
						'listing' => $listing,
						'wrap_in' => sprintf(
										'col-lg-%1$d col-md-%2$d col-sm-%3$d col-xs-%4$d grid-item',
										12 / absint( $data['columns']['lg'] ), 12 / absint( $data['columns']['md'] ),
										12 / absint( $data['columns']['sm'] ), 12 / absint( $data['columns']['xs'] )
									),
						]) ?>
				<?php endforeach ?>
			</div>
		</div>
	</section>
<?php endif ?>

<?php if ($data['template'] == 'carousel'): ?>
	<section class="i-section listing-feed-2">
		<div class="container">
			<div class="row section-body">
				<div class="owl-carousel listing-feed-carousel">
					<?php foreach ($listings as $listing): $listing->_c27_show_promoted_badge = $data['show_promoted_badge'] == true; ?>
						<div class="item">
							<?php c27()->get_partial('listing-preview', ['listing' => $listing]) ?>
						</div>
					<?php endforeach ?>

					<?php if (count($listings) <= 3): ?>
						<?php foreach (range(0, absint(count($listings) - 4)) as $i): ?>
							<div class="item c27-blank-slide"></div>
						<?php endforeach ?>
					<?php endif ?>
				</div>
			</div>
			<?php if (count($listings) >= 3): ?>
				<div class="lf-nav <?php echo $data['invert_nav_color'] ? 'lf-nav-light' : '' ?>">
					<ul>
						<li>
							<a href="#" class="listing-feed-prev-btn">
								<i class="material-icons">keyboard_arrow_left</i>
							</a>
						</li>
						<li>
							<a href="#" class="listing-feed-next-btn">
								<i class="material-icons">keyboard_arrow_right</i>
							</a>
						</li>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif ?>

<?php if ($data['is_edit_mode']): ?>
    <script type="text/javascript">case27_ready_script(jQuery);</script>
<?php endif ?>
