<?php

add_filter( 'mylisting/sections/listing-feed/args', function( $args ) {
    $listing_types = ['evento'];
    if ( empty( $args['meta_query'] ) || empty( $args['meta_query']['c27_listing_type_clause'] ) ) {
        return $args;
    }
    $meta_value = $args['meta_query']['c27_listing_type_clause']['value'];
    if ( ! isset( $meta_value ) ) {
        return $args;
    }
    if ( is_array( $meta_value ) && ! empty( $meta_value ) ) {
        $meta_value = $meta_value[0];
    }
     // Only apply to the listing types defined in $listing_types.
    if ( ! in_array( $meta_value, $listing_types ) ) {
        return $args;
    }
    $args['meta_query'][] = [
        'relation' => 'OR',
        [
            'key' => '_termino',
            'value' => date('DD MMMM, YY, h:mm A'),
            'compare' => '>=',
            'type' => 'DATE',
        ],
        [
            'key' => '_job_date',
            'value' => '',
        ],
        [
            'key' => '_job_date',
            'compare' => 'NOT EXISTS',
        ],
    ];
    return $args;
}, 100 );
