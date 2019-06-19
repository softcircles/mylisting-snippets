<?php

add_action( 'init', function() {

    if ( ! isset( $_GET['job_expires'] ) ) {
        return;
    }

    $args = [
        'post_type'     => 'job_listing',
        'post_status'   => 'publish',
        'posts_per_page'=> -1,
        'meta_query'    => [
            [
               'key'        => '_job_expires',
               'compare'    => 'EXISTS'
            ]
        ]
    ];

    $query = new WP_Query( $args );
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $_job_expires = get_post_meta( get_the_ID(), '_job_expires', true );
            if ( empty( $_job_expires ) ) {
                continue;
            }

            delete_post_meta( get_the_ID(), '_job_expires' );
        }
    }

    exit();

}, 99 );
