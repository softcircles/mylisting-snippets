<?php
/**
 * `Similar Listings` section in single listing page.
 *
 * @since 2.0
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

$similar_listings = \MyListing\Src\Queries\Similar_Listings::instance()->run( $listing->get_id() );

if ( $listing->get_package() ) {
    return false;
}

if ( ! ( is_a( $similar_listings, 'WP_Query') && $similar_listings->posts ) ) {
    return;
}
?>
<section class="i-section similar-listings hide-until-load">
    <div class="container">
        <div class="row section-title">
            <h2 class="case27-primary-text">
                <?php _ex( 'You May Also Be Interested In', 'Single Listing > Similar Listings section title', 'my-listing' ) ?>
            </h2>
        </div>

        <div class="row section-body grid">
            <?php foreach ( $similar_listings->posts as $similar_listing_id ) {
                printf(
                    '<div class="%s">%s</div>',
                    apply_filters( 'mylisting/similar-listings/wrapper-class', 'col-lg-4 col-md-4 col-sm-4 col-xs-12 grid-item' ),
                    \MyListing\get_preview_card( $similar_listing_id )
                );
            } ?>
        </div>
    </div>
</section>
