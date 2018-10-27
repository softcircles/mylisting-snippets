<?php

/*
* Collect all listing emails.
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

    $email_list = [];

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $_job_email = get_post_meta( get_the_ID(), '_job_email', true );

            if ( empty( $_job_email ) ) {
                continue;
            }

            $email_list[] = $_job_email;
        }
    }

    echo '<pre>';
    print_r( $email_list );
    exit();
} );
