<?php
if ( ! isset( $field['value'] ) && is_user_logged_in() ) :
	$current_user = wp_get_current_user();
	$field['value'] = $current_user->user_email;
endif;
?>
<input
	type="email"
	class="input-text"
	name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>"
	id="<?php echo esc_attr( $key ); ?>" <?php if ( ! empty( $field['required'] ) ) echo 'required'; ?>
	placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
	value="<?php echo isset( $field['value'] ) ? esc_attr( $field['value'] ) : ''; ?>"
>
