<?php

namespace MyListing\Src\User_Roles\Profile_Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Text_Field extends Base_Profile_Field {

	protected function field_props() {
		$this->props['type'] = 'text';
		$this->props['minlength'] = '';
		$this->props['maxlength'] = '';
	}

	protected function get_posted_value() {

		if ( $this->get_key() === 'display_name' ) {
			$user = wp_get_current_user();

			// $first_name = get_user_meta( get_current_user_id(), 'first_name', true );
			// $last_name = get_user_meta( get_current_user_id(), 'last_name', true );
			$value = $user->first_name . ' ' . $user->last_name;
		} else {
			$value = isset( $_POST[ $this->get_form_key() ] ) ? $_POST[ $this->get_form_key() ] : '';
		}

		return sanitize_text_field( stripslashes( $value ) );
	}

	protected function get_editor_options() {
		$this->get_label_option();
		$this->get_description_option();
		$this->get_minlength_option();
		$this->get_maxlength_option();
		$this->get_required_option();
		$this->get_show_in_register_option();
		$this->get_show_in_account_details_option();
	}

	protected function validate() {
		$value = $this->the_posted_value();
		$this->validate_minlength();
		$this->validate_maxlength();
	}

	public function get_form_markup() {
		if ( $this->key === 'username' && get_option('woocommerce_registration_generate_username') !== 'no' ) {
			return;
		}


		?>

		<div class="form-group">
			<input
				type="text"
				name="<?php echo esc_attr( $this->get_form_key() ) ?>"
				id="reg_<?php echo esc_attr( $this->get_key() ) ?>"
				value="<?php echo $this->the_posted_value() ?: $this->get_value() ?>"
				<?php if ( $this->key === 'username' && $this->form === static::FORM_ACCOUNT_DETAILS ) echo 'disabled' ?>
				placeholder=" "
			>
			<label><?php echo $this->get_label() ?></label>
			<?php if ( $desc = $this->get_description() ): ?>
				<p><?php echo $desc ?></p>
			<?php endif ?>
		</div>
		<?php
	}
}
