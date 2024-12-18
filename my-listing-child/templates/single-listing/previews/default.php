<?php
$link = $listing->get_link();

if ( $listing->get_data('_job_website') ) {
    $link = $listing->get_data('_job_website');
}
?>
<div class="lf-item <?php echo esc_attr( 'lf-item-'.$options['template'] ) ?>" data-template="default">
    <a href="<?php echo esc_url( $link ) ?>">

        <?php
        /**
         * Include section overlay template.
         *
         * @since 1.0
         */
        require locate_template( 'templates/single-listing/previews/partials/overlay.php' ) ?>

        <?php if ($options['background']['type'] == 'gallery' && ( $gallery = $listing->get_field( 'gallery' ) ) ): ?>
            <div class="owl-carousel lf-background-carousel">
                <?php foreach ( array_slice( $gallery, 0, $gallery_count ) as $gallery_image ): ?>
                    <div class="item">
                        <div class="lf-background" style="background-image: url('<?php echo esc_url( c27()->get_resized_image( $gallery_image, $bg_size ) ) ?>');"></div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php else: $options['background']['type'] = 'image'; endif; // Fallback to cover image if no gallery images are present ?>

        <?php if ($options['background']['type'] == 'image' && ( $cover = $listing->get_cover_image( $bg_size ) ) ): ?>
            <div class="lf-background" style="background-image: url('<?php echo esc_url( $cover ) ?>');"></div>
        <?php endif ?>

        <div class="lf-item-info">
            <?php if ( $logo = $listing->get_logo() ): ?>
                <div class="lf-avatar" style="background-image: url('<?php echo esc_url( $logo ) ?>')"></div>
            <?php endif ?>

            <h4 class="case27-primary-text listing-preview-title">
                <?php echo $listing->get_name() ?>
                <?php if ( $listing->is_verified() ): ?>
                    <img class="verified-listing" src="<?php echo esc_url( c27()->image('tick.svg') ) ?>">
                <?php endif ?>
            </h4>

            <?php
            /**
             * Include info fields template.
             *
             * @since 1.0
             */
            require locate_template( 'templates/single-listing/previews/partials/info-fields.php' ) ?>
        </div>

        <?php
        /**
         * Include head buttons template.
         *
         * @since 1.0
         */
        require locate_template( 'templates/single-listing/previews/partials/head-buttons.php' ) ?>
    </a>

    <?php
    /**
     * Include gallery background nav.
     *
     * @since 1.0
     */
    if ( $options['background']['type'] === 'gallery' && $gallery_count > 1 ) {
        require locate_template( 'templates/single-listing/previews/partials/gallery-nav.php' );
    } ?>
</div>

<?php
/**
 * Include footer sections template.
 *
 * @since 1.0
 */
require locate_template( 'templates/single-listing/previews/partials/footer-sections.php' ) ?>
