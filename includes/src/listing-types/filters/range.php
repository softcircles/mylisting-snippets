<?php

namespace MyListing\Src\Listing_Types\Filters;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Range extends Base_Filter {

	private $cache = [];

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

	public function get_editor_options() {
		$this->getLabelField();
		$this->getSourceField();

		$this->selectProp( 'option_type', 'Type', [
			'range' => 'Range Slider',
			'simple' => 'Single Slider',
		] );

		$this->numberProp( 'step', 'Step size' );
		$this->textProp( 'prefix', 'Prefix' );
		$this->textProp( 'suffix', 'Suffix' );
		$this->checkboxProp( 'format_value', 'Format the numeric value for display (e.g. 12345 becomes 12,345)' );
	}

	public function apply_to_query( $args, $form_data ) {
		$field_key = $this->get_prop( 'show_field' );
		$range_type = $this->get_prop( 'option_type' );

		if ( empty( $form_data[ $field_key ] ) && empty( $form_data[ $field_key.'_default' ] ) ) {
			return $args;
		}

		$range = $form_data[ $field_key ];
		$default_range = $form_data[ $field_key.'_default' ];

		// In case the range values include the maximum and minimum possible field values,
		// then skip, since the meta query is unnecessary, and would only make the query slower.
		if ( $default_range === $range ) {
			return $args;
		}

		if ( $range_type === 'range' && strpos( $range, '::' ) !== false ) {
			$meta_value = array_map('intval', explode('::', $range));
            $meta_type  = 'NUMERIC';

            if ( $field_key == 'leasingfaktor' || $field_key == 'leasingrate') {
                $meta_value = explode('::', $range );
                $meta_type  = 'DECIMAL(3,2)';
            }

			$args['meta_query'][] = [
				'key'     => '_'.$field_key,
				'value'   => $meta_value,
				'compare' => 'BETWEEN',
				'type'    => $meta_type,
			];
		}

		if ( $range_type === 'simple' ) {
			$args['meta_query'][] = [
				'key'     => '_'.$field_key,
				'value'   => intval( $range ),
				'compare' => '<=',
				'type'    => 'NUMERIC',
			];
		}

		return $args;
	}

	public function get_range_min() {
		if ( isset( $this->cache['range_min'] ) ) {
			return $this->cache['range_min'];
		}

		global $wpdb;
		$post_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT {$wpdb->posts}.ID
				FROM {$wpdb->posts}
				INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
				WHERE {$wpdb->postmeta}.meta_key = %s
					AND {$wpdb->postmeta}.meta_value != ''
				    AND {$wpdb->posts}.post_type = 'job_listing'
				    AND {$wpdb->posts}.post_status = 'publish'
				GROUP BY {$wpdb->posts}.ID
				ORDER BY {$wpdb->postmeta}.meta_value +0 ASC
				LIMIT 0, 1
		", '_'.$this->get_prop( 'show_field' ) ) );

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
		$post_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT {$wpdb->posts}.ID
				FROM {$wpdb->posts}
				INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id )
				WHERE {$wpdb->postmeta}.meta_key = %s
					AND {$wpdb->postmeta}.meta_value != ''
				    AND {$wpdb->posts}.post_type = 'job_listing'
				    AND {$wpdb->posts}.post_status = 'publish'
				GROUP BY {$wpdb->posts}.ID
				ORDER BY {$wpdb->postmeta}.meta_value +0 DESC
				LIMIT 0, 1
		", '_'.$this->get_prop( 'show_field' ) ) );

		if ( ! empty( $post_id ) && ( $max_value = get_post_meta( $post_id, '_'.$this->get_prop( 'show_field' ), true ) ) ) {
			$this->cache['range_max'] = (float) $max_value;
		} else {
			$this->cache['range_max'] = 0;
		}

		return $this->cache['range_max'];
	}

	public function get_request_value() {
		if ( ! empty( $_GET[ $this->get_prop('url_key') ] ) ) {
		    $range = explode( '::', (string) $_GET[ $this->get_prop('url_key') ] );
		} elseif ( ! empty( $_GET[ $this->get_prop('show_field') ] ) ) {
		    $range = explode( '::', (string) $_GET[ $this->get_prop('show_field') ] );
		} else {
		    $range = [];
		}

		$value = [];

		if ( ! empty( $range[0] ) && is_numeric( $range[0] ) ) {
		    $value['start'] = $range[0];
		}

		if ( ! empty( $range[1] ) && is_numeric( $range[1] ) ) {
		    $value['end'] = $range[1];
		}

		return $value;
	}

	public function get_request_components() {
		$key = $this->get_prop('show_field');
		$default = $this->get_prop('option_type') === 'simple'
			? $this->get_range_max()
			: $this->get_range_min().'::'.$this->get_range_max();

		return [
			$key => join( '::', $this->get_request_value() ),
			$key.'_default' => $default
		];
	}
}
