<?php

add_action( 'wp_trash_post', function( $listing_id ) {

	$listing = \MyListing\Src\Listing::get( $listing_id );

	if ( $listing ) {
		// Get packages.
		$promotions = get_posts( [
			'post_type'        => 'cts_promo_package',
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'post__in'         => [],
			'order'            => 'asc',
			'orderby'          => 'post__in',
			'suppress_filters' => false,
			'fields'           => 'ids',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => '_user_id',
					'value'   => get_current_user_id(),
					'compare' => 'IN',
				],
				[
		            'key' => '_listing_id',
		            'compare' => 'EXISTS',
		        ],
		        [
		            'key'     => '_listing_id',
		            'value'   => '',
		            'compare' => '!=',
		        ],
			],
		] );

		foreach ( $promotions as $promotion_id ) {

			if ( $listing = \MyListing\Src\Listing::get( $promotion_id ) ) {
				continue;
			}

			if ( mylisting()->promotions()->expire_package( $promotion_id ) ) {
          
			}
		}
	}
} );
