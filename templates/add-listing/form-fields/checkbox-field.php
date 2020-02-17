<?php
/**
 * Displays `checkbox` form fields on Add Listing form.
 *
 * @since 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// maintain backwards compatibility (checkboxes only had valud of 1 or 0).
if ( ! is_array( $field['options'] ) || empty( $field['options'] ) ) {
	$field['options'] = [ '1' => ! empty( $field['placeholder'] ) ? $field['placeholder'] : '' ];
}
?>

<?php foreach ( $field['options'] as $option_key => $value ): $option_id = 'cbopt-'.\MyListing\Utils\Random_Id::generate(7); ?>

	<div class="md-checkbox">
		<input
			type="checkbox"
			name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ) ?>[]"
			id="<?php echo esc_attr( $option_id ) ?>"
			value="<?php echo esc_attr( $option_key ); ?>"
			<?php checked( in_array( $option_key, ! empty( $field['value'] ) ? (array) $field['value'] : [] ), true ) ?>
		>
		<label for="<?php echo esc_attr( $option_id ) ?>">
			<?php if( $field->key == 'partner-terms-conditions-box' ): 
				echo $field['placeholder'];
			else:
				echo esc_html( $value );
			endif;?>

		</label>
	</div>

<?php endforeach; ?>
