<?php

namespace MyListing\Ext\Listing_Types;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Permalinks {
    use \MyListing\Src\Traits\Instantiatable;

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

    private $strings = [];

    /**
     * Latest WPJM settings
     * @var string
     */
    private $option_name = 'wpjm_permalinks';
    private $is_latest_wpjm = false;
    /*
     * Constructor.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->_wp_permalink = get_option('permalink_structure');

        if ( ! $this->_wp_permalink || ! class_exists( '\WP_Job_Manager_Post_Types' ) ) {
            return null;
        }

        remove_action( 'post_updated', 'bmr_api_call', 11, 3 );

        $this->set_option_name();

        $this->strings['missing_region'] = _x( 'unlocated', 'Permalinks > Missing Region Text', 'my-listing' );
        $this->strings['missing_category'] = _x( 'uncategorized', 'Permalinks > Missing Category Text', 'my-listing' );
        $this->strings['missing_type'] = _x( 'other', 'Permalinks > Missing Type Text', 'my-listing' );

        add_action( 'init', [ $this, 'add_permalink_structure' ], 99 );
        add_action( 'wp', [ $this, 'valid_url_redirection'] );

        add_filter( 'register_post_type_args', [ $this, 'extend_job_listing_args'], 10, 2 );

        // hack to bypass wp job manager sanitize title
        add_filter( 'pre_update_option_'. $this->option_name, [ $this, 'update_wpjm_permalink_value'] );

        add_filter( 'post_type_link', [ $this, 'post_type_link' ], 10, 3 );

        // Add support with base url
        add_filter( 'job_listing_rewrite_rules', [ $this, 'rewrite_rules'] );

        // Flush Rewrite Rules on terms update
        add_action('created_job_listing_category', 'flush_rewrite_rules');
        add_action('created_region', 'flush_rewrite_rules');

        add_action( 'save_post_case27_listing_type', 'flush_rewrite_rules' );
        add_action( 'mylisting/admin/types/after-update', [ $this, 'refresh_listing_types' ] );

        add_action( 'post_updated', [ $this, 'beamer_updated_permalink' ], 11, 3 );

        // Display docs in Settings > Permalinks.
        add_action( 'current_screen', [ $this, 'show_permalink_docs' ], 10 );
    }

    /**
     * Add permalink structure
     *
     * @access public
     * @return null|void
     */
    public function add_permalink_structure() {
        global $wp_rewrite;

        $option_value = get_option( $this->option_name );

        $permalink_structure = $this->normalize_value( $option_value );

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
    public function extend_job_listing_args( $args, $post_type ) {
        if ( $post_type != 'job_listing' ) {
            return $args;
        }

        $option_value = get_option( $this->option_name );
        $permalink_structure = $this->normalize_value( $option_value );

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
                    $tag_value = $listing->type->get_permalink_name();
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
                    $tag_value = $structure_tag;
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

        $request_uri = parse_url( trailingslashit( $_SERVER['REQUEST_URI'] ) );

        $post_permalink = get_permalink( $post );
        $permalink_structure = parse_url( $post_permalink );

        if ( $request_uri['path'] == $permalink_structure['path'] ) {
            return null;
        }

        if ( isset( $request_uri['query'] ) ) {
            $post_permalink .= '?' . $request_uri['query'];
        }

        wp_safe_redirect( $post_permalink, 301 );
        exit;
    }

    /**
     * Update WPJM permalink
     * Bypass wpjm sanitize filter
     *
     * @param  string $value
     * @return string
     */
    public function update_wpjm_permalink_value( $value ) {
        if ( ! isset( $_POST['wpjm_job_base_slug'] ) ) {
            return $value;
        }

        $value = $this->normalize_value( $value );

        $permalink_tags = $this->_parse_permalink_tags( $_POST['wpjm_job_base_slug'] );

        $value['job_base'] = implode( '/', $permalink_tags );

        if ( count( $permalink_tags ) > 1 ) {
            $value['job_base'] = $value['job_base'] . '/';
        }

        if ( $this->is_json_value() ) {
            return wp_json_encode( $value );
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
            if ( $type = Listing_Type::get( $post->ID ) ) {
                $listing_types[] = $type->get_permalink_name();
            }
        }

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

    public function beamer_updated_permalink( $post_ID, $post_after, $post_before ) {

        $beamer_id = bmr_api_has_id($post_ID) ? bmr_api_get_id($post_ID) : 0;

        if ( $post_after->post_type == 'job_listing' || $this->_wp_permalink ) {

            if ( ( $listing = \MyListing\Src\Listing::get( $post_after ) ) || $listing->type ) {

                // Remove base from URL
                $strip_base_url = false;

                // if ( ! $this->_url_base ) {
                //     $post_link = str_replace( '/' . $this->_default_listing_slug . '/', '', $post_link );
                // }

                $structure = [];

                foreach( $this->_permalink_structure as $structure_tag ) {

                    $tag_value = '';

                    switch ( $structure_tag ) {

                        case '%listing_type%' :
                            $tag_value = $listing->type->get_permalink_name();
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
                            $tag_value = $structure_tag;
                        break;
                    }

                    if ( ! $tag_value ) {
                        continue;
                    }

                    $structure[ $structure_tag ] = $tag_value;
                }

                $structure[] = $post_after->post_name;

                if ( $post_after->post_status != 'auto-draft' ) {

                    $post_after->guid = trailingslashit( home_url( implode( '/', $structure ) ) );

                    if( $post_after->post_status == 'trash' OR $post_after->post_status == 'draft' OR $post_after->post_status == 'pending' OR bmr_get_meta( 'bmr_ignore' ) == 'ignore' ){
                        // DELETE
                        $api_key = bmr_api_get_key();
                        $api_url = bmr_api_url('posts', $beamer_id);

                        // JSON here
                        $ch = curl_init($api_url);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Beamer-Api-Key: '.$api_key,
                            'User-Agent: WordPress Plugin v'.bmr_version().' (DELETE_post)'
                        ));
                        $result = curl_exec($ch);

                        // Update post meta with the Beamer custom fields
                        $prefix = 'bmr_';
                        $beamer_meta = array(
                            $prefix.'title' => $post_after->post_title,
                            $prefix.'content' => $content,
                            $prefix.'publish' => true,
                            $prefix.'linkUrl' => $post_after->guid,
                            $prefix.'date' => $date,
                            $prefix.'id' => null
                        );
                        foreach($beamer_meta as $key => $var){
                            update_post_meta($post_ID, $key, $var);
                        }
                    }elseif( $post_after->post_status == 'publish' OR $post_after->post_status == 'future' ){
                        // POST
                        $api_key = bmr_api_get_key();
                        $api_url = bmr_api_has_id($post_ID) ? bmr_api_url('posts', $beamer_id) : bmr_api_url('posts');

                        // Set date
                        $date = $post_after->post_date_gmt;
                        $date = str_replace(' ', 'T', $date);

                        // Set content
                        if( $post_after->post_excerpt != '' ){
                            // Look for the excerpt
                            $body = $post_after->post_excerpt;
                        }else{
                             if( strpos( $post_after->post_content, '<!--more-->' ) ){
                                // Look for read more tag
                                $content = $post_after->post_content;
                                $content_extended = get_extended( $content );
                                $content_iframe_filter = preg_replace('/<iframe.*?\/iframe>/i', '', $content_extended['main']);
                                $content_script_filter = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content_iframe_filter);
                                $body = $content_script_filter;
                             }else{
                                // Create a custom exerpt
                                $content = $post_after->post_content;
                                $content_iframe_filter = preg_replace('/<iframe.*?\/iframe>/i', '', $content);
                                $content_script_filter = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content_iframe_filter);
                                $limit = bmr_get_setting('api_excerpt');
                                $body = $limit ? bmr_api_trim_content( $content_script_filter, $limit ) : bmr_api_trim_content( $content_script_filter );
                             }
                        }

                        // Set Featured Image
                        if( has_post_thumbnail($post_ID) ){
                            $thumbnail_url = get_the_post_thumbnail_url($post_ID, 'full');
                            $thumbnail = '<img src="'.$thumbnail_url.'" alt="'.$thumbnail_url.'"/>';
                        }

                        $content = $thumbnail ? $thumbnail.' '.$body : $body;

                        // Set category
                        $category = bmr_get_meta('bmr_category');

                        // Set Read More
                        if( bmr_get_meta('bmr_link_text') ){
                            // Manual
                            $readmore = bmr_get_meta('bmr_link_text');
                        }elseif( bmr_get_setting('api_readmore') ){
                            // Default
                            $readmore = bmr_get_setting('api_readmore');
                        }

                        // Set feedback
                        if( bmr_get_meta( 'bmr_feedback' ) === 'off' ){
                            $feedback = false;
                        }else{
                            $feedback = true;
                        }

                        // Set reactions
                        if( bmr_get_meta( 'bmr_reactions' ) === 'off' ){
                            $react = false;
                        }else{
                            $react = true;
                        }

                        // Create data array
                        $data = array();

                            // Check array elements
                            if( is_bool( $post_after->post_title ) === true OR is_null( $post_after->post_title ) === true ) {
                                $data['title'] = array( '' );
                            } else {
                                $data['title'] = array( $post_after->post_title );
                            }

                            if( is_bool( $content ) === true OR is_null( $content ) === true ) {
                                $data['content'] = array( '' );
                            } else {
                                $data['content'] = array( $content );
                            }

                            if( is_bool( $category ) === true OR is_null( $category ) === true ) {
                                $data['category'] = 'new';
                            } else {
                                $data['category'] = $category;
                            }

                            $data['publish'] = true;

                            if( is_bool( $post_after->guid ) === true OR is_null( $post_after->guid ) === true ) {
                                $data['linkUrl'] = array( '' );
                            } else {
                                $data['linkUrl'] = array( $post_after->guid );
                            }

                            if( is_bool( $readmore ) === true OR is_null( $readmore ) === true ) {
                                $data['linkText'] = array( 'Read More' );
                            } else {
                                $data['linkText'] = array( $readmore ?: 'Read more' );
                            }

                            $data['date'] = $date;
                            $data['enableFeedback'] = $feedback;
                            $data['enableReactions'] = $react;
                            $data['autoOpen'] = false;
                            $data['language'] = array( 'EN' );

                        // Set request
                        $request = bmr_api_has_id($post_ID) ? 'PUT' : 'POST';

                        // Check if ignore
                        if( bmr_get_meta( 'bmr_ignore' ) == null ){
                            // JSON here
                            $data_string = json_encode($data);
                            $ch = curl_init($api_url);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: application/json',
                                'Beamer-Api-Key: '.$api_key,
                                'User-Agent: WordPress Plugin (v'.bmr_version().'/php'.phpversion().'/'.$request.')'
                            ));
                            $result = curl_exec($ch);
                            $decoded = json_decode($result, true);
                        }

                        // Update post meta with the Beamer custom fields
                        $prefix = 'bmr_';
                        $beamer_meta = array(
                            $prefix.'title' => $post_after->post_title,
                            $prefix.'content' => $content,
                            $prefix.'publish' => true,
                            $prefix.'linkUrl' => $post_after->guid,
                            $prefix.'date' => $date
                        );
                        if( !bmr_api_has_id($post_ID) ){
                            $beamer_meta[$prefix.'id'] = $decoded['id'];
                        }
                        foreach($beamer_meta as $key => $var){
                            update_post_meta($post_ID, $key, $var);
                        }
                    }
                }
            }
        }
    }

    public function show_permalink_docs() {
        $screen = get_current_screen();
        if ( empty( $screen ) || $screen->id !== 'options-permalink' ) {
            return;
        }

        $content = sprintf(
            '<p>Available tags: <code>%s</code> <code>%s</code> <code>%s</code> %s',
            '%listing_type%',
            '%listing_category%',
            '%listing_region%',
            '<a href="#" class="cts-show-tip" data-tip="permalink-docs" title="Click to learn more">[Learn More]</a>'
        );

        add_action( 'admin_footer', function() use( $content ) { ?>
            <script type="text/javascript">
                if ( jQuery('input[name="wpjm_job_base_slug"]').length ) {
                    jQuery('input[name="wpjm_job_base_slug"]').after( <?php echo wp_json_encode( $content ) ?> );
                    jQuery('input[name="wpjm_job_type_slug"]').parents('tr').hide();
                }
            </script>
        <?php } );
    }

    private function set_option_name() {
        if ( defined( '\WP_Job_Manager_Post_Types::PERMALINK_OPTION_NAME' ) ) {
            $this->option_name = \WP_Job_Manager_Post_Types::PERMALINK_OPTION_NAME;
            $this->is_latest_wpjm = true;
        }
    }

    private function normalize_value( $option_value ) {
        if ( $this->is_latest_wpjm ) {
            return (array) json_decode( $option_value );
        }

        return $option_value;
    }

    private function is_json_value() {
        return $this->is_latest_wpjm ? true : false;
    }
}
