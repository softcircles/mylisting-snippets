add_action( 'mylisting/get-listings/before-query', function ( &$args ) {
    $listing_types = ['events'];
    
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

    $args['meta_query'][] = [
        'relation' => 'OR',
        [
            'key' => '_job_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE',
        ],
    ];
}, 10 );
