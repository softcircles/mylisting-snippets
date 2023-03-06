<?php

namespace MyListing\Src\Listing_Types\Filters;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Proximity extends Base_Filter {

	protected $form_key = 'proximity';

	public function filter_props() {
		$this->props['type'] = 'proximity';
		$this->props['label'] = 'Proximity';
		$this->props['units'] = 'metric';
		$this->props['step'] = 1;
		$this->props['max'] = 500;
		$this->props['default'] = 10;
	}

	public function apply_to_query( $args, $form_data ) {
		global $wpdb;

		if ( empty( $form_data[ $this->get_form_key() ] ) || empty( $form_data['search_location'] ) ) {
			return $args;
		}

		if ( empty( $form_data['lat'] ) || empty( $form_data['lng'] ) ) {
			return $args;
		}

		$proximity = (float) $form_data[ $this->get_form_key() ];
		$lat = (float) $form_data['lat'];
		$lng = (float) $form_data['lng'];
		$earth_radius = ( $this->get_prop( 'units' ) === 'imperial' ) ? 3959 : 6371;

		$sql = $wpdb->prepare( \MyListing\Helpers::get_proximity_sql(), $earth_radius, $lat, $lng, $lat, $proximity );
		$post_ids = array_keys( (array) $wpdb->get_results( $sql, OBJECT_K ) );

		if ( empty( $post_ids ) ) {
			$post_ids = [0];
		}

		/**
		 * If the `post__in` parameter has already been set, we must make sure to only include
		 * listings that are both in the original `post__in` and in our new custom list, so the
		 * filters don't conflict with each other and behave as expected.
		 *
		 * If `array_intersect` returns zero matches, then no search results should be returned,
		 * so we set `post__in` to `[0]`.
		 */
		if ( ! empty( $args['post__in'] ) ) {
			$post_ids = array_intersect( $args['post__in'], $post_ids );
			if ( empty( $post_ids ) ) {
				$post_ids = [0];
			}
		}

		$args['post__in'] = $post_ids;

		// Remove search_location filter when using proximity filter.
		$args['search_location'] = '';

		return $args;
	}

	public function get_request_value() {
		if ( ! empty( $_GET[ $this->get_form_key() ] ) && is_numeric( $_GET[ $this->get_form_key() ] ) ) {
			return $_GET[ $this->get_form_key() ];
		}

		return $this->get_prop('default');
	}
}
