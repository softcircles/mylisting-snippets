<?php

namespace MyListing\Src\Queries;

class Explore_Listings extends Query {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		add_action( 'mylisting_ajax_get_listings', [ $this, 'handle' ] );
		add_action( 'mylisting_ajax_nopriv_get_listings', [ $this, 'handle' ] );

		// @todo: use the custom ajax handler instead of wp_ajax
		add_action( 'wp_ajax_get_listings', [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_get_listings', [ $this, 'handle' ] );
	}

	/**
	 * Handle AJAX listing queries.
	 *
	 * @since 1.0.0
	 */
	public function handle() {
		check_ajax_referer( 'c27_ajax_nonce', 'security' );

		$result = $this->run( $_GET );

		wp_send_json( $result );
	}

	/**
	 * Handle Explore Listings requests, typically $_GET or $_POST.
	 * Request can be manually constructed, which allows using
	 * this function outside Ajax/POST context.
	 *
	 * @since 1.7.0
	 */
	public function run( $request ) {
		global $wpdb;

		if ( empty( $request['form_data'] ) || ! is_array( $request['form_data'] ) || empty( $request['listing_type'] ) ) {
			return false;
		}

		if ( ! ( $listing_type_obj = ( get_page_by_path( $request['listing_type'], OBJECT, 'case27_listing_type' ) ) ) ) {
			return false;
		}

		$type = new \MyListing\Src\Listing_Type( $listing_type_obj );
		$form_data = $request['form_data'];
		$starttime = microtime(true);



		$page = absint( isset($form_data['page']) ? $form_data['page'] : 0 );
		$per_page = absint( isset($form_data['per_page']) ? $form_data['per_page'] : c27()->get_setting('general_explore_listings_per_page', 9));
		$orderby = sanitize_text_field( isset($form_data['orderby']) ? $form_data['orderby'] : 'date' );
		$context = sanitize_text_field( isset( $form_data['context'] ) ? $form_data['context'] : 'advanced-search' );

		if( in_array( $type->get_slug(), [ 'listing-type-slug' ] ) ){
			$per_page = 15;	
		}

		$args = [
			'order' => sanitize_text_field( isset($form_data['order']) ? $form_data['order'] : 'DESC' ),
			'offset' => $page * $per_page,
			'orderby' => $orderby,
			'posts_per_page' => $per_page,
			'tax_query' => [],
			'meta_query' => [],
			'fields' => 'ids',
			'recurring_dates' => [],
		];

		$this->get_ordering_clauses( $args, $type, $form_data );

		// Make sure we're only querying listings of the requested listing type.
		if ( ! $type->is_global() ) {
			$args['meta_query']['listing_type_query'] = [
				'key'     => '_case27_listing_type',
				'value'   =>  $type->get_slug(),
				'compare' => '='
			];
		}

		if ( $context === 'term-search' ) {
			$taxonomy = ! empty( $form_data['taxonomy'] ) ? sanitize_text_field( $form_data['taxonomy'] ) : false;
			$term = ! empty( $form_data['term'] ) ? sanitize_text_field( $form_data['term'] ) : false;

			if ( ! $taxonomy || ! $term || ! taxonomy_exists( $taxonomy ) ) {
				return false;
			}

			$tax_query_operator = apply_filters( 'mylisting/explore/match-all-terms', false ) === true ? 'AND' : 'IN';
			$args['tax_query'][] = [
				'taxonomy' => $taxonomy,
				'field' => 'term_id',
				'terms' => $term,
				'operator' => $tax_query_operator,
				'include_children' => $tax_query_operator !== 'AND',
			];

			// add support for nearby order in single term page
			if ( isset( $form_data['proximity'], $form_data['lat'], $form_data['lng'] ) ) {
				$proximity = absint( $form_data['proximity'] );
				$location = isset( $form_data['search_location'] ) ? sanitize_text_field( stripslashes( $form_data['search_location'] ) ) : false;
				$lat = (float) $form_data['lat'];
				$lng = (float) $form_data['lng'];
				$units = isset($form_data['proximity_units']) && $form_data['proximity_units'] == 'mi' ? 'mi' : 'km';
				if ( $lat && $lng && $proximity && $location ) {
					$earth_radius = $units == 'mi' ? 3959 : 6371;
					$sql = $wpdb->prepare( \MyListing\Helpers::get_proximity_sql(), $earth_radius, $lat, $lng, $lat, $proximity );
					$post_ids = (array) $wpdb->get_results( $sql, OBJECT_K );
					if ( empty( $post_ids ) ) { $post_ids = ['none']; }
					$args['post__in'] = array_keys( (array) $post_ids );
					$args['search_location'] = '';
				}
			}
		} else {
			foreach ( (array) $type->get_advanced_filters() as $filter ) {
				$args = $filter->apply_to_query( $args, $form_data );
			}
		}

		$results = [];
		$result['found_jobs'] = false;
		$listing_wrap = ! empty( $request['listing_wrap'] ) ? sanitize_text_field( $request['listing_wrap'] ) : '';
		$listing_wrap = apply_filters( 'mylisting/explore/listing-wrap', $listing_wrap );

		/**
		 * Hook after the search args have been set, but before the query is executed.
		 *
		 * @since 1.7.0
		 */
		do_action_ref_array( 'mylisting/get-listings/before-query', [ &$args, $type, $result ] );

		$listings = $this->query( $args );

		if ( ! empty( $request['return_query'] ) ) {
			return $listings;
		}

		ob_start();

		if ( CASE27_ENV === 'dev' ) {
			$result['args'] = $args;
			$result['sql'] = $listings->request;
		}

		if ( ! empty( $listings->posts ) ) {
			$result['found_jobs'] = true;

			foreach ( (array) $listings->posts as $listing_id ) {
				printf(
					'<div class="%s">%s</div>',
					$listing_wrap,
					\MyListing\get_preview_card( $listing_id )
				);
			}

			$result['html'] = ob_get_clean();

			wp_reset_postdata();
		} else {
			require locate_template( 'partials/no-listings-found.php' );
			$result['html'] = ob_get_clean();
		}

		// Generate pagination
		$result['pagination'] = c27()->get_listing_pagination( $listings->max_num_pages, ($page + 1) );
		$result['found_posts'] = $listings->found_posts;

		if ( $listings->found_posts < 1 ) {
			$result['showing'] = __( 'No results', 'my-listing' );
		} elseif ( $listings->found_posts === 1 ) {
			$result['showing'] = __( 'Showing <b>one</b> result', 'my-listing' );
		} elseif ( $listings->found_posts <= $per_page ) {
			$result['showing'] = sprintf( __( 'Showing <b>%d</b> results', 'my-listing' ), $listings->found_posts );
		} else {
			$result['showing'] = sprintf(
				__( 'Showing <b>%d</b> results out of <b>%s</b>', 'my-listing'),
				$listings->post_count,
				number_format_i18n( $listings->found_posts )
			);
		}

		if ( CASE27_ENV === 'dev' ) {
			$result['timing'] = sprintf( '%sms', round( ( microtime(true) - $starttime ) * 1000 ) );
		}

		$result['max_num_pages'] = $listings->max_num_pages;

		return $result;
	}

	/**
	 * Generate the 'orderby' argument, allowing for custom 'orderby' clauses.
	 *
	 * @since 1.6.0
	 */
	public function get_ordering_clauses( &$args, $type, $form_data ) {
		$options = (array) $type->get_ordering_options();
		$sortby  = ! empty( $form_data['sort'] ) ? sanitize_text_field( $form_data['sort'] ) : false;
		if ( empty( $options ) ) {
			return false;
		}

		// default to the first ordering option
		if ( empty( $sortby ) ) {
			$sortby = $options[0]['key'];
		}

		if ( ( $key = array_search( $sortby, array_column( $options, 'key' ) ) ) === false ) {
			return false;
		}

		$option  = $options[$key];
		$clauses = $option['clauses'];
		$orderby = [];

		foreach ( $clauses as $clause ) {
			if ( empty( $clause['context'] ) || empty( $clause['orderby'] ) || empty( $clause['order'] ) || empty( $clause['type'] ) ) {
				continue;
			}

			$clause_hash = substr( md5( json_encode( $clause ) ), 0, 16 );
			$clause_id = sprintf( 'clause-%s-%s', $option['key'], $clause_hash );

			if ( $clause['context'] === 'option' ) {
				if ( $clause['orderby'] === 'rand' ) {
					// Randomize every 3 hours.
					$seed = apply_filters( 'mylisting/explore/rand/seed', floor( time() / 10800 ) );
					$orderby[ "RAND({$seed})" ] = $clause['order'];
				} elseif ( $clause['orderby'] === 'rating' ) {
					add_filter( 'posts_join', [ $this, 'rating_field_join' ], 35, 2 );
					add_filter( 'posts_orderby', [ $this, 'rating_field_orderby' ], 35, 2 );
					$args['mylisting_orderby_rating'] = true; // Note the custom order to $args, so it's cached properly.
					$orderby[ $clause_id ] = []; // Add a dummy orderby, to override the default one.
				} elseif ( $clause['orderby'] === 'proximity' ) {
					$orderby = 'post__in';

					add_filter( 'mylisting/explore/args', function( $args ) use ( $clause ) {
						// Support descending order for distance/proximity.
						if ( $clause['order'] === 'DESC' && ! empty( $args['post__in'] ) ) {
							$args['post__in'] = array_reverse( $args['post__in'] );
						}

						return $args;
					} );
				} else {
					$orderby[ $clause['orderby'] ] = $clause['order'];
				}
			}

			if ( $clause['context'] === 'meta_key' ) {
				$field = $type->get_field( $clause['orderby'] );

				if ( $field && $field->get_type() === 'recurring-date' ) {

					// if a recurring date filter isn't present, join the events table
					// and filter listings with the start date set to the current date
					// so that only future events are shown by default
					$args['recurring_dates'][ $field->get_key() ] = [
						'start' => date('Y-m-d H:i:s', current_time('timestamp')),
						'end' => '',
						'orderby' => true,
						'order' => $clause['order'],
					];

					$orderby[ $clause_id ] = []; // Add a dummy orderby, to override the default one.
				} else {
					$args['meta_query'][ $clause_id ] = [
						'key' => '_' . $clause['orderby'],
						'compare' => 'EXISTS',
						'type' => $clause['type'],
					];

					$orderby[ $clause_id ] = $clause['order'];
				}
			}

			if ( $clause['context'] === 'raw_meta_key' ) {
				$args['meta_query'][ $clause_id ] = [
					'key' => $clause['orderby'],
					'compare' => 'EXISTS',
					'type' => $clause['type'],
				];

				$orderby[ $clause_id ] = $clause['order'];
			}
		}

		if ( ! empty( $orderby ) ) {
			$args['orderby'] = $orderby;

			if ( isset( $args['order'] ) ) {
				unset( $args['order'] );
			}

			// Ignore order by priority if set.
			if ( ! empty( $option['ignore_priority'] ) ) {
				$args['mylisting_ignore_priority'] = true;
				add_filter( 'mylisting/explore/listing-wrap', function( $wrap ) {
					$wrap .= ' hide-priority';
					return $wrap;
				} );
			}
		}

		// dd($clauses, $option);
		// dd($args, $orderby);
	}
}
