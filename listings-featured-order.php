<?php

add_action( 'init', function() {

    if ( empty( $_GET['listing_order'] ) || ! current_user_can( 'administrator' ) ) {
        return false;
    }

    $next_data = 50;
    $offset = 0;

    $priority = [];

    do {
        $listings = (array) get_posts([
            'post_type' => 'job_listing',
            'offset'   => $offset,
            'posts_per_page' => $next_data,
            'post_status' => ['publish', 'private', 'expired'],
        ]);

        foreach ( $listings as $listing ) {

            $package_id = get_post_meta( $listing->ID, '_package_id', true );

            if ( ! $package_id ) {
                continue;
            }

            switch ( $package_id ) {

                case 45 : // Premium Package
                    update_post_meta( $listing->ID, '_featured', 5 );
                break;

                case 46 : // Premium Package
                    update_post_meta( $listing->ID, '_featured', 6 );
                break;

                case 47 : // Advanced Package
                    update_post_meta( $listing->ID, '_featured', 7 );
                break;
            }
        }

        $offset = ( ! $offset ) ? $next_data : $offset + $next_data;

    } while( ! empty( $listings ) );

    exit('All listings are updated, you can close this window.');

} );
