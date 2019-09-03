<?php
$data = c27()->merge_options( [
	'icon' => '',
	'icon_style' => 1,
	'title' => '',
	'content' => '',
	'wrapper_class' => 'block-element grid-item',
	'wrapper_id' => '',
	'ref' => '',
	'do_shortcode' => true,
	'block' => false,
	'listing' => false,
], $data );

if ( ! empty( $data['block'] ) && ! empty( $data['listing'] ) ) {
	$conditions = new MyListing\Src\Conditions( $data['block'], $data['listing'] );
	if ( ! $conditions->passes() ) {
		return;
	}
}

if ( $data['do_shortcode'] ) {
	if ( ! empty( $GLOBALS['wp_embed'] ) ) {
		$data['content'] = $GLOBALS['wp_embed']->run_shortcode( $data['content'] );
	}

	$data['content'] = do_shortcode( $data['content'] );
}

if ( empty( $data['content'] ) ) {
	return false;
}
?>

<div class="<?php echo esc_attr( $data['wrapper_class'] ) ?>" <?php echo $data['wrapper_id'] ? sprintf( 'id="%s"', $data['wrapper_id'] ) : '' ?> v-pre>
	<div class="element content-block">
		<div class="pf-head">
			<div class="title-style-1 title-style-<?php echo esc_attr( $data['icon_style'] ) ?>">
				<?php if ($data['icon_style'] != 3): ?>
					<?php echo c27()->get_icon_markup($data['icon']) ?>
				<?php endif ?>
				<h5><?php echo esc_html( $data['title'] ) ?></h5>
			</div>
		</div>
		<div class="pf-body">
			<?php echo $data['content'] ?>
		</div>
	</div>
</div>
