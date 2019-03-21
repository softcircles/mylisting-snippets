<?php

add_action( 'init', function() {
    if ( ! isset( $_GET['_user_package_id'] ) ) {
        return;
    }
    $args = [
        'post_type'     => 'job_listing',
        'post_status'   => 'publish',
        'posts_per_page'=> -1,
        'meta_query'    => [
            [
               'key'        => '_user_package_id',
               'compare'    => 'EXISTS'
            ]
        ]
    ];
	
    $query = new WP_Query( $args );
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
			delete_post_meta( get_the_ID(), '_user_package_id' );
        }
    }

    exit('All listings are updated, you can close this window.');
} );
