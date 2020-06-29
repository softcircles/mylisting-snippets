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

			if ( is_array( $value ) ) {
				$value = join( ', ', $value );
			}
		} else {
			$value = '';
		}

		// if any of the used fields are empty, return false
		if ( empty( $value ) && $require_all_fields ) {
			return false;
		}

		// escape square brackets so any shortcode added by the listing owner won't be run
		$value = str_replace( [ "[" , "]" ] , [ "&#91;" , "&#93;" ] , $value );

		if ( ! in_array( $slug, $allow_markup, true ) ) {
			$value = wp_kses_post( $value );
		}

		// replace the field bracket with it's value
		$string = str_replace( "[[$slug]]", $value, $string );
	}

	// Preserve line breaks.
	return $string;
}
