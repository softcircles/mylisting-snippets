<?php

namespace MyListing\Ext\Advanced_Custom_Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Advanced_Custom_Fields {

	public static function boot() {
		new self;
	}

	public function __construct() {
		// Load the plugin.
		require locate_template( 'includes/extensions/advanced-custom-fields/plugin/acf.php' );

		// Paths to save acf data.
		add_filter( 'acf/settings/path',       [ $this, 'settings_path' ] );
		add_filter( 'acf/settings/dir',        [ $this, 'settings_dir' ] );
		add_filter( 'acf/settings/save_json',  [ $this, 'save_json' ] );
		add_filter( 'acf/settings/load_json',  [ $this, 'load_json' ] );
		add_filter( 'acf/settings/show_admin', [ $this, 'show_admin' ], 30 );

		// Include custom field types.
        add_action( 'acf/include_field_types', [ $this, 'load_custom_field_types' ] );

		// Add admin option pages.
		add_action( 'mylisting/init', [ $this, 'add_theme_options_page' ] );
		add_action( 'mylisting/init', [ $this, 'add_integrations_page' ] );

		// filter for every field
		add_filter( 'acf/fields/post_object/query', [ $this, 'post_object_query' ], 30, 3 );
		add_filter( 'acf/fields/post_object/result', [ $this, 'post_object_result' ], 30, 4 );
	}

	public function settings_path( $path ) {
		return trailingslashit( __DIR__ ) . 'plugin/';
	}

	public function settings_dir( $dir ) {
    	return trailingslashit( get_template_directory_uri() ) . 'includes/extensions/advanced-custom-fields/plugin/';
	}

	public function save_json( $path ) {
		return trailingslashit( __DIR__ ) . 'acf-json/';
	}

	public function load_json( $paths ) {
		// Remove original path.
    	unset( $paths[0] );
    	$paths[] = trailingslashit( __DIR__ ) . 'acf-json';
	    return $paths;
	}

    public function show_admin() {
        return CASE27_ENV === 'dev';
    }

    public function add_theme_options_page() {

    	if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
            return false;
        }

		acf_add_options_sub_page( [
			'page_title' 	=> __('Theme Options', 'my-listing'),
			'menu_title'	=> __('Theme Options', 'my-listing'),
			'menu_slug' 	=> 'theme-general-settings',
			'capability'	=> 'manage_options',
			'redirect'		=> false,
			'parent_slug'   => 'case27/tools.php',
		] );
	}

    public function add_integrations_page() {

    	if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
            return false;
        }
        
		acf_add_options_sub_page( [
			'page_title' 	=> _x( 'Other', 'Theme Tools > Other (page title)', 'my-listing' ),
			'menu_title'	=> _x( 'Other', 'Theme Tools > Other (menu title)', 'my-listing' ),
			'menu_slug' 	=> 'theme-integration-settings',
			'capability'	=> 'manage_options',
			'redirect'		=> false,
			'parent_slug'   => 'case27/tools.php',
		] );
	}

	/**
	 * Order 'Post Object' dropdown fields by date,
	 * based on it's classname (.order-by-date).
	 *
	 * @since 1.7.0
	 */
	public function post_object_query( $args, $field, $post_id ) {
		if ( ! isset( $field['wrapper'], $field['wrapper']['class'], $field['type'] ) || $field['type'] !== 'post_object' ) {
			return $args;
		}

		// order results by date
		if ( strpos( $field['wrapper']['class'], 'order-by-date' ) !== false ) {
			$args['orderby'] = 'date';
			$args['order'] = 'DESC';
		}

		// search by `id` column
		if ( strpos( $field['wrapper']['class'], 'search-by-id' ) !== false && ! empty( $args['s'] ) && is_numeric( $args['s'] ) ) {
			global $wpdb;

			$post__in = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID LIKE %s", '%'.absint( $args['s'] ).'%' ) );
			if ( is_array( $post__in ) && ! empty( $post__in ) ) {
				$args['post__in'] = $post__in;
				unset( $args['s'] );
			}
		}

	    return $args;
	}

	/**
	 * Filter the dropdown item name.
	 *
	 * @since 1.7.0
	 */
	public function post_object_result( $title, $post, $field, $post_id ) {
		if ( ! isset( $field['wrapper'], $field['wrapper']['class'], $field['type'] ) || $field['type'] !== 'post_object' ) {
			return $title;
		}

		// Prepend ID.
		if ( strpos( $field['wrapper']['class'], 'prepend-item-id' ) !== false ) {
			$title = sprintf( '<strong>#%d</strong> &mdash; %s', $post->ID, $title );
		}

		return $title;
	}

	/**
	 * Load custom made ACF field types.
	 *
	 * @since 2.0
	 */
	public function load_custom_field_types() {
		new Sidebar_Field;
		new Iconpicker_Field;
		new Code_Field\Code_Field;
	}
}
