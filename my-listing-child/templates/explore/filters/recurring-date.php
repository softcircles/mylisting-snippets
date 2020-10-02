<?php
/**
 * Template for rendering a `recurring-date` filter in Explore page.
 *
 * @since 2.4
 *
 * @var $filter
 * @var $location
 * @var $onchange
 */
if ( ! defined('ABSPATH') ) {
    exit;
} ?>

<recurring-date-filter
	listing-type="<?php echo esc_attr( $filter->listing_type->get_slug() ) ?>"
    filter-key="<?php echo esc_attr( $filter->get_form_key() ) ?>"
    location="<?php echo esc_attr( $location ) ?>"
    label="<?php echo esc_attr( $filter->get_label() ) ?>"
	:presets="<?php echo esc_attr( wp_json_encode( $filter->get_prop('ranges') ) ) ?>"
	:enable-datepicker="<?php echo $filter->get_prop( 'datepicker' ) ? 'true' : 'false' ?>"
	:enable-timepicker="<?php echo $filter->get_prop( 'timepicker' ) ? 'true' : 'false' ?>"
    :l10n="<?php echo esc_attr( wp_json_encode( [
        'pick' => _x( 'Pick a date', 'Explore page > Recurring Date filter', 'my-listing' ),
        'from' => _x( 'From...', 'Explore page > Recurring Date filter', 'my-listing' ),
        'to' => _x( 'To...', 'Explore page > Recurring Date filter', 'my-listing' ),
    ] ) ) ?>"
    @input="<?php echo esc_attr( $onchange ) ?>"
	inline-template
>
    <div v-if="location==='advanced-form'" class="form-group explore-filter recur-filter">
        <label>{{label}}</label>

        <div class="range-list" v-if="presets.length">
            <a v-for="range in presets" href="#" :class="range.key===selected?'active':''"
                class="single-range" @click.prevent="setPreset(range.key)">
                {{ range.label }}
            </a>
            <a v-if="enableDatepicker" href="#" class="single-range custom-date"
                :class="selected==='@custom'?'active':''" @click.prevent="selected='@custom'">
                {{l10n.pick}}
            </a>
        </div>

        <div class="double-input datepicker-form-group date-filter" :class="selected!=='@custom'?'hide':''">
            <div class="datepicker-wrapper" :class="startDate?'active':''">
                <input type="text" class="start-picker" :placeholder="l10n.from" :value="startDate"
                    ref="start" @datepicker:change="startDate=$event.detail.value; updateInput();">
            </div>
            <div class="datepicker-wrapper" :class="{active: endDate, disabled: !startDate}">
                <input type="text" class="end-picker" :placeholder="l10n.to" :value="endDate"
                    ref="end" @datepicker:change="endDate=$event.detail.value; updateInput();">
            </div>
        </div>
    </div>
    <div v-else class="form-group explore-filter">
        <label><a class="recurring-filter-back" style="display: none;">← </a>{{label}}</label>

        <div v-show="presets.length && selected!=='@custom'" class="recurring-filter-selector">
            <select ref="select" required :value="selected" @select:change="setPreset($event.detail.value)">
                <option v-for="range in presets" :value="range.key">{{range.label}}</option>
                <option v-if="enableDatepicker" value="@custom">{{l10n.pick}}</option>
            </select>
        </div>

        <div v-show="selected==='@custom' || !presets.length"
            class="double-input datepicker-form-group date-filter">
            <div class="datepicker-wrapper" :class="startDate?'active':''">
                <input type="text" class="start-picker" :placeholder="l10n.from" :value="startDate"
                    ref="start" @datepicker:change="startDate=$event.detail.value; updateInput();">
            </div>
            <div class="datepicker-wrapper" :class="{active: endDate, disabled: !startDate}">
                <input type="text" class="end-picker" :placeholder="l10n.to" :value="endDate"
                    ref="end" @datepicker:change="endDate=$event.detail.value; updateInput();">
            </div>
        </div>
    </div>
</recurring-date-filter>
