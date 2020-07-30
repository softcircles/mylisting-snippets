<?php
/**
 * Template for rendering a `upcoming-dates` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$dates = $block->get_dates();
if ( empty( $dates ) ) {
	return;
} ?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element content-block upcoming-dates-block">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">
			<ul class="event-dates-timeline">
				<?php foreach ( $dates as $date ): ?>
					<li class="upcoming-event-date">
						<i class="fa fa-calendar-alt"></i>
						<span>
	    					<?php echo \MyListing\Src\Recurring_Dates\display_instance( $date, 'date' ) ?>
						</span>

						<?php if ( $block->get_prop( 'show_add_to_gcal' ) ): ?>
							<a class="add-to-google-cal" target="_blank" rel="nofollow" href="<?php echo esc_url( $date['gcal_link'] ) ?>">
								<i class="fab fa-google"></i>
								<?php _e( 'Add to Google Calendar', 'my-listing' ) ?>
							</a>
						<?php endif ?>
					</li>
				<?php endforeach ?>
			</ul>
		</div>
	</div>
</div>
