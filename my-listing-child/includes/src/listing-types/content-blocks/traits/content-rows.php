<?php
/**
 * Helper functions for block types that have content rows, like tables,
 * accordions, details, and tabs blocks.
 *
 * @since 2.2
 */

namespace MyListing\Src\Listing_Types\Content_Blocks\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Content_Rows {

	/**
	 * Validate and format rows for use in template files.
	 *
	 * @since 2.2
	 */
	public function get_formatted_rows( $listing ) {
		$rows = [];
		foreach ( (array) $this->get_prop('rows') as $row ) {
		    if ( ! ( is_array( $row ) && $listing->has_field( $row['show_field'] ) ) ) {
		        continue;
		    }

		    $row_field = $listing->get_field( $row['show_field'], true );
		    $row_field_value = $row_field->get_value();
		    if ( is_array( $row_field_value ) ) {
		        $row_field_value = join( ', ', $row_field_value );
		    }
			
			/**
			 * Escape html output, unless it's a wp-editor field or a texteditor field with mode set
			 * to wp-editor. These require HTML markup for rendering, so shouldn't be escaped.
			 */
			$escape_html = ! ( $row_field->get_type() === 'wp-editor' || ( $row_field->get_type() === 'texteditor' && $row_field->get_prop('editor-type') !== 'textarea' ) );
			if ( $escape_html ) {
				$row_field_value = esc_html( $row_field_value );
			}

			// replace the field value into [[field]] placeholder
		    $row_content = str_replace( '[[field]]', c27()->esc_shortcodes( $row_field_value ), $row['content'] );
			//print_r( $row_content );
			// print_r( $row_field_value );
			if ( $row_field->get_key() == 'asking-price' ) {
				$row_content = '£' . number_format_i18n( do_shortcode( $row_field_value ) );
			} else {
			    // run shortcodes added in the listing type editor (not user ones)
			    $row_content = wpautop( do_shortcode( $row_content ) );
			}

		    // insert row
		    $rows[] = [
		        'title' => $row['label'],
		        'content' => $row_content,
        		'icon' => isset( $row['icon'] ) ? $row['icon'] : '',
		    ];
		}

		return $rows;
	}

	protected function getRowsField() { ?>
		<div class="repeater-option">
			<label>Rows</label>
			<draggable v-model="block.rows" :options="{group: 'repeater', handle: '.row-head'}">
				<div v-for="row, row_id in block.rows" class="row-item">
					<div class="row-head" @click="toggleRepeaterItem($event)">
						<div class="row-head-toggle"><i class="mi chevron_right"></i></div>
						<div class="row-head-label">
							<h4>{{ fieldLabelBySlug( row.show_field ) || '(choose a field)' }}</h4>
							<div class="details">
								<div class="detail">Field: {{ fieldLabelBySlug( row.show_field ) || 'None' }}</div>
							</div>
						</div>
						<div class="row-head-actions">
							<span title="Remove" @click.stop="block.rows.splice(row_id, 1)" class="action red"><i class="mi delete"></i></span>
						</div>
					</div>
					<div class="row-edit">
						<div class="form-group" v-if="block.type === 'details'">
							<label>Icon</label>
							<iconpicker v-model="row.icon"></iconpicker>
						</div>

						<div class="form-group" v-if="block.type !== 'details'">
							<label>Label</label>
							<input type="text" v-model="row.label">
						</div>

						<div class="form-group">
							<label>Field to use</label>
							<div class="select-wrapper">
								<select v-model="row.show_field">
									<option value="" disabled="disabled">Use Field:</option>
									<option v-for="field in fieldsByType(['text', ( block.type !== 'table' ? 'texteditor' : '' ), ( block.type !== 'table' ? 'wp-editor' : '' ), 'checkbox', 'radio', 'select', 'multiselect', 'textarea', 'date', 'time', 'datetime',  'email', 'url', 'number', 'location', 'file'])" :value="field.slug">{{ field.label }}</option>
									<option value="__listing_rating">Rating</option>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label>Content</label>
							<input type="text" v-model="row.content">
						</div>

						<div class="text-right mt10">
							<div class="btn btn-xs" @click.prevent="toggleRepeaterItem($event)">Done</div>
						</div>
					</div>
				</div>
				<div class="text-right mt10">
					<div class="btn btn-xs" @click.prevent="block.rows.push({label: '', show_field: '', content: '[[field]]', icon: ''})">Add row</div>
				</div>
			</draggable>
		</div>
	<?php }

}
