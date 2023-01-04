<?php
/**
 * Template for rendering an `Repeater` block in single listing page.
 *
 * @since 2.8
 */
if ( ! defined('ABSPATH') ) {
    exit;
}

$value = $listing->get_field( $block->get_prop( 'show_field' ) );
$template = $block->get_prop( 'template' );
$cols = $block->get_prop( 'cols' );
$cols_sm = $block->get_prop( 'cols_sm' );
$cols_xs = $block->get_prop( 'cols_xs' );
$gap = $block->get_prop( 'gap' );
$currency = isset( $field['currency'] ) ? $field['currency'] : '';

if ( empty( $value ) ) {
    return;
}

$style = '';
if ( $gap ) {
    $style .= 'grid-gap:'.esc_attr( $gap ).'px';
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ); echo $template === 'list-view' ? ' repeater-list-view' : '' ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
    <div class="element content-block">
        <div class="pf-head">
            <div class="title-style-1">
                <i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
                <h5><?php echo esc_html( $block->get_title() ) ?></h5>
            </div>
        </div>
        <div class="food-menu-items" <?php if ( $style ): ?>style="<?php echo $style ?>"<?php endif ?>>
            <?php foreach ( $value as $key => $row ) :
                if ( empty( $row['menu-label'] ) ) {
                    continue;
                }
            ?>
                <div class="single-menu-item element mt-30">
                	<div class="gr-content">
                    <?php 
                    if ( isset( $row['mylisting_accordion_photo'] ) && ! empty( $row['mylisting_accordion_photo'] ) ) : ?>
                        <div class="menu-thumb photoswipe-gallery">
                            <?php 
                            $image_url = c27()->get_resized_image( $row['mylisting_accordion_photo'], 'full' );
                            $attachment_id = c27()->get_attachment_by_guid( $row['mylisting_accordion_photo'] );
                            ?>

                            <a
                                class="photoswipe-item"
                                href="<?php echo esc_url( $image_url ) ?>"
                            >
                                <?php echo wp_get_attachment_image( $attachment_id, 'full' ); ?>
                            </a>
                        </div>
                    <?php
                    endif; ?>
                    <div class="menu-content ml-30">
                        <span class="menu-item-title"><?php echo esc_html( $row['menu-label'] ); ?></span>
                        <?php if ( isset( $row['menu-description'] ) ) : ?>
                        	<?php echo wpautop( $row['menu-description'] ); ?>
                        <?php endif; ?>

                        <?php if ( isset( $row['menu-url'] ) && isset( $row['link-label'] ) ) : ?>
                            <a href="<?php echo esc_url( $row['menu-url'] ); ?>" class="button buttons button-5"><?php echo $row['link-label'] ? wp_kses_post( $row['link-label'] ) : _ex( 'Link', 'General repeater field button', 'my-listing' ) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if ( isset( $row['menu-price'] ) && $row['menu-price'] ) : ?>
                    	<div class="menu-price-btn"><?php echo $row['menu-price']; echo $currency ? $currency : ''; ?></div>
                    <?php endif; ?>
                </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style type="text/css">
        <?php if ( $cols ): ?>
            @media only screen and (min-width : 1200px) {
                #<?php echo esc_attr( $block->get_wrapper_id() ) ?> .food-menu-items {
                    grid-template-columns: repeat(<?php echo absint( $cols ) ?>, 1fr);
                }
            }
        <?php endif ?>

        <?php if ( $cols_sm ): ?>
            @media (min-width:768px) and (max-width:1200px) {
                #<?php echo esc_attr( $block->get_wrapper_id() ) ?> .food-menu-items {
                    grid-template-columns: repeat(<?php echo absint( $cols_sm ) ?>, 1fr);
                }
            }
        <?php endif ?>

        <?php if ( $cols_xs ): ?>
            @media only screen and (max-width : 768px) {
                #<?php echo esc_attr( $block->get_wrapper_id() ) ?> .food-menu-items {
                    grid-template-columns: repeat(<?php echo absint( $cols_xs ) ?>, 1fr);
                }
            }
        <?php endif ?>
    </style>
</div>
