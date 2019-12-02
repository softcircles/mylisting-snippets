<?php

global $post;

$listing = MyListing\Src\Listing::get( $post );

if ( ! $listing->type ) {
    return;
}

// Get the layout blocks for the single listing page.
$layout = $listing->type->get_layout();
$tagline = $listing->get_field( 'tagline' );

$listing_logo = $listing->get_logo( 'medium' );
?>

<!-- SINGLE LISTING PAGE -->
<div class="single-job-listing <?php echo ! $listing_logo ? 'listing-no-logo' : '' ?>" id="c27-single-listing">
    <input type="hidden" id="case27-post-id" value="<?php echo esc_attr( get_the_ID() ) ?>">
    <input type="hidden" id="case27-author-id" value="<?php echo esc_attr( get_the_author_meta('ID') ) ?>">

    <!-- <section> opening tag is omitted -->
        <?php
        /**
         * Cover section.
         */
        $cover_template_path = sprintf( 'partials/single/cover/%s.php', $layout['cover']['type'] );
        if ( $cover_template = locate_template( $cover_template_path ) ) {
            require $cover_template;
        } else {
            require locate_template( 'partials/single/cover/none.php' );
        } ?>


        <div class="container listing-main-info">
            <div class="col-md-6">
                <div class="profile-name <?php echo esc_attr( $tagline ? 'has-tagline' : 'no-tagline' ) ?> <?php echo esc_attr( $listing->get_rating() ? 'has-rating' : 'no-rating' ) ?>">
                    <?php if ( $listing_logo ): ?>
                        <a
                            class="profile-avatar open-photo-swipe"
                            href="<?php echo esc_url( $listing->get_logo( 'full' ) ) ?>"
                            style="background-image: url('<?php echo esc_url( $listing_logo ) ?>')"
                        ></a>
                    <?php endif ?>

                    <h1 class="case27-primary-text">
                        <?php echo $listing->get_name() ?>
                        <?php if ( $listing->is_verified() ): ?>
                            <span class="verified-badge" data-toggle="tooltip" data-title="<?php echo esc_attr( _x( 'Verified listing', 'Single listing', 'my-listing' ) ) ?>">
                                <img class="verified-listing" data-toggle="tooltip" src="<?php echo esc_url( c27()->image('tick.svg') ) ?>">
                            </span>
                        <?php endif ?>
                        <?php if ( $listing->editable_by_current_user() && function_exists( 'wc_get_account_endpoint_url' ) ):
                            $edit_link = add_query_arg( [
                                'action' => 'edit',
                                'job_id' => $listing->get_id(),
                            ], wc_get_account_endpoint_url( 'my-listings' ) );
                            ?>
                            <a
                                href="<?php echo esc_url( $edit_link ) ?>"
                                class="edit-listing"
                                data-toggle="tooltip"
                                data-title="<?php echo esc_attr( _x( 'Edit listing', 'Single listing edit link title', 'my-listing' ) ) ?>"
                            ><i class="mi edit"></i></a>
                        <?php endif ?>
                    </h1>
                    <div class="pa-below-title">
                        <?php mylisting_locate_template( 'partials/star-ratings.php', [
                            'rating' => $listing->get_rating(),
                            'max-rating' => MyListing\Ext\Reviews\Reviews::max_rating( $listing->get_id() ),
                            'class' => 'listing-rating',
                        ] ) ?>

                        <?php if ( $tagline ): ?>
                            <h2 class="profile-tagline listing-tagline-field"><?php echo esc_html( $tagline ) ?></h2>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <?php
            /**
             * Quick actions list.
             *
             * @since 2.0
             */
            require locate_template( 'templates/single-listing/cover-details.php' );
            ?>
        </div>
    </section>

    <div class="profile-header">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="profile-menu">
                        <ul>
                            <?php
                            $i = 0;
                            $tab_ids = [];
                            foreach ((array) $layout['menu_items'] as $key => $menu_item): $i++;
                                // @todo: move logic to Listing_Type class.
                                if ( ! empty( $menu_item['slug'] ) ) {
                                    $tab_id = $menu_item['slug'];
                                } else {
                                    $tab_id = sanitize_title( $menu_item['label'] );
                                }

                                $tab_ids[ $tab_id ] = isset( $tab_ids[ $tab_id ] ) ? $tab_ids[ $tab_id ]+1 : 1;
                                if ( $tab_ids[ $tab_id ] > 1 ) {
                                    $tab_id .= '-'.$tab_ids[ $tab_id ];
                                }

                                $layout['menu_items'][$key]['slug'] = $tab_id;

                                if (
                                    $menu_item['page'] == 'bookings' &&
                                    $menu_item['provider'] == 'timekit' &&
                                    ! $listing->has_field( $menu_item['field'] )
                                ) { continue; }

                                $tab_options = [];

                                // Store tab options.
                                if ( $menu_item['page'] === 'store' ) {
                                    // Get selected products.
                                    $tab_options['products'] = isset( $menu_item['field'] ) && $listing->get_field( $menu_item['field'] )
                                        ? (array) $listing->get_field( $menu_item['field'] )
                                        : [];

                                    // hide tab if empty.
                                    if ( empty( $tab_options['products'] ) && ! empty( $menu_item['hide_if_empty'] ) && $menu_item['hide_if_empty'] === true ) {
                                        continue;
                                    }
                                }

                                // Related listings tab options.
                                if ( $menu_item['page'] === 'related_listings' ) {
                                    $tab_options['field_key'] = ! empty( $menu_item['related_listing_field'] ) ? $menu_item['related_listing_field'] : '';
                                }

                                ?><li>
                                    <a id="<?php echo esc_attr( 'listing_tab_'.$tab_id.'_toggle' ) ?>" data-section-id="<?php echo esc_attr( $tab_id ) ?>" class="listing-tab-toggle <?php echo esc_attr( "toggle-tab-type-{$menu_item['page']}" ) ?>" data-options="<?php echo c27()->encode_attr( (object) $tab_options ) ?>">
                                        <?php echo esc_html( $menu_item['label'] ) ?>

                                        <?php if ($menu_item['page'] == 'comments'): ?>
                                            <span class="items-counter"><?php echo $listing->get_review_count() ?></span>
                                        <?php endif ?>

                                        <?php if ( $menu_item['page'] === 'related_listings' ): ?>
                                            <span class="items-counter hide"></span>
                                            <span class="c27-tab-spinner tab-spinner">
                                                <i class="fa fa-circle-o-notch fa-spin"></i>
                                            </span>
                                        <?php endif ?>

                                        <?php if ( $menu_item['page'] === 'store' ): ?>
                                            <span class="items-counter"><?php echo number_format_i18n( count( $tab_options['products'] ) ) ?></span>
                                        <?php endif ?>
                                    </a>
                                </li><?php
                            endforeach; ?>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    /**
     * Quick actions list.
     *
     * @since 2.0
     */
    require locate_template( 'templates/single-listing/quick-actions/quick-actions.php' );
    ?>

    <div class="tab-content listing-tabs">
        <?php foreach ((array) $layout['menu_items'] as $key => $menu_item): ?>
            <section class="profile-body listing-tab tab-hidden <?php echo esc_attr( "tab-type-{$menu_item['page']}" ) ?> <?php echo esc_attr( sprintf( 'tab-layout-%s', ! empty( $menu_item['template'] ) ? $menu_item['template'] : 'masonry' ) ) ?> pre-init" id="listing_tab_<?php echo esc_attr( $menu_item['slug'] ) ?>">

                <?php if ($menu_item['page'] == 'main' || $menu_item['page'] == 'custom'):
                    if ( empty( $menu_item['template'] ) ) {
                        $menu_item['template'] = 'masonry';
                    }

                    if ( empty( $menu_item['layout'] ) ) {
                        $menu_item['layout'] = [];
                    }

                    if ( empty( $menu_item['sidebar'] ) ) {
                        $menu_item['sidebar'] = [];
                    }

                    // Column settings for each page template.
                    if ( $menu_item['template'] == 'two-columns' ) {
                        $columns = [
                            'main-col-wrap' => '<div class="col-md-6"><div class="row cts-column-wrapper cts-main-column">',
                            'main-col-end'  => '</div></div>',
                            'side-col-wrap' => '<div class="col-md-6"><div class="row cts-column-wrapper cts-side-column">',
                            'side-col-end'  => '</div></div>',
                            'block-class'   => 'col-md-12',
                        ];
                    } elseif ( $menu_item['template'] == 'full-width' ) {
                        $columns = [
                            'main-col-wrap' => '',
                            'main-col-end'  => '',
                            'side-col-wrap' => '',
                            'side-col-end'  => '',
                            'block-class'   => 'col-md-12',
                        ];
                    } elseif ( in_array( $menu_item['template'], ['content-sidebar', 'sidebar-content'] ) ) {
                        $columns = [
                            'main-col-wrap' => '<div class="col-md-%d"><div class="row cts-column-wrapper cts-left-column">',
                            'main-col-end'  => '</div></div>',
                            'side-col-wrap' => '<div class="col-md-%d"><div class="row cts-column-wrapper cts-right-column">',
                            'side-col-end'  => '</div></div>',
                            'block-class'   => 'col-md-12',
                        ];

                        $columns['main-col-wrap'] = sprintf( $columns['main-col-wrap'], $menu_item['template'] === 'content-sidebar' ? 7 : 5 );
                        $columns['side-col-wrap'] = sprintf( $columns['side-col-wrap'], $menu_item['template'] === 'content-sidebar' ? 5 : 7 );
                    } else {
                        // Masonry.
                        $columns = [
                            'main-col-wrap' => '',
                            'main-col-end'  => '',
                            'side-col-wrap' => '',
                            'side-col-end'  => '',
                            'block-class'   => 'col-md-6 col-sm-12 col-xs-12 grid-item',
                        ];
                    }

                    // For templates with two columns, merge the other column items into the main column.
                    // And divide them with an 'endcolumn' array item, which will later be used to contruct columns.
                    if ( in_array( $menu_item['template'], ['two-columns', 'content-sidebar', 'sidebar-content'] ) ) {
                        $first_col = $menu_item['template'] === 'sidebar-content' ? 'sidebar' : 'layout';
                        $second_col = $first_col === 'layout' ? 'sidebar' : 'layout';

                        $menu_item[ 'layout' ] = array_merge( $menu_item[ $first_col ], ['endcolumn'], $menu_item[ $second_col ] );
                    }
                    ?>

                    <div class="container <?php printf( 'tab-template-%s', $menu_item['template'] ) ?>">
                        <div class="row <?php echo $menu_item['template'] == 'masonry' ? 'listing-tab-grid' : '' ?>">

                            <?php echo $columns['main-col-wrap'] ?>

                            <?php foreach ( $menu_item['layout'] as $block ):
                                if ( $block === 'endcolumn' ) {
                                    echo $columns['main-col-end'];
                                    echo $columns['side-col-wrap'];
                                    $columns['main-col-end'] = $columns['side-col-end'];
                                    continue;
                                }

                                if ( empty( $block['type'] ) ) {
                                    $block['type'] = 'default';
                                }

                                if ( empty( $block['id'] ) ) {
                                    $block['id'] = '';
                                }

                                // Default block icons used on previous versions didn't include the icon pack name.
                                // Since they were all material icons, we just add the "mi" prefix to them.
                                $default_icons = ['view_headline', 'insert_photo', 'view_module', 'map', 'email', 'layers', 'av_timer', 'attach_file', 'alarm', 'videocam', 'account_circle'];
                                if ( ! empty( $block['icon'] ) && in_array( $block['icon'], $default_icons ) ) {
                                    $block['icon'] = sprintf( 'mi %s', $block['icon'] );
                                }

                                $block->add_wrapper_classes( $columns['block-class'] );

                                $block_wrapper_class = $columns['block-class'];
                                $block_wrapper_class .= ' block-type-' . esc_attr( $block['type'] );

                                if ( ! empty( $block['show_field'] ) ) {
                                    $block_wrapper_class .= ' block-field-' . esc_attr( $block['show_field'] );
                                }

                                if ( ! empty( $block['class'] ) ) {
                                    $block_wrapper_class .= ' ' . esc_attr( $block['class'] );
                                }

                                // Get the block value if available.
                                if ( ! empty( $block['show_field'] ) && $listing->has_field( $block['show_field'] ) && ( $field = $listing->get_field( $block['show_field'], true ) ) ) {
                                    $block_content = $field->get_value();
                                } else {
                                    $block_content = false;
                                    $field = false;
                                }

                                // content block location path
                                $template_base = 'templates/single-listing/content-blocks/%s-block.php';

                                // first check if there's a template with the block type in it's name
                                if ( $template = locate_template( sprintf( $template_base, $block->get_type() ) ) ) {
                                    require $template;

                                // some block's type contains underscores; to keep consistency in file naming, support hyphenated versions too
                                } elseif ( $template = locate_template( sprintf( $template_base, str_replace( '_', '-', $block->get_type() ) ) ) ) {
                                    require $template;
                                }

                            endforeach ?>

                            <?php echo $columns['main-col-end'] ?>

                        </div>
                    </div>
                <?php endif ?>

                <?php if ($menu_item['page'] == 'comments'): ?>
                <?php endif ?>

                <?php if ($menu_item['page'] == 'related_listings'): ?>
                    <?php require locate_template( 'templates/single-listing/tabs/related-listings.php' ) ?>
                <?php endif ?>

                <?php if ($menu_item['page'] == 'store'): ?>
                    <?php require locate_template( 'templates/single-listing/tabs/store.php' ) ?>
                <?php endif ?>

                <?php if ($menu_item['page'] == 'bookings'): ?>
                    <?php require locate_template( 'templates/single-listing/tabs/bookings.php' ) ?>
                <?php endif ?>

            </section>
        <?php endforeach; ?>
    </div>

    <div>
        <?php $GLOBALS['case27_reviews_allow_rating'] = $listing->type->is_rating_enabled() ?>
        <?php comments_template() ?>
    </div>
    
    <?php
    /**
     * Similar listings section.
     *
     * @since 2.0
     */
    if ( $layout['similar_listings']['enabled'] && apply_filters( 'mylisting/single/show-similar-listings', true ) !== false ) {
        require locate_template( 'templates/single-listing/similar-listings.php' );
    } ?>

</div>

<?php echo apply_filters( 'mylisting/single/output_schema', $listing->schema->get_markup() ) ?>
