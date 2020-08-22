<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $field['required'] ) ) {
	$field['options'] = [
		'' => ! empty( $field['placeholder'] )
			? $field['placeholder']
			: _x( 'Choose an option:', 'Radio select field in add listing form', 'my-listing' ),
	] + (array) $field['options'];
}
?>

<?php foreach ( (array) $field['options'] as $option_key => $value ): $option_id = 'radopt-'.\MyListing\Utils\Random_Id::generate(7); ?>
	<div class="md-checkbox">
		<input
			type="radio"
			id="<?php echo esc_attr( $option_id ) ?>"
			name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>"
			value="<?php echo esc_attr( $option_key ); ?>"
		>
		<label for="<?php echo esc_attr( $option_id ) ?>">
			<?php echo esc_html( $value ); ?>
		</label>
	</div>
<?php endforeach; ?>
