<?php if (!class_exists('WooCommerce')) return;


$form_action = wc_get_page_permalink('myaccount');

if ( ! empty( $_GET['redirect_to'] ) ) {
	$form_action = $_GET['redirect_to'];
}
?>

<div class="sign-in-box element">
	<div class="title-style-1">
		<i class="material-icons user-area-icon">person</i>
		<h5><?php _e( 'Create an account', 'my-listing' ) ?></h5>
	</div>
	<form class="sign-in-form register" method="POST" action="<?php echo esc_url( $form_action ) ?>">

		<?php do_action( 'woocommerce_register_form_start' ); ?>

		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
			<div class="form-group">
				<input type="text" name="username" id="reg_username" value="<?php if ( ! empty( $_POST['username'] ) ) echo esc_attr( $_POST['username'] ); ?>" placeholder="<?php esc_attr_e( 'Username', 'my-listing' ) ?>">
			</div>
		<?php endif; ?>

		<div class="form-group">
			<input type="email" name="email" id="reg_email" value="<?php if ( ! empty( $_POST['email'] ) ) echo esc_attr( $_POST['email'] ); ?>" placeholder="<?php esc_attr_e( 'Email', 'my-listing' ) ?>">
		</div>

		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
			<div class="form-group">
				<input type="password" name="password" id="reg_password" placeholder="<?php esc_attr_e( 'Password', 'my-listing' ) ?>">
			</div>
		<?php endif; ?>

		<!-- Spam Trap -->
		<div style="<?php echo ( ( is_rtl() ) ? 'right' : 'left' ); ?>: -999em; position: absolute;"><label for="trap"><?php _e( 'Anti-spam', 'my-listing' ); ?></label><input type="text" name="email_2" id="trap" tabindex="-1" autocomplete="off" /></div>

		<?php do_action( 'woocommerce_register_form' ); ?>

		<!-- <div class="form-info">
			<div class="md-checkbox">
				<input id="i2" type="checkbox">
				<label for="i2" class="">I agree to the <a href="#">Terms and Conditions</a></label>
			</div>
		</div> -->

		<div class="form-group">
			<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
			<button type="submit" class="buttons button-2 full-width" name="register" value="Register">
				<?php _e( 'Sign Up', 'my-listing' ) ?>
			</button>
		</div>

		<?php if ( ! empty( $_GET['redirect_to'] ) ): ?>
			<input type="hidden" name="redirect" value="<?php echo esc_url( $_GET['redirect_to'] ) ?>">
		<?php endif ?>

		<?php do_action( 'woocommerce_register_form_end' ); ?>

		<a href="#" class="c27-open-modal" data-target="#sign-in-modal"><?php _e( 'Already Registered?', 'my-listing' ) ?></a>

	</form>

	<?php c27()->get_partial( 'spinner', [
		'color' => '#777',
		'classes' => 'center-vh',
		'size' => 24,
		'width' => 2.5,
	] ); ?>
</div>
