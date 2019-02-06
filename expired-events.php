<?php

add_action( 'mylisting/get-listings/before-query', function ( &$args ) {
    $listing_types = ['activity'];
    
    if ( empty( $args['meta_query'] ) || empty( $args['meta_query']['listing_type_query'] ) ) {
        return $args;
    }
    
    $meta_value = $args['meta_query']['listing_type_query']['value'];
   
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

    $args['meta_query'] = [
        'listing_type_query' => [
            'key' => '_case27_listing_type',
            'value' => 'activity',
            'compare' => '='
        ],
        [
            'key' => '_date-fin',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE',
        ],
    ];
  
}, 10 );
