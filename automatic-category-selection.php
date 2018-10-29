<?php

/*
* Automatic Category Selection With jobs Shortcode.
*/

add_filter( 'job_manager_output_jobs_defaults', function( $args ) {

    if ( 'categories' != get_query_var( 'explore_tab' )  || ! get_query_var( 'explore_category' ) ) {
        return $args;
    }

    $term_name = get_query_var( 'explore_category' );

    $term = get_term_by( 'slug', $term_name, 'job_listing_category' );

    if ( ! $term ) {
        return $args;
    }

    $args['selected_category'] = $term->term_id;

    return $args;
} );
