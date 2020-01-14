<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Number_Field extends Base_Field {

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? sanitize_text_field( stripslashes( $_POST[ $this->key ] ) )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();

		// validate it's a number
		if ( ! is_numeric( $value ) ) {
			// translators: %s is the field label.
			throw new \Exception( sprintf( _x( '%s must be a number.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
		}

		$val  = (float) $value;
		$min  = is_numeric( $this->props['min'] ) ? (float) $this->props['min'] : false;
		$max  = is_numeric( $this->props['max'] ) ? (float) $this->props['max'] : false;
		$step = is_numeric( $this->props['step'] ) ? (float) $this->props['step'] : false;

		if ( $min !== false && $val < $min ) {
			// translators: %1$s is the field label; %2%s is the minimum allowed value.
			throw new \Exception( sprintf( _x( '%1$s can\'t be smaller than %2$s.', 'Add listing form', 'my-listing' ), $this->props['label'], $min ) );
		}

		if ( $max !== false && $val > $max ) {
			// translators: %1$s is the field label; %2%s is the maximum allowed value.
			throw new \Exception( sprintf( _x( '%1$s can\'t be bigger than %2$s.', 'Add listing form', 'my-listing' ), $this->props['label'], $min ) );
		}
	}

	public function field_props() {
		$this->props['type'] = 'number';
		$this->props['min']  = '';
		$this->props['max']  = '';
		$this->props['step'] = 1;
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();

		$this->getMinField();
		$this->getMaxField();
		$this->getStepField();

		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
	}

	public function update() {
		$value = $this->get_posted_value();
		$value = ($this->key == 'price' ) ? $value * 2 : $value;
		update_post_meta( $this->listing->get_id(), '_'.$this->key, $value );
	}
}
