<?php

namespace MyListing\Src;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Permalinks {

    /**
     * Allowed Permalink tags
     * @var array
     */
    private $_allowed_permalink_tags = [
            '%listing_type%',
            '%listing_region%',
            '%listing_category%'
        ];

    /**
     * Default listing slug
     * @var string
     */
    private $_default_listing_slug = 'listing';

    /**
     * URL base
     * @var string
     */
    private $_url_base = '';

    /**
     * Permalink Structure array
     * @var array
     */
    private $_permalink_structure = [];

    /**
     * WordPress Permalink Structure
     * @var string
     */
    private $_wp_permalink = false;

    // MyListing permalink structure
    private $_mylisting_permalinks = [];

    private $strings = [];

    public static function boot() {
        new self;
    }

    /*
     * Constructor.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->_wp_permalink = get_option('permalink_structure');
        $this->_mylisting_permalinks = $this->get_permalink_structure();

        if ( ! $this->_wp_permalink ) {
            return null;
        }

        $this->strings['missing_region'] = _x( 'unlocated', 'Permalinks > Missing Region Text', 'my-listing' );
        $this->strings['missing_category'] = _x( 'uncategorized', 'Permalinks > Missing Category Text', 'my-listing' );
        $this->strings['missing_type'] = _x( 'other', 'Permalinks > Missing Type Text', 'my-listing' );

        add_action( 'init', [ $this, 'add_permalink_structure' ], 99 );
        add_action( 'wp', [ $this, 'valid_url_redirection'] );

        add_filter( 'register_post_type_args', [ $this, 'extend_listing_args' ], 10, 2 );

        // validate listing base before updating
        add_filter( 'pre_update_option_mylisting_permalinks', [ $this, 'update_listing_base' ] );

        add_filter( 'post_type_link', [ $this, 'post_type_link' ], -1, 3 );

        // Add support with base url
        add_filter( 'job_listing_rewrite_rules', [ $this, 'rewrite_rules'] );

        // Flush Rewrite Rules on terms and listings update
        add_action( 'created_job_listing_category', 'flush_rewrite_rules' );
        add_action( 'mylisting/submission/done', [ $this, 'maybe_flush_rules_on_submission' ] );
        add_action( 'mylisting/admin/save-listing-data', [ $this, 'maybe_flush_rules_on_submission' ] );
        add_action( 'created_region', 'flush_rewrite_rules' );

        add_action( 'save_post_case27_listing_type', 'flush_rewrite_rules' );
        add_action( 'mylisting/admin/types/after-update', [ $this, 'refresh_listing_types' ] );
    }

    /**
     * Add permalink structure
     *
     * @access public
     * @return null|void
     */
    public function add_permalink_structure() {
        global $wp_rewrite;

        $permalink_structure = $this->_mylisting_permalinks;
        $this->_parse_permalink_tags( $permalink_structure['job_base'] );

        $tag_index = 0;
        foreach ( $this->_permalink_structure as $tag ) {
            if ( ! in_array( $tag, $this->_allowed_permalink_tags ) ) {
                continue;
            }

            $tag_index++;
            if ( $this->_url_base || $tag_index > 1 ) {
                $tag_value = '([^/]+)';
                continue;
            }

            switch ( $tag ) {
                case '%listing_type%' :
                    $tag_value = '(' . implode( '|', $this->get_listing_types() ) . ')';
                break;

                case '%listing_category%' :
                    $tag_value = '(' . implode( '|', $this->get_listing_categories() )  . ')';
                break;

                case '%listing_region%' :
                    $tag_value = '(' . implode( '|', $this->get_listing_regions() )  . ')';
                break;

                default :
                    $tag_value = '([^/]+)';
                break;
            }

            add_rewrite_tag( $tag, $tag_value );
        }

        // Add URL without baseurl
        $this->add_support_without_baseurl();

        $permalink_structure = implode( '/', array_merge( $this->_permalink_structure, ['%job_listing%'] ) );
        add_permastruct( 'job_listing', $permalink_structure, false );
    }

