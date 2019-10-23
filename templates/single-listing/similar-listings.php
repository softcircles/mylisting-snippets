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
if ( ! ( is_a( $similar_listings, 'WP_Query') && $similar_listings->posts && $listing->get_priority() != 1 ) ) {
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
            <?php
            foreach ( $similar_listings->posts as $similar_listing ) {
                mylisting_locate_template( 'partials/listing-preview.php', [
                    'listing' => $similar_listing,
                    'wrap_in' => 'col-lg-4 col-md-4 col-sm-4 col-xs-12 grid-item',
                ] );
            }

            // If set to order by rating or proximity, the promotion badge is hidden.
            // After similar listings have been shown, remove this behavior.
            remove_filter( 'mylisting/preview-card/show-badge', '__return_false', 55 );
            ?>
        </div>
    </div>
</section>
