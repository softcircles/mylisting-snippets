<?php

add_action( 'init', function() {

    if ( empty( $_GET['geolocate-listings'] ) || ! current_user_can( 'administrator' ) ) {
        return false;
    }

    $next_data = 50;
    $offset = 0;

    do {
        $listings = (array) get_posts([
            'post_type' => 'job_listing',
            'offset'   => $offset,
            'posts_per_page' => $next_data,
            'post_status' => ['publish', 'private', 'expired'],
        ]);

        foreach ( $listings as $listing ) {

            WP_Job_Manager_Geocode::generate_location_data(
                $listing->ID,
                get_post_meta( $listing->ID, '_job_location', true )
            );
        }

        $offset = ( ! $offset ) ? $next_data : $offset + $next_data;

    } while( ! empty( $listings ) );

    exit('All listings are updated, you can close this window.');

} );
