<div class="lf-head">
    <?php if ( $listing->get_priority() >= 1 ): ?>
        <div class="lf-head-btn ad-badge" data-toggle="tooltip" data-placement="bottom" data-original-title="<?php echo esc_attr( $promotion_tooltip ) ?>">
            <span><i class="icon-flash"></i></span>
        </div>
    <?php endif ?>

    <?php if ($options['buttons']): ?>

        <?php foreach ($options['buttons'] as $button): ?>

            <?php if ( $button['show_field'] == '__listing_rating' ): ?>
                <?php if ( $listing->get_rating() ): ?>
                    <?php mylisting_locate_template( 'partials/star-ratings.php', [
                        'rating' => $listing->get_rating(),
                        'max-rating' => MyListing\Ext\Reviews\Reviews::max_rating( $listing->get_id() ),
                        'class' => 'listing-rating lf-head-btn rating-preview-card',
                    ] ) ?>
                <?php endif ?>
            <?php elseif ( $button['show_field'] == 'work_hours' ): ?>
                <?php if ( $listing->get_field('work_hours') ): ?>
                    <?php if ( $is_caching ): ?>
                        <var #open-now></var>
                        <?php add_filter( 'mylisting/preview-'.$listing->get_id().'/vars', function( $vars ) use ( $listing ) {
                            $vars['open-now'] = $listing->schedule->get_short_format();
                            return $vars;
                        } ) ?>
                    <?php elseif ( $listing->schedule->get_status() !== 'not-available' ): ?>
                        <?php $open_now = $listing->get_schedule()->get_open_now(); ?>
                        <?php $back_color = ($open_now) ? 'green' : 'red'; ?>
                        <div class="lf-head-btn open-status <?php echo sprintf( 'listing-status-%s', $open_now ? 'open' : 'closed' ) ?>" style="background-color:<?php echo $back_color; ?>;">
                            <span><?php echo $open_now ? __( 'Open', 'my-listing' ) : __( 'Closed', 'my-listing' ) ?></span>
                        </div>
                    <?php endif ?>
                <?php endif ?>
            <?php else:
                if ( ! $listing->has_field( $button['show_field'] ) ) {
                    continue;
                }

                $button_val = $listing->get_field( $button['show_field'] );

                // Escape square brackets so any shortcode added by the listing owner won't be run.
                $button_val = str_replace( [ "[" , "]" ] , [ "&#91;" , "&#93;" ] , $button_val );
                $button_val = apply_filters( 'case27\listing\preview\button\\' . $button['show_field'], $button_val, $button, $listing );

                if ( is_array( $button_val ) ) {
                    $button_val = join( ', ', $button_val );
                }

                $GLOBALS['c27_active_shortcode_content'] = $button_val;
                $btn_content = str_replace( '[[field]]', $button_val, do_shortcode( $button['label'] ) );
                ?>

                <?php if ( strlen( $btn_content ) ): ?>
                    <div class="lf-head-btn <?php echo has_shortcode($button['label'], '27-format') ? 'formatted' : '' ?>">
                        <?php echo $btn_content ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

        <?php endforeach ?>
    <?php endif ?>
</div>
