<?php

global $post;

$listing = MyListing\Src\Listing::get( $post );

if ( ! $listing->type() ) {
    return;
}

// Get the layout blocks for the single listing page.
$layout = $listing->type->get_layout();
$fields = $listing->type->get_fields();
$tagline = $listing->get_field( 'tagline' );

$listing_logo = $listing->get_logo( 'medium' );

$GLOBALS['case27_custom_styles'] .= sprintf( ' body.single-listing .title-style-1 i { color: %s; } ', c27()->get_setting( 'single_listing_content_block_icon_color', '#c7cdcf' ) );
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
                                    $tab_options['type'] = ! empty( $menu_item['related_listing_type'] ) ? $menu_item['related_listing_type'] : '';
                                }

                                if ( $menu_item['page'] == 'comments' && is_user_logged_in() && $listing->get_author_id() == get_current_user_id() ) {
                                    continue;
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

                                $block_wrapper_class = $columns['block-class'];
                                $block_wrapper_class .= ' block-type-' . esc_attr( $block['type'] );

                                if ( ! empty( $block['show_field'] ) ) {
                                    $block_wrapper_class .= ' block-field-' . esc_attr( $block['show_field'] );
                                }

                                if ( ! empty( $block['class'] ) ) {
                                    $block_wrapper_class .= ' ' . esc_attr( $block['class'] );
                                }

                                // Get the block value if available.
                                if ( ! empty( $block['show_field'] ) && $listing->has_field( $block['show_field'] ) ) {
                                    $field_obj = $listing->get_field( $block['show_field'], true );
                                    // Get the field options if available.
                                    $field = $field_obj->options;
                                    $block_content = $field_obj->value;
                                } else {
                                    $block_content = false;
                                    $field = false;
                                }

                                // Text Block.
                                if ( $block['type'] == 'text' && $block_content ) {
                                    $escape_html = true;
                                    $allow_shortcodes = false;
                                    if ( $field ) {
                                        if ( ! empty( $field['type'] ) && in_array( $field['type'], [ 'texteditor', 'wp-editor' ] ) ) {
                                            $escape_html = empty( $field['editor-type'] ) || $field['editor-type'] == 'textarea';

                                            if ( $field['type'] == 'wp-editor' ) {
                                                $escape_html = false;
                                            }

                                            $allow_shortcodes = ! empty( $field['allow-shortcodes'] ) && $field['allow-shortcodes'] && ! $escape_html;
                                        }
                                    }

                                    c27()->get_section( 'content-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_headline',
                                        'title' => $block['title'],
                                        'content' => $block_content,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                        'escape_html' => $escape_html,
                                        'allow-shortcodes' => $allow_shortcodes,
                                    ] );
                                }

                                // Gallery Block.
                                if ( $block['type'] == 'gallery' && ( $gallery_items = (array) $listing->get_field( $block['show_field'] ) ) ) {
                                    $gallery_type = 'carousel';
                                    foreach ((array) $block['options'] as $option) {
                                        if ($option['name'] == 'gallery_type') $gallery_type = $option['value'];
                                    }

                                    if ( array_filter( $gallery_items ) ) {
                                        c27()->get_section('gallery-block', [
                                            'ref' => 'single-listing',
                                            'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi insert_photo',
                                            'title' => $block['title'],
                                            'gallery_type' => $gallery_type,
                                            'wrapper_class' => $block_wrapper_class,
                                            'wrapper_id' => $block['id'],
                                            'gallery_items' => array_filter( $gallery_items ),
                                            'gallery_item_interface' => 'CASE27_JOB_MANAGER_ARRAY',
                                            ]);
                                    }
                                }

                                // Files Block.
                                if ( $block['type'] == 'file' && ( $files = (array) $listing->get_field( $block['show_field'] ) ) ) {
                                    if ( array_filter( $files ) ) {
                                        c27()->get_section('files-block', [
                                            'ref' => 'single-listing',
                                            'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi attach_file',
                                            'title' => $block['title'],
                                            'wrapper_class' => $block_wrapper_class,
                                            'wrapper_id' => $block['id'],
                                            'items' => array_filter( $files ),
                                            ]);
                                    }
                                }

                                // Categories Block.
                                if ( $block['type'] == 'categories' && ( $terms = $listing->get_field( 'job_category' ) ) ) {
                                    c27()->get_section('listing-categories-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                        'title' => $block['title'],
                                        'terms' => $terms,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                        ]);
                                }

                                // Tags Block.
                                if ( $block['type'] == 'tags' && ( $terms = $listing->get_field( 'job_tags' ) ) ) {
                                    c27()->get_section('list-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                        'title' => $block['title'],
                                        'items' => $terms,
                                        'item_interface' => 'WP_TERM',
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                        ]);
                                }

                                if ( $block['type'] == 'terms' ) {
                                    // Keys = taxonomy name
                                    // Value = taxonomy field name
                                    $taxonomies = array_merge( [
                                        'job_listing_category' => 'job_category',
                                        'case27_job_listing_tags' => 'job_tags',
                                        'region' => 'region',
                                    ], mylisting_custom_taxonomies( 'slug', 'slug' ) );

                                    $taxonomy = 'job_listing_category';
                                    $template = 'listing-categories-block';

                                    if ( isset( $block['options'] ) ) {
                                        foreach ((array) $block['options'] as $option) {
                                            if ($option['name'] == 'taxonomy') $taxonomy = $option['value'];
                                            if ($option['name'] == 'style') $template = $option['value'];
                                        }
                                    }

                                    if ( ! isset( $taxonomies[ $taxonomy ] ) ) {
                                        continue;
                                    }

                                    if ( $terms = $listing->get_field( $taxonomies[ $taxonomy ] ) ) {
                                        if ( $template == 'list-block' ) {
                                            c27()->get_section('list-block', [
                                                'ref' => 'single-listing',
                                                'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                                'title' => $block['title'],
                                                'items' => $terms,
                                                'item_interface' => 'WP_TERM',
                                                'wrapper_class' => $block_wrapper_class,
                                                'wrapper_id' => $block['id'],
                                            ]);
                                        } else {
                                            c27()->get_section('listing-categories-block', [
                                                'ref' => 'single-listing',
                                                'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                                'title' => $block['title'],
                                                'terms' => $terms,
                                                'wrapper_class' => $block_wrapper_class,
                                                'wrapper_id' => $block['id'],
                                            ]);
                                        }
                                    }
                                }

                                // Location Block.
                                if ( $block['type'] == 'location' && isset( $block['show_field'] ) && ( $block_location = $listing->get_field( $block['show_field'] ) ) ) {
                                    if ( ! ( $listing_logo = $listing->get_logo( 'thumbnail' ) ) ) {
                                        $listing_logo = c27()->image( 'marker.jpg' );
                                    }

                                    $location_arr = [
                                        'address' => $block_location,
                                        'marker_image' => ['url' => $listing_logo],
                                    ];

                                    if ( $block['show_field'] == 'job_location' && ( $lat = $listing->get_data('geolocation_lat') ) && ( $lng = $listing->get_data('geolocation_long') ) ) {
                                        $location_arr = [
                                            'marker_lat' => $lat,
                                            'marker_lng' => $lng,
                                            'marker_image' => ['url' => $listing_logo],
                                        ];
                                    }

                                    $map_skin = 'skin1';
                                    if ( ! empty( $block['options'] ) ) {
                                        foreach ((array) $block['options'] as $option) {
                                            if ($option['name'] == 'map_skin') $map_skin = $option['value'];
                                        }
                                    }

                                    c27()->get_section('map', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi map',
                                        'title' => $block['title'],
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                        'template' => 'block',
                                        'options' => [
                                            'locations' => [ $location_arr ],
                                            'zoom' => 11,
                                            'draggable' => true,
                                            'skin' => $map_skin,
                                        ],
                                    ]);
                                }

                                // Contact Form Block.
                                if ($block['type'] == 'contact_form') {
                                    $contact_form_id = false;
                                    $email_to = ['job_email'];
                                    foreach ((array) $block['options'] as $option) {
                                        if ($option['name'] == 'contact_form_id') $contact_form_id = absint( $option['value'] );
                                        if ($option['name'] == 'email_to') $email_to = $option['value'];
                                    }

                                    $email_to = array_filter( $email_to );
                                    $recipients = [];
                                    foreach ( $email_to as $email_field ) {
                                        if ( ( $email = $listing->get_field( $email_field ) ) && is_email( $email ) ) {
                                            $recipients[] = $email;
                                        }
                                    }

                                    if ( $contact_form_id && count( $email_to ) && count( $recipients ) ) {
                                        c27()->get_section('raw-block', [
                                            'ref' => 'single-listing',
                                            'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi email',
                                            'title' => $block['title'],
                                            'content' => str_replace('%case27_recipients%', join('|', $email_to), do_shortcode( sprintf( '[contact-form-7 id="%d"]', $contact_form_id ) ) ),
                                            'wrapper_class' => $block_wrapper_class,
                                            'wrapper_id' => $block['id'],
                                            'escape_html' => false,
                                        ]);
                                    }
                                }

                                // Host Block.
                                if ($block['type'] == 'related_listing' && ( $related_listing = $listing->get_field( 'related_listing' ) ) ) {
                                    c27()->get_section('related-listing-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi layers',
                                        'title' => $block['title'],
                                        'related_listing' => $related_listing,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                    ]);
                                }

                                // Countdown Block.
                                if ($block['type'] == 'countdown' && ( $countdown_date = $listing->get_field( $block['show_field'] ) ) ) {
                                    c27()->get_section('countdown-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi av_timer',
                                        'title' => $block['title'],
                                        'countdown_date' => $countdown_date,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                    ]);
                                }

                                // Video Block.
                                if ($block['type'] == 'video' && ( $video_url = $listing->get_field( $block['show_field'] ) ) ) {
                                    c27()->get_section('video-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi videocam',
                                        'title' => $block['title'],
                                        'video_url' => $video_url,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                    ]);
                                }

                                if ( in_array( $block['type'], [ 'table', 'accordion', 'tabs', 'details' ] ) ) {
                                    $rows = [];

                                    foreach ((array) $block['options'] as $option) {
                                        if ($option['name'] == 'rows') {
                                            foreach ((array) $option['value'] as $row) {
                                                if ( ! is_array( $row ) || empty( $row['show_field'] ) || ! $listing->has_field( $row['show_field'] ) ) {
                                                    continue;
                                                }

                                                $row_field = $listing->get_field( $row['show_field'] );
                                                if ( is_array( $row_field ) ) {
                                                    $row_field = join( ', ', $row_field );
                                                }

                                                $rows[] = [
                                                    'title' => $row['label'],
                                                    'content' => $listing->compile_field_string( $row['content'], $row_field ),
                                                    'icon' => isset( $row['icon'] ) ? $row['icon'] : '',
                                                ];
                                            }
                                        }
                                    }
                                }

                                // Table Block.
                                if ( $block['type'] == 'table' && count( $rows ) ) {
                                    c27()->get_section('table-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                        'title' => $block['title'],
                                        'rows' => $rows,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                        ]);
                                }

                                // Details Block.
                                if ( $block['type'] == 'details' && count( $rows ) ) {
                                    c27()->get_section('list-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                        'title' => $block['title'],
                                        'item_interface' => 'CASE27_DETAILS_ARRAY',
                                        'items' => $rows,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                        ]);
                                }

                                // Accordion Block.
                                if ( $block['type'] == 'accordion' && count( $rows ) ) {
                                    c27()->get_section('accordion-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                        'title' => $block['title'],
                                        'rows' => $rows,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                        ]);
                                }

                                // Tabs Block.
                                if ( $block['type'] == 'tabs' && count( $rows ) ) {
                                    c27()->get_section('tabs-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                        'title' => $block['title'],
                                        'rows' => $rows,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                        ]);
                                }

                                // Work Hours Block.
                                if ($block['type'] == 'work_hours' && ( $work_hours = $listing->get_field( 'work_hours' ) ) ) {
                                    c27()->get_section('work-hours-block', [
                                        'wrapper_class' => $block_wrapper_class . ' open-now sl-zindex',
                                        'wrapper_id' => $block['id'],
                                        'ref' => 'single-listing',
                                        'title' => $block['title'],
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi alarm',
                                        'hours' => (array) $work_hours,
                                    ]);
                                }

                                // Social Networks (Links) Block.
                                if ( $block['type'] == 'social_networks' && ( $networks = $listing->get_social_networks() ) ) {
                                    c27()->get_section('list-block', [
                                        'ref' => 'single-listing',
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                        'title' => $block['title'],
                                        'item_interface' => 'CASE27_LINK_ARRAY',
                                        'items' => $networks,
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                    ]);
                                }

                                // Author Block.
                                if ($block['type'] == 'author') {
                                    c27()->get_section('author-block', [
                                        'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi account_circle',
                                        'ref' => 'single-listing',
                                        'author' => $listing->get_author(),
                                        'title' => $block['title'],
                                        'wrapper_class' => $block_wrapper_class,
                                        'wrapper_id' => $block['id'],
                                    ]);
                                }

                                // Code block.
                                if ( $block['type'] == 'code' && ! empty( $block['content'] ) ) {
                                    if ( ( $content = $listing->compile_string( $block['content'] ) ) ) {
                                        c27()->get_section('raw-block', [
                                            'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                            'ref' => 'single-listing',
                                            'title' => $block['title'],
                                            'wrapper_class' => $block_wrapper_class,
                                            'wrapper_id' => $block['id'],
                                            'content' => $content,
                                            'do_shortcode' => true,
                                        ]);
                                    }
                                }

                                // Raw content block.
                                if ( $block['type'] == 'raw' ) {
                                    $content = '';
                                    foreach ((array) $block['options'] as $option) {
                                        if ($option['name'] == 'content') $content = $option['value'];
                                    }

                                    if ( $content ) {
                                        c27()->get_section('raw-block', [
                                            'icon' => ! empty( $block['icon'] ) ? $block['icon'] : 'mi view_module',
                                            'ref' => 'single-listing',
                                            'title' => $block['title'],
                                            'wrapper_class' => $block_wrapper_class,
                                            'wrapper_id' => $block['id'],
                                            'content' => $content,
                                            'block' => $block,
                                            'listing' => $listing,
                                        ]);
                                    }
                                }

                                /**
                                * @todo {
                                *   pass $listing as parameter
                                *   change case27/ to mylisting/
                                *   check if this block type exists in sections/ directory, so the filter doesn't have to be used.
                                * }
                                */
                                do_action( "case27/listing/blocks/{$block['type']}", $block );

                            endforeach ?>

                            <?php echo $columns['main-col-end'] ?>

                        </div>
                    </div>
                <?php endif ?>

                <?php if ($menu_item['page'] == 'comments'): ?>
                    <div>
                        <?php $GLOBALS['case27_reviews_allow_rating'] = $listing->type->is_rating_enabled() ?>
                        <?php comments_template() ?>
                    </div>
                <?php endif ?>

                <?php if ($menu_item['page'] == 'related_listings'): ?>
                    <?php require locate_template( 'templates/single-listing/related.php' ) ?>
                <?php endif ?>

                <?php if ($menu_item['page'] == 'store'): ?>
                    <?php require locate_template( 'templates/single-listing/store.php' ) ?>
                <?php endif ?>

                <?php if ($menu_item['page'] == 'bookings'): ?>
                    <div class="container">
                        <div class="row">
                            <?php // Contact Form Block.
                            if ($menu_item['provider'] == 'basic-form') {
                                $contact_form_id = absint( $menu_item['contact_form_id'] );
                                $email_to = array_filter( [$menu_item['field']] );
                                $recipients = [];
                                foreach ( $email_to as $email_field ) {
                                    if ( ( $email = $listing->get_field( $email_field ) ) && is_email( $email ) ) {
                                        $recipients[] = $email;
                                    }
                                }

                                if ( $contact_form_id && count( $email_to ) && count( $recipients ) ) {
                                    c27()->get_section( 'raw-block', [
                                        'ref' => 'single-listing',
                                        'content' => str_replace('%case27_recipients%', join('|', $email_to), do_shortcode( sprintf( '[contact-form-7 id="%d"]', $contact_form_id ) ) ),
                                        'wrapper_class' => 'col-md-6 col-md-push-3 col-sm-8 col-sm-push-2 col-xs-12 grid-item bookings-form-wrapper',
                                        'escape_html' => false,
                                    ] );
                                }
                            }
                            ?>

                            <?php // TimeKit Widget.
                            if ($menu_item['provider'] == 'timekit' && ( $timekitID = $listing->get_field( $menu_item['field'] ) ) ): ?>
                                <div class="col-md-8 col-md-push-2 c27-timekit-wrapper">
                                    <iframe src="https://my.timekit.io/<?php echo esc_attr( $timekitID ) ?>" frameborder="0"></iframe>
                                </div>
                            <?php endif ?>

                        </div>
                    </div>
                <?php endif ?>

            </section>
        <?php endforeach; ?>
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
