<?php

namespace MyListing\Src\Endpoints;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Posts_List_Endpoint {

	public function __construct() {
		add_action( 'mylisting_ajax_mylisting_list_posts', [ $this, 'handle' ] );
		add_action( 'mylisting_ajax_nopriv_mylisting_list_posts', [ $this, 'handle' ] );
	}

	/**
	 * Retrieve a list of listings with the given args.
	 * For use in select/multiselect fields.
	 *
	 * @since 2.0
	 */
	public function handle() {
		mylisting_check_ajax_referrer();

		try {
			$page = ! empty( $_REQUEST['page'] ) ? ( absint( $_REQUEST['page'] ) - 1 ) : 0;
			$author = ! empty( $_REQUEST['author'] ) ? ( absint( $_REQUEST['author'] ) ) : 0;
			$search = ! empty( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';
			$per_page = apply_filters( 'mylisting/queries/posts-list/items-per-page', 25 );
			$listing_type = ! empty( $_REQUEST['listing-type'] )
				? array_map( 'sanitize_text_field', (array) $_REQUEST['listing-type'] )
				: [];

			$post_status = ! empty( $_REQUEST['post-status'] )
				? array_map( 'sanitize_text_field', (array) $_REQUEST['post-status'] )
				: [];

			$args = [
				'post_type' => 'job_listing',
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'offset' => $page * $per_page,
				'meta_query' => [],
				'orderby' => 'name',
				'order' => 'ASC',
			];

			if ( ! empty( $listing_type ) ) {
				$args['meta_query'][] = [
					'key' => '_case27_listing_type',
					'value' => $listing_type,
					'compare' => 'IN',
				];
			}

			if ( ! empty( $post_status ) ) {
				$valid_statuses = array_filter( $post_status, function( $status ) {
					return in_array( $status, [ 'publish', 'pending', 'pending_payment', 'expired' ] );
				} );

				$valid_statuses[] = 'publish';

				if ( ! empty( $valid_statuses ) ) {
					$args['post_status'] = $valid_statuses;
				}
			}

			if ( ! empty( $author ) ) {
				$args['author'] = $author;
			}

			if ( ! empty( trim( $search ) ) ) {
				$args['s'] = trim( $search );
			}

			$posts = get_posts( $args );
			if ( empty( $posts ) || is_wp_error( $posts ) ) {
				throw new \Exception( _x( 'No posts found.', 'Posts dropdown list', 'my-listing' ) );
			}

			$results = [];
			foreach ( $posts as $post ) {
				$results[] = [
					'id' => $post->ID,
					'text' => $post->post_title,
				];
			}

			wp_send_json( [
				'success' => true,
				'results' => $results,
				'more' => count( $results ) === $per_page,
				'args' => CASE27_ENV === 'dev' ? $args : [],
			] );
		} catch ( \Exception $e ) {
			wp_send_json( [
				'success' => false,
				'message' => $e->getMessage(),
			] );
		}
	}
}
