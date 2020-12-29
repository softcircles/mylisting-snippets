<?php

namespace MyListing\Src\Queries;

class Related_Listings extends Query {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		add_action( 'mylisting_ajax_get_related_listings', [ $this, 'handle' ] );
		add_action( 'mylisting_ajax_nopriv_get_related_listings', [ $this, 'handle' ] );
	}

	public function handle() {
		if ( empty( $_GET['listing_id'] ) || empty( $_GET['field_key'] ) ) {
			return;
		}

		$listing = \MyListing\Src\Listing::get( $_GET['listing_id'] );
		if ( ! $listing ) {
			return;
		}

		$field = $listing->get_field_object( sanitize_text_field( $_GET['field_key'] ) );
		$related_items = [];
		if (  $field && $field->get_type() === 'related-listing' ) {
			$related_items = (array) $field->get_related_items();
		}

		$page = absint( isset( $_GET['page'] ) ? $_GET['page'] : 0 );
		$per_page = 9;

		if ( $field && $field->get_key() == 'job-place-relation' ) {
			return $this->send( [
				'post__in' => ! empty( $related_items ) ? $related_items : [0],
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'offset' => $page * $per_page,
				'orderby' => '',
				'order' => 'DESC',
				'output' => [ 'item-wrapper' => 'col-md-4 col-sm-6 col-xs-12' ],
				'fields' => 'ids',
				'recurring_dates' => [
					'event_date'  => [
						'start' => date('Y-m-d H:i:s', current_time('timestamp') ),
						'end' => '',
						'orderby' => true,
						'order' => 'ASC',
						'where_clause' => true,
					]
				],
			] );
		} else {
			return $this->send( [
				'post__in' => ! empty( $related_items ) ? $related_items : [0],
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'offset' => $page * $per_page,
				'orderby' => 'post__in',
				'order' => 'DESC',
				'output' => [ 'item-wrapper' => 'col-md-4 col-sm-6 col-xs-12' ],
				'fields' => 'ids',
			] );
		}
	}
}
