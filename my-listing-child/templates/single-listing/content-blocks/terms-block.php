<?php
/**
 * Template for rendering a `terms` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// Keys = taxonomy name
// Value = taxonomy field name
$taxonomies = array_merge( [
    'job_listing_category' => 'category',
    'case27_job_listing_tags' => 'tags',
    'region' => 'region',
], mylisting_custom_taxonomies( 'slug', 'slug' ) );

$field_key = isset( $taxonomies[ $block->get_prop('taxonomy') ] ) ? $taxonomies[ $block->get_prop('taxonomy') ] : false;

// get the field instance
if ( ! $field_key || ! ( $field = $listing->get_field_object( $field_key ) ) ) {
	return;
}

// get list of terms
$terms = $field->get_value();

// validate
if ( empty( $terms ) || is_wp_error( $terms ) ) {
	return;
}

// format for display
$formatted_terms = array_filter( array_map( function( $term ) {
	if ( ! $term = \MyListing\Src\Term::get( $term ) ) {
		return false;
	}

	return [
		'link' => $term->get_link(),
		'name' => $term->get_full_name(),
		'color' => $term->get_color(),
		'text_color' => $term->get_text_color(),
		'icon' => $term->get_icon( [ 'background' => false, 'color' => false ] ),
	];
}, $terms ) );
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">

			<?php if ( $block->get_prop('style') === 'list-block' ): ?>

				<?php mylisting_locate_template(
					'templates/single-listing/content-blocks/lists/outlined-list.php', [
					'items' => $formatted_terms
				] ) ?>

			<?php else: ?>

				<?php mylisting_locate_template(
					'templates/single-listing/content-blocks/lists/colored-list.php', [
					'items' => $formatted_terms
				] ) ?>

			<?php endif ?>

		</div>
	</div>
</div>
