<?php

namespace MyListing\Apis\Wp_All_Import;

if ( ! defined('ABSPATH') ) {
	exit;
}

function import_location( $field, $field_value, $log ) {
	$method = $field_value['method'] ?? 'address';
	$previous_address = get_post_meta( $field->listing->get_id(), '_'.$field->get_key(), true );
	$previous_lat = get_post_meta( $field->listing->get_id(), 'geolocation_lat', true );
	$previous_long = get_post_meta( $field->listing->get_id(), 'geolocation_long', true );
	$force_geocode = apply_filters( 'mylisting/wp-all-import/force-geocode', false );

	if ( $method === 'address' ) {
		$geocoder = \MyListing\Src\Geocoder\Geocoder::get();
		if ( is_null( $geocoder ) ) {
			return $log( sprintf(
				'<strong>WARNING:</strong>"%s" - no geocoding service available, skipping.',
				$field->get_label()
			) );
		}

		$address = $field_value['address'] ?? null;

		if ( ! empty( $address ) && is_serialized( $address ) ) {
			$address = maybe_unserialize( $address );

			$locations = validate_location_values( $address );

			if ( empty( $locations ) ) {
				return $log( sprintf(
					'<strong>WARNING:</strong>"%s" skipping.',
					$field->get_label()
				) );
			}

			$_POST[ $field->get_key() ] = $locations;
			$field->update();
			unset( $_POST[ $field->get_key() ] );

		} else {

			try {
				$feature = $geocoder->geocode( $address );

				$_POST[ $field->get_key() ] = [ [
					'address' => $address,
					'lat' => $feature['latitude'],
					'lng' => $feature['longitude'],
				] ];
				$field->update();
				unset( $_POST[ $field->get_key() ] );
			} catch ( \Exception $e ) {
				return $log( sprintf(
					'<strong>WARNING:</strong>"%s" - geocoding failed: "%s", skipping.',
					$field->get_label(),
					$e->getMessage()
				) );
			}
		}
	}

	if ( $method === 'coordinates' ) {
		$geocoder = \MyListing\Src\Geocoder\Geocoder::get();
		if ( is_null( $geocoder ) ) {
			return $log( sprintf(
				'<strong>WARNING:</strong>"%s" - no reverse geocoding service available, skipping.',
				$field->get_label()
			) );
		}

		$latitude = $field_value['latitude'] ?? null;
		$longitude = $field_value['longitude'] ?? null;

		// don't geocode if lat/lng have not changes and an address is present, unless explicitly set
		// to geocode using `add_filter( 'mylisting/wp-all-import/force-geocode', '__return_true' );`
		if (
			$force_geocode !== true && ! empty( $previous_address )
			&& ( (string) $previous_lat === (string) $latitude )
			&& ( (string) $previous_long === (string) $longitude ) ) {
			return $log( sprintf(
				'<strong>NOTICE:</strong>"%s" - coordinates have not changed and an address is already present, skipping.',
				$field->get_label()
			) );
		}

		try {
			$feature = $geocoder->geocode( [ $latitude, $longitude ] );
			$_POST[ $field->get_key() ] = [ [
				'address' => $feature['address'],
				'lat' => $latitude,
				'lng' => $longitude,
			] ];
			$field->update();
			unset( $_POST[ $field->get_key() ] );
		} catch ( \Exception $e ) {
			return $log( sprintf(
				'<strong>WARNING:</strong>"%s" - reverse geocoding failed: "%s", skipping.',
				$field->get_label(),
				$e->getMessage()
			) );
		}
	}

	if ( $method === 'manual' ) {
		$address = $field_value['manual_address'] ?? '';
		$latitude = $field_value['manual_latitude'] ?? '';
		$longitude = $field_value['manual_longitude'] ?? '';
		$_POST[ $field->get_key() ] = [ [
			'address' => $address,
			'lat' => $latitude,
			'lng' => $longitude,
		] ];
		$field->update();
		unset( $_POST[ $field->get_key() ] );
	}
}

function validate_location_values( $value = [] ) {
	$geocoder = \MyListing\Src\Geocoder\Geocoder::get();

	$locations = [];

	foreach ( $value as $index => $address_data ) {
		if ( empty( $address_data ) || ! is_string( $address_data ) ) {
			continue;
		}

		// Explode the input string by comma separator
		$parts = explode(',', $address_data );

		// Check if there are at least two elements
		if (count($parts) >= 2) {
			// Separate the last two elements as separate keys from the rest of the array
			$lastTwo = array_splice($parts, -2);
			$keys = array('lat', 'lng');
			$lastTwo = array_combine($keys, $lastTwo);
		} else {
			$lastTwo = array('lat' => '', 'lng' => '');
		}

		// Combine the remaining elements into the address key
		$address = implode(',', $parts);
		$location_data = array('address' => $address) + $lastTwo;

		try {

			$feature = $geocoder->geocode( $location_data['address'] );

			$locations[ $index ] = [
				'address' => $location_data['address'],
				'lat' => $feature['latitude'],
				'lng' => $feature['longitude'],
			];
		} catch ( \Exception $e ) {
			continue;
		}
	}

	return $locations;
}
