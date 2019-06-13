<?php
	$data = c27()->merge_options([
			'icon' => '',
			'icon_style' => 1,
			'title' => '',
			'rows' => [],
			'wrapper_class' => 'block-element grid-item',
			'wrapper_id' => '',
			'ref' => '',
		], $data);
?>

<div class="<?php echo esc_attr( $data['wrapper_class'] ) ?>" <?php echo $data['wrapper_id'] ? sprintf( 'id="%s"', $data['wrapper_id'] ) : '' ?>>
	<div class="element accordion-block">
		<div class="pf-head">
			<div class="title-style-1 title-style-<?php echo esc_attr( $data['icon_style'] ) ?>">
				<?php if ($data['icon_style'] != 3): ?>
					<?php echo c27()->get_icon_markup($data['icon']) ?>
				<?php endif ?>
				<h5><?php echo esc_html( $data['title'] ) ?></h5>
			</div>
		</div>
		<div class="pf-body">
			<?php $i = 0; $acc_parent_id = uniqid(); ?>
			<div class="panel-group block-accordion <?php echo esc_attr( $acc_parent_id ) ?>" role="tablist" aria-multiselectable="true">
				<?php foreach ((array) $data['rows'] as $row): $i++; $acc_id = uniqid() . '__' . $i;
					if ( is_array( $row['content'] ) ) {
						$row['content'] = join( ', ', $row['content'] );
					}
					?>
					<div class="panel panel-default">
						<div class="panel-heading" role="tab">
							<h4 class="panel-title">
								<a
									role="button"
									data-toggle="collapse"
									data-parent=".<?php echo esc_attr( $acc_parent_id ) ?>"
									href="#<?php echo esc_attr( $acc_id ) ?>"
									aria-expanded="<?php echo $i == 1 ? 'true' : 'false' ?>"
									aria-controls="<?php echo esc_attr( $acc_id ) ?>">
									<?php echo esc_html( $row['title'] ) ?>
								</a>
							</h4>
						</div>
						<div id="<?php echo esc_attr( $acc_id ) ?>" class="panel-collapse collapse" role="tabpanel">
							<div class="panel-body">
								<p>
									<?php echo $row['content'] ?>
								</p>
							</div>
						</div>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	</div>
</div>
