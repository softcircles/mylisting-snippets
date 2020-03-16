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

			<?php foreach ( $listings as $related_listing ):
				$related_listing = \MyListing\Src\Listing::get( $related_listing );
				?>
				<div class="event-host">
					<a href="<?php echo esc_url( $related_listing->get_link() ) ?>">
						<?php if ( $listing_thumbnail = $related_listing->get_logo() ): ?>
							<div class="avatar">
								<img src="<?php echo esc_url( $listing_thumbnail ) ?>">
							</div>
						<?php endif ?>
						<span class="host-name"><?php echo $related_listing->get_name() ?></span>
					</a>
					<?php if( $website = $related_listing->get_field( 'website' ) ) : ?>
						<a href="<?php echo esc_url( $website ); ?>"><?php esc_html_e( 'Website', 'my-listing' ) ?><span> <?php echo $website; ?></span></a>
					<?php endif; ?>
					<?php if( $phone = $related_listing->get_field( 'phone' ) ) : ?>
						<p> <?php esc_html_e( 'Phone Number', 'my-listing' ); ?><span><?php echo $phone; ?></span> </p>
					<?php endif; ?>
					<?php if( $email = $related_listing->get_field( 'email' ) ) : ?>
						<p> <?php esc_html_e( 'Email', 'my-listing' ); ?><span><?php echo $email; ?></span> </p>
					<?php endif; ?>
				</div>
			<?php endforeach ?>

		</div>
	</div>
</div>
