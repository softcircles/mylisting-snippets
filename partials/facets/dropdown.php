<?php
$data = c27()->merge_options( [
    'facet' => '',
    'options' => [
    	'multiselect' => false,
    	'hide_empty' => true,
        'order_by' => 'count',
    	'order' => 'DESC',
        'placeholder' => __( 'Select an option', 'my-listing' ),
    ],
    'facet_data' => [
    	'choices' => [],
    ],
    'is_vue_template' => true,
], $data );

$type = $data['type'];
$facet = $data['facet'];
$fieldkey = sprintf( 'types["%s"].filters["%s"]', $data['listing_type'], $facet['show_field'] );

$facet_show_field = $facet['show_field'];
if ( $facet_show_field == 'job_category' ) {
    $facet_show_field = 'category';
} elseif ( $facet_show_field == 'job_tags' ) {
    $facet_show_field = 'tag';
}

if ( ! empty( $_GET[$facet['url_key']] ) ) {
    $selected = (array) $_GET[$facet['url_key']];
} elseif ( ! empty( $_GET[$facet['show_field']] ) ) {
    $selected = (array) $_GET[$facet['show_field']];
} elseif ( ( $selected_val = get_query_var( sprintf( 'explore_%s', $facet_show_field ) ) ) ) {
    $selected = (array) $selected_val;
} else {
    $selected = [];
}

if ( ! $facet || ! ( $field = $type->get_field( $facet[ 'show_field' ] ) ) ) {
    return;
}

foreach( (array) $facet['options'] as $option ) {
    if ( isset( $data['options'][ $option['name'] ] ) ) {
        $data['options'][ $option['name'] ] = $option['value'];
    }
}

if ( ! $data['is_vue_template'] ) {
    $data['options']['multiselect'] = false;
}

$placeholder = ! empty( $data['options']['placeholder'] ) ? $data['options']['placeholder'] : false;

if ( ! empty( $field['taxonomy'] ) && taxonomy_exists( $field['taxonomy'] ) ) {
    $selected_terms = [];
    $_selected_terms = ! empty( $selected ) ? get_terms( [
            'taxonomy' => $field['taxonomy'],
            'hide_empty' => false,
            'slug' => $selected,
    ] ) : [];

    if ( is_wp_error( $_selected_terms ) ) {
        $_selected_terms = [];
    }

    // validate selected terms
    foreach ( $_selected_terms as $_term ) {
        if ( ! $_term instanceof \WP_Term ) {
            continue;
        }

        // ignore term if it doesn't belong to this listing type
        $term_types = array_filter( array_map( 'absint', (array) get_term_meta( $_term->term_id, 'listing_type', true ) ) );
        if ( ! empty( $term_types ) && ! in_array( $type->get_id(), $term_types ) ) {
            continue;
        }

        $selected_terms[] = $_term;
    }

    $GLOBALS['c27-facets-vue-object'][$data['listing_type']][$facet['show_field']] = array_map( function( $term ) {
        return $term->slug;
    }, $selected_terms );

    $is_single = ! $data['options']['multiselect'];
    $ajax_params = [
        'taxonomy' => $field['taxonomy'],
        'listing-type-id' => $type->get_id(),
        'orderby' => $data['options']['order_by'],
        'order' => $data['options']['order'],
        'hide_empty' => $data['options']['hide_empty'] ? 'yes' : '',
        'term-value' => 'slug',
    ]; ?>

    <?php if ( $is_single ):
        $ajax_params['parent'] = 0;
        $selected_tree = [];
        if ( ! empty( $selected_terms ) && $selected_terms[0] instanceof \WP_Term ) {
            $term_list = array_reverse( get_ancestors( $selected_terms[0]->term_id, $field['taxonomy'], 'taxonomy' ) );
            $term_list[] = $selected_terms[0]->term_id;
            foreach ( $term_list as $term_id ) {
                $term = get_term( $term_id );
                $selected_tree[] = [
                    'value' => $term->slug,
                    'label' => $term->name,
                ];
            }
        } ?>
        <div class="cts-term-hierarchy form-group <?php echo esc_attr( ! $placeholder ? 'md-group' : '' ) ?>">
            <input
                <?php echo sprintf( '@termhierarchy:change="%s = $event.detail.value; getListings();"', esc_attr( $fieldkey ) ) ?>
                type="text" class="term-hierarchy-input"
                data-selected="<?php echo c27()->encode_attr( $selected_tree ); ?>"
                name="<?php echo esc_attr( $facet['url_key'] ) ?>"
                <?php printf( 'data-placeholder="%s"', esc_attr( $placeholder ?: " " ) ) ?>
                data-mylisting-ajax-params="<?php echo c27()->encode_attr( $ajax_params ) ?>"
                data-template="<?php echo $data['is_vue_template'] ? 'default' : 'alternate' ?>"
            >
            <label><?php echo esc_html( $facet['label'] ) ?></label>
        </div>
    <?php else: ?>
        <div class="form-group explore-filter dropdown-filter-multiselect <?php echo esc_attr( ! $placeholder ? 'md-group' : '' ) ?> dropdown-filter <?php echo esc_attr( ! empty( $selected_terms ) ? 'md-active' : '' ) ?>">
            <div class="main-term">
                <select
                    multiple="multiple"
                    <?php printf( 'placeholder="%s"', esc_attr( $placeholder ?: " " ) ) ?>
                    class="custom-select"
                    name="<?php echo esc_attr( $facet['url_key'] ).'[]' ?>"
                    <?php printf( '@select:change="%s = $event.detail.value; getListings();"', esc_attr( $fieldkey ) ) ?>
                    data-mylisting-ajax="true"
                    data-mylisting-ajax-url="mylisting_list_terms"
                    data-mylisting-ajax-params="<?php echo c27()->encode_attr( $ajax_params ) ?>"
                >
                    <?php foreach ( (array) $selected_terms as $term ): ?>
                        <option value="<?php echo esc_attr( $term->slug ) ?>" selected="selected"><?php echo esc_attr( $term->name ) ?></option>
                    <?php endforeach ?>
                </select>
                <label><?php echo esc_html( $facet['label'] ) ?></label>
            </div>
        </div>
    <?php endif ?>
    <?php
    return;
}

