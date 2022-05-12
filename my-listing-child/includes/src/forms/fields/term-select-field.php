<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Term_Select_Field extends Base_Field {

	public $modifiers = [
		'slugs' => '%s Slug(s)',
	];

	public function get_posted_value() {
		$field = $this->props;
		$key = $this->key;
		if ( ! empty( $field['terms-template'] ) && in_array( $field['terms-template'], [ 'single-select', 'hierarchy', 'multiselect', 'checklist'] ) ) {
			$template = $field['terms-template'];
		} else {
			$template = 'multiselect';
		}

		$value = ! empty( $_POST[ $key ] ) ? (array) $_POST[ $key ] : [];

		if ( $template === 'single-select' || $template === 'hierarchy' ) {
			return ! empty( $value[0] ) && $value[0] > 0 ? absint( $value[0] ) : '';
		}

		if ( $template === 'multiselect' && $this->props['create_tag'] ) {
			$new_terms = [];
			foreach ( $value as $term ) {
				if ( intval( $term ) ) {
					$new_terms[] = $term;
					continue;
				}

				$term = sanitize_text_field( $term );
				$term_exists = term_exists( $term, $this->props['taxonomy'] );
				if ( $term_exists !== 0 && $term_exists !== null ) {
					$term_obj = get_term_by( 'name', $term, $this->props['taxonomy'] );
					$new_terms[] = $term_obj->term_id;
					continue;
				}

				$new_item = wp_insert_term( $term, $this->props['taxonomy'] );
				if ( is_wp_error( $new_item ) ) {
					unset( $value[ $term ] );
					continue;
				}

				$term_obj = get_term_by( 'id', $new_item['term_id'], $this->props['taxonomy'] );
				$new_terms[] = $term_obj->term_id;
			}

			return array_map( 'absint', $new_terms );
		} elseif  ( $template === 'multiselect' ) {
			return array_map( 'absint', $value );
		}

		if ( $template === 'checklist' ) {
			return array_map( 'absint', $value );
		}
	}

	public function validate() {
		$value = $this->get_posted_value();

		// make sure the field has a taxonomy set
		if ( empty( $this->props['taxonomy'] ) || ! ( taxonomy_exists( $this->props['taxonomy'] ) ) || ! $this->listing_type ) {
			// translators: %s is the field label.
			throw new \Exception( sprintf( _x( '%s is an invalid field.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
		}

		foreach ( (array) $value as $term_id ) {
			// make sure each posted term exists
			if ( ! term_exists( $term_id, $this->props['taxonomy'] ) ) {
				// translators: %s is the field label.
				throw new \Exception( sprintf( _x( 'Invalid value supplied for %s.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
			}

			// make sure the selected terms can be used with this listing type
			$term_meta = get_term_meta( $term_id, 'listing_type', true );
			if ( is_array( $term_meta ) && ! empty( $term_meta ) && ! in_array( $this->listing_type->get_id(), $term_meta ) ) {
				// translators: %s is the field label.
				throw new \Exception( sprintf( _x( 'Invalid term supplied for %s.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
			}
		}
	}

	public function update() {
		$value = $this->get_posted_value();
		wp_set_object_terms( $this->listing->get_id(), $value, $this->props['taxonomy'], false );

		// save drag&drop term order
		if ( ! empty( $value ) ) {
			c27()->set_terms_order( $this->listing->get_id(), $value );
		}

		do_action( 'mylisting/fields/term-select:updated', $value, $this->props['taxonomy'], $this );
	}

	public function get_value() {
		$terms = wp_get_object_terms( $this->listing->get_id(), $this->props['taxonomy'], [
			'orderby' => 'term_order',
			'order' => 'ASC',
		] );

		return ! is_wp_error( $terms ) ? $terms : [];
	}

	public function string_value( $modifier = null ) {
		$terms = (array) $this->get_value();
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$names = array_map( function( $term ) {
			return $term->name;
		}, $terms );

		if ( $modifier === 'slugs' ) {
			$names = array_map( function( $term ) {
				return $term->slug;
			}, $terms );
		}

		return join( ', ', $names );
	}

	public function field_props() {
		$this->props['type'] = 'term-select';
		$this->props['taxonomy'] = '';
		$this->props['terms-template'] = 'multiselect';
		$this->props['create_tag'] = false;
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getTermsTemplateField();
		$this->getCreateTagsField();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
		$this->getShowInCompareField();
	}

	public function getCreateTagsField() { ?>

		<div class="form-group" v-show="field['terms-template'] == 'multiselect'">
			<label>Allow users to add terms?</label>
			<label class="form-switch mb0">
				<input type="checkbox" v-model="field.create_tag">
				<span class="switch-slider"></span>
			</label>
		</div>
		<?php
	}

	public function getTermsTemplateField() { ?>
		<div class="form-group">
			<label>Template</label>
			<div class="select-wrapper">
				<select v-model="field['terms-template']">
					<option value="single-select">Term Select</option>
					<option value="multiselect">Term Multiselect</option>
					<option value="hierarchy">Term Hierarchy</option>
					<option value="checklist">Term Checklist</option>
				</select>
			</div>
		</div>
	<?php }
}
