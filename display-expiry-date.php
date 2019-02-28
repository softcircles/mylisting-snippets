<?php


add_filter('case27/listingtypes/profile_layout_blocks', function( $content_block ) {

    $content_block[] =
    [
        'type' => 'listing_expiry',
        'icon' => 'fa fa-clock-o',
        'title' => 'Listing Expiry',
    ];

    return $content_block;
});

add_action('case27/listing/blocks/listing_expiry', function( $block ) {

    global $post;

    $listing = MyListing\Src\Listing::get( $post );

    $listing_expiry = get_post_meta( $listing->get_id(), '_job_expires', true );

    if ( ! $listing_expiry ) {
        return false;
    }

    $block_wrapper_class = ' block-type-' . esc_attr( $block['type'] );

    if ( ! empty( $block['show_field'] ) ) {
        $block_wrapper_class .= ' block-field-' . esc_attr( $block['show_field'] );
    }

    if ( ! empty( $block['class'] ) ) {
        $block_wrapper_class .= ' ' . esc_attr( $block['class'] );
    }

    $icon_style = 1;
    ?>

    <div class="<?php echo esc_attr( $block_wrapper_class ) ?>" <?php echo $block['id'] ? sprintf( 'id="%s"', $block['id'] ) : '' ?>>
        <div class="element related-listing-block">
            <div class="pf-head">
                <div class="title-style-1 title-style-<?php echo esc_attr( $icon_style ) ?>">
                    <?php if ($icon_style != 3): ?>
                        <?php echo c27()->get_icon_markup($block['icon']) ?>
                    <?php endif ?>
                    <h5><?php echo esc_html( $block['title'] ) ?></h5>
                </div>
            </div>
            <div class="pf-body">
                <div class="event-host">
                    <div class="expiry-listing-block">
                        <?php echo $listing_expiry; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
} );
