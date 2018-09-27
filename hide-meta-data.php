// Use the following snippet to hide the description while sharing the post on Facebook.

add_filter( 'mylisting\single\og:tags', function( $tags ) {

    if ( isset( $tags['og:description'] ) ) {
        unset( $tags['og:description'] );
    }

    return $tags;
} );
