<?php

/*
* Change Alphabetical Order in Related Listings
*/

add_filter( 'job_manager_get_listings', function( $query_args, $args ) {

    if ( ! isset( $query_args['meta_query'] ) || empty( $query_args['meta_query'] ) ) {
        return $query_args;
    }

    foreach ( $query_args['meta_query'] as $key => $value ) {

        if ( ! $value['key'] && empty( $value['key'] ) ) {
            continue;
        }

        if ( $value['key'] != '_related_listing' ) {
            continue;
        }

        $query_args['orderby'] = 'title';
        $query_args['order']   = 'ASC';
    }

    return $query_args;

}, 99, 2 );
