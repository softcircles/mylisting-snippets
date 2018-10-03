<?php
/*
 * Add Confirm Password Field on register form
 */
add_filter('woocommerce_registration_errors', 'registration_errors_validation', 10,3);

function registration_errors_validation( $reg_errors, $sanitized_user_login, $user_email ) {

	global $woocommerce;

	extract( $_POST );

	if ( strcmp( $password, $password2 ) !== 0 ) {
		return new WP_Error( 'registration-error', __( 'Passwords do not match.', 'woocommerce' ) );
	}

	return $reg_errors;
}

add_action( 'woocommerce_register_form', 'wc_register_form_password_repeat' );

function wc_register_form_password_repeat() {
	?>
	<p class="form-row form-row-wide">
		<label for="reg_password2"><?php _e( 'Password Repeat', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input type="password" class="input-text" name="password2" id="reg_password2" value="<?php if ( ! empty( $_POST['password2'] ) ) echo esc_attr( $_POST['password2'] ); ?>" />
	</p>
	<?php
}
