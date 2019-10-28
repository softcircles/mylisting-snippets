<?php
/**
 * Template for rendering a `code` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get content and parse bracket syntax
$content = $listing->compile_string( $block->get_prop('content') );
	// if ( empty( $content ) ) {
	// 	return;
	// }

// run shortcodes added by admin
if ( ! empty( $GLOBALS['wp_embed'] ) ) {
	$content = $GLOBALS['wp_embed']->run_shortcode( $content );
}
$content = do_shortcode( $content );
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element content-block">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">
			<?php echo $content ?>
		</div>
	</div>
</div>
