<?php

namespace MyListing\Src\Admin;

if ( ! defined('ABSPATH') ) {
    exit;
}

/**
 * Adds `Report Listing` functionality.
 *
 * @since 1.0
 */
class Reports_Screen {
    use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
        if ( is_admin() ) {
            add_action( 'load-post.php', [ $this, 'init_metabox' ] );
            add_filter( 'manage_case27_report_posts_columns', [ $this, 'admin_columns' ] );
            add_action( 'manage_case27_report_posts_custom_column', [ $this, 'admin_columns_content' ], 10, 2 );
            add_action( 'admin_menu',  [ $this, 'add_reports_as_listings_submenu' ], 250 );
        }

        add_action( 'wp_ajax_report_listing', [ $this, 'report_listing' ] );
        add_action( 'init', [ $this, 'register_post_type' ] );

        add_action( 'mylisting/get-footer', function() {
            if ( is_singular( 'job_listing' ) && is_user_logged_in() ) {
                require locate_template( 'partials/report-modal.php' );
            }
        } );
	}

    /**
     * Add Reports as Listings Submenu.
     *
     * @link  https://shellcreeper.com/how-to-add-wordpress-cpt-admin-menu-as-sub-menu/
     * @since 1.7.0
     */
    public function add_reports_as_listings_submenu() {
        $cpt_obj = get_post_type_object( 'case27_report' );
        if ( ! is_object( $cpt_obj ) || $cpt_obj === null ) {
            return;
        }

        $menu_title = _x( 'Reports', 'Reported Listings menu title in wp-admin', 'my-listing' );
        $counts = wp_count_posts( 'case27_report', 'readable' );
        if ( is_object( $counts ) && ! empty( $counts->publish ) && absint( $counts->publish ) > 0 ) {
            $menu_title .= sprintf( ' <span class="awaiting-mod update-plugins">%s</span>', number_format_i18n( $counts->publish ) );
        }

        add_submenu_page(
            'edit.php?post_type=job_listing', // Parent slug.
            $cpt_obj->labels->name, // Page title.
            $menu_title, // Menu title.
            $cpt_obj->cap->edit_posts, // Capability.
            'edit.php?post_type=case27_report' // Menu slug.
        );
    }

    /**
     * Modify the columns for the reports post type page in backend.
     *
     * @since 1.0
     */
    public function admin_columns( $columns ) {
        unset( $columns['title'] );

        $columns = [
            'cb' => $columns['cb'],
            'reported_listing' => _x( 'Listing', 'Reported listings', 'my-listing' ),
            'report_reason' => _x( 'Reason', 'Reported listings', 'my-listing' ),
            'reported_by' => _x( 'Reported By', 'Reported listings', 'my-listing' ),
            'date' => $columns['date'],
            'report_actions' => _x( 'Actions', 'Reported listings', 'my-listing' ),
        ];

        return $columns;
    }

    /**
     * Add content for the custom columns.
     *
     * @since 1.0
     */
    public function admin_columns_content( $column, $post_id ) {
        switch ( $column ) {
            case 'reported_listing':
                $listingID = get_post_meta( $post_id, '_report_listing_id', true );
                echo $listingID ? esc_html( get_the_title( $listingID ) ) : ( '<em>' . _x( 'This listing does not exist.', 'Reported listings', 'my-listing' ) . '</em>' );
                break;

            case 'report_reason':
                echo c27()->the_text_excerpt( get_post_meta( $post_id, '_report_content', true ), 200 );
                break;

            case 'reported_by':
                $userID = get_post_meta( $post_id, '_report_user_id', true );
                $user = $userID ? get_user_by( 'id', $userID ) : false;

                echo $user ? $user->data->display_name : ( '<em>' . _x( 'This account no longer exists.', 'Reported listings', 'my-listing' ) . '</em>' );
            break;

            case 'report_actions':
                $listingID = get_post_meta( $post_id, '_report_listing_id', true );
                $review_link = $listingID ? get_permalink( $listingID ) : false;

                if ( $review_link ) {
                    printf( '<a href="%1$s" class="button button-primary button-large" title="%2$s" target="_blank"><i class="fa fa-eye"></i></a> ', $review_link, _x( 'Review Listing', 'Reported listings', 'my-listing' ) );
                }

                printf( '<a href="%1$s" class="button button-large" title="%2$s"><i class="icon-pencil-2"></i></a> ',  get_edit_post_link( $post_id ), _x( 'View Report', 'Reported listings', 'my-listing' ) );
                printf( '<a href="%1$s" class="button button-large" title="%2$s"><i class="fa fa-check"></i></a>',  get_delete_post_link( $post_id ), _x( 'Close Report', 'Reported listings', 'my-listing' ) );
                break;
        }
    }

    /**
     * Add report details metabox in admin backend.
     *
     * @since 1.0
     */
	public function init_metabox() {
        add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
	}

    /**
     * Add report details metabox content in admin backend.
     *
     * @since 1.0
     */
    public function add_metabox() {
        add_meta_box(
            'case27-report',
            _x( 'Report Details', 'Reported listings', 'my-listing' ),
            [ $this, 'render_metabox' ],
            'case27_report',
            'advanced',
            'high'
        );
    }

    /**
     * Renders the report details meta box.
     *
     * @since 1.0
     */
    public function render_metabox( $post ) {
        // Add nonce for security and authentication.
        wp_nonce_field( 'reported_listings_nonce', 'custom_nonce' );

        require locate_template( 'templates/admin/reported-listings/report.php' );
    }

    /**
     * Ajax endpoint for submitting a report.
     *
     * @since 1.0
     */
    public function report_listing() {
        // security check
        check_ajax_referer( 'c27_ajax_nonce', 'security' );

        $listing_id = ! empty( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : false;
        $report_content = ! empty( $_POST['content'] ) ? sanitize_textarea_field( $_POST['content'] ) : false;
        $user_id = get_current_user_id();

        if ( ! ( $listing_id && $report_content ) ) {
            return wp_send_json( [
                'status' => 'error',
                'message' => _x( 'Please fill in all the necessary data.', 'Reported listings', 'my-listing' )
            ] );
        }

        if ( empty( $_POST['recaptcha'] ) ) {
            return wp_send_json( [
                'status' => 'error',
                'message' => _x( 'Security check failed. Please try again.', 'Reported listings', 'my-listing' )
            ] );
        }

        $response = wp_remote_get( add_query_arg( [
            'secret'   => mylisting_get_setting( 'recaptcha_secret_key' ),
            'response' => isset( $_POST['recaptcha'] ) ? $_POST['recaptcha'] : '',
            'remoteip' => isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'],
        ], 'https://www.google.com/recaptcha/api/siteverify' ) );

        if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
            return wp_send_json( [
                'status' => 'error',
                'message' => _x( 'Security check failed. Please try again.', 'Reported listings', 'my-listing' )
            ] );
        }

        $json = json_decode( $response['body'] );
        if ( ! $json || ! $json->success ) {
            return wp_send_json( [
                'status' => 'error',
                'message' => _x( 'Security check failed. Please try again.', 'Reported listings', 'my-listing' )
            ] );
        }

        // make sure a report from this user on this listings doesn't already exist
        $report_exists = get_posts( [
            'post_type' => 'case27_report',
            'post_status' => 'publish',
            'meta_query' => [
                [ 'key' => '_report_listing_id', 'value' => $listing_id ],
                [ 'key' => '_report_user_id', 'value' => $user_id ],
            ],
        ] );

        if ( $report_exists ) {
            return wp_send_json( [
                'status' => 'error',
                'message' => _x( 'You\'ve already reported this listing. It is currently being reviewed.', 'Reported listings', 'my-listing' )
            ] );
        }

        // insert report
        $report_id = wp_insert_post( [
            'post_type' => 'case27_report',
            'post_author' => $user_id,
            'post_title' => _x( 'New user report submitted.', 'Reported listings', 'my-listing' ),
            'post_status' => 'publish',
            'meta_input' => [
                '_report_listing_id' => $listing_id,
                '_report_user_id' => $user_id,
                '_report_content' => $report_content,
            ],
        ] );

        // error inserting report
        if ( ! $report_id || is_wp_error( $report_id ) ) {
            return wp_send_json( [
                'status' => 'error',
                'message' => _x( 'An error occurred while processing your request. Please try again.', 'Reported listings', 'my-listing' )
            ] );
        }

        /**
         * Triggered when a new listing report has been successfully submitted.
         *
         * @since 2.1.6
         */
        do_action( 'mylisting/reports:new-submission', $report_id );

        // success
        return wp_send_json( [
            'status' => 'success',
            'message' => _x( 'Your report was submitted successfully. It will be shortly reviewed by our team.', 'Reported listings', 'my-listing' )
        ] );
    }

    public function register_post_type() {
        $labels = [
            'name'               => _x( 'Reports', 'post type general name', 'my-listing' ),
            'singular_name'      => _x( 'Report', 'post type singular name', 'my-listing' ),
            'menu_name'          => _x( 'Reports', 'admin menu', 'my-listing' ),
            'name_admin_bar'     => _x( 'Report', 'add new on admin bar', 'my-listing' ),
            'add_new'            => _x( 'Add New', 'Report', 'my-listing' ),
            'add_new_item'       => __( 'Add New Report', 'my-listing' ),
            'new_item'           => __( 'New Report', 'my-listing' ),
            'edit_item'          => __( 'Edit Report', 'my-listing' ),
            'view_item'          => __( 'View Report', 'my-listing' ),
            'all_items'          => __( 'Reports', 'my-listing' ),
            'search_items'       => __( 'Search Reports', 'my-listing' ),
            'parent_item_colon'  => __( 'Parent Report:', 'my-listing' ),
            'not_found'          => __( 'No Reports found.', 'my-listing' ),
            'not_found_in_trash' => __( 'No Reports found in Trash.', 'my-listing' )
        ];

        $args = [
            'labels'             => $labels,
            'description'        => __( 'Allow users to report listings.', 'my-listing' ),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'case27_report' ],
            'capability_type'    => 'page',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 180,
            'supports'           => [ 'title' ],
        ];

        register_post_type( 'case27_report', $args );
    }
}
