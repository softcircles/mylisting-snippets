<?php

/**
 *
 * Use the following snippet to fix the gravity form error on listing preview
 *
 */

if ( isset( $_REQUEST['job_manager_form'] )  && $_REQUEST['job_manager_form'] == 'submit-listing' ) {

    $unset_gform_fields = [
        'gform_ajax',
        'gform_submit',
        'gform_unique_id',
        'gform_target_page_number_2',
        'gform_source_page_number_2',
        'gform_field_values'
    ];

    foreach ( $unset_gform_fields as $field) {
        if ( ! isset( $_POST[ $field ] ) ) {
            continue;
        }

        unset( $_POST[ $field ] );
    }
}
