<?php

namespace MyListing\Src\Listing_Types\Filters;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Range extends Base_Filter {

	public function filter_props() {
		$this->props['type'] = 'range';
		$this->props['label'] = 'Range';
		$this->props['show_field'] = '';
		$this->props['option_type'] = 'range';
		$this->props['step'] = 1;
		$this->props['prefix'] = '';
		$this->props['suffix'] = '';
		$this->props['format_value'] = 1;

		// set allowed fields
		$this->allowed_fields = ['text', 'number'];
	}

	public function apply_to_query( $args, $form_data ) {
		if ( empty( $form_data[ $this->get_form_key() ] ) ) {
			return $args;
		}

		$field_key = $this->get_prop( 'show_field' );
		$range_type = $this->get_prop( 'option_type' );
		$range = $form_data[ $this->get_form_key() ];

		if ( empty( $range ) ) {
			return $args;
		}

		/**
		 * To allow range filters to work with float values, we have to cast values
		 * to DECIMAL in MySQL. The default range is (11,2) which will work with
		 * field values from -999999999.99 to 999999999.99.
		 *
		 * This filter allows to widen the range if needed, for example:
		 * add_filter( 'mylisting/range-filter:typecast', function() {
		 *     return 'DECIMAL(14,2)';
		 * } );
		 *
		 * @since 2.4.5
		 */
		$typecast = apply_filters( 'mylisting/range-filter:typecast', 'DECIMAL(11,2)', $this );

		if ( $range_type === 'range' && strpos( $range, '..' ) !== false ) {
			$args['meta_query'][] = [
				'key'     => '_'.$field_key,
				'value'   => array_map( 'floatval', explode( '..', $range ) ),
				'compare' => 'BETWEEN',
				'type' => $typecast,
			];
		}

		if ( $range_type === 'simple' ) {
			$args['meta_query'][] = [
				'key' => '_'.$field_key,
				'value' => floatval( $range ),
				'compare' => '<=',
				'type' => $typecast,
			];
		}

		return $args;
	}

	public function get_range_min() {
		if ( isset( $this->cache['range_min'] ) ) {
			return $this->cache['range_min'];
		}

		global $wpdb;

		if ( $this->listing_type->is_global() ) {
			$post_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT {$wpdb->posts}.ID
					FROM {$wpdb->posts}
					INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
					INNER JOIN {$wpdb->postmeta} AS mt1 ON ( {$wpdb->posts}.ID = mt1.post_id )
					WHERE {$wpdb->postmeta}.meta_key = %s
						AND {$wpdb->postmeta}.meta_value != ''
					    AND {$wpdb->posts}.post_type = 'job_listing'
					    AND {$wpdb->posts}.post_status = 'publish'
					GROUP BY {$wpdb->posts}.ID
					ORDER BY {$wpdb->postmeta}.meta_value +0 ASC
					LIMIT 0, 1
			", '_'.$this->get_prop( 'show_field' ) ) );
		} else {
			$post_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT {$wpdb->posts}.ID
					FROM {$wpdb->posts}
					INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
					INNER JOIN {$wpdb->postmeta} AS mt1 ON ( {$wpdb->posts}.ID = mt1.post_id )
					WHERE {$wpdb->postmeta}.meta_key = %s
						AND {$wpdb->postmeta}.meta_value != ''
					    AND {$wpdb->posts}.post_type = 'job_listing'
					    AND {$wpdb->posts}.post_status = 'publish'
						AND mt1.meta_key = '_case27_listing_type'
						AND mt1.meta_value = %s
					GROUP BY {$wpdb->posts}.ID
					ORDER BY {$wpdb->postmeta}.meta_value +0 ASC
					LIMIT 0, 1
			", '_'.$this->get_prop( 'show_field' ), $this->listing_type->get_slug() ) );
		}

		if ( ! empty( $post_id ) && ( $min_value = get_post_meta( $post_id, '_'.$this->get_prop( 'show_field' ), true ) ) ) {
			$this->cache['range_min'] = (float) $min_value;
		} else {
			$this->cache['range_min'] = 0;
		}

		return $this->cache['range_min'];
	}

	public function get_range_max() {
		if ( isset( $this->cache['range_max'] ) ) {
			return $this->cache['range_max'];
		}

		global $wpdb;
		if ( $this->listing_type->is_global() ) {
			$post_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT {$wpdb->posts}.ID
					FROM {$wpdb->posts}
					INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
					INNER JOIN {$wpdb->postmeta} AS mt1 ON ( {$wpdb->posts}.ID = mt1.post_id )
					WHERE {$wpdb->postmeta}.meta_key = %s
						AND {$wpdb->postmeta}.meta_value != ''
					    AND {$wpdb->posts}.post_type = 'job_listing'
					    AND {$wpdb->posts}.post_status = 'publish'
					GROUP BY {$wpdb->posts}.ID
					ORDER BY {$wpdb->postmeta}.meta_value +0 DESC
					LIMIT 0, 1
			", '_'.$this->get_prop( 'show_field' ) ) );
		} else {
			$post_id = $wpdb->get_var( $wpdb->prepare( "
				SELECT {$wpdb->posts}.ID
					FROM {$wpdb->posts}
					INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
					INNER JOIN {$wpdb->postmeta} AS mt1 ON ( {$wpdb->posts}.ID = mt1.post_id )
					WHERE {$wpdb->postmeta}.meta_key = %s
						AND {$wpdb->postmeta}.meta_value != ''
					    AND {$wpdb->posts}.post_type = 'job_listing'
					    AND {$wpdb->posts}.post_status = 'publish'
						AND mt1.meta_key = '_case27_listing_type'
						AND mt1.meta_value = %s
					GROUP BY {$wpdb->posts}.ID
					ORDER BY {$wpdb->postmeta}.meta_value +0 DESC
					LIMIT 0, 1
			", '_'.$this->get_prop( 'show_field' ), $this->listing_type->get_slug() ) );

		}
		
		if ( ! empty( $post_id ) && ( $max_value = get_post_meta( $post_id, '_'.$this->get_prop( 'show_field' ), true ) ) ) {
			$this->cache['range_max'] = (float) $max_value;
		} else {
			$this->cache['range_max'] = 0;
		}

		return $this->cache['range_max'];
	}

	public function get_request_value() {
		return ! empty( $_GET[ $this->get_form_key() ] )
			? sanitize_text_field( $_GET[ $this->get_form_key() ] )
			: '';
	}

}
