<?php

add_action( 'mylisting/admin/save-listing-data', function( $post_id, $listing ) {

    // update expiry date
    if ( isset( $_POST['mylisting_modify_expiry'] ) && $_POST['mylisting_modify_expiry'] === 'yes' ) {

        if ( ! empty( $_POST['mylisting_expiry_date'] ) ) {
            update_post_meta( $post_id, '_job_expires', strtotime( date( 'Y-m-d', $_POST['mylisting_expiry_date'] ) ) );
        }

        delete_post_meta( $post_id, '_job_expires' );
    }

}, 100, 2 );

add_action( 'init', function() {
    if ( ! isset( $_GET['job_expires'] ) ) {
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
            'post_status' => ['publish', 'expired', 'pending', 'preview', 'pending_payment'],
            'meta_query'    => [
                [
                   'key'        => '_job_expires',
                   'compare'    => 'EXISTS'
                ]
            ]
        ] );
        printf(
            "Fetching expiry date from listing %d to %d <br />",
            $offset + 1,
            $offset + $next_data
        );
        flush();
        ob_flush();
        foreach ( $listings as $listing ) {
            if ( ! ( $job_expires = get_post_meta( $listing->ID, '_job_expires', true ) ) ) {
                printf( '<p style="color: #8e8e8e;">Skipping expiry for listing #%d (missing date)</p>', $listing->ID );
                continue;
            }

            printf( '<p style="color: green;">Expiry Date successful for listing #%d </p>', $listing->ID );

           delete_post_meta( $listing->ID, '_job_expires' );
            
            // Change the status of each post to publish
            $wpdb->query( $wpdb->prepare("UPDATE $wpdb->posts SET post_status = 'publish' WHERE ID = $listing->ID" ) );

            // Check to see if loop is returning posts, and if they were updated
            printf( '<p style="color: green;">Expiry Date successful for listing #%d</p>', $listing->ID );
        }

        $offset = ( ! $offset ) ? $next_data : $offset + $next_data;
    } while( ! empty( $listings ) );
    exit('All listings are updated, you can close this window.');
}, 250 );
