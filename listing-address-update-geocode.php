<?php


add_action( 'init', function() {
    if ( empty( $_GET['geolocate_listings'] ) || ! current_user_can( 'administrator' ) ) {
        return;
    }
    $next_data = 50;
    $offset = 0;
    do {
        $listings = (array) get_posts( [
            'post_type' => 'job_listing',
            'offset'        => $offset,
            'posts_per_page' => $next_data,
            'post_status' => ['publish', 'private', 'expired'],
            'meta_query' => [
                'relation' => 'OR',
                [ 'key' => 'geolocation_lat', 'compare' => 'NOT EXISTS' ],
                [ 'key' => 'geolocation_long', 'compare' => 'NOT EXISTS' ],
            ],
        ] );

        printf(
            "Fetching geolocation data from listing %d to %d <br />",
            $offset + 1,
            $offset + $next_data
        );
        flush();
        ob_flush();
        foreach ( $listings as $listing ) {

            if ( ! ( $location = get_post_meta( $listing->ID, '_job_location', true ) ) ) {
                printf( '<p style="color: #8e8e8e;">Skipping geolocation for listing #%d (missing address)</p>', $listing->ID );
                continue;
            }

            if ( $_location_updated = get_post_meta( $listing->ID, '_location_updated', true ) ) {
                printf( '<p style="color: #8e8e8e;">Already Updated for listing #%d</p>', $listing->ID );
                continue;
            }

            $location = explode( ',', $location );

            $geocoded = geoLocate( $location[0], $location[1] );

            update_post_meta( $listing->ID, 'geolocation_lat', $geocoded['lat'] );
            update_post_meta( $listing->ID, 'geolocation_long', $geocoded['long'] );
            update_post_meta( $listing->ID, 'geolocation_formatted_address', $geocoded['formatted_address'] );
            update_post_meta( $listing->ID, '_job_location', $geocoded['formatted_address'] );
            update_post_meta( $listing->ID, '_location_updated', true );
        }

        $offset = ( ! $offset ) ? $next_data : $offset + $next_data;
    } while( ! empty( $listings ) );
    exit('All listings are updated, you can close this window.');
}, 250 );


function geoLocate($lat,$lng)
{
    try {

        $geocode_api_url = "https://maps.google.com/maps/api/geocode/json?key=AIzaSyBoC4pVv-FM7urnysqxUj-VNrFee1WgCVw&latlng=".trim($lat).",".trim($lng)."&sensor=false";

        $result           = wp_remote_get(
            $geocode_api_url,
            array(
                'timeout'     => 5,
                'redirection' => 1,
                'httpversion' => '1.1',
                'sslverify'   => false,
            )
        );
        $result           = wp_remote_retrieve_body( $result );
        $geocoded_address = json_decode( $result );

        if ( $geocoded_address->status ) {
            if ( 'ZERO_RESULTS' === $geocoded_address->status ) {
                throw new Exception( __( 'No results found', 'my-listing' ) );
            } elseif ( 'OVER_QUERY_LIMIT' === $geocoded_address->status ) {
                set_transient( 'mylisting_over_query_limit', 1, HOUR_IN_SECONDS );
                throw new Exception( __( 'Query limit reached', 'my-listing' ) );
            } elseif ( 'OK' === $geocoded_address->status && ! empty( $geocoded_address->results[0] ) ) {
                set_transient( $transient_name, $geocoded_address, DAY_IN_SECONDS * 7 );
            } else {
                throw new Exception( __( 'Geocoding error', 'my-listing' ) );
            }
        } else {
            throw new Exception( __( 'Geocoding error', 'my-listing' ) );
        }
    } catch ( Exception $e ) {
        return new WP_Error( 'error', $e->getMessage() );
    }

    $address                      = [];
    $address['lat']               = sanitize_text_field( $geocoded_address->results[0]->geometry->location->lat );
    $address['long']              = sanitize_text_field( $geocoded_address->results[0]->geometry->location->lng );
    $address['formatted_address'] = sanitize_text_field( $geocoded_address->results[0]->formatted_address );

    return $address;
}
