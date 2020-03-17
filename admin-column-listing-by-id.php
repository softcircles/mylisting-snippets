<?php

add_filter( 'manage_edit-job_listing_columns', function( $defaults ) {
    $defaults['listing_id'] = esc_html__( 'Listing ID', 'my-listing' );
    return $defaults;
}, 99);

add_filter( 'manage_job_listing_posts_custom_column', function( $column ) {
    global $post;

    if ( ! ( $listing = \MyListing\Src\Listing::get( $post ) ) ) {
        return;
    }

    if ( $column === 'listing_id' ) {
        $url = add_query_arg( [
            'post_type'                 => 'job_listing',
            'filter_by_listing_id'    => $listing->get_id(),
        ], admin_url( 'edit.php' ) );
        printf( '<a href="%s">%s</a>', esc_url( $url ), $listing->get_id() );
    }

    return $column;
}, 99 );

add_filter( 'parse_query', function( $query ) {

    global $typenow;

    if ( $typenow !== 'job_listing' || empty( $_GET['filter_by_listing_id'] ) || ! is_admin() ) {
        return $query;
    }
	
	$listing = \MyListing\Src\Listing::get( $_GET['filter_by_listing_id'] );
	
	$query->query_vars['post__in'] = array( $listing->get_id() );

    return $query;
}, 99 );

add_filter( 'manage_edit-job_listing_sortable_columns', function( $columns ) {

    $columns['listing_id'] = 'listing_id';

    return $columns;
} );
