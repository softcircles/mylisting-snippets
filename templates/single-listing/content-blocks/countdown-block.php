<?php
/**
 * Template for rendering a `countdown` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

$diff = $block->get_date_diff();

if ( empty( $diff ) ) {
 return;
}

if ( ! $diff->invert ) {
	return false;
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element countdown-box countdown-block">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">
			<ul class="countdown-list">
				<li>
					<p><?php echo $diff->invert ? sprintf('%02d', $diff->format('%a')) : '00' ?></p>
					<span><?php _e( 'Days', 'my-listing' ) ?></span>
				</li>
				<li>
					<p><?php echo $diff->invert ? $diff->format('%H') : '00' ?></p>
					<span><?php _e( 'Hours', 'my-listing' ) ?></span>
				</li>
				<li>
					<p><?php echo $diff->invert ? $diff->format('%I') : '00' ?></p>
					<span><?php _e( 'Minutes', 'my-listing' ) ?></span>
				</li>
			</ul>
		</div>
	</div>
</div>
