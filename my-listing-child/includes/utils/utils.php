<?php

namespace MyListing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the nearest thousands to a given number and create a string from that.
 * For example:
 * 515 => "1-1000"
 * 2440 => "2001-3000"
 * 10000 => "9999-10000"
 *
 * @since 2.2.3
 */
function nearest_thousands( $number ) {
	// numbers like 0, 1000, 2000, etc. should be included in the previous thousands group
	if ( $number % 1000 === 0 ) {
		$number -= 1;
	}

	// calculate upper and lower thousands
	$up = (int) ( 1000 * ceil( $number / 1000 ) );
	$down = ( (int) ( 1000 * floor( $number / 1000 ) ) ) + 1;

	return "{$down}-{$up}";
}

/**
 * Basic HTML minification.
 *
 * @since 2.2.3
 */
function minify_html( $content ) {
    $search = [
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
        '/<!--(.|\s)*?-->/' // Remove HTML comments
    ];

    $replace = [ '>', '<', '\\1', '' ];
    $content = preg_replace( $search, $replace, $content );
    return $content;
}

/**
* Converts shorthand memory notation value to bytes
* From http://php.net/manual/en/function.ini-get.php
*
* @param $size_str Memory size shorthand notation string e.g. 256M
* @since 2.2.3
*/
function return_bytes( $size_str ) {
    switch ( substr( $size_str, -1 ) ) {
        case 'M': case 'm': return (int) $size_str * 1048576;
        case 'K': case 'k': return (int) $size_str * 1024;
        case 'G': case 'g': return (int) $size_str * 1073741824;
        default: return $size_str;
    }
}

/**
 * Get taxonomy version (updated every time one of it's terms changes),
 * to be used for caching purposes.
 *
 * @since 2.2.3
 */
function get_taxonomy_versions( $taxonomy = null ) {
	$versions = (array) json_decode( get_option( 'mylisting_taxonomy_versions', null ), ARRAY_A );
	if ( ! empty( $taxonomy ) ) {
		return isset( $versions[ $taxonomy ] ) ? absint( $versions[ $taxonomy ] ) : 0;
	}

	return $versions;
}

/**
 * Delete given directory.
 *
 * @since 2.2.3
 */
function delete_directory( $target ) {
    if ( is_dir( $target ) ) {
        $files = glob( $target . '*', GLOB_MARK );
        foreach( $files as $file ) {
            delete_directory( $file );
        }

        @rmdir( $target );
    } elseif ( is_file( $target ) ) {
        @unlink( $target );
    }
}

/**
 * Return all registered image sizes.
 *
 * @since 2.3.4
 */
function get_image_sizes() {
	global $_wp_additional_image_sizes;
	$sizes = [];

	foreach ( [ 'thumbnail', 'medium', 'medium_large', 'large' ] as $size ) {
	    $sizes[ $size ] = [
	        'width'  => intval( get_option( "{$size}_size_w" ) ),
	        'height' => intval( get_option( "{$size}_size_h" ) ),
	        'crop'   => get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false,
	    ];
	}

	if ( ! empty( $_wp_additional_image_sizes ) ) {
	    $sizes = array_merge( $sizes, $_wp_additional_image_sizes );
	}

	return $sizes;
}

function add_dashboard_page( $args ) {
	return \MyListing\Ext\WooCommerce\WooCommerce::instance()->add_dashboard_page( $args );
}

/**
 * Retrieve a posts array from the given post type. If post
 * count is too big, query isn't run and `false` is
 * returned to avoid memory overflows.
 *
 * @since 2.4.4
 */
