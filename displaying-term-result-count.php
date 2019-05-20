<?php

add_shortcode( 'mylisting_term_result_count', 'term_result_count' );

function term_result_count( $atts = '' ) {

    if ( ! isset( $atts['id'] ) || $atts['id'] == '' ) {
        return false;
    }

    $categories = (array) get_terms([
        'taxonomy' => 'job_listing_category',
        'hide_empty' => false,
        'include' => 37
    ]);

    if ( is_wp_error( $categories ) ) {
        return false;
    }

    $term_count = 0;

    foreach ( $categories as $category ) {

        if ( ! $category instanceof \WP_Term ) {
            continue;
        }

        $term = new MyListing\Src\Term( $category );

        $term_count = $term->get_count();
    }

    return $term_count;
}
