<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Date_Field extends Base_Field {

	public $modifiers = [
		'date' => '%s Date',
		'time' => '%s Time',
	];

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? sanitize_text_field( stripslashes( $_POST[ $this->key ] ) )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
		//
	}

	public function field_props() {
		$this->props['type'] = 'date';
		$this->props['format'] = 'date';
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getFormatField();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
	}

	public function string_value( $modifier = null ) {
		$value = $this->get_value();
		$timestamp = strtotime( $value );
		if ( empty( $value ) || empty( $timestamp ) ) {
			return '';
		}

		if ( $modifier === 'date' ) {
			return date_i18n( get_option('date_format'), $timestamp );
		}

		if ( $modifier === 'time' ) {
			return date_i18n( get_option('time_format'), $timestamp );
		}

		return date_i18n( get_option('date_format'), $timestamp );
	}
}
