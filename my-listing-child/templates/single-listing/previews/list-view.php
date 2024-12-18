<div class="lf-item <?php echo esc_attr( 'lf-item-'.$options['template'] ) ?>" data-template="list-view">
        <div class="lf-item-info">
            <?php if ( $logo = $listing->get_logo() ): ?>
                <div class="lf-avatar" style="background-image: url('<?php echo esc_url( $logo ) ?>')"></div>
            <?php endif ?>

            <h4 class="case27-primary-text listing-preview-title">
                <?php echo $listing->get_name() ?>
                <?php if ( $listing->is_verified() ): ?>
                    <img height="18" width="18" alt="<?php echo esc_attr( _ex( 'Verified listing', 'Alt text for verified icon', 'my-listing' ) ) ?>" class="verified-listing" src="<?php echo esc_url( c27()->image('tick.svg') ) ?>">
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
</div>

<?php
/**
 * Include footer sections template.
 *
 * @since 1.0
 */
require locate_template( 'templates/single-listing/previews/partials/footer-sections.php' ) ?>
