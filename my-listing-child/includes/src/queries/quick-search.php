<?php

namespace MyListing\Src\Queries;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Quick_Search extends Query {
	use \MyListing\Src\Traits\Instantiatable;

	public $action = 'mylisting_quick_search';

    public function handle() {
		mylisting_check_ajax_referrer();

		$search_term = sanitize_text_field( ! empty( $_GET['s'] ) ? $_GET['s'] : '' );
		$sections = [];

		if ( apply_filters( 'mylisting/quicksearch/show-categories', true ) === true ) {
			$sections['categories'] = $this->get_terms( [
				'title' => _x( 'Categories', 'Quick search > Categories section title', 'my-listing' ),
				'taxonomy' => 'job_listing_category',
				'search_term' => $search_term,
			] );
		}

		$query = $this->query( [
			'search_keywords' => $search_term,
			'posts_per_page' => 5,
        	'orderby' => 'relevance',
        	'meta_query' => [
            	[
	                'key' => '_case27_listing_type',
	                'value' => '',
	                'compare' => '!=',
            	],
            	[
            		'key' => '_case27_listing_type',
	                'value' => 'place',
	                'compare' => '==',
            	],
	        ],
		] );

		if ( ! is_wp_error( $query ) && $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				if ( ! ( $listing = \MyListing\Src\Listing::get( get_the_ID() ) ) || ! $listing->type ) {
					continue;
				}

				$section_id = sprintf( 'listing-type-%s', $listing->type->get_slug() );

				if ( empty( $sections[ $section_id ] ) ) {
					$sections[ $section_id ] = [];
					$sections[ $section_id ][] = sprintf( '<li class="ir-cat">%s</li>', $listing->type->get_plural_name() );
				}

				$sections[ $section_id ][] = sprintf(
					'<li>
						<a href="%1$s">
							<div class="avatar"><img src="%2$s"></div>
							<span class="category-name">%3$s</span>
						</a>
					</li>',
					esc_url( $listing->get_link() ),
					esc_url( $listing->get_logo() ?: c27()->image( 'marker.jpg' ) ),
					esc_html( $listing->get_name() )
				);
			}

			wp_reset_postdata();
		}

		if ( apply_filters( 'mylisting/quicksearch/show-regions', true ) === true ) {
			$sections['regions'] = $this->get_terms( [
				'title' => _x( 'Regions', 'Quick search > Regions section title', 'my-listing' ),
				'taxonomy' => 'region',
				'search_term' => $search_term,
			] );
		}

		if ( apply_filters( 'mylisting/quicksearch/show-tags', true ) === true ) {
			$sections['tags'] = $this->get_terms( [
				'title' => _x( 'Tags', 'Quick search > Tags section title', 'my-listing' ),
				'taxonomy' => 'case27_job_listing_tags',
				'search_term' => $search_term,
			] );
		}

		$sections = apply_filters( 'mylisting/quicksearch/sections', $sections );

		// Prepare JSON response.
		$response = [];

		// Concatenate all search results.
		$response['content'] = '';
		foreach ( $sections as $section ) {
			$response['content'] .= implode( '', $section );
		}

		if ( CASE27_ENV === 'dev' ) {
			$response['sql'] = $query->request;
		}

		return wp_send_json( $response );
    }

    public function get_terms( $args ) {
        $terms = get_terms( [
            'taxonomy' => $args['taxonomy'],
            'search' => $args['search_term'],
            'number' => 2,
        ] );

        $list = [];
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
        	return $list;
        }

        // Section title.
		$list[] = sprintf( '<li class="ir-cat">%s</li>', $args['title'] );

    	foreach ( $terms as $wp_term ) {
        	$term = new \MyListing\Src\Term( $wp_term );
	    	$list[] = sprintf(
	    		'<li>
	                <a href="%1$s">
	                    <span class="cat-icon" style="background-color: %2$s;">%3$s</span>
	                    <span class="category-name">%4$s</span>
	                </a>
	            </li>',
	            esc_url( $term->get_link() ),
	            esc_attr( $term->get_color() ),
	            $term->get_icon( [ 'background' => false ] ),
	            esc_html( $term->get_name() )
	    	);
    	}

    	return $list;
    }
}
