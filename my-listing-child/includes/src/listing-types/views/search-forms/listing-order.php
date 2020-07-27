<?php
if ( ! defined('ABSPATH') ) {
	exit;
} ?>
<div class="tab-content full-width" v-if="currentSubTab == 'order'">
	<div class="form-section">
		<h3>Set how results are ordered on Explore page, and what listings should appear first</h3>
		<p>These options will appear in the "Order By" dropdown in the Explore page. Click on an option to edit. Drag & Drop to reorder.</p>
	</div>

	<div class="editor-column col-2-3 rows row-padding">
		<div class="form-section mb10">
			<h4 class="mb5">Ordering options</h4>
			<p>Click to edit. Drag & drop to reorder.</h4>
		</div>

		<draggable v-model="search.order.options" :options="{group: 'search-order-options', handle: '.row-head'}">
			<div v-for="option, opt_key in search.order.options" class="row-item" :class="option === state.search.active_order ? 'open' : ''">
				<div class="row-head" @click="state.search.active_order = ( option !== state.search.active_order ) ? option : null">
					<div class="row-head-toggle"><i class="mi chevron_right"></i></div>
					<div class="row-head-label">
						<h4>{{ option.label }}</h4>
						<div class="details">
							<div class="detail">{{ option.key }}</div>
						</div>
					</div>
					<div class="row-head-actions">
						<span title="Remove" @click.stop="searchTab().removeOption(option)" class="action red"><i class="mi delete"></i></span>
					</div>
				</div>
				<div class="row-edit">
					<div class="form-group">
						<label>Label</label>
						<input type="text" v-model="option.label" @input="searchTab().setOptionKey(option)">
					</div>

					<div class="form-group">
						<label>Key</label>
						<input type="text" :value="option.key" @input="option.key = slugify( $event.target.value )">
						<p>
							This key can be used to automatically select this option using a url parameter,
							such as example.com/explore-places?order=latest
						</p>
					</div>

					<div class="clauses">
						<div class="clause" v-for="clause, key in option.clauses">
							<div class="clause-heading mt20">
								<div class="editor-column col-1-2">
									<h4>Edit clause #{{ key + 1 }}</h4>
								</div><!--
								--><div class="editor-column col-1-2 text-right">
									<div class="btn btn-xs btn-plain" v-show="key >= 1" @click="searchTab().removeClause(clause, option)"><i class="mi delete"></i> Remove</div>
								</div>
							</div>

							<div class="form-group">
								<label>Order by</label>
								<div class="select-wrapper">
									<select v-model="clause.context">
										<option value="option">Option</option>
										<option value="meta_key">Custom Field</option>
										<option value="raw_meta_key">Raw Field</option>
									</select>
								</div>

								<div>
									<p v-show="clause.context == 'option' || ! clause.context">
										Order by Option: Use one of the ordering methods provided next.
									</p>

									<p v-show="clause.context == 'meta_key'">
										Order by Custom Field: Use one of the fields you've added in the "Fields" tab for ordering.
									</p>

									<p v-show="clause.context == 'raw_meta_key'">
										Order by Raw Field: Use a listing meta field that hasn't been added in the "Fields" tab, but either programatically, or by another plugin.
									</p>
								</div>
							</div>

							<div class="form-group">
								<div v-show="clause.context == 'option' || ! clause.context">
									<label>Select option</label>
									<div class="select-wrapper">
										<select v-model="clause.orderby">
											<option value="date">Date</option>
											<option value="title">Title</option>
											<option value="author">Author</option>
											<option value="rating">Rating</option>
											<option value="proximity">Proximity</option>
											<option value="comment_count">Review Count</option>
											<option value="relevance">Relevance</option>
											<option value="modified">Modified</option>
											<option value="menu_order">Menu Order</option>
											<option value="rand">Random</option>
											<option value="ID">Listing ID</option>
											<option value="name">Slug</option>
											<option value="none">None</option>
										</select>
									</div>
								</div>

								<div v-show="clause.context === 'meta_key'">
									<label>Select field</label>
									<div class="select-wrapper">
										<select v-model="clause.orderby">
											<option v-for="field in fieldsByType(['recurring-date', 'number', 'date', 'checkbox', 'radio', 'select', 'text', 'password'])" :value="field.slug">
												{{ field.label }}
											</option>
										</select>
									</div>
								</div>

								<div v-show="clause.context === 'raw_meta_key'">
									<label>Enter field key</label>
									<input type="text" v-model="clause.orderby">
								</div>
							</div>

							<div class="form-group" v-if="
								(clause.context === 'meta_key' && searchTab().optionType(clause.orderby)!=='recurring-date')
								|| clause.context === 'raw_meta_key'">
								<label>Data type</label>
								<div class="select-wrapper" v-show="!clause.custom_type">
									<select v-model="clause.type">
										<option value="CHAR">Text</option>
										<option value="NUMERIC">Numeric</option>
										<option value="DATE">Date</option>
										<option value="DATETIME">Datetime</option>
										<option value="TIME">Time</option>
										<option value="DECIMAL">Decimal</option>
										<option value="UNSIGNED">Unsigned</option>
										<option value="SIGNED">Signed</option>
										<option value="BINARY">Binary</option>
									</select>
								</div>
								<input type="text" v-show="clause.custom_type" v-model="clause.type">
								<p class="mt10">
									<label><input type="checkbox" v-model="clause.custom_type" class="form-checkbox"> Enter manually</label>
									<span v-show="clause.custom_type">Use this to specify precision and scale if using the 'DECIMAL' or 'NUMERIC' types (for example, 'DECIMAL(10,5)' or 'NUMERIC(10)' are valid).</span>
								</p>
							</div>

							<div class="form-group">
								<label class="mb5">Order</label>
								<label><input type="radio" v-model="clause.order" value="ASC" :name="'clause-order-' + key + '-option-' + opt_key" class="form-radio mb5">Ascending</label>
								<label><input type="radio" v-model="clause.order" value="DESC" :name="'clause-order-' + key + '-option-' + opt_key" class="form-radio">Descending</label>
							</div>
						</div>
					</div>

					<div class="mt5 mb5 text-center">
						<div class="btn btn-outline btn-xs mb5" @click="searchTab().addClause(option)">{{ option.clauses.length === 1 ? 'Add secondary clause' : 'Add ordering clause' }}</div>
						<div class="btn btn-plain btn-xs" v-if="option.clauses.length > 1">Careful: Adding multiple ordering clauses could drastically decrease search performance.</div>
					</div>

					<hr>

					<div class="form-group full-width">
						<label class="mt10"><input type="checkbox" v-model="option.ignore_priority" class="form-checkbox"> Ignore listing priority</label>
						<p>
							Listings will be ordered based on their <a href="#" class="cts-show-tip" data-tip="priority-docs">priority level</a> first.
							Use this setting if you wish to disable this behavior for this specific ordering option.
						</p>
					</div>

					<div class="text-right">
						<div class="btn" @click="state.search.active_order = null">Done</div>
					</div>
				</div>
			</div>
		</draggable>

		<div v-if="!search.order.options.length" class="mt40 text-center">
			<div class="btn btn-plain">
				<i class="mi playlist_add"></i>
				No options added yet.
			</div>
		</div>
	</div><!--
	--><div class="editor-column col-1-3">
		<div class="form-section mb10">
			<h4 class="mb5">Preset ordering options.</h4>
			<p>Click on an option to use it.</h4>
		</div>

		<div class="btn btn-block mb10" @click.prevent="searchTab().addOption( 'Latest', 'latest', 'date', 'DESC', 'option' )">Latest listings</div>
		<div class="btn btn-block mb10" @click.prevent="searchTab().addOption( 'Top rated', 'top-rated', 'rating', 'DESC', 'option', 'DECIMAL(10,2)', false, true )">Top rated</div>
		<div class="btn btn-block mb10" @click.prevent="searchTab().addOption( 'Nearby', 'nearby', 'proximity', 'ASC', 'option' )">Nearby Listings</div>
		<div class="btn btn-block mb10" @click.prevent="searchTab().addOption( 'A-Z', 'a-z', 'title', 'ASC', 'option' )">A-Z</div>
		<div class="btn btn-block mb10" @click.prevent="searchTab().addOption( 'Random', 'random', 'rand', 'DESC', 'option' )">Random</div>

		<div class="text-center mt20">
			<div class="btn btn-outline mb10" @click.prevent="searchTab().addOption()">Add Custom Order</div>
		</div>
	</div>
</div>