function get_posts_dropdown( $post_type, $key = 'ID', $value = 'post_title', $ignore_limit = false ) {
	static $cache = [];

	$cache_key = sprintf( '%s-%s-%s', $post_type, $key, $value );

	// if this post type has already been requested once, retrieve it from cache
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$limit = apply_filters( 'mylisting/posts-dropdown-limit', 500, $post_type );
	$cache[ $cache_key ] = [];
	$post_count = absint( wp_count_posts( $post_type, 'readable' )->publish );
	$allowed_fields = [ 'ID', 'post_title', 'post_name' ];
	$key = in_array( $key, $allowed_fields, true ) ? $key : 'ID';
	$value = in_array( $value, $allowed_fields, true ) ? $value : 'post_title';

	// no posts available, return an empty array
	if ( $post_count < 1 ) {
		return $cache[ $cache_key ];
	}

	// post limit reached, return `false` so the caller can handle this
	// dropdown in another way, e.g. using a number input for the post id,
	// in which case a dropdown isn't needed.
	if ( ( $post_count > $limit ) && $ignore_limit !== true ) {
		$cache[ $cache_key ] = false;
		return $cache[ $cache_key ];
	}

	// retrieve posts from database
	global $wpdb;
	$posts = $wpdb->get_results( $wpdb->prepare( "
		SELECT {$key}, {$value} FROM {$wpdb->posts}
		WHERE post_type = %s AND post_status = 'publish' ORDER BY post_title ASC
	", $post_type ) );

	// store in `ID => post_title` pairs
	if ( is_array( $posts ) && ! empty( $posts ) ) {
		foreach ( $posts as $post ) {
			$cache[ $cache_key ][ $post->$key ] = $post->$value;
		}
	}

	return $cache[ $cache_key ];
}

/**
 * `str_contains` polyfill.
 *
 * @since 2.4.5
 */
function str_contains( $haystack, $needle ) {
    return $needle === '' || strpos( $haystack, $needle ) !== false;
}

/**
 * Replace field tags with the actual field value.
 * Example items to be replaced: [[tagline]] [[description]] [[twitter-id]]
 *
 * @since 1.5.0
 */
function compile_string( $string, $require_all_fields, $listing ) {
	preg_match_all('/\[\[+(?P<fields>.*?)\]\]/', $string, $matches);

	if ( empty( $matches['fields'] ) ) {
		return $string;
	}

	// To allow a field, field+modifier, or a special key to output HTML markup,
	// it must be explicity whitelisted.
	$allow_markup = apply_filters(
		'mylisting/compile-string/unescaped-fields',
		[':reviews-stars'],
		$listing
	);

	// Get all field values.
	foreach ( array_unique( $matches['fields'] ) as $slug ) {
		// $slug can be just the key e.g. [[location]], or the field
		// key and a modifier, e.g. [[location.lat]]
		$parts = explode( '.', $slug );
		$field_key = $parts[0];
		$modifier = isset( $parts[1] ) ? $parts[1] : null;

		// check if it's a special key
		if ( $special_key = $listing->get_special_key( $slug ) ) {
			$value = $special_key;
		}
		// otherwise get value from field
		elseif ( $listing->has_field( $field_key ) ) {
			$field = $listing->get_field( $field_key, true );
			$value = apply_filters(
				'mylisting/compile-string-field',
				$field->get_string_value( $modifier ),
				$field,
				$modifier,
				$listing
			);

			$value = apply_filters( 'mylisting/compile-string-field/'.$field_key, $value, $field, $modifier );

			if ( is_array( $value ) ) {
				$value = join( ', ', $value );
			}
		} else {
			$value = '';
		}

		// if any of the used fields are empty, return false
		if ( ( empty( $value ) && ! in_array( $value, [ 0, '0', 0.0 ], true ) ) && $require_all_fields ) {
			return false;
		}

		// escape square brackets so any shortcode added by the listing owner won't be run
		$value = str_replace( [ "[" , "]" ] , [ "&#91;" , "&#93;" ] , $value );

		if ( ! in_array( $slug, $allow_markup, true ) ) {
			$value = esc_html( $value );
		}

		// replace the field bracket with it's value
		$string = str_replace( "[[$slug]]", $value, $string );
	}

	// Preserve line breaks.
	return $string;
}

/**
 * Generate dynamic CSS styles file.
 *
 * @since 1.0
 */
function generate_dynamic_styles() {
	$upload_dir = wp_get_upload_dir();
	if ( ! is_array( $upload_dir ) || empty( $upload_dir['basedir'] ) ) {
		return;
	}

	ob_start();
	require locate_template( 'assets/dynamic/dynamic-css.php' );
	echo c27()->get_setting( 'custom_css' );

	// remove excessive whitespace
	$styles = preg_replace( '/\s+/S', ' ', ob_get_clean() );
	file_put_contents( trailingslashit( $upload_dir['basedir'] ) . 'mylisting-dynamic-styles.css', $styles );
	mlog( 'Generated mylisting-dynamic-styles.css' );
}

function display_recaptcha() {
	wp_enqueue_script( 'recaptcha' );
	$site_key = mylisting_get_setting( 'recaptcha_site_key' ); ?>
	<div class="google-recaptcha">
		<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ) ?>"></div>
	</div>
<?php }

function validate_recaptcha() {
	if ( empty( $_POST['g-recaptcha-response'] ) ) {
		throw new \Exception( __( 'Security check failed. Please try again.', 'my-listing' ) );
	}

	$response = wp_remote_get( add_query_arg( [
		'secret'   => mylisting_get_setting( 'recaptcha_secret_key' ),
		'response' => isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '',
		'remoteip' => isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'],
	], 'https://www.google.com/recaptcha/api/siteverify' ) );

	if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
		throw new \Exception( __( 'Security check failed. Please try again.', 'my-listing' ) );
	}

	$json = json_decode( $response['body'] );
	if ( ! $json || ! $json->success ) {
		throw new \Exception( __( 'Security check failed. Please try again.', 'my-listing' ) );
	}

	// captcha is verified
}

