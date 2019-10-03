<?php
/**
 * Template for rendering a `raw` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// get block content
$content = $block->get_prop('content');
if ( empty( $content ) ) {
	return;
}

// check conditions
$conditions = new \MyListing\Src\Conditions( $block, $listing );
if ( ! $conditions->passes() ) {
	return;
}

// run shortcodes
if ( ! empty( $GLOBALS['wp_embed'] ) ) {
	$content = $GLOBALS['wp_embed']->run_shortcode( $content );
}

$content = do_shortcode( $content );

if ( $content ) :
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

<?php
endif;
