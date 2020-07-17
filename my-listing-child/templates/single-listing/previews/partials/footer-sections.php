<?php
/**
 * Footer Sections template for the preview card template.
 *
 * @since 2.2
 */
if ( ! defined('ABSPATH') ) {
    exit;
}

$section_count = 0;

foreach ( (array) $options['footer']['sections'] as $section ) {

    if ( $section['type'] === 'categories' ) {
        // Keys = taxonomy name
        // Value = taxonomy field name (in the listing type editor)
        $taxonomies = array_merge( [
            'job_listing_category' => 'job_category',
            'case27_job_listing_tags' => 'job_tags',
            'region' => 'region',
        ], mylisting_custom_taxonomies( 'slug', 'slug' ) );

        $taxonomy = ! empty( $section['taxonomy'] ) ? $section['taxonomy'] : 'job_listing_category';
        if ( ! isset( $taxonomies[ $taxonomy ] ) ) {
            continue;
        }

        if ( ! ( $terms = $listing->get_field( $taxonomies[ $taxonomy ] ) ) ) {
            continue;
        }

        $section_count++;
        $category_count = count( $terms );
        $first_category = array_shift( $terms );
        $first_category = new \MyListing\Src\Term( $first_category );
        $category_names = array_map( function( $category ) {
            return $category->name;
        }, $terms );
        $categories_string = join(', ', $category_names);
        ?>
        <div class="listing-details c27-footer-section">
            <ul class="c27-listing-preview-category-list">
                <li>
                    <a href="<?php echo esc_url( $first_category->get_link() ) ?>">
                        <span class="cat-icon" style="background-color: <?php echo esc_attr( $first_category->get_color() ) ?>;">
                            <?php echo $first_category->get_icon( [ 'background' => false ] ) ?>
                        </span>
                        <span class="category-name"><?php echo esc_html( $first_category->get_name() ) ?></span>
                    </a>
                </li>

                <?php if ( count( $terms ) ): ?>
                    <li data-toggle="tooltip" data-placement="top" data-original-title="<?php echo esc_attr( $categories_string ) ?>" data-html="true">
                        <div class="categories-dropdown dropdown c27-more-categories">
                            <a href="#other-categories">
                                <span class="cat-icon cat-more">+<?php echo $category_count - 1 ?></span>
                            </a>
                        </div>
                    </li>
                <?php endif ?>
            </ul>

            <div class="ld-info">
                <ul>
                    <?php if (isset($section['show_quick_view_button']) && $section['show_quick_view_button'] == 'yes'): ?>
                        <?php require locate_template( 'templates/single-listing/previews/partials/quick-view-button.php' ) ?>
                    <?php endif ?>
                    <?php if (isset($section['show_bookmark_button']) && $section['show_bookmark_button'] == 'yes'): ?>
                        <?php require locate_template( 'templates/single-listing/previews/partials/bookmark-button.php' ) ?>
                    <?php endif ?>
                </ul>
            </div>
        </div>
    <?php }

    if ( $section['type'] === 'host' ) {
        $field_key = ! empty( $section['show_field'] ) ? $section['show_field'] : 'related_listing';
        $field = $listing->get_field_object( $field_key );
        if ( ! ( $field && $field->get_type() === 'related-listing' ) ) {
            continue;
        }

        $related_items = (array) $field->get_related_items();
        if ( empty( $related_items ) ) {
            continue;
        }

        $section_count++; ?>

        <?php foreach ( $related_items as $key => $related_item ):
            if ( ! ( $related_item = \MyListing\Src\Listing::get( $related_item ) ) ) {
                continue;
            }

            // pre v2.2, only the listing title could be displayed using [[listing_name]] wildcard;
            // now the full bracket syntax is supported, so keep compatibility by changing [[listing_name]]
            // to the bracket syntax counterpart: [[title]]
            $section['label'] = str_replace( '[[listing_name]]', '[[title]]', $section['label'] );
            ?>
            <div class="event-host c27-footer-section">
                <a href="<?php echo esc_url( $related_item->get_link() ) ?>">
                    <?php if ( $related_item_logo = $related_item->get_logo() ): ?>
                        <div class="avatar">
                            <img src="<?php echo esc_url( $related_item_logo ) ?>" alt="<?php echo esc_attr( $related_item->get_name() ) ?>">
                        </div>
                    <?php endif ?>
                    <span class="host-name"><?php echo $related_item->compile_string( $section['label'] ) ?></span>
                </a>

                <?php if ( $key === 0): ?>
                    <div class="ld-info">
                        <ul>
                            <?php if (isset($section['show_quick_view_button']) && $section['show_quick_view_button'] == 'yes'): ?>
                                <?php require locate_template( 'templates/single-listing/previews/partials/quick-view-button.php' ) ?>
                            <?php endif ?>
                            <?php if (isset($section['show_bookmark_button']) && $section['show_bookmark_button'] == 'yes'): ?>
                                <?php require locate_template( 'templates/single-listing/previews/partials/bookmark-button.php' ) ?>
                            <?php endif ?>
                        </ul>
                    </div>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    <?php }

    if ( $section['type'] === 'author' && ( $listing->author instanceof \MyListing\Src\User ) && $listing->author->exists() ) {
        $section_count++; ?>
            <div class="event-host c27-footer-section">
                <a href="<?php echo esc_url( $listing->author->get_link() ) ?>">
                    <?php if ( $avatar = $listing->author->get_avatar() ): ?>
                        <div class="avatar">
                            <img src="<?php echo esc_url( $avatar ) ?>" alt="<?php echo esc_attr( $listing->author->get_name() ) ?>">
                        </div>
                    <?php endif ?>
                    <span class="host-name">
                        <?php
                        // backward compatibility pre v2.4.5
                        $section['label'] = str_replace( '[[author]]', '[[:authname]]', $section['label'] );
                        echo $listing->compile_string( $section['label'] );
                        ?>
                    </span>
                </a>
                <div class="ld-info">
                    <ul>
                        <?php if (isset($section['show_quick_view_button']) && $section['show_quick_view_button'] == 'yes'): ?>
                            <?php require locate_template( 'templates/single-listing/previews/partials/quick-view-button.php' ) ?>
                        <?php endif ?>
                        <?php if (isset($section['show_bookmark_button']) && $section['show_bookmark_button'] == 'yes'): ?>
                            <?php require locate_template( 'templates/single-listing/previews/partials/bookmark-button.php' ) ?>
                        <?php endif ?>
                    </ul>
                </div>
            </div>
    <?php }

    if ( $section['type'] === 'details' && ! empty( $section['details'] ) ) {
        $section_count++; ?>
        <div class="listing-details-3 c27-footer-section">
            <ul class="details-list">
                <?php foreach ( (array) $section['details'] as $detail ) {
                    $string = $detail['label'];
                    $attributes = [];
                    $classes = [];

                    if ( $is_caching ) {
                        list( $string, $attributes, $cls ) = \MyListing\prepare_string_for_cache( $string, $listing );
                    }

                    if ( \MyListing\str_contains( $string, '[[:reviews-stars]]' ) ) {
                        $classes[] = 'listing-rating';
                    }

                    $content = do_shortcode( $listing->compile_string( $string ) );
                    if ( ! empty( $content ) ) { ?>
                        <li class="<?php echo esc_attr( join( ' ', $classes ) ) ?>"  <?php echo join( ' ', $attributes ) ?>>
                            <?php if ( ! empty( $detail['icon'] ) ): ?>
                                <i class="<?php echo esc_attr( $detail['icon'] ) ?>"></i>
                            <?php endif ?>
                            <?php if ( is_numeric( $content ) ) : ?>
                                <span><?php echo number_format_i18n($content) ?></span>
                            <?php else : ?>
                                <span><?php echo $content; ?></span>
                            <?php endif; ?>
                        </li>
                    <?php }
                } ?>
            </ul>
        </div>
    <?php }

    if ($section['type'] == 'actions' || $section['type'] == 'details') {
        if (
            ( isset($section['show_quick_view_button']) && $section['show_quick_view_button'] == 'yes' ) ||
            ( isset($section['show_bookmark_button']) && $section['show_bookmark_button'] == 'yes' )
         ): $section_count++; ?>
            <div class="listing-details actions c27-footer-section">
                <div class="ld-info">
                    <ul>
                        <?php if (isset($section['show_quick_view_button']) && $section['show_quick_view_button'] == 'yes'): ?>
                            <?php require locate_template( 'templates/single-listing/previews/partials/quick-view-button.php' ) ?>
                        <?php endif ?>
                        <?php if (isset($section['show_bookmark_button']) && $section['show_bookmark_button'] == 'yes'): ?>
                            <?php require locate_template( 'templates/single-listing/previews/partials/bookmark-button.php' ) ?>
                        <?php endif ?>
                    </ul>
                </div>
            </div>
        <?php endif ?>
    <?php }
}

if ( $section_count < 1 ) {
    echo '<div class="c27-footer-empty"></div>';
}
