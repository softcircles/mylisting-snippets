<?php
/**
 * Display "Head Buttons" in the listing preview card template.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
    exit;
} ?>
<div class="lf-head">
    <?php if ( $listing->get_priority() >= 1 ): ?>
        <div class="lf-head-btn ad-badge" data-toggle="tooltip" data-placement="bottom"
            data-original-title="<?php echo esc_attr( $promotion_tooltip ) ?>">
            <span><i class="icon-flash"></i></span>
        </div>
    <?php endif ?>

    <?php if ( ! empty( $options['buttons'] ) ) {
        foreach ( (array) $options['buttons'] as $button ) {
            $string = $button['label'];
            $attributes = [];
            $classes = [ 'lf-head-btn', has_shortcode( $button['label'], '27-format' ) ? 'formatted' : '' ];

            if ( $is_caching ) {
                list( $string, $attributes, $cls ) = \MyListing\prepare_string_for_cache( $string, $listing );
                $classes += $cls;
            } elseif ( \MyListing\str_contains( $string, '[[work_hours]]' ) ) {
                $classes[] = 'open-status listing-status-'.$listing->schedule->get_status();
            }

            if ( \MyListing\str_contains( $string, '[[:reviews-stars]]' ) ) {
                $classes[] = 'listing-rating rating-preview-card';
            }

            $content = do_shortcode( $listing->compile_string( $string ) );
            if ( ! empty( $content ) ) {?>
                <div class="<?php echo esc_attr( join( ' ', $classes ) ) ?>" <?php echo join( ' ', $attributes ) ?>>
                    <?php if ( is_numeric( $content ) ) : ?>
                        <?php echo number_format_i18n($content) ?>
                    <?php else : ?>
                        <?php echo $content; ?>
                    <?php endif; ?>
                </div>
            <?php }
        }
    } ?>
</div>