    /**
     * Extend `job_listing` post type args
     *
     * @param  array $args
     * @param  string $post_type
     * @return array
     */
    public function extend_listing_args( $args, $post_type ) {
        if ( $post_type !== 'job_listing' ) {
            return $args;
        }

        $permalink_structure = $this->_mylisting_permalinks;

        if ( ! isset( $permalink_structure['job_base'] ) ) {
            $permalink_structure['job_base'] = '';
        }

        $this->_parse_permalink_tags( $permalink_structure['job_base'] );

        $listing_slug = $this->_default_listing_slug;

        if ( ! empty( $this->_permalink_structure[0] ) ) {
            $listing_slug = $this->_permalink_structure[0];
        }

        // Consider the first element as base slug
        $args['rewrite']['slug'] = sanitize_title_with_dashes( $listing_slug );

        return $args;
    }

    /**
     * Fix permalinks output.
     *
     * @param String  $post_link link url.
     * @param WP_Post $post post object.
     * @param String  $leavename for edit.php.
     *
     * @version 2.0
     *
     * @return string
     */
    public function post_type_link( $post_link, $post, $leavename ) {
        if ( $post->post_type != 'job_listing' || ! $this->_wp_permalink ) {
            return $post_link;
        }

        if ( ! ( $listing = \MyListing\Src\Listing::get( $post ) ) || ! $listing->type ) {
            return $post_link;
        }

        // Remove base from URL
        $strip_base_url = false;

        if ( ! $this->_url_base ) {
            $post_link = str_replace( '/' . $this->_default_listing_slug . '/', '', $post_link );
        }

        $structure = [];

        foreach( $this->_permalink_structure as $structure_tag ) {

            $tag_value = '';

            switch ( $structure_tag ) {

                case '%listing_type%' :
                    $tag_value = sanitize_title_with_dashes( $listing->type->get_permalink_name() );
                break;

                case '%listing_region%' :
                    $regions = $listing->get_field( 'region' );
                    if ( ! $regions ) {
                        $tag_value = $this->strings['missing_region'];
                        break;
                    }

                    // Consider the first region as primary region.
                    $tag_value = $regions[0]->slug;
                break;

                case '%listing_category%' :
                    $categories = $listing->get_field( 'category' );
                    if ( ! $categories ) {
                        $tag_value = $this->strings['missing_category'];
                        break;
                    }

                    // Consider the first category as primary category.
                    $tag_value = $categories[0]->slug;
                break;

                default :
                    $tag_value = sanitize_title_with_dashes( $structure_tag );
                break;
            }

            if ( ! $tag_value ) {
                continue;
            }

            $structure[ $structure_tag ] = $tag_value;
        }

        $structure[] = $leavename ? '%pagename%' : $post->post_name;
        return trailingslashit( home_url( implode( '/', $structure ) ) );
    }

