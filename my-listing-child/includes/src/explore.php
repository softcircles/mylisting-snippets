<?php

namespace MyListing\Src;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Explore {
	public
		$active_listing_type = false,
		$data,
		$types,
		$taxonomies;

	public static
		$custom_taxonomies,
		$explore_page;

	public static function boot() {
		self::$custom_taxonomies = mylisting_custom_taxonomies();

		add_action( 'init', [ __CLASS__, 'add_rewrite_rules' ], 5 );
		add_action( 'template_redirect', [ __CLASS__, 'handle_single_term_page' ], 35 );
	}

	public function __construct( $data ) {
		$this->data = $data;
		$this->types = array_filter( array_map( function( $listing_type ) {
			return \MyListing\Src\Listing_Type::get_by_name( $listing_type );
		}, array_column( (array) $this->data['listing_types'], 'type' ) ) );

		$this->get_active_listing_type();
		$this->get_taxonomies();
	}

	public function get_types_config() {
		$config = [];
		$index = 0;

		// preserve page initially for the active listing type, so that the 'pg' url param has effect
		$pg = ! empty( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1;

		foreach ( (array) $this->types as $type ) {
			$tabs = $type->get_explore_tabs();
			$fields = $type->get_fields();
			$allowed_tabs = ['search-form'];

			// taxonomy tabs config
			$taxonomies = $this->taxonomies;
			foreach ( $taxonomies as $key => $tax ) {
				// determine active term, validate if it belongs to this listing type
				if ( $tax['activeTermId'] && ( $term = \MyListing\Src\Term::get( $tax['activeTermId'] ) ) ) {
			        $term_types = $term->get_listing_types();

			        if ( ! empty( $term_types ) && ! in_array( $type->get_id(), $term_types ) ) {
			        	// term doesn't belong to this listing type, unset it
			            $taxonomies[ $key ]['activeTermId'] = 0;
			        }
				}

				// only show terms from a taxonomy if that taxonomy is used as an explore tab for this listing type,
				// or as an add listing field for this listing type.
				if ( in_array( $tax['tab_id'], array_keys( $tabs ) ) || in_array( $tax['field_name'], array_keys( $fields ) ) ) {
					$allowed_tabs[] = $tax['tab_id'];
				}
			}

			// determine active tab
			// First check if the tab is available as a query var. This has the highest priority.
			if ( in_array( get_query_var( 'explore_tab' ), $allowed_tabs ) ) {
				$active_tab = get_query_var( 'explore_tab' );
			}
			// Then check if the tab is provided as a GET param.
			elseif ( ! empty( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $tabs ) ) ) {
				$active_tab = sanitize_text_field( $_GET['tab'] );
			}
			// Otherwise, default to the first tab for each listing type.
			else {
				$active_tab = ! empty( $tabs ) ? reset( $tabs )['type'] : 'search-form';
			}

			$filters = [
				'page' => ( $pg >= 1 ? $pg - 1 : 0 ),
				'preserve_page' => $pg > 1,
			];

			foreach ( (array) $type->get_advanced_filters() as $filter ) {
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

			$config[ $type->get_slug() ] = [
				'index' => $index,
				'name' => $type->get_plural_name(),
				'icon' => $type->get_setting('icon'),
				'slug' => $type->get_slug(),
				'id' => $type->get_id(),
				'tabs' => $tabs,
				'filters' => $filters,
				'tab' => $active_tab,
				'defaultTab' => ! empty( $tabs ) ? reset( $tabs )['type'] : 'search-form',
				'taxonomies' => $taxonomies,
				'max_num_pages' => 0,
				'is_first_load' => true,
			];

			$index++;
		}

		return $config;
	}

	public function get_active_mobile_tab() {
		$tabs = ['f' => 'filters', 'r' => 'results', 'm' => 'map'];
		if ( ! empty( $_GET['mt'] ) && isset( $tabs[ $_GET['mt'] ] ) ) {
			return $tabs[ $_GET['mt'] ];
		}
		return 'results';
	}

	public function get_active_listing_type() {
		if ( empty( $this->types ) ) {
			$this->active_listing_type = false;
			return false;
		}

		$this->active_listing_type = $this->types[0];

		if ( isset( $_GET['type'] ) && ( $getType = sanitize_text_field( $_GET['type'] ) ) ) {
			foreach ( $this->types as $listing_type ) {
				if ( $listing_type->get_slug() == $getType ) {
					$this->active_listing_type = $listing_type;
					break;
				}
			}
		}
	}

	public function get_taxonomies() {
		$taxonomies = array_merge( [ 'categories', 'regions', 'tags' ], array_keys( self::$custom_taxonomies ) );
		$base_taxonomies = [
			'categories' => [ 'tax' => 'job_listing_category', 'query_var' => 'explore_category', 'field' => 'job_category' ],
			'regions' => [ 'tax' => 'region', 'query_var' => 'explore_region', 'field' => 'region' ],
			'tags' => [ 'tax' => 'case27_job_listing_tags', 'query_var' => 'explore_tag', 'field' => 'job_tags' ],
		];

		foreach ( $taxonomies as $taxonomy ) {
			// get the query var for this taxonomy
			$query_var = isset( $base_taxonomies[ $taxonomy ] ) ? $base_taxonomies[ $taxonomy ]['query_var'] : 'explore_'.$taxonomy;

			// get the actual taxonomy name (for base taxonomies, it's different from the tab id, e.g. categories -> job_listing_category)
			$tax = isset( $base_taxonomies[ $taxonomy ] ) ? $base_taxonomies[ $taxonomy ]['tax'] : $taxonomy;
			$field = isset( $base_taxonomies[ $taxonomy ] ) ? $base_taxonomies[ $taxonomy ]['field'] : $taxonomy;

			// get the active term if available
			$active_term_config = false;
			$active_term_id = 0;
			if ( get_query_var( $query_var ) && ( $term = get_term_by( 'slug', sanitize_title( get_query_var( $query_var ) ), $tax ) ) ) {
				$active_term = \MyListing\Src\Term::get( $term );
				$active_term_id = $active_term->get_id();

				// @todo: refactor, prevent duplicate code with explore-terms-endpoint.php
				$background = $active_term->get_image();
				$active_term_config = [
					'term_id' => $active_term->get_id(),
					'name' => $active_term->get_name(),
					'description' => $active_term->get_description(),
					'slug' => $active_term->get_slug(),
					'link' => $active_term->get_link(),
					'parent' => $active_term->get_parent_id(),
					'count' => $active_term->get_count(),
					'single_icon' => $active_term->get_icon( [ 'background' => false, 'color' => true ] ),
					'color' => $active_term->get_color(),
					'icon' => $active_term->get_icon( [ 'background' => false, 'color' => false ] ),
					'background' => is_array( $background ) && ! empty( $background ) ? $background['sizes']['large'] : false,
					'listing_types' => array_filter( array_map( 'absint', (array) get_term_meta( $active_term->get_id(), 'listing_type', true ) ) ),
				];

				// if we're loading a term explore page, then determine the best listing type to load based on the term's taxonomy.
				$this->set_active_type_by_taxonomy( [ 'taxonomy' => $tax, 'tab_id' => $taxonomy, 'field_name' => $field ], $active_term );
			}

			$pg = ! empty( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1;
			$this->taxonomies[ $taxonomy ] = [
	        	'tax' => $tax,
	        	'field_name' => $field,
	        	'tab_id' => $taxonomy,
	        	'page' => ( $pg - 1 ),
	        	'termsLoading' => false,
	        	'termsPage' => 0,
	        	'activeTermId' => $active_term_id,
	        	'activeTerm' => $active_term_config,
	        	'hasMore' => false,
	        	'terms' => new \stdClass,
			];
		}
	}

	/**
	 * If we're loading a term explore page, then determine the best listing type
	 * to load based on the term's taxonomy.
	 *
	 * @since 2.1
	 */
	public function set_active_type_by_taxonomy( $tax, $term ) {
		$active_term_types = $term->get_listing_types();

		foreach ( (array) $this->types as $type ) {
			$tabs = $type->get_explore_tabs();
			$fields = $type->get_fields();

			// if there's an active term, make sure it only loads a listing type
			// that it's been assigned to, if any.
			if ( ! empty( $active_term_types ) ) {
				if ( ! in_array( $type->get_id(), $active_term_types )  ) {
					continue;
				}

				mlog()->note( 'Found listing type in active term\'s allowed types. Setting as active.' );
				$this->active_listing_type = $type;
				break;
			}

			// check if there's a listing type that's using a tab for this taxonomy.
			// if there is, set the first occurrence as the active listing type
			if ( in_array( $tax['tab_id'], array_keys( $tabs ) ) ) {
				mlog()->note( 'Found listing type with a '.$tax['tab_id'].' tab available. Setting as active type.' );
				$this->active_listing_type = $type;
				break;
			}

			// if above check fails, then search for listing types that at least use this taxonomy in their add listing page
			if ( in_array( $tax['field_name'], array_keys( $fields ) ) ) {
				mlog()->note( 'Found listing type that uses the '.$tax['field_name'].' taxonomy field. Setting as active type.' );
				$this->active_listing_type = $type;
				break;
			}

			// if both check fails, the requested term's taxonomy isn't being used anywhere, so it's an invalid request.
		}
	}

	/**
	 * Add rewrite rules for pretty url-s in Explore page.
	 * e.g. site/explore/category/category-name
	 * 		site/explore/regions/region-name
	 * 		site/explore/tags/tag-name
	 */
	public static function add_rewrite_rules() {
		// Stack overflow link: https://wordpress.stackexchange.com/questions/89164/passing-parameters-to-a-custom-page-template-using-clean-urls
		if ( ! ( $explore_page_id = get_option( 'options_general_explore_listings_page', false ) ) ) {
			return;
		}

		if ( ! ( $explore_page = get_post( $explore_page_id ) ) ) {
			return;
		}

		self::$explore_page = $explore_page;

		// Add query vars.
		global $wp;
	    $wp->add_query_var( 'explore_tab' );

    	$bases = \MyListing\Src\Permalinks::get_permalink_structure();

		// default taxonomies
		self::_rewrite_listing_taxonomy( 'category', $bases['category_base'], 'categories' );
		self::_rewrite_listing_taxonomy( 'region', $bases['region_base'], 'regions' );
		self::_rewrite_listing_taxonomy( 'tag', $bases['tag_base'], 'tags' );

		// custom taxonomies
		foreach ( self::$custom_taxonomies as $slug => $label ) {
			self::_rewrite_listing_taxonomy( $slug, $slug );
		}
	}

	private static function _rewrite_listing_taxonomy( $taxonomy, $base, $explore_tab = null ) {
		if ( $explore_tab === null ) {
			$explore_tab = $taxonomy;
		}

		// rewrite tag
    	add_rewrite_tag( '%explore_'.$taxonomy.'%', '([^/]+)' );

    	// rewrite rule
	    add_rewrite_rule(
	    	sprintf( '^%s/([^/]+)?', $base ),
	    	sprintf( 'index.php?page_id=%d&explore_tab=%s&explore_%s=$matches[1]', self::$explore_page->ID, $explore_tab, $taxonomy ),
	    	'top'
	    );
	}

	/**
	 * Since taxonomy archive pages are redirected to the Explore page, filter page
	 * meta data to show term title/description/details instead of Explore page details.
	 *
	 * @since 2.1
	 */
	public static function handle_single_term_page() {
	    $taxonomies = [
	        [ 'taxonomy' => 'job_listing_category', 'query_var' => 'explore_category', 'name_filter' => 'single_cat_title' ],
	        [ 'taxonomy' => 'case27_job_listing_tags', 'query_var' => 'explore_tag', 'name_filter' => 'single_tag_title' ],
	        [ 'taxonomy' => 'region', 'query_var' => 'explore_region' ],
	    ];

	    foreach( mylisting_custom_taxonomies() as $key => $label ) {
	    	$taxonomies[] = [
	    		'taxonomy' => $key,
	    		'query_var' => 'explore_'.$key,
	    	];
	    }

	    foreach ( $taxonomies as $tax ) {
	    	if ( ! get_query_var( $tax['query_var'] ) ) {
	    		continue;
	    	}

	    	$term = \MyListing\Src\Term::get_by_slug( get_query_var( $tax['query_var'] ), $tax['taxonomy'] );
	    	if ( ! $term ) {
	    		continue;
	    	}

	    	/* we're on single listing term page */
	    	$image = $term->get_image();
    		$page_title = apply_filters( isset( $tax['name_filter'] ) ? $tax['name_filter'] : 'single_term_title', $term->get_name() );
            $page_title .= ' ' . apply_filters( 'document_title_separator', '-' ) . ' ';
            $page_title .= get_bloginfo( 'name', 'display' );
			$page_title = capital_P_dangit( esc_html( convert_chars( wptexturize( $page_title ) ) ) );

	    	$cfg = new \stdClass;
	    	$cfg->title = $page_title;
	    	$cfg->link = $term->get_link();
	    	$cfg->image = is_array( $image ) && ! empty( $image ) ? $image['sizes']['medium_large'] : false;
	    	$cfg->is_yoast = defined( 'WPSEO_VERSION' );
	    	$cfg->is_seopress = defined( 'SEOPRESS_VERSION' );
			$cfg->description = $term->get_description();

			if ( $cfg->is_yoast ) {
				$meta = get_option( 'wpseo_taxonomy_meta' );
				$term_object = get_term( $term->get_id(), $tax['taxonomy'] );
				$tax_slug = $term_object->taxonomy;
				$term_id = $term_object->term_id;
				$replacer = new \WPSEO_Replace_Vars();

				if ( isset( $meta[ $tax_slug ][ $term_id ]['wpseo_title'] ) ) {
					$cfg->title = $replacer->replace(
						$meta[ $tax_slug ][ $term_id ]['wpseo_title'],
						$term_object
					);
	            } else {
					$cfg->title = $replacer->replace(
						\WPSEO_Options::get('title-tax-'.$tax_slug, ''),
						$term_object
					);
	            }

	    		if ( isset( $meta[ $tax_slug ][ $term_id ]['wpseo_desc'] ) ) {
					$cfg->description = $replacer->replace(
						$meta[ $tax_slug ][ $term_id ]['wpseo_desc'],
						$term_object
					);
	            } elseif( empty( $cfg->description ) ) {
					$cfg->description = $replacer->replace(
						\WPSEO_Options::get('metadesc-tax-'.$tax_slug, ''),
						$term_object
					);
	            }
			}

			if ( empty( $cfg->title ) ) {
				$cfg->title = $page_title;
			}

			if ( empty( $cfg->description ) ) {
	    		$cfg->description = sprintf(
	    			__( 'Browse listings in %s | %s', 'my-listing' ),
	    			$term->get_name(),
	    			get_bloginfo('description')
	    		);
	    	}

	    	/**
	    	 * Handle redirects for custom landing pages.
	    	 * @since 1.7
	    	 */
	    	$redirect_page = get_term_meta( $term->get_id(), '_landing_page', true );
	    	if ( $redirect_page && is_numeric( $redirect_page ) && ( $redirect_url = get_permalink( absint( $redirect_page ) ) ) ) {
	            wp_redirect( $redirect_url );
	            exit;
	        }

	    	/**
	    	 * Filter the page title and meta tags.
	    	 * @since 1.6.2
	    	 */
			add_filter( 'pre_get_document_title', function() use ( $cfg ) { return $cfg->title; }, 10e3 );
			add_action( 'wp_head', function() use ( $cfg ) {
				// title meta tag is also output by Yoast, so only add it if Yoast isn't active
				if ( ! $cfg->is_yoast && ! $cfg->is_seopress ) {
					printf( '<meta property="og:title" content="%s"/>'."\n", esc_attr( $cfg->title ) );
				}

				// description tags are also output by Yoast, so only add if Yoast isn't active
				if ( $cfg->description && ! $cfg->is_yoast && ! $cfg->is_seopress ) {
					printf( '<meta name="description" content="%s"/>'."\n", esc_attr( $cfg->description ) );
					printf( '<meta property="og:description" content="%s"/>'."\n", esc_attr( $cfg->description ) );
				}

				// output term image meta tag
				if ( $cfg->image && ! $cfg->is_yoast && ! $cfg->is_seopress ) {
					printf( '<meta property="og:image" content="%s"/>'."\n", esc_attr( $cfg->image ) );
				}
			}, 1 );

			/**
			 * Avoid page being marked as duplicate content by search engines b\w explore page
			 * and single term pages, by adding unique canonical url's for every term.
	    	 * @since 2.1
	    	 */
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			add_filter( 'get_canonical_url', function() use ( $cfg ) { return $cfg->link; } );

			// Yoast
			add_filter( 'wpseo_title', function() use ( $cfg ) { return $cfg->title; }, 10e3 );
			add_filter( 'wpseo_opengraph_url', function() use ( $cfg ) { return $cfg->link; }, 10e3 );
			add_filter( 'wpseo_metadesc', function() use ( $cfg ) { return $cfg->description; } );
			add_filter( 'wpseo_opengraph_desc', function() use ( $cfg ) { return $cfg->description; } );
			add_filter( 'wpseo_canonical', function() use ( $cfg ) { return $cfg->link; } );
			if ( $cfg->is_yoast && $cfg->image ) {
				add_filter( 'wpseo_opengraph_image', function( $image ) use ( $cfg ) { return $cfg->image; }, 10e5, 1 );
			}

			// SEOpress
			add_filter( 'seopress_titles_title', function() use ( $cfg ) { return $cfg->title; }, 10e3 );
			add_filter( 'seopress_social_og_url', function() use ( $cfg ) {
				$html = '<meta property="og:url" content="'. esc_url( $cfg->link ) .'" />"'; 
				return $html;
			}, 10e3 );
			add_filter( 'seopress_titles_desc', function() use ( $cfg ) { return $cfg->description; } );
			add_filter( 'seopress_social_og_desc', function() use ( $cfg ) {
				$html = '<meta property="og:description" content="'.$cfg->description.'" />'; 
				return $html;
			} );
			add_filter( 'seopress_titles_canonical', function() use ( $cfg ) {
				$html = '<link rel="canonical" href="'.htmlspecialchars(urldecode($cfg->link)).'" />'; 
				return $html;
			} );
			if ( $cfg->is_seopress && $cfg->image ) {
				add_filter( 'seopress_social_og_thumb', function( $html ) use ( $cfg ) {
					$html = '<meta property="og:image" content="'.esc_url($cfg->image).'" />'; 
					return $html;
				}, 10e5, 1 );
			}

	    	return;
	    }
	}
}
