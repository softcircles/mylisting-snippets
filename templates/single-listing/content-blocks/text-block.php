<?php
/**
 * Template for rendering a `text` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// check whether the requested field has a value to display
if ( ! ( $listing->has_field( $block->get_prop( 'show_field' ) ) ) ) {
	return;
}

// get the field instance
if ( ! ( $field = $listing->get_field_object( $block->get_prop( 'show_field' ) ) ) ) {
	return;
}

/**
 * Escape html output, unless it's a wp-editor field or a texteditor field with mode set
 * to wp-editor. These require HTML markup for rendering, so shouldn't be escaped.
 */
$escape_html = ! ( $field->get_type() === 'wp-editor' || ( $field->get_type() === 'texteditor' && $field->get_prop('editor-type') !== 'textarea' ) );

/**
 * Render shortcodes in the content if the `allow-shortcodes` setting has been checked. This setting
 * is only available for field types wp-editor and texteditor with mode set to wp-editor.
 */
$allow_shortcodes = in_array( $field->get_type(), [ 'texteditor', 'wp-editor' ], true ) && $field->get_prop('allow-shortcodes') && ! $escape_html;

/**
 * Get the field value. To keep compatibility with previosuly supported fields that have
 * an array as value, we simple join these values in a comma-separated list.
 */
$block_content = $field->get_value();
if ( is_array( $block_content ) ) {
	$block_content = join( ', ', $block_content );
}

// render shortcodes if the block has been configured to do so
if ( $allow_shortcodes ) {
	if ( ! empty( $GLOBALS['wp_embed'] ) ) {
		$block_content = $GLOBALS['wp_embed']->autoembed( $block_content );
	}

	$block_content = do_shortcode( $block_content );
}
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element content-block <?php echo $escape_html ? 'plain-text-content' : 'wp-editor-content' ?>">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">
			<?php if ( $escape_html ): ?>
				<p><?php echo wpautop( wp_kses( $block_content, [] ) ) ?></p>
			<?php else: ?>
				<?php echo wpautop( str_replace('<a ', '<a data-vars-ga-category="TEST123" ', $block_content) ) ?>
			<?php endif ?>
		</div>
	</div>
</div>
