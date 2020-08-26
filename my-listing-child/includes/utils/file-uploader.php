<?php

namespace MyListing\Utils;

if ( ! defined('ABSPATH') ) {
	exit;
}

class File_Uploader {
	use \MyListing\Src\Traits\Instantiatable;

	public $upload_dir;

	public function __construct() {
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/media.php';
		include_once ABSPATH . 'wp-admin/includes/image.php';
	}

	public function upload( $file, $args ) {
		$args = wp_parse_args( $args, [
			'allowed_mime_types' => get_allowed_mime_types(),
			'upload_dir' => null,
		] );
		
		$fileinfo = @getimagesize($file["tmp_name"]);
		$width = $fileinfo[0];
	    $height = $fileinfo[1];

	    if ( $width > "300" || $height > "200" ) {
	    	throw new \Exception( 'Image dimension should be within 300X200' );
	    }

		if ( is_string( $args['upload_dir'] ) && ! empty( trim( $args['upload_dir'] ) ) ) {
			$this->upload_dir = $args['upload_dir'];
			add_filter( 'upload_dir', [ $this, 'set_upload_dir' ], 26 );
		}

		$uploaded_file = new \stdClass();
		if ( ! in_array( $file['type'], $args['allowed_mime_types'] ) ) {
			throw new \Exception( sprintf(
				_x( 'Uploaded files need to be one of the following file types: %s', 'File upload', 'my-listing' ),
				implode( ', ', array_keys( $args['allowed_mime_types'] ) )
			) );
		}

		$upload = wp_handle_upload( $file, [ 'test_form' => false ] );
		if ( ! empty( $upload['error'] ) ) {
			throw new \Exception( $upload['error'] );
		}

		$uploaded_file->url = $upload['url'];
		$uploaded_file->file = $upload['file'];
		$uploaded_file->name = basename( $upload['file'] );
		$uploaded_file->type = $upload['type'];
		$uploaded_file->size = $file['size'];
		$uploaded_file->extension = substr( strrchr( $uploaded_file->name, '.' ), 1 );

		remove_filter( 'upload_dir', [ $this, 'set_upload_dir' ], 26 );

		return $uploaded_file;
	}

	/**
	 * Prepare files in $_FILES array for upload.
	 *
	 * @since 2.1
	 */
	public function prepare( $filekey ) {
		$prepared = [];
		if ( empty( $_FILES[ $filekey ] ) || ! is_array( $_FILES[ $filekey ] ) ) {
			return $prepared;
		}

		$files = $_FILES[ $filekey ];

		// single file upload
		if ( is_string( $files['name'] ) ) {
			$files['type'] = wp_check_filetype( $files['name'] )['type'];
			$files['filekey'] = $filekey;
			$prepared[] = $files;
		}

		// multiple file upload
		if ( is_array( $files['name'] ) ) {
			foreach ( $files['name'] as $key => $name ) {
				$prepared[] = [
					'name' => $name,
					'type' => wp_check_filetype( $name )['type'],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error' => $files['error'][ $key ],
					'size' => $files['size'][ $key ],
					'filekey' => $filekey,
				];
			}
		}

		return $prepared;
	}

	public function set_upload_dir( $pathdata ) {
		$dir = untrailingslashit( $this->upload_dir );

		if ( empty( $pathdata['subdir'] ) ) {
			$pathdata['path'] = trailingslashit( $pathdata['path'] ) . $dir;
			$pathdata['url'] = trailingslashit( $pathdata['url'] ) . $dir;
			$pathdata['subdir'] = '/' . $dir;
		} else {
			$new_subdir = '/' . $dir . $pathdata['subdir'];
			$pathdata['path'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
			$pathdata['url'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
			$pathdata['subdir'] = $new_subdir;
		}

		return $pathdata;
	}
}
