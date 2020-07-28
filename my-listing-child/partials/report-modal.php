<!-- Modal - Report Listing -->
<div id="report-listing-modal" class="modal modal-27" role="dialog">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="sign-in-box element">
				<div class="title-style-1">
					<i class="mi report_problem"></i>
					<h5><?php _e( 'Report this Listing', 'my-listing' ) ?></h5>
				</div>
				<div class="report-wrapper">
					<div class="validation-message"></div>
					<div class="form-group">
						<textarea placeholder="<?php esc_attr_e( 'What\'s wrong with this listing?', 'my-listing' ) ?>" rows="7" class="report-content"></textarea>
					</div>

					<?php
					$field = [];
		$field['label'] = '';
		$field['required'] = true;
		$field['site_key'] = mylisting_get_setting( 'recaptcha_site_key' );
		mylisting_locate_template( 'templates/add-listing/form-fields/recaptcha-field.php', [
			'key'   => 'recaptcha',
			'field' => $field,
		] );
		?>
					<div class="form-group">
						<button type="submit" class="buttons button-2 full-width button-animated report-submit" name="login" value="Login">
							<?php _e( 'Submit Report', 'my-listing' ) ?> <i class="mi keyboard_arrow_right"></i>
						</button>
					</div>
				</div>

				<?php c27()->get_partial( 'spinner', [
					'color' => '#777',
					'classes' => 'center-vh',
					'size' => 24,
					'width' => 2.5,
				] ) ?>
			</div>
		</div>
	</div>
</div>
