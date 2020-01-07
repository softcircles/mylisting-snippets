<?php

add_action( 'init', function() {
    if ( empty( $_GET['update-type'] ) || ! current_user_can( 'administrator' ) ) {
        return;
    }

    $next_data = 50;
    $offset = 0;
    do {
        $listings = (array) get_posts( [
            'post_type' => 'job_listing',
            'offset'   => $offset,
            'posts_per_page' => $next_data,
            'post_status' => ['publish', 'private', 'expired'],
            'meta_query' => [
                'relation' => 'OR',
                [ 'key' => '_case27_listing_type', 'value' => '' ],
                [ 'key' => '_case27_listing_type', 'compare' => 'NOT EXISTS' ],
            ],
        ] );

        printf(
            "Fetching data from listing %d to %d <br />",
            $offset + 1,
            $offset + $next_data
        );
        flush();
        ob_flush();
        foreach ( $listings as $listing ) {
            // print_r( $listing->ID );
            update_post_meta( $listing->ID, '_case27_listing_type', 'plombiers' );
        }
        $offset = ( ! $offset ) ? $next_data : $offset + $next_data;
    } while( ! empty( $listings ) );
    exit('All listings are updated, you can close this window.');
}, 250 );
