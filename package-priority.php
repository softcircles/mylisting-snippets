add_action( 'wp_insert_post', function ( $post_id, $post, $update ) {
    // You can find your package ids in WordPress admin panel
    $priority = null;

    switch ( $package_id ) {
        case 46 : // Premium Package
            $priority = 5;
        break;

        case 48 : // Advanced Package
            $priority = 7;
        break;
    }

    if ( is_null( $priority ) ) {
        return null;
    }

    update_post_meta( $post_id, '_featured', $priority );
}, 10, 3 );
