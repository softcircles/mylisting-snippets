<?php


add_shortcode( '27-listing-card', function( $atts = [] ) {

    $atts = shortcode_atts( [
        'id'   => '',
    ], $atts, '27-listing-card');

    if ( ! $atts['id'] ) {
        return false;
    }

    $args = [
        'post_type'     => 'job_listing',
        'post_status'   => 'publish',
        'post__in'      => array( $atts['id'] ),
    ];

// print_r( $args );exit();
    $listings = get_posts( $args );

    if ( ! $listings ) {
        return false;
    }

    ?>

        <div class="row">
            <?php
            foreach ( $listings as $listing ) {
                mylisting_locate_template( 'partials/listing-preview.php', [
                    'listing' => $listing,
                    'wrap_in' => 'col-lg-4 col-md-4 col-sm-4 col-xs-12 grid-item',
                ] );
            }

            // If set to order by rating or proximity, the promotion badge is hidden.
            // After similar listings have been shown, remove this behavior.
            remove_filter( 'mylisting/preview-card/show-badge', '__return_false', 55 );
            ?>
        </div>
    <?php
} );
