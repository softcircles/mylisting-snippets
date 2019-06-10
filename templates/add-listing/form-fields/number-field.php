<?php

if ( empty($field['value'] ) ) {
    $field['value'] = 0;
}

?>

<input type="number" class="input-text" name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>"
	   id="<?php echo esc_attr( $key ); ?>" <?php if ( ! empty( $field['required'] ) ) echo 'required'; ?>
	   placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo isset( $field['value'] ) ? esc_attr( $field['value'] ) : ''; ?>"
	   min="<?php echo isset( $field['min'] ) && is_numeric( $field['min'] ) ? esc_attr( $field['min'] ) : ''; ?>"
	   max="<?php echo isset( $field['max'] ) && is_numeric( $field['max'] ) ? esc_attr( $field['max'] ) : ''; ?>"
	   step="<?php echo ! empty( $field['step'] ) ? esc_attr( $field['step'] ) : ''; ?>"
	   >