    /**
     * Rewrite Rules
     * for WPJM
     * @param  array $rules
     * @return array
     */
    public function rewrite_rules( $rules ) {
        $new_rules = [];

        if ( $this->_url_base ) {
            $new_rules[ $this->_url_base . '/([^/]+)/?$' ] = 'index.php?job_listing=$matches[1]';
        }

        end( $rules );
        $last_key = key( $rules );

        unset( $rules[ $last_key ] );

        $custom_rule = [];
        $regex_size = 1;
        $tag_index = 0;

        foreach ( $this->_permalink_structure as $structure ) {

            // If the permalink structure has a base, or has already had a tag with
            // the custom regex, then all following tags can use the generic matcher.
            if ( in_array( $structure, $this->_allowed_permalink_tags ) ) {
                $tag_index++;

                if ( $this->_url_base || $tag_index > 1 ) {
                    $regex_size++;
                    $custom_rule[] = '([^/]+)';
                    continue;
                }
            }

            switch( $structure ) {
                case '%listing_type%' :
                    $custom_rule[] = '(' . implode( '|', $this->get_listing_types() ) . ')';
                break;

                case '%listing_category%' :
                    $custom_rule[] = '(' . implode( '|', $this->get_listing_categories() ) . ')';
                break;

                case '%listing_region%' :
                    $custom_rule[] = '(' . implode( '|', $this->get_listing_regions() ) . ')';
                break;

                default :
                    $custom_rule[] = $structure;
                    $regex_size--;
                break;
            }

            $regex_size++;
        }

        $custom_rule[] = '([^/]+)/?$';
        $custom_rule = implode( '/', $custom_rule );

        $new_rules[ $custom_rule ] = 'index.php?job_listing=$matches[' . $regex_size . ']';

        $overwrite_rules = [];
        foreach ( $rules as $regex => $structure ) {

            $bracket_index = 0;
            $match_index = 1;

            preg_match_all('/\$matches\[\d+\]/', $structure, $matches, PREG_SET_ORDER );

            $regex_parts = explode('/', $regex );

            foreach ( $regex_parts as &$part ) {

                $first_bracket = strstr( $part, '(' );

                if ( $first_bracket && isset( $matches[ $bracket_index ] ) ) {

                    $structure = str_replace( $matches[ $bracket_index ][0], "\$matches[__{$match_index}__]", $structure );
                    $bracket_index++;
                    $match_index++;

                } elseif ( strstr( $part, '|' ) ) {
                    $part = '(' . $part . ')';
                    $match_index++;
                }
            }

            // Overwrite the structure variables
            $structure = str_replace('__', '', $structure);

            $regex = implode('/', $regex_parts);
            $overwrite_rules[ $regex ] = $structure;
        }

        $new_rules = array_merge( $overwrite_rules, $new_rules );

        return $new_rules;
    }

    /**
     * Valid URL redirection
     *
     * @return null|void
     */
    public function valid_url_redirection() {
        global $post;

        if ( ! is_singular( 'job_listing' ) ) {
            return null;
        }

        $request_uri = parse_url( $_SERVER['REQUEST_URI'] );

        $post_permalink = get_permalink( $post );
        $permalink_structure = parse_url( $post_permalink );

        if ( trailingslashit( strtolower( $request_uri['path'] ) ) == strtolower( $permalink_structure['path'] ) ) {
            return null;
        }

        if ( isset( $request_uri['query'] ) ) {
            $post_permalink .= '?' . $request_uri['query'];
        }

        wp_safe_redirect( $post_permalink, 301 );
        exit;
    }

    /**
     * Validate listing base before updating.
     *
     * @since 2.1
     */
    public function update_listing_base( $value ) {
        if ( ! isset( $value['job_base'] ) ) {
            return $value;
        }

        $permalink_tags = $this->_parse_permalink_tags( $value['job_base'] );

        $value['job_base'] = implode( '/', $permalink_tags );

        if ( count( $permalink_tags ) > 1 ) {
            $value['job_base'] = $value['job_base'] . '/';
        }

        return $value;
    }

    /**
     * Add url support without base url
     *
     * @return void
     */
    protected function add_support_without_baseurl() {
        if ( $this->_url_base ) {
            return null;
        }

        add_action( 'pre_get_posts', [ $this, 'extend_main_query' ] );
    }

    /**
     * Extend Main Query
     * Hook to extend wp query object
     *
     * @param  object $query
     * @return void
     */
    public function extend_main_query( $query ) {
        if ( ! $query->is_main_query() || 2 != count( $query->query ) || ! $query->get('name') || ! isset( $query->query['page'] ) ) {
            return null;
        }

        $query->set('post_type', ['job_listing', 'post', 'page']);
    }

    public function refresh_listing_types() {
        $this->get_listing_types( true );
    }

