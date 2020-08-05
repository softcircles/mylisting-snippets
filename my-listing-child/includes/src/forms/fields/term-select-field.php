<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Term_Select_Field extends Base_Field {

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

		if ( $template === 'multiselect' || $template === 'checklist' ) {
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
		return wp_get_object_terms( $this->listing->get_id(), $this->props['taxonomy'], [
			'orderby' => 'term_order',
			'order' => 'ASC',
			// 'fields' => 'ids',
		] );
	}

	public function string_value( $modifier = null ) {
		$terms = (array) $this->get_value();
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$modify = $terms;
		$parent = ( count( $modify ) == 1 && ! empty( $modify[0]->parent ) ) ? true : false;

		if ( $parent ) {
			$modify = $modify[0];

			$parents = [];
		    $ancestors = [];

		    while ( ! is_wp_error( $modify ) && ! empty( $modify->parent ) && ! in_array( $modify->parent, $ancestors ) ) {
		    	$ancestors[] = (int) $modify->parent;
		        if ( $ancestors && ( $parent = get_term( $modify->parent, $this->props['taxonomy'] ) ) ) {
		        	$parents[] = $parent->name . '&#9656;' . $modify->name;
				}
		    }

		    return $parents;
		}

		$names = array_map( function( $term ) {
			return $term->name;
		}, $terms );

		return join( ', ', $names );
	}

	public function field_props() {
		$this->props['type'] = 'term-select';
		$this->props['taxonomy'] = '';
		$this->props['terms-template'] = 'multiselect';
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getTermsTemplateField();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
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
