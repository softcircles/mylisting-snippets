<?php

namespace MyListing\Src\Forms;

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Handles frontend Edit Listing form.
 *
 * @since 2.1
 */
class Edit_Listing_Form extends Add_Listing_Form {
	use \MyListing\Src\Traits\Instantiatable;

	// form name
	public $form_name = 'edit-listing';

	// message shown on save
	private $save_message, $restricted_message;

	public function __construct() {
		add_action( 'wp', [ $this, 'submit_handler' ] );
		$this->job_id = ! empty( $_REQUEST['job_id'] ) ? absint( $_REQUEST['job_id'] ) : 0;
		add_action( 'mylisting/get-footer', function() {
			echo '<script type="text/javascript">document.body.classList.add(\'edit-listing-form\');</script>';
		} );

		if ( ! \MyListing\Src\Listing::user_can_edit( $this->job_id ) ) {
			$this->job_id = 0;
		}
	}

	/**
	 * Output function.
	 *
	 * @param array $atts
	 */
	public function output( $atts = array() ) {
		$this->enqueue_scripts();

		if ( ! empty( $this->errors ) ) {
			$this->show_errors();
		} elseif ( ! empty( $this->save_message ) ) {
			echo '<div class="job-manager-message">' . wp_kses_post( $this->save_message ) . '</div>';
		}

		$this->submit();
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		$this->init_fields();

		$listing = \MyListing\Src\Listing::force_get( $this->job_id );
		$disable_form = false;
		$disable_message = '';
		if ( ! ( $this->job_id && $listing ) ) {
			return;
		}

		// check if published edits are allowed
		if ( $listing->get_status() === 'publish' && ! in_array( mylisting_get_setting( 'user_can_edit_published_submissions' ), [ 'yes', 'yes_moderated' ] ) ) {
			$disable_form = true;
			$disable_message = _x( 'Published listings cannot be edited.', 'Edit listing form', 'my-listing' );
		}

		// check if pending edits are allowed
		if ( $listing->get_status() !== 'publish' && ! mylisting_get_setting( 'user_can_edit_pending_submissions' ) ) {
			$disable_form = true;
			$disable_message = _x( 'Pending listings cannot be edited.', 'Edit listing form', 'my-listing' );
		}

		// disable the form interactivity if needed
		if ( $disable_form === true ) {
			if ( empty( $this->save_message ) ) {
				printf( '<div class="job-manager-message">%s</div>', $disable_message );
			}
			?>
			<style type="text/css">
				#submit-job-form .form-section { position: relative; }
				#submit-job-form .form-footer { display: none; }
				#submit-job-form .form-section:before { position: absolute; width: 100%; height: 100%; top: 0; left: 0; content: ''; display: block; background: rgba(255, 255, 255, .4); z-index: 50; }
			</style>
		<?php }

		foreach ( $this->fields as $key => $field ) {
			// form has been submitted, value is retrieved from $_POST through `validate_fields` method.
			if ( isset( $field['value'] ) ) {
				continue;
			}

			$field->set_listing( $listing );
			$this->fields[ $key ]['value'] = $field->get_value();
		}

		$save_button_text = __( 'Save changes', 'my-listing' );
		if ( 'publish' === get_post_status( $this->job_id ) && mylisting_get_setting( 'user_can_edit_published_submissions' ) === 'yes_moderated' ) {
			$save_button_text = __( 'Submit changes for approval', 'my-listing' );
		}

		$save_button_text = apply_filters( 'update_job_form_submit_button_text', $save_button_text );

		mylisting_locate_template( 'templates/add-listing/submit-form.php', [
			'form' => $this->form_name,
			'job_id' => $this->get_job_id(),
			'action' => $this->get_action(),
			'fields' => $this->fields,
			'step' => $this->get_step(),
			'submit_button_text' => $save_button_text,
		] );
	}

	/**
	 * Submit Step is posted.
	 *
	 * @throws \Exception When invalid fields are submitted.
	 */
	public function submit_handler() {
		if ( empty( $_POST['submit_job'] ) ) {
			return;
		}

		$listing = \MyListing\Src\Listing::get( $this->job_id );

		try {
			// check if it's a valid listing
			if ( ! ( $listing && $listing->type && $listing->editable_by_current_user() ) ) {
				throw new \Exception( _x( 'Invalid listing', 'Edit listing form', 'my-listing' ) );
			}

			// check if published edits are allowed
			if ( $listing->get_status() === 'publish' && ! in_array( mylisting_get_setting( 'user_can_edit_published_submissions' ), [ 'yes', 'yes_moderated' ] ) ) {
				throw new \Exception( _x( 'Published listings cannot be edited.', 'Edit listing form', 'my-listing' ) );
			}

			// check if pending edits are allowed
			if ( $listing->get_status() !== 'publish' && ! mylisting_get_setting( 'user_can_edit_pending_submissions' ) ) {
				throw new \Exception( _x( 'Pending listings cannot be edited.', 'Edit listing form', 'my-listing' ) );
			}
		} catch ( \Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}

		$this->listing_type = $listing->type;

		// if field validation throws any errors, cancel submission
		$this->validate_fields();
		if ( ! empty( $this->errors ) ) {
			return;
		}

		$save_post_status = mylisting_get_setting( 'user_can_edit_published_submissions' ) === 'yes_moderated' ? 'pending' : '';
		$original_status = $listing->get_status();
		$description = isset( $this->fields['job_description'] ) ? $this->fields['job_description']['value'] : '';

		// update listing
		$this->save_listing( $this->fields['job_title']['value'], $description, 'draft' );
		$this->update_listing_data();

		// add custom listing data
		update_post_meta( $this->job_id, '_job_edited', time() );
		do_action( 'mylisting/submission/save-listing-data', $this->job_id, $this->fields );
		do_action( 'mylisting/submission/listing-updated', $this->job_id );

		// refresh listing
		$listing = \MyListing\Src\Listing::force_get( $this->job_id );

		// add success message
		$save_message = _x( 'Your changes have been saved.', 'Edit listing form', 'my-listing' );
		$post_status = get_post_status( $listing->get_id() );

		if ( 'publish' === $post_status ) {
			$save_message = $save_message . ' <a href="' . $listing->get_link() . '">' . _x( 'View &rarr;', 'Edit listing form', 'my-listing' ) . '</a>';
		} elseif ( 'publish' === $original_status && 'pending' === $post_status ) {
			$save_message = _x( 'Your changes have been submitted and your listing will be visible again once approved.', 'Edit listing form', 'my-listing' );
		}

		$this->save_message = $save_message;

	}
}
