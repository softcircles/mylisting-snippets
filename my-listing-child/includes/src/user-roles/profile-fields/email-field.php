<?php

namespace MyListing\Src\User_Roles\Profile_Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Email_Field extends Base_Profile_Field {

	protected function field_props() {
		$this->props['type'] = 'email';
	}

	protected function get_posted_value() {
		$value = isset( $_POST[ $this->get_form_key() ] ) ? $_POST[ $this->get_form_key() ] : '';
		return sanitize_email( stripslashes( $value ) );
	}

	protected function validate() {
		$value = $this->the_posted_value();
		$this->validate_email();
	}

	protected function get_editor_options() {
		$this->get_label_option();
		$this->get_description_option();
		$this->get_required_option();
		$this->get_show_in_register_option();
		$this->get_show_in_account_details_option();
	}

	public function get_form_markup() { ?>

		<div class="form-group">
			<?php if ( is_edit_account_page() ) : ?>
				<input
					type="email"
					name="<?php echo esc_attr( $this->get_form_key() ) ?>"
					id="reg_<?php echo esc_attr( $this->get_key() ) ?>"
					value="<?php echo $this->the_posted_value() ?: $this->get_value() ?>"
					placeholder=""
					readonly
				>
			<?php else : ?>
				<input
					type="email"
					name="<?php echo esc_attr( $this->get_form_key() ) ?>"
					id="reg_<?php echo esc_attr( $this->get_key() ) ?>"
					value="<?php echo $this->the_posted_value() ?: $this->get_value() ?>"
					placeholder=" "

				>
			<?php endif; ?>
			<label><?php echo $this->get_label() ?></label>
			<?php if ( $desc = $this->get_description() ): ?>
				<p><?php echo $desc ?></p>
			<?php endif ?>
		</div>
	<?php }
}
