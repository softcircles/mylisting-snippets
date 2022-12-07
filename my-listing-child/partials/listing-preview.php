<?php
/**
 * Listing preview card template.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
    exit;
}

$data = array_replace_recursive( [
    'listing' => '',
    'is_caching' => false,
], $data );

if ( ! $data['listing'] ) {
    return;
}

$listing = \MyListing\Src\Listing::get( $data['listing'] );
if ( ! ( $listing && $listing->type ) ) {
    return;
}

$is_caching = $data['is_caching'];

// get the preview template options for the listing type of the current listing
$options = $listing->type->get_preview_options();

// general configuration settings for preview cards
$bg_size = apply_filters( 'mylisting/preview-card:bg-size', 'medium_large', $listing );
$gallery_count = apply_filters( 'mylisting/preview-card:gallery-count', 3, $listing );

$classes = [
    'default' => '',
    'alternate' => 'lf-type-2',
    'list-view' => 'lf-list-view',
];

// Categories.
$taxonomies = array_merge( [
	'job_listing_category' => 'job_category',
	'case27_job_listing_tags' => 'job_tags',
	'region' => 'region',
], mylisting_custom_taxonomies( 'slug', 'slug' ) );
$taxonomy = $options['map_marker']['taxonomy'] ? $options['map_marker']['taxonomy'] : 'job_listing_category';
if ( isset( $taxonomies[ $taxonomy ] ) ) {
	$categories = $listing->get_field( $taxonomies[ $taxonomy ] );
	$first_category = $categories ? new MyListing\Src\Term( $categories[0] ) : false;
}

$listing_thumbnail = $listing->get_logo( 'thumbnail' ) ?: c27()->image( 'marker.jpg' );

// Tagline.
if ( $listing->has_field( 'tagline' ) ) {
    $tagline = $listing->get_field( 'tagline' );
} elseif ( $listing->has_field( 'description' ) ) {
    $tagline = c27()->the_text_excerpt( wp_kses( $listing->get_field( 'description' ), [] ), 114, '&hellip;', false );
} else {
    $tagline = false;
}

// Get the number of details, so the height of the listing preview
// can be reduced if there are many details.
$detailsCount = 0;
foreach ((array) $options['footer']['sections'] as $section) {
    if ( $section['type'] == 'details' ) $detailsCount = count( $section['details'] );
}

$wrapper_classes = get_post_class( [
    'lf-item-container',
    'listing-preview',
    'type-' . $listing->type->get_slug(),
    isset( $classes[ $options['template'] ] ) ? $classes[ $options['template'] ] : '',
], $listing->get_id() );

if ( $detailsCount > 2 ) {
    $wrapper_classes[] = 'lf-small-height';
}

if ( $listing->is_verified() ) {
    $wrapper_classes[] = 'c27-verified';
}

$wrapper_classes[] = $listing->get_logo() ? 'has-logo' : 'no-logo';
$wrapper_classes[] = $tagline ? 'has-tagline' : 'no-tagline';
$wrapper_classes[] = ! empty( $options['info_fields'] ) ? 'has-info-fields' : 'no-info-fields';

if ( $listing->get_priority() >= 2 ) {
    $wrapper_classes[] = $priority_class = 'level-promoted';
    $promotion_tooltip = _x( 'Promoted', 'Listing Preview Card: Promoted Tooltip Title', 'my-listing' );
} elseif ( $listing->get_priority() === 1 ) {
    $wrapper_classes[] = $priority_class = 'level-featured';
    $promotion_tooltip = _x( 'Featured', 'Listing Preview Card: Promoted Tooltip Title', 'my-listing' );
} else {
    $wrapper_classes[] = $priority_class = 'level-normal';
    $promotion_tooltip = '';
}

$wrapper_classes[] = sprintf( 'priority-%d', $listing->get_priority() );
$locations = $listing->get_field('location') ? : false;

$terms = array_filter( array_map( function( $term ) {
    if ( ! $term = \MyListing\Src\Term::get( $term ) ) {
        return false;
    }

    return $term->get_name() == 'Restaurants' ? true : false;
}, $categories ) );
?>
<div
    class="<?php echo esc_attr( join( ' ', $wrapper_classes ) ) ?>"
    data-id="listing-id-<?php echo esc_attr( $listing->get_id() ); ?>"
    <?php if ( isset( $categories ) ): ?>
    data-category-icon="<?php echo esc_attr( $first_category ? $first_category->get_icon() : '' ) ?>"
    data-category-color="<?php echo esc_attr( $first_category ? $first_category->get_color() : '' ) ?>"
    data-category-text-color="<?php echo esc_attr( $first_category ? $first_category->get_text_color() : '' ) ?>"
    <?php endif ?>
    data-thumbnail="<?php echo esc_url( $listing_thumbnail ) ?>"
    data-template="<?php echo esc_attr( $options['template'] ) ?>"
    data-locations="<?php echo htmlspecialchars(json_encode($locations), ENT_QUOTES, 'UTF-8') ?>"
>

<?php
if ( $terms ) {
    $options['template'] = 'alternate';
}

if ( has_action( sprintf( 'mylisting/preview-card-template:%s', $options['template'] ) ) ) {
    do_action( sprintf( 'mylisting/preview-card-template:%s', $options['template'] ), $listing, $listing->type );
} elseif ( $preview_template = locate_template( sprintf( 'templates/single-listing/previews/%s.php', $options['template'] ) ) ) {
    require $preview_template;
} else {
    require locate_template( 'templates/single-listing/previews/default.php' );
}
?>
</div>
<?php
/**
 * When caching preview cards, some parts are dynamic and cannot be cached,
 * such as the Open/Closed status. We'll store the work hours and similar info
 * at the end of the preview card, then use it to add this dynamic data when
 * retrieving the cards.
 *
 * @since 2.2.3
 */
$vars = apply_filters( 'mylisting/preview-'.$listing->get_id().'/vars', [] );
if ( ! empty( $vars ) ) {
    printf( '<div hidden #vars>%s</div #vars>', wp_json_encode( $vars ) );
}
