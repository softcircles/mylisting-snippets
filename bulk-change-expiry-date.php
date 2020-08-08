<?php

add_action( 'init', function() {
    if ( empty( $_GET['update-expiry'] ) || ! current_user_can( 'administrator' ) ) {
        return;
    }

    global $wpdb;

    $next_data = 50;
    $offset = 0;

    do {
        $listings = (array) get_posts( [
            'post_type' => 'job_listing',
            'offset'   => $offset,
            'posts_per_page' => $next_data,
            'fields' => 'ids',
            'post_status' => ['expired'],
        ] );

        printf(
            "Fetching Expiry data from listing %d to %d <br />",
            $offset + 1,
            $offset + $next_data
        );

        flush();
        ob_flush();

        foreach ( $listings as $listing_id ) {
            
            update_post_meta( $listing_id, '_job_expires', '2030-12-31' );
            
            $wpdb->query( $wpdb->prepare("UPDATE $wpdb->posts SET post_status = 'publish' WHERE ID = $listing_id" ) );

            printf( '<p style="color: green;">Expiry successfully Updated for listing #%d</p>', $listing_id );
        }

        $offset = ( ! $offset ) ? $next_data : $offset + $next_data;
    } while( ! empty( $listings ) );

    exit('All listings are updated, you can close this window.');
}, 250 );
