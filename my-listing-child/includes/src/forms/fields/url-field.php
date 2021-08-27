<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Url_Field extends Base_Field {

	public function get_posted_value() {
		return ! empty( $_POST[ $this->key ] )
			? sanitize_text_field( $_POST[ $this->key ] )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
	}

	public function field_props() {
		$this->props['type'] = 'url';
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
	}
}
