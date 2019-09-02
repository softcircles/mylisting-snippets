<?php

add_filter( 'manage_edit-job_listing_columns', function( $defaults ) {
    $defaults['custom_field'] = esc_html__( 'Custom Field', 'my-listing' );
    return $defaults;
}, 99);

add_filter( 'manage_job_listing_posts_custom_column', function( $column ) {
    global $post;

    if ( ! ( $listing = \MyListing\Src\Listing::get( $post ) ) ) {
        return;
    }

    $_value = absint( get_post_meta( $listing->get_id(), '_CUSTOM_FIELD_KEY', true ) );

    if ( $column === 'custom_field' && $_value ) {
        $url = add_query_arg( [
            'post_type'                 => 'job_listing',
            'filter_by_custom_field'    => $_value,
        ], admin_url( 'edit.php' ) );
        printf( '<a href="%s">%s</a>', esc_url( $url ), $_value );
    }

    return $column;
}, 99 );
add_filter( 'parse_query', function( $query ) {

    global $typenow;

    if ( $typenow !== 'job_listing' || empty( $_GET['filter_by_custom_field'] ) || ! is_admin() ) {
        return $query;
    }

    $query->query_vars['meta_key']   = '_CUSTOM_FIELD_KEY';
    $query->query_vars['meta_value'] = $_GET['filter_by_custom_field'];

    return $query;
}, 99 );
