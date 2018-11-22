<?php

/*
* Collect all listings Email and Listing Name
*/

add_action( 'init', function() {

    if ( ! isset( $_GET['get_email'] ) ) {
        return;
    }

    $args = [
        'post_type'     => 'job_listing',
        'post_status'   => 'publish',
        'posts_per_page'=> -1,
        'meta_query'    => [
            [
               'key'        => '_job_email',
               'compare'    => 'EXISTS'
            ]
        ]
    ];

    $result = [];

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $_job_email = get_post_meta( get_the_ID(), '_job_email', true );
            $_job_title = get_the_title( get_the_ID() );

            $result[] = [
                $_job_email,
                $_job_title
            ];
        }
    }

    wp_reset_postdata();

    echo '<pre>';
    print_r( $result );
    exit();
} );