if ( $data['options']['order_by'] == 'include' ) {
    if ( $data['options']['order'] == 'DESC' ) {
        $field['options'] = array_reverse( (array) $field['options'] );
    }

    foreach ( (array) $field['options'] as $option ) {
        $data['facet_data']['choices'][] = [
            'value' => $option,
            'label' => $option,
            'selected' => false,
        ];
    }
} else {
    // dump($facet, $field);
    if (!function_exists('c27_dropdown_facet_query_group_by_filter')) {
        function c27_dropdown_facet_query_group_by_filter( $groupby ) { global $wpdb;
            return $wpdb->postmeta . '.meta_value ';
        }
    }

    if (!function_exists('c27_dropdown_facet_query_fields_filter')) {
        function c27_dropdown_facet_query_fields_filter( $fields ) { global $wpdb;
            return $wpdb->postmeta . '.meta_value ';
        }
    }

    add_filter('posts_fields', 'c27_dropdown_facet_query_fields_filter');
    add_filter('posts_groupby', 'c27_dropdown_facet_query_group_by_filter');

	$posts = query_posts( [
		'post_type' => 'job_listing',
		'posts_per_page' => apply_filters( 'mylisting/filters/dropdown/per-page', -1, $facet, $type, $data ),
        'orderby' => $data['options']['order_by'],
        'order' => $data['options']['order'],
        'meta_query' => [
            ['key' => "_{$facet['show_field']}"],
            ['key' => '_case27_listing_type', 'value' => $type->get_slug()],
        ],
	] );

    remove_filter('posts_fields', 'c27_dropdown_facet_query_fields_filter');
    remove_filter('posts_groupby', 'c27_dropdown_facet_query_group_by_filter');
    wp_reset_query();

	foreach ((array) $posts as $post) {
        if ( empty( $post->meta_value ) ) {
            continue;
        }

        if ( is_serialized( $post->meta_value ) ) {
            foreach ( array_filter( (array) unserialize( $post->meta_value ) ) as $value ) {
                $data['facet_data']['choices'][] = [
                    'value' => $value,
                    'label' => ucwords( trim( preg_replace( "#([^a-z0-9])#i", " ", strtolower($value) ) ) ),
                    'selected' => false,
                ];
            }

            continue;
        }

		$data['facet_data']['choices'][] = [
			'value' => $post->meta_value,
            'label' => ucwords( trim( preg_replace( "#([^a-z0-9])#i", " ", strtolower("{$post->meta_value}") ) ) ),
			'selected' => false,
		];
	}

    $data['facet_data']['choices'] = array_map( 'unserialize', array_unique( array_map( 'serialize', $data['facet_data']['choices'] ) ) );
}

$choices_flat = (array) array_column( $data['facet_data']['choices'], 'value' );
$selected = array_filter( array_filter( $selected, function( $value ) use ( $choices_flat ) {
    return in_array( $value, $choices_flat );
} ) );

$GLOBALS['c27-facets-vue-object'][$data['listing_type']][$facet['show_field']] = $selected;
?>

<div class="form-group explore-filter <?php echo esc_attr( ! $placeholder ? 'md-group' : '' ) ?> dropdown-filter <?php echo esc_attr( ! empty( $selected ) ? 'md-active' : '' ) ?> <?php echo $data['options']['multiselect'] ? 'dropdown-filter-multiselect' : '' ?>">
    <?php if ($data['is_vue_template']): ?>
        <select
            @select:change="<?php echo esc_attr( $fieldkey ) ?> = $event.detail.value; getListings();"
            class="custom-select"
            <?php echo $data['options']['multiselect'] ? 'multiple="multiple"' : '' ?>
            <?php printf( 'placeholder="%s"', esc_attr( $placeholder ?: " " ) ) ?>
        >
            <?php
            // single selects must have an empty <optio></option> to handle the placeholder and stop other issues
            if ( ! $data['options']['multiselect'] ): ?>
                <option></option>
            <?php endif ?>
            <?php foreach ( $data['facet_data']['choices'] as $choice ): ?>
                <option value="<?php echo esc_attr( $choice['value'] ) ?>" <?php selected( in_array( $choice['value'], $selected ), true ) ?>><?php echo esc_attr( $choice['label'] ) ?></option>
            <?php endforeach ?>
        </select>
    <?php else: ?>
        <select name="<?php echo esc_attr( $facet['url_key'] ) ?>[]"
                placeholder="<?php echo esc_attr( $data['options']['placeholder'] ) ?>" class="custom-select">
            <option></option>
            <?php foreach ($data['facet_data']['choices'] as $choice): ?>
                <option value="<?php echo esc_attr( $choice['value'] ) ?>"><?php echo esc_html( $choice['label'] ) ?></option>
            <?php endforeach ?>
        </select>
    <?php endif ?>

    <label><?php echo esc_html( $facet['label'] ) ?></label>
</div>