function get_login_url() {
	return get_permalink( get_option('woocommerce_myaccount_page_id') );
}

function get_register_url() {
	return get_permalink( get_option('woocommerce_myaccount_page_id') ).'?register';
}

function get_current_url() {
	return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function current_user_can_edit( $post_id ) {
	return ( is_user_logged_in() && (
		current_user_can( 'edit_others_posts', $post_id ) ||
		absint( get_post_field( 'post_author', $post_id ) ) === absint( get_current_user_id() )
	) );
}

/**
 * Get a list of all countries in `country_code` => `country_name` format.
 *
 * @since 2.0
 */
function get_list_of_countries() {
	static $list;
	if ( ! is_null( $list ) ) {
		return $list;
	}

	$list = require locate_template( 'includes/utils/data/list-of-countries.php' );
	return $list;
}

/**
 * Get a country name by its ISO 3166-1 alpha-2 code.
 *
 * @link https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
 * @since 2.0
 */
function get_country_name_by_code( $code ) {
	$countries = get_list_of_countries();
	if ( empty( $countries[ $code ] ) ) {
		return false;
	}

	return $countries[ $code ];
}

function get_assets_version() {
	static $version;
	if ( ! is_null( $version ) ) {
		return $version;
	}

	$version = is_dev_mode() ? rand(1, 1e4) : wp_get_theme( get_template() )->get('Version');
	return $version;
}

function get_listing_types() {
	static $types = null;
	if ( is_array( $types ) ) {
		return $types;
	}

	$types = [];
	$type_ids = get_posts( [
		'post_type' => 'case27_listing_type',
		'post_status' => 'publish',
		'fields' => 'ids',
		'posts_per_page' => -1,
	] );

	foreach ( (array) $type_ids as $type_id ) {
		if ( $type = \MyListing\Src\Listing_Type::get( $type_id ) ) {
			$types[] = $type;
		}
	}

	return $types;
}

function set_cookie( $name, $value = '', $expires = 0, $secure = false, $httponly = false ) {
	if ( headers_sent() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	        headers_sent( $file, $line );
	        trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
	    }

	    return false;
	}

	setcookie( $name, $value, $expires, COOKIEPATH ?: '/', COOKIE_DOMAIN, $secure, $httponly );
}

function get_cookie( $name ) {
	return $_COOKIE[ $name ] ?? false;
}

function bookmarks_endpoint_slug() {
	return _x( 'my-bookmarks', 'URL endpoint for the "Bookmarks" page in user dashboard', 'my-listing' );
}

function my_listings_endpoint_slug() {
	return _x( 'my-listings', 'URL endpoint for the "My Listings" page in user dashboard', 'my-listing' );
}

function promotions_endpoint_slug() {
	return _x( 'promotions', 'URL endpoint for the "Promotions" page in user dashboard', 'my-listing' );
}

function get_basic_form_config_for_types( $types ) {
	if ( is_string( $types ) ) {
		$types = (array) explode( ',', $types );
	}

	$config = [];
	$listing_types = [];
	foreach ( (array) $types as $listing_type ) {
		if ( ! ( $type = \MyListing\Src\Listing_Type::get_by_name( trim( $listing_type ) ) ) ) {
			continue;
		}

		$filters = [];
		foreach ( (array) $type->get_basic_filters() as $filter ) {
			if ( $filter->is_ui() ) {
				continue;
			}

			$request_value = $filter->get_request_value();
			if ( is_array( $request_value ) ) {
				$filters += $request_value;
			} else {
				$filters[ $filter->get_form_key() ] = $filter->get_request_value();
			}
		}

		$listing_types[] = $type;
		$config[ $type->get_slug() ] = [
			'id' => $type->get_id(),
			'name' => $type->get_plural_name(),
			'icon' => $type->get_setting('icon'),
			'slug' => $type->get_slug(),
			'filters' => $filters,
		];
	}

	return [
		'types' => $listing_types,
		'config' => $config,
	];
}

function get_directions_link( $location ) {
	$query = $location['address'] ?? '';

	if ( apply_filters( 'mylisting/get-directions/use-latlng-query', false ) === true ) {
		if ( ! empty( $location['lat'] ) && ! empty( $location['lng'] ) ) {
			$query = join( ',', [ $location['lat'], $location['lng'] ] );
		}
	}

	if ( empty( $query ) ) {
		return '';
	}

	return sprintf( 'http://maps.google.com/maps?daddr=%s', urlencode( $query ) );
}

