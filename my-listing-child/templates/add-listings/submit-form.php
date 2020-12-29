<?php
/**
 * Listing submission form template.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'mylisting-listing-form' );
wp_enqueue_style( 'mylisting-add-listing' );
$can_post = is_user_logged_in() || ! mylisting_get_setting( 'submission_requires_account' );

if ( $form == 'edit-listing' ) {
	$listing = \MyListing\Src\Listing::get( $job_id );
	$_REQUEST['listing_package'] = $listing->get_product_id();
}

if ( ! empty( $_REQUEST['job_id'] ) && absint( $_REQUEST['job_id'] ) && $job_id == $_REQUEST['job_id'] && isset( $_REQUEST['action'] ) && 'edit' == $_REQUEST['action'] && empty( $_REQUEST['listing_package'] ) ) {
	$listing = \MyListing\Src\Listing::get( $job_id );
	$_REQUEST['listing_package'] = $listing->get_product_id();
}

?>

<div class="i-section">
	<div class="container">
		<div class="row section-title">
			<h2 class="case27-primary-text"><?php _ex( 'Your listing details', 'Add listing form', 'my-listing' ) ?></h2>
		</div>
		<form action="<?php echo esc_url( $action ); ?>" method="post" id="submit-job-form" class="job-manager-form light-forms c27-submit-listing-form" enctype="multipart/form-data">

			<?php
			/**
			 * Display login/register message at
			 * the top of the add-listing form.
			 *
			 * @since 1.0
			 */
			require locate_template( 'templates/add-listing/auth.php' ) ?>

			<?php if ( $can_post || \MyListing\Src\Listing::user_can_edit( $job_id ) ) : ?>
				<div class="form-section-wrapper" id="form-section-general">
					<div class="element form-section">
						<div class="pf-head round-icon">
							<div class="title-style-1">
								<i class="icon-pencil-2"></i>
								<h5><?php _ex( 'General', 'Add listing form', 'my-listing' ) ?></h5>
							</div>
						</div>
						<div class="pf-body">

						<?php do_action( 'mylisting/add-listing/form-fields/start' ) ?>

						<?php foreach ( $fields as $key => $field ) : ?>

						<?php if ( $field->get_type() === 'form-heading' ): ?>
						</div></div></div>
						<div class="form-section-wrapper" id="form-section-<?php echo esc_attr( ! empty( $key ) ? $key : \MyListing\Utils\Random_Id::generate(7) ) ?>">
							<div class="element form-section">
							<?php if ( ( $icon = $field->get_prop('icon') ) && ( $label = $field->get_label() ) ): ?>
								<div class="pf-head round-icon">
									<div class="title-style-1">
										<i class="<?php echo esc_attr( $icon ) ?>"></i>
										<h5><?php echo esc_html( $label ) ?></h5>
									</div>
								</div>
							<?php endif ?>
							<div class="pf-body">
							<?php else:
								$classes = [];
								if ( $field->get_type() === 'term-select' ) {
									$classes[] = 'term-type-'.$field->get_prop('terms-template');
								}
								?>
								<div class="fieldset-<?php echo esc_attr( $key ) ?> <?php echo esc_attr( 'field-type-'.$field->get_type() ) ?> form-group <?php echo join( ' ', array_map( 'esc_attr', $classes ) ) ?>">
									<div class="field-head">
										<label for="<?php echo esc_attr( $key ) ?>">
											<?php echo $field->get_label() ?>
											<?php echo $field->is_required() ? '' : ' <small>' . _x( '(optional)', 'Add listing form', 'my-listing' ) . '</small>' ?>
										</label>
										<?php if ( ! empty( $field->get_description() ) ): ?>
											<small class="description"><?php echo $field->get_description() ?></small>
										<?php endif ?>
									</div>
									<div class="field <?php echo $field->is_required() ? 'required-field' : ''; ?>">
										<?php mylisting_locate_template( 'templates/add-listing/form-fields/'.$field->get_type().'-field.php', [ 'key' => $key, 'field' => $field ] ); ?>
									</div>
								</div>
							<?php endif ?>
						<?php endforeach; ?>

						<?php do_action( 'mylisting/add-listing/form-fields/end' ) ?>

						</div>
					</div>
				</div>
				<div class="form-section-wrapper form-footer" id="form-section-submit">
					<div class="form-section">
						<div class="pf-body">
							<div class="hidden">
								<input type="hidden" name="job_manager_form" value="<?php echo esc_attr( $form ) ?>">
								<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ) ?>">
								<input type="hidden" name="step" value="<?php echo esc_attr( $step ) ?>">
								<?php if ( ! empty( $_REQUEST['listing_type'] ) ): ?>
									<input type="hidden" name="listing_type" value="<?php echo esc_attr( $_REQUEST['listing_type'] ) ?>">
								<?php endif ?>
								<?php if ( ! empty( $_REQUEST['listing_package'] ) ): ?>
									<input type="hidden" name="listing_package" value="<?php echo esc_attr( $_REQUEST['listing_package'] ) ?>">
								<?php endif ?>
							</div>

							<div class="listing-form-submit-btn">
								<button type="submit" name="submit_job" class="preview-btn button buttons button-2" value="submit">
									<?php echo esc_attr( $submit_button_text ) ?>
								</button>

								<?php if ( $form === 'submit-listing' ): ?>
									<button type="submit" name="submit_job" class="skip-preview-btn button buttons button-3" value="submit--no-preview">
										<?php echo esc_attr( _x( 'Skip preview and submit', 'Add listing form', 'my-listing' ) ) ?>
									</button>
								<?php endif ?>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</form>
	</div>
</div>

<div class="add-listing-nav">
	<ul></ul>
</div>

<div class="loader-bg main-loader add-listing-loader" style="background-color: #fff; display: none;">
	<?php c27()->get_partial( 'spinner', [ 'color' => '#000' ] ) ?>
	<p class="add-listing-loading-message"><?php _ex( 'Please wait while the request is being processed.', 'Add listing form', 'my-listing' ) ?></p>
</div>
