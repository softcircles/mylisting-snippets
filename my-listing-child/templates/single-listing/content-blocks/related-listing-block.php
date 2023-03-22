<?php
/**
 * Template for rendering a `related_listing` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get the field instance
if ( ! ( $field = $listing->get_field_object( $block->get_prop( 'show_field' ) ) ) ) {
	return;
}

$ids = (array) $field->get_value();
if ( empty( $ids ) ) {
	return;
}

$listings = get_posts( [
	'post_type' => 'job_listing',
	'post_status' => 'publish',
	'post__in' => $ids,
	'posts_per_page' => -1,
	'orderby' => 'post__in',
	'order' => 'DESC',
] );

if ( empty( $listings ) ) {
	return;
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element related-listing-block">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">

			<?php 
			$listing_wrap = 'col-lg-12';
			foreach ( $listings as $related_listing ):
				$related_listing = \MyListing\Src\Listing::get( $related_listing );
				
				printf(
					'<div class="%s">%s</div>',
					$listing_wrap,
					\MyListing\get_preview_card( $related_listing->get_id() )
				);
			endforeach ?>

		</div>
	</div>
</div>
<script type='text/javascript'>
	jQuery(document).ready(function( $ ){
		// Isotope
	    var $grid;
	    if ($('body').hasClass('rtl')) {
	        var $grid = $('.grid').isotope({
	            originLeft: false,
	        });
	    } else {
	        var $grid = $('.grid').isotope();
	    }
	});
</script>
