/**
 * SNIPPET #1 - Hide description field
 *
 * Use the following snippet to hide the description while sharing the post on Facebook.
 */

add_filter( 'mylisting\single\og:tags', function( $tags ) {

    if ( isset( $tags['og:description'] ) ) {
        unset( $tags['og:description'] );
    }

    return $tags;
} );

/**
 * SNIPPET #2 - Display the cover image instead of logo
 * 
 * Use the following snippet to display the cover image instead of the logo while sharing on facebook. 
 */

add_filter( 'mylisting\single\og:image', function() {
    return 'cover';
} );
