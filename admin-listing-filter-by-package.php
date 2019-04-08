<?php


add_filter( 'manage_edit-job_listing_columns', function( $defaults ) {
    $defaults['job_package'] = esc_html__( 'Package', 'my-listing' );
    return $defaults;
});

add_filter( 'manage_job_listing_posts_custom_column', function( $column ) {

    global $post;

    if ( ! ( $listing = \MyListing\Src\Listing::get( $post ) ) ) {
        return;
    }

    $_package_id = absint( get_post_meta( $listing->get_id(), '_package_id', true ) );
    $package     = wc_get_product( $_package_id );

    if ( $column === 'job_package' && $package ) {

        $url = add_query_arg( [
            'post_type'         => 'job_listing',
            'filter_by_package' => $package->get_slug(),
        ], admin_url( 'edit.php' ) );

        printf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $package->get_name() ) );
    }

    return $column;
});

add_filter( 'parse_query', function( $query ) {

    global $typenow;

    if ( $typenow !== 'job_listing' || empty( $_GET['filter_by_package'] ) || ! is_admin() ) {
        return $query;
    }

    $package = get_page_by_path( $_GET['filter_by_package'], OBJECT, 'product' );

    if ( is_numeric( $package ) ) {
        $package = get_post( $package );
    }

    if ( ! $package instanceof \WP_Post ) {
        return $query;
    }

    if ( $package->post_type !== 'product' ) {
        return $query;
    }

    $query->query_vars['meta_key']   = '_package_id';
    $query->query_vars['meta_value'] = $package->ID;

    add_action( 'admin_notices', function() use ($package) {

        global $_case27_filter_listings_by_package;
        if ( isset( $_case27_filter_listings_by_package ) ) {
            return;
        }
        $_case27_filter_listings_by_package = 1;

        $back_url = add_query_arg( [
            'post_type'        => 'job_listing',
        ], admin_url( 'edit.php' ) );
        ?>
        <div class="notice notice-info">
            <p>
                <?php printf( _x( 'Showing all %s.', 'WP Admin > Listings > Filter By Package', 'my-listing' ), $package->post_title ) ?>
                <?php printf( '<a href="%s">%s</a>', esc_url( $back_url ), _x( 'Go back.', 'WP Admin > Listings > Filter by package', 'my-listing' ) ) ?>
            </p>
        </div>
        <?php
    } );

    return $query;

});
