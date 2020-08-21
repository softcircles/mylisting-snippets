<?php
/**
 * Shows the `file` form field on listing forms.
 *
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// handles ajax uploads
wp_enqueue_script( 'mylisting-ajax-file-upload' );

$is_multiple = ! empty( $field['multiple'] );

if ( $key == 'job_logo' || $key == 'job_cover' || $key == 'job_gallery' ) {
	$allowed_mime_types = array_keys( [
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'heic'	=> 'image/heic',
	] );
} else {
	$allowed_mime_types = array_keys( ! empty( $field['allowed_mime_types'] ) ? $field['allowed_mime_types'] : get_allowed_mime_types() );
}

$field_name = isset( $field['name'] ) ? $field['name'] : $key;
$field_name .= $is_multiple ? '[]' : '';
$uploaded_files = ! empty( $field['value'] ) ? (array) $field['value'] : [];
?>

<div class="file-upload-field <?php echo $is_multiple ? 'multiple-uploads' : 'single-upload' ?> form-group-review-gallery ajax-upload">
	<input
		type="file"
		class="input-text review-gallery-input wp-job-manager-file-upload"
		data-file_types="<?php echo esc_attr( implode( '|', $allowed_mime_types ) ) ?>"
		data-max_count="<?php echo ! empty( $field['file_limit'] ) && absint( $field['file_limit'] ) > 1 ? absint( $field['file_limit'] ) : '' ?>"
		<?php if ( ! empty( $field['multiple'] ) ) echo 'multiple' ?>
		name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ) ?><?php if ( ! empty( $field['multiple'] ) ) echo '[]' ?>"
		id="<?php echo esc_attr( $key ) ?>"
		placeholder="<?php echo empty( $field['placeholder'] ) ? '' : esc_attr( $field['placeholder'] ) ?>"
		style="display: none;"
	>
	<div class="uploaded-files-list review-gallery-images">
		<label class="upload-file review-gallery-add" for="<?php echo esc_attr( $key ) ?>">
			<i class="mi file_upload"></i>
			<div class="content"></div>
		</label>

		<div class="job-manager-uploaded-files">
			<?php foreach ( $uploaded_files as $file ): ?>
				<?php mylisting_locate_template( 'templates/add-listing/form-fields/uploaded-file-html.php', [
					'key' => $key,
					'name' => 'current_'.$field_name,
					'value' => $file,
					'field' => $field
				] ) ?>
			<?php endforeach ?>
		</div>
	</div>

	<small class="description">
		<?php printf( _x( 'Maximum file size: %s.', 'Add listing form', 'my-listing' ), size_format( wp_max_upload_size() ) ); ?>
		<?php if ( ! empty( $field['file_limit'] ) && absint( $field['file_limit'] ) > 1 ): ?>
			<?php printf( _x( 'Up to %d files allowed.', 'Add listing form', 'my-listing' ), absint( $field['file_limit'] ) ) ?>
		<?php endif ?>
	</small>
</div>
