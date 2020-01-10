<?php
/**
 * Template for rendering a `categories` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get list of categories
$terms = $listing->get_field( 'category' );

// validate
if ( empty( $terms ) || is_wp_error( $terms ) ) {
	return;
}
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

			<?php mylisting_locate_template(
				'templates/single-listing/content-blocks/lists/colored-list.php', [
				'items' => array_filter( array_map( function( $term ) {
					if ( ! $term = \MyListing\Src\Term::get( $term ) ) {
						return false;
					}

					return [
						'link' => $term->get_link(),
						'name' => $term->get_name(),
						'color' => $term->get_color(),
						'icon' => $term->get_icon( [ 'background' => false ] ),
						'description' => $term=>get_description(),
					];
				}, $terms ) )
			] ) ?>

		</div>
	</div>
</div>