    /**
     * Get list of types
     * @return array
     */
    public function get_listing_types( $refresh = false ) {
        $types = get_option( 'mylisting_permalinks_types_cache' );

        if ( is_array( $types ) && ! empty( $types ) && $refresh !== true ) {
            return $types;
        }

        global $post;

        $types = new \WP_Query( [
            'post_type' => 'case27_listing_type',
            'posts_per_page' => -1,
        ] );

        $listing_types = [];
        $listing_types[] = $this->strings['missing_type'];

        while( $types->have_posts() ) {
            $types->the_post();
            if ( $type = \MyListing\Src\Listing_Type::get( $post->ID ) ) {
                $listing_types[] = sanitize_title_with_dashes( $type->get_permalink_name() );
            }
        }

        // remove empty values
        $listing_types = array_filter( $listing_types );

        wp_reset_postdata();

        update_option( 'mylisting_permalinks_types_cache', $listing_types, true );
        return $listing_types;
    }

    /**
     * Get list of categories
     * @return array
     */
    public function get_listing_categories() {
        $categories = get_terms( [
            'taxonomy' => 'job_listing_category',
            'fields' => 'id=>slug',
            'hide_empty' => true,
        ] );

        if ( is_wp_error( $categories ) ) {
            return [];
        }

        // For listings without a category, display a message
        // e.g. 'uncategorized'. This message also needs to be part of the regex.
        $categories[] = $this->strings['missing_category'];

        return $categories;
    }

    /**
     * Get list of regions
     * @return array
     */
    public function get_listing_regions() {
        $regions = get_terms( [
            'taxonomy' => 'region',
            'fields' => 'id=>slug',
            'hide_empty' => true,
        ] );

        if ( is_wp_error( $regions ) ) {
            return [];
        }

        // For listings without a region, display a message
        // e.g. 'unlocated'. This message also needs to be part of the regex.
        $regions[] = $this->strings['missing_region'];

        return $regions;
    }

    /**
     * On new listing submissions, flush permalink rules to avoid 404 errors with
     * terms that were previously empty. This is only necessary if the base
     * permalink tag is a taxonomy i.e. category or region.
     *
     * @since 2.1
     */
    public function maybe_flush_rules_on_submission() {
        $permalink_structure = $this->_mylisting_permalinks;
        $this->_parse_permalink_tags( $permalink_structure['job_base'] );
        $base_struct = ! empty( $this->_permalink_structure ) ? $this->_permalink_structure[0] : false;

        if ( in_array( $base_struct, [ '%listing_category%', '%listing_region%' ], true ) ) {
            mlog('updating permalinks');
            flush_rewrite_rules();
        }
    }

    /**
     * Parse permalink tags
     *
     * @param  string $permalink
     * @return string
     */
    private function _parse_permalink_tags( $permalink ) {
        $permalink_tags = explode( '/', $permalink );

        // Verify Tags
        foreach ( $permalink_tags as $index => $tag ) {

            if ( $tag && ! strstr( $tag, '%' ) ) {
                $this->_url_base = ( $index == 0 ) ? $tag : $this->_url_base;
                continue;
            }

            // Remove unsupported tags
            if ( ! in_array( $tag, $this->_allowed_permalink_tags ) ) {
                unset( $permalink_tags[ $index ] );
            }
        }

        $this->_permalink_structure = ! empty( $permalink_tags ) ? $permalink_tags : [ $this->_default_listing_slug ];

        return $this->_permalink_structure;
    }

    /**
     * Retrieves permalink settings.
     *
     * @since 2.1
     */
    public static function get_permalink_structure() {
        $permalinks = [];

        $mylisting_permalinks = (array) get_option( 'mylisting_permalinks', [] );
        if ( is_array( $mylisting_permalinks ) && ! empty( $mylisting_permalinks ) ) {
            $permalinks = array_merge( $permalinks, $mylisting_permalinks );
        }

        $permalinks = wp_parse_args( array_filter( $permalinks ), [
            'job_base' => 'listing',
            'category_base' => 'category',
            'region_base' => 'region',
            'tag_base' => 'tag',
        ] );

        return $permalinks;
    }
}
