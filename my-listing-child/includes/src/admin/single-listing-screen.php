<?php

namespace MyListing\Src\Admin;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Single_Listing_Screen {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'save_post', [ $this, 'save_post' ], 1, 2 );
		add_action( 'add_meta_boxes', [ $this, 'display_listing_fields' ] );
		add_action( 'mylisting/admin/save-listing-data', [ $this, 'save_listing_fields' ], 20, 2 );
		add_action( 'admin_init', [ $this, 'remove_taxonomy_metaboxes' ] );
		add_action( 'add_meta_boxes', [ $this, 'display_package_metabox' ], 40 );
		add_action( 'add_meta_boxes', [ $this, 'display_priority_settings' ], 60 );
		add_action( 'add_meta_boxes', [ $this, 'display_verification_metabox' ], 70 );
		add_action( 'add_meta_boxes', [ $this, 'display_author_metabox' ], 80 );

		// expiry date
		add_action( 'add_meta_boxes', [ $this, 'display_expiry_metabox' ], 35 );
		add_action( 'transition_post_status', [ $this, 'set_expiry_on_publish' ], 35, 3 );

		// listing settings
		add_action( 'mylisting/admin/save-listing-data', [ $this, 'save_listing_settings' ], 100, 2 );

		// display custom post statuses
		foreach ( [ 'post', 'post-new' ] as $hook ) {
			add_action( "admin_footer-{$hook}.php", [ $this, 'display_custom_post_statuses' ] );
		}
	}

	/**
	 * Handles `save_post` action.
	 *
	 * @since 2.1
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) || $post->post_type !== 'job_listing' ) {
			return;
		}

		if ( is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || empty( $_POST['mylisting_save_fields_nonce'] ) || ! wp_verify_nonce( $_POST['mylisting_save_fields_nonce'], 'save_meta_data' ) ) {
			return;
		}

		do_action( 'mylisting/admin/save-listing-data', $post_id, \MyListing\Src\Listing::get( $post ) );
		do_action( 'job_manager_save_job_listing', $post_id, $post ); // legacy
	}

	/**
	 * Get the list of fields to be shown in
	 * the backend edit listing form.
	 *
	 * @since 2.1
	 */
	public function get_listing_fields() {
		global $post;

		$listing = \MyListing\Src\Listing::get( $post );
		if ( ! ( $listing && $listing->type ) ) {
			return [];
		}

		// get fields for this listing type
		$fields = $listing->get_fields();

		// Filter out fields set to be hidden from the backend submission form.
		$fields = array_filter( $fields, function( $field ) {
			return $field->props['show_in_admin'];
		} );

		// allow modifiying fields through filters
		$fields = apply_filters( 'mylisting/admin/submission/fields', $fields, $listing );

		// unset the title field on backend, to make use of the post title input in wp backend
		if ( isset( $fields['job_title'] ) ) {
			unset( $fields['job_title'] );
		}

		// in backend form, description field must be shown with high priority,
		// regardless of the order in the listing type editor
		if ( isset( $fields['job_description'] ) ) {
			$fields['job_description']->props['priority'] = 0.2;
		}

		// order by priority
		uasort( $fields, function( $a, $b ) {
			$first = isset( $a->props['priority'] ) ? $a->props['priority'] : 0;
			$second = isset( $b->props['priority'] ) ? $b->props['priority'] : 0;
			return $first - $second;
		} );

		return $fields;
	}

	/**
	 * Handles the saving of listing data fields.
	 *
	 * @since 2.1
	 */
	public function save_listing_fields( $post_id, $listing ) {
		foreach ( $this->get_listing_fields() as $key => $field ) {
			// description requires special handling to avoid triggering an
			// infinite loop of 'save_post' hook calls
			if ( $field->get_key() === 'job_description' ) {
				remove_action( 'save_post', [ $this, 'save_post' ], 1 );
				wp_update_post( [
					'ID' => $post_id,
					'post_content' => $field->get_posted_value(),
				] );
				add_action( 'save_post', [ $this, 'save_post' ], 1, 2 );
			} else {
				$field->update();
			}
		}
	}

	/**
	 * Handle changing the listing type, author, expiry date...
	 * in backend edit listing page.
	 *
	 * @since 2.0
	 */
	public function save_listing_settings( $post_id, $listing ) {
		remove_action( 'save_post', [ $this, 'save_post' ], 1 );

		// post statuses
		$current_status = isset( $_POST['post_status'] ) ? $_POST['post_status'] : false;
		$previous_status = isset( $_POST['original_post_status'] ) ? $_POST['original_post_status'] : false;

		// update listing type
        if ( isset( $_POST['_case27_listing_type'] ) ) {
        	update_post_meta( $post_id, '_case27_listing_type', $_POST['_case27_listing_type'] );
        }

        // update expiry date
        if ( isset( $_POST['mylisting_modify_expiry'] ) && $_POST['mylisting_modify_expiry'] === 'yes' ) {
        	$expiry_date = ! empty( $_POST['mylisting_expiry_date'] ) ? strtotime( $_POST['mylisting_expiry_date'] ) : false;
        	if ( $expiry_date ) {
        		mlog( 'Applying custom expiry date set in metabox.' );
				update_post_meta( $post_id, '_job_expires', date( 'Y-m-d', $expiry_date ) );
        	}
        }

        // expire listing if past expiry date
		$expiry_date = get_post_meta( $post_id, '_job_expires', true );
		$has_expired = $expiry_date && strtotime( $expiry_date ) && ( strtotime( $expiry_date ) < current_time( 'timestamp' ) );
		if ( $has_expired && ! in_array( $current_status, [ 'draft', 'expired' ], true ) ) {
			mlog( 'Listing has reached expiration date, setting post status to "expired".' );
			wp_update_post( [
				'ID' => $post_id,
				'post_status' => 'expired',
			] );
		}

		// update priority
		if ( isset( $_POST['cts-listing-priority'] ) ) {
			$priority = $_POST['cts-listing-priority'];

			// Save custom priority value.
			if ( $priority === 'custom' ) {
				$custom_priority = ! empty( $_POST['cts-listing-custom-priority'] ) ? absint( $_POST['cts-listing-custom-priority'] ) : false;
				if ( $custom_priority >= 0 ) {
					update_post_meta( $post_id, '_featured', $custom_priority );
				}
			} elseif ( absint( $priority ) >= 0 ) {
				update_post_meta( $post_id, '_featured', absint( $priority ) );
			}
		}

		// update verification status
		if ( isset( $_POST['mylisting_modify_verified_status'] ) && $_POST['mylisting_modify_verified_status'] === 'yes' ) {
			$is_verified = isset( $_POST['mylisting_verification_status'] ) && $_POST['mylisting_verification_status'] === 'verified';
			update_post_meta( $post_id, '_claimed', $is_verified ? 1 : 0 );
		}

        // update author
        if ( ! empty( $_POST['mylisting_author'] ) && isset( $_POST['mylisting_change_author'] ) && $_POST['mylisting_change_author'] === 'yes' ) {
			wp_update_post( [
				'ID' => $post_id,
				'post_author' => $_POST['mylisting_author'] > 0 ? absint( $_POST['mylisting_author'] ) : 0,
			] );
        }

        // update payment package
        if ( isset( $_POST['mylisting_switch_package'] ) && $_POST['mylisting_switch_package'] === 'yes' ) {
        	$switch_package_id = ! empty( $_POST['mylisting_payment_package'] ) ? absint( $_POST['mylisting_payment_package'] ) : false;
        	$switch_package = \MyListing\Src\Package::get( $switch_package_id );

        	// if no package is selected, remove the payment package and associated data
        	if ( ! $switch_package ) {
        		delete_post_meta( $post_id, '_job_duration' );
        		delete_post_meta( $post_id, '_package_id' );
        		delete_post_meta( $post_id, '_user_package_id' );

				// make sure any listing promotions aren't made inactive when disabling package
				$priority = (int) get_post_meta( $post_id, '_featured', true );
				if ( $priority <= 1 ) {
					delete_post_meta( $post_id, '_featured' );
				}

				// recalculate expiry
				$expires = \MyListing\Src\Listing::calculate_expiry( $post_id );
				update_post_meta( $post_id, '_job_expires', $expires );
				mlog()->note( 'Deleted payment package for listing #'.$post_id );
        	} else {
        		// otherwise, apply the selected package

				// if the listing already has this package, and it is published, ignore request
				if ( $listing->get_data('post_status') !== 'publish' || absint( $listing->get_package_id() ) !== absint( $switch_package->get_id() ) ) {
					wp_update_post( [
						'ID' => $listing->get_id(),
						'post_status' => 'publish',
					] );

					$switch_package->assign_to_listing( $listing->get_id() );
	        		mlog()->note( 'Switched to package #'.$switch_package_id.' for listing #'.$post_id );
				}
        	}
        } elseif ( ! empty( $_POST['mylisting_sync_package'] ) ) {
        	mlog()->note( 'Syncing product id with the package product id for listing #'.$post_id );
        	update_post_meta( $post_id, '_package_id', absint( $_POST['mylisting_sync_package'] ) );
        }

		add_action( 'save_post', [ $this, 'save_post' ], 1, 2 );
	}

	/**
	 * Calculate the expiry date when listing gets published.
	 *
	 * @since 2.1.6
	 */
	public function set_expiry_on_publish( $new_status, $old_status, $post ) {
		// run this only when the new status is publish and the old one anything but publish
		if ( $post->post_type !== 'job_listing' || $new_status !== 'publish' || $old_status === 'publish' ) {
			return;
		}

		$listing = \MyListing\Src\Listing::get( $post );

		// if the expiry date meta field isn't set at all, this is likely the initial
		// listing publish, so calculate the expiry date and set it
		if ( ! metadata_exists( 'post', $listing->get_id(), '_job_expires' ) ) {
			mlog( '"Expiry Date" meta key not set, calculating default expiry.' );
			$expiry_date = \MyListing\Src\Listing::calculate_expiry( $listing->get_id() );
			update_post_meta( $listing->get_id(), '_job_expires', $expiry_date );
		}

		// if the listing already has an expiry date, make sure it's valid and is a
		// date in the future; otherwise, calculate a fresh expiry date and set it
		$expiry = $listing->get_expiry_date();
		if ( ! $expiry || ( $expiry->getTimestamp() < current_time( 'timestamp' ) ) ) {
			mlog( 'Current expiry date has passed, setting a new one on listing publish.' );
			$expiry_date = \MyListing\Src\Listing::calculate_expiry( $listing->get_id() );
			update_post_meta( $listing->get_id(), '_job_expires', $expiry_date );
		}
	}

	/**
	 * Add custom meta boxes to single listing screen.
	 *
	 * @since 1.7.0
	 */
	public function display_listing_fields() {
		global $current_user;
	    
	    if ( $current_user->roles[0] == 'administrator' ) {

			add_meta_box(
				'job_listing_data',
				_x( 'Listing data', 'Listing fields metabox title', 'my-listing' ),
				[ $this, 'fields_metabox_content' ],
				'job_listing',
				'normal',
				'high'
			);
		}
	}

	/**
	 * Display current author information, and ability
	 * to switch author.
	 *
	 * @since 2.1.6
	 */
	public function display_author_metabox() {

		global $current_user;
	    
	    if ( $current_user->roles[0] == 'administrator' ) {
			add_meta_box(
				'mylisting_author_metabox',
				_x( 'Author', 'Listing author metabox title', 'my-listing' ),
				function( $listing ) {
					$listing = \MyListing\Src\Listing::get( $listing );
					require locate_template( 'templates/admin/single-listing-screen/author-metabox.php' );
				},
				'job_listing',
				'side',
				'default'
			);
		}
	}

	/**
	 * Insert basic settings for listing priority in listing
	 * edit page in wp-admin.
	 *
	 * @since 1.7.0
	 */
	public function display_priority_settings() {

		global $current_user;
	    
	    if ( $current_user->roles[0] == 'administrator' ) {
			add_meta_box(
				'cts_listing_promotion_settings',
				_x( 'Priority', 'Listing priority settings metabox in wp-admin', 'my-listing' ),
				function( $listing ) {
					$listing = \MyListing\Src\Listing::get( $listing );
					require locate_template( 'templates/dashboard/promotions/admin/priority-settings.php' );
				},
				'job_listing',
				'side',
				'default'
			);
		}
	}

	/**
	 * Display verification status and ability to modify it.
	 *
	 * @since 2.1.6
	 */
	public function display_verification_metabox() {

		global $current_user;
	    
	    if ( $current_user->roles[0] == 'administrator' ) {
			add_meta_box(
				'mylisting_verification_metabox',
				_x( 'Verification Status', 'Listing verification status metabox title', 'my-listing' ),
				function( $listing ) {
					$listing = \MyListing\Src\Listing::get( $listing );
					require locate_template( 'templates/admin/single-listing-screen/verification-metabox.php' );
				},
				'job_listing',
				'side',
				'default'
			);
		}
	}

	/**
	 * Display current package information, and ability
	 * to switch package / change details.
	 *
	 * @since 2.1.6
	 */
	public function display_package_metabox() {

		global $current_user;
	    
	    if ( $current_user->roles[0] == 'administrator' ) {
			add_meta_box(
				'mylisting_package_metabox',
				_x( 'Package', 'Listing package metabox title', 'my-listing' ),
				function( $listing ) {
					$listing = \MyListing\Src\Listing::get( $listing );
					require locate_template( 'templates/admin/single-listing-screen/package-metabox.php' );
				},
				'job_listing',
				'side',
				'default'
			);
		}
	}

	/**
	 * Display the listing expiry date and ability
	 * to modify it.
	 *
	 * @since 2.1.6
	 */
	public function display_expiry_metabox() {

		global $current_user;
	    
	    if ( $current_user->roles[0] == 'administrator' ) {
			add_meta_box(
				'mylisting_expiry_metabox',
				_x( 'Expiry Date', 'Listing package metabox title', 'my-listing' ),
				function( $listing ) {
					$listing = \MyListing\Src\Listing::get( $listing );
					require locate_template( 'templates/admin/single-listing-screen/expiry-metabox.php' );
				},
				'job_listing',
				'side',
				'default'
			);
		}
	}

	/**
	 * The fields metabox contents.
	 *
	 * @since 2.1
	 */
	public function fields_metabox_content( $post ) {
		global $thepostid;
		$thepostid = $post->ID;
		$listing = \MyListing\Src\Listing::get( $post );

		echo '<div class="wp_job_manager_meta_data">';

		wp_nonce_field( 'save_meta_data', 'mylisting_save_fields_nonce' );

		require locate_template( 'templates/add-listing/form-fields/admin/select-listing-type.php' );

		echo '</div></div></div><div class="ml-admin-listing-form">';
		wp_enqueue_style( 'mylisting-admin-form' );
		wp_enqueue_script( 'mylisting-admin-form' );

		foreach ( $this->get_listing_fields() as $key => $field ) {
			$field['value'] = $field->get_value();
			require locate_template( 'templates/add-listing/form-fields/admin/default.php' );
		}

		// @todo test this
		$user_edited_date = get_post_meta( $post->ID, '_job_edited', true );
		if ( $user_edited_date ) {
			echo '<p class="form-field">';
			echo '<em>' . sprintf( esc_html__( 'Listing was last modified by the user on %s.', 'my-listing' ), esc_html( date_i18n( get_option( 'date_format' ), $user_edited_date ) ) ) . '</em>';
			echo '</p>';
		}

		echo '</div><div><div><div>';
		echo '</div>';
	}

	/**
	 * Workaround to show custom post statuses in the
	 * status dropdown in admin edit listing page.
	 *
	 * @since 2.1
	 */
	public function display_custom_post_statuses() {
		global $post, $post_type;

		// Abort if we're on the wrong post type, but only if we got a restriction.
		if ( 'job_listing' !== $post_type ) {
			return;
		}

		// Get all non-builtin post status and add them as <option>.
		$options = '';
		$display = '';
		foreach ( \MyListing\Src\Listing::get_post_statuses() as $status => $name ) {
			$selected = selected( $post->post_status, $status, false );

			// If we one of our custom post status is selected, remember it.
			if ( $selected ) {
				$display = $name;
			}

			// Build the options.
			$options .= "<option{$selected} value='{$status}'>" . esc_html( $name ) . '</option>';
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				<?php if ( ! empty( $display ) ) : ?>
					jQuery( '#post-status-display' ).html( <?php echo wp_json_encode( $display ); ?> );
				<?php endif; ?>

				var select = jQuery( '#post-status-select' ).find( 'select' );
				jQuery( select ).html( <?php echo wp_json_encode( $options ); ?> );
			} );
		</script>
		<?php
	}

	/**
	 * Remove taxonomy metaboxes in edit listing screen, since taxonomies
	 * can be edited through the listing form fields.
	 *
	 * @since 2.1
	 */
	public function remove_taxonomy_metaboxes() {
		remove_meta_box( 'job_listing_categorydiv', 'job_listing', 'normal' );
		remove_meta_box( 'regiondiv', 'job_listing', 'normal' );
		remove_meta_box( 'tagsdiv-case27_job_listing_tags', 'job_listing', 'normal' );
		foreach ( mylisting_custom_taxonomies() as $slug => $label ) {
			remove_meta_box( $slug.'div', 'job_listing', 'normal' );
		}
	}
}