function is_rating_enabled( $listing_id ) {
	$listing = \MyListing\Src\Listing::get( $listing_id );
	if ( ! ( $listing && $listing->type ) ) {
		return false;
	}

	return $listing->type->is_rating_enabled();
}

function duplicate_listing( $listing_id ) {
	$listing = \MyListing\Src\Listing::get( $listing_id );
	if ( ! $listing ) {
		return null;
	}

	$new_post_id = wp_insert_post( [
		'post_title' => $listing->get_title(),
		'post_type' => 'job_listing',
		'post_status' => 'pending',
		'post_author' => $listing->get_author_id(),
		'post_content' => $listing->get_field('description'),
		'meta_input' => [
			'_case27_listing_type' => $listing->type->get_slug(),
		],
	] );

	$new_listing = \MyListing\Src\Listing::get( $new_post_id );
	$fields = $new_listing->get_fields();

	foreach ( $fields as $field ) {
		// duplicate related listing fields
		if ( $field->get_type() === 'related-listing' ) {
			// get the related listing field instance from the listing that is getting duplicated
			$old_field = $listing->get_field_object( $field->get_key() );

			// $field->update() works with data from $_POST, so we simulate a post request
			// and pass the related listing ids to it using `$old_field->get_value()`
			$_POST[ $field->get_key() ] = $old_field->get_value();
			$field->update();
		}

		// duplicate recurring date fields
		elseif ( $field->get_type() === 'recurring-date' ) {
			$old_field = $listing->get_field_object( $field->get_key() );
			\MyListing\Src\Recurring_Dates\update_field( $field, $old_field->get_value() );
		}

		// duplicate term fields
		elseif ( $field->get_type() === 'term-select' ) {
			$old_field = $listing->get_field_object( $field->get_key() );

			// returns an array of WP_Term objects
			$terms = $old_field->get_value();

			// generate an array of only term ids
			$term_ids = array_map( function( $term ) {
				return $term->term_id;
			}, $terms );

			// update new listing with these terms
			wp_set_object_terms( $new_listing->get_id(), $term_ids, $field->get_prop('taxonomy'), false );
		}

		// other fields
		else {
			$meta_value = get_post_meta( $listing->get_id(), '_'.$field->get_key(), true );
			update_post_meta( $new_listing->get_id(), '_'.$field->get_key(), maybe_unserialize( $meta_value ) );
		}
	}

	return $new_listing->get_id();
}

function get_script_tag( $pub_id ) {
	return sprintf(
		'<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=%s" crossorigin="anonymous"></script>',
		$pub_id
	);
}

function print_script_tag( $pub_id ) {
	static $printed;
	if ( is_null( $printed ) ) {
		$printed = true;
		echo get_script_tag( $pub_id );
	}
}

function get_tracks( $listing_id, $tracks = null ) {
    $listing = \MyListing\Src\Listing::get( $listing_id );
    if ( ! ( $listing && $listing->type ) ) {
        return [];
    }

    // allow to pass tracks as an argument if they were already retrieved from database in earlier query
    if ( is_null( $tracks ) ) {
		$tracks = (array) json_decode( get_post_meta( $listing->get_id(), '__track_stats', true ), ARRAY_A );
    }

	$changed = false;
    $layout = $listing->type->get_layout();
    $list = [];

    foreach ( $tracks as $action_key => $count ) {
		$action_type = substr( $action_key, 0, 4 ) === 'cta-' ? 'cover_actions' : 'quick_actions';
		$action_prefix = $action_type === 'cover_actions' ? 'cta' : 'qa';
		$actions = $layout[ $action_type ] ?? [];

		$found = false;
		foreach ( $actions as $action ) {
			$id = sprintf( '%s-%s', $action_prefix, substr( md5(
				json_encode( [ $action['action'], $action['label'] ] )
			), 0, 6 ) );

			if ( $id === $action_key ) {
				$list[ $id ] = [
					'name' => do_shortcode( $listing->compile_string( $action['label'] ) ),
					'count' => $count,
				];

				$found = true;
			}
		}

		if ( ! $found ) {
			unset( $tracks[ $action_key ] );
			$changed = true;
		}
    }

    // cleanup unused stats
    if ( $changed ) {
		delete_post_meta( $listing_id, '__track_stats' );
		update_post_meta( $listing->get_id(), '__track_stats', wp_json_encode( $tracks ) );
    }

    return $list;
}
