<?php

add_action( 'init', function() {

    if ( empty( $_GET['update_email'] ) || ! current_user_can( 'administrator' ) ) {
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
        ] );

        foreach ( $listings as $listing ) {

            $old_email_key = get_post_meta( $listing->ID, '_email', true );

            if ( empty( $old_email_key ) ) {
                continue;
            }

            update_post_meta( $listing->ID, '_job_email', $old_email_key );
        }

        $offset = ( ! $offset ) ? $next_data : $offset + $next_data;

    } while( ! empty( $listings ) );

    exit('All listings are updated, you can close this window.');

}, 250 );
