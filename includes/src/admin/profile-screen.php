<?php

namespace MyListing\Src\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Profile_Screen {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
        // default avatar
		add_filter( 'pre_get_avatar_data', [ $this, 'set_initials_avatar' ], 20, 2 );
		add_filter( 'bp_core_avatar_default', [ $this, 'set_initials_avatar_bp' ], 20, 2 );
        add_filter( 'avatar_defaults', function( $avatars ) {
        	$avatars['mylisting_user_initials'] = sprintf( __( 'User Initials (Generated through %s)', 'my-listing' ), '<a href="https://ui-avatars.com/" target="_blank">UI Avatars</a>' );
        	return $avatars;
        } );

        if ( apply_filters( 'mylisting/enable-user-avatars', true ) !== false ) {
			// backend
	        add_filter( 'user_profile_picture_description', [ $this, 'admin_add_avatar_setting' ], 30, 2 );
	        add_action( 'user_profile_update_errors', [ $this, 'admin_update_avatar' ], 30, 3 );

	        // frontend
	        add_action( 'woocommerce_edit_account_form_start', [ $this, 'add_avatar_setting' ], 15 );
	        add_action( 'woocommerce_save_account_details', [ $this, 'update_avatar' ], 30 );
		}
	}

	/**
	 * Add avatar setting markup in WP Admin > Users > User Profile.
	 *
	 * @since 2.1
	 */
	public function admin_add_avatar_setting( $description, $user ) {
        wp_enqueue_media();
        $photo = get_user_meta( $user->ID, '_mylisting_profile_photo', true );
        $photo_url = get_user_meta( $user->ID, '_mylisting_profile_photo_url', true );
        ?>
        <div class="update-profile-photo">
            <button class="button change-photo"><?php _e( 'Change photo', 'my-listing' ) ?></button>
            <button class="button remove-photo"><i class="material-icons">delete_outline</i></button>
            <input type="hidden" name="mylisting_profile_photo" value="<?php echo ! empty( $photo ) ? absint( $photo ) : '' ?>"
            	data-url="<?php echo esc_url( $photo_url ) ?>" data-default="<?php echo esc_url( get_avatar_url( $user->ID, [ 'force_default' => true ] ) ) ?>">
            <div class="uploaded-image-preview"></div>
        </div>
    <?php }

	/**
	 * Handles the avatar setting in WP Admin > Users > User Profile.
	 *
	 * @since 2.1
	 */
    public function admin_update_avatar( $errors, $update, $user ) {
        if ( ! $update ) {
            return;
        }

        $photo = ! empty( $_POST['mylisting_profile_photo'] ) ? absint( $_POST['mylisting_profile_photo'] ) : '';
        $photo_url = wp_get_attachment_image_url( $photo, 'thumbnail' );
        if ( $photo && $photo_url ) {
	        update_user_meta( $user->ID, '_mylisting_profile_photo', $photo );
	        update_user_meta( $user->ID, '_mylisting_profile_photo_url', $photo_url );
        } else {
	        delete_user_meta( $user->ID, '_mylisting_profile_photo' );
	        delete_user_meta( $user->ID, '_mylisting_profile_photo_url' );
        }
    }

	/**
	 * Add avatar setting markup in User Dashboard > Account Details.
	 *
	 * @since 2.1
	 */
    public function add_avatar_setting() {
		wp_enqueue_script( 'mylisting-ajax-file-upload' );

		$photo = get_user_meta( get_current_user_id(), '_mylisting_profile_photo', true );
    	$photo_url = get_user_meta( get_current_user_id(), '_mylisting_profile_photo_url', true );
		$allowed_mime_types = [ 'jpg', 'gif', 'png', 'jpeg', 'jpe' ];
		?>

		<fieldset id="change-avatar-fieldset">
			<legend><?php _e( 'Change Avatar', 'my-listing' ) ?></legend>
			<div class="file-upload-field single-upload form-group-review-gallery ajax-upload">
				<input
					type="file"
					class="input-text review-gallery-input wp-job-manager-file-upload"
					data-file_types="<?php echo esc_attr( implode( '|', $allowed_mime_types ) ) ?>"
					name="avatars"
					id="mylisting_profile_photo"
					style="display: none;"
				>
				<div class="uploaded-files-list review-gallery-images">
					<label class="upload-file review-gallery-add" for="mylisting_profile_photo">
						<i class="mi file_upload"></i>
						<div class="content"></div>
					</label>

					<div class="job-manager-uploaded-files">
						<?php if ( $photo && ! empty( $photo_url ) ): ?>
							<?php mylisting_locate_template( 'templates/add-listing/form-fields/uploaded-file-html.php', [
								'key' => 'mylisting_profile_photo',
								'name' => 'current_avatars',
								'value' => $photo_url,
							] ) ?>
						<?php endif ?>
					</div>
				</div>

				<small class="description">
					<?php printf( _x( 'Maximum file size: %s.', 'Add listing form', 'my-listing' ), size_format( wp_max_upload_size() ) ); ?>
				</small>
			</div>
		</fieldset>
    <?php }

	/**
	 * Handle saving avatar setting in User Dashboard > Account Details.
	 *
	 * @since 2.1
	 */
    public function update_avatar( $user_id ) {
    	global $wpdb;

    	// remove avatar if empty
    	if ( empty( $_POST['current_avatars'] ) ) {
	        delete_user_meta( $user_id, '_mylisting_profile_photo' );
	        delete_user_meta( $user_id, '_mylisting_profile_photo_url' );
    		return;
    	}

    	// validate file extension
    	$file_url = esc_url_raw( base64_decode( str_replace( 'b64:', '', $_POST['current_avatars'] ) ) );
		$file_info = wp_check_filetype( current( explode( '?', $file_url ) ) );
		if ( ! ( $file_info && in_array( $file_info['ext'], [ 'jpg', 'gif', 'png', 'jpeg', 'jpe' ], true ) ) ) {
			return;
		}

		// validate attachment
		$attachment = $wpdb->get_row( $wpdb->prepare(
			"SELECT ID, post_parent, post_status FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid = %s and post_author = %d LIMIT 1",
			$file_url,
			get_current_user_id()
		) );
		if ( ! is_object( $attachment ) || empty( $attachment->ID ) ) {
			return;
		}

		// validate image exists
        $photo_url = wp_get_attachment_image_url( $attachment->ID, 'thumbnail' );
        if ( ! $photo_url ) {
        	return;
        }

        // update attachment status from preview to inherit
		wp_update_post( [
			'ID' => $attachment->ID,
			'post_status' => 'inherit',
		] );

		// update user avatar metadata
        update_user_meta( $user_id, '_mylisting_profile_photo', $attachment->ID );
        update_user_meta( $user_id, '_mylisting_profile_photo_url', $photo_url );
    }

	/**
	 * Sets the default avatar of user initials, using ui-avatars.
	 *
	 * @since 2.1
	 */
	public function set_initials_avatar( $args, $id_or_email ) {
		if ( $args['default'] !== 'mylisting_user_initials' ) {
			 return $args;
		}

		if ( ! ( $user = c27()->get_user_by_id_or_email( $id_or_email ) ) ) {
			$args['default'] = 'mm';
			return $args;
		}

		$args['default'] = $this->generate_ui_avatar( $user );
		return $args;
	}

	/**
	 * Sets the default avatar of user initials in BuddyPress.
	 *
	 * @since 2.1
	 */
	public function set_initials_avatar_bp( $default, $args ) {
		if ( $default !== 'mylisting_user_initials' ) {
			 return $default;
		}

		if ( ! ( $args['object'] === 'user' && ( $user = c27()->get_user_by_id_or_email( $args['item_id'] ) ) ) ) {
			return 'mm';
		}

		return $this->generate_ui_avatar( $user );
	}

	/**
	 * Generates the ui-avatars url for a given user.
	 *
	 * The Gravatar request can't handle non-latin characters in the "d=" parameter
	 * for the default image. If the user's display name only has non-latin
	 * characters, it will result in an empty string and no image will be returned.
	 *
	 * To avoid that, we append the user login as fallback.
	 * The user login will always contain only latin characters.
	 *
	 * @since 2.1
	 */
	private function generate_ui_avatar( $user ) {
		$colors = [
			'f19066', 'f5cd79', '546de5', 'e15f41', 'c44569',
			'574b90', 'f78fb3', '3dc1d3', 'e66767', '303952',
		];

		$query_args = [
			'name' => $user->display_name . $user->user_login,
			'size' => 96,
			'background' => $colors[ $user->ID % ( count( $colors ) - 1 ) ],
			'color' => 'fff',
			'length' => 1,
			'font-size' => 0.4,
			'rounded' => false,
			'uppercase' => true,
			'bold' => true,
		];

		return 'https://ui-avatars.com/api/' . join( '/', $query_args );
	}
}
