<?php

namespace MyListing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	/**
	 * List of script handles to defer.
	 *
	 * @since 2.1
	 */
	public $deferred_scripts = [
		'mylisting-messages',
		'google-platform-js',
		'recaptcha',

		// password strength
		'zxcvbn-async',
		'password-strength-meter',
		'wc-password-strength-meter',
	];

	/**
	 * Defer non-critical CSS.
	 *
	 * @link  https://web.dev/defer-non-critical-css/
	 * @since 2.2.3
	 */
	public $deferred_styles = [
		'wp-block-library',
		'wc-block-style',
	];

	public static function boot() {
		new self;
	}

	public function __construct() {
		// register scripts and styles
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'register_scripts' ] );

		// enqueue assets
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 30 );
		add_action( 'wp_head', [ $this, 'print_head_content' ], -1 );
		add_action( 'admin_head', [ $this, 'print_head_content' ], -1 );
		add_action( 'customize_controls_enqueue_scripts', [ $this, 'print_head_content' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'print_head_content' ] );

		// dynamic styles
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dynamic_styles' ], 1000 );
		add_action( 'wp_head', [ $this, 'print_element_queries' ], 1000 );
		add_action( 'acf/save_post', [ $this, 'maybe_generate_dynamic_styles' ], 120 );
		add_action( 'after_switch_theme', '\MyListing\generate_dynamic_styles', 20 );

		// defer scripts
		add_filter( 'script_loader_tag', [ $this, 'defer_scripts' ], 10, 2 );
		add_filter( 'style_loader_tag', [ $this, 'defer_styles' ], 10, 4 );

		// dequeue unnecessary scripts
		add_action( 'wp_print_scripts', [ $this, 'dequeue_scripts' ], 1000 );

		if ( apply_filters( 'mylisting/disable-wp-emoji', true ) !== false ) {
			add_action( 'init', function() {
				remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
				remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
				remove_action( 'wp_print_styles', 'print_emoji_styles' );
				remove_action( 'admin_print_styles', 'print_emoji_styles' );
				remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
				remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
				remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
				add_filter( 'tiny_mce_plugins', function( $plugins ) {
					return is_array( $plugins ) ? array_diff( $plugins, [ 'wpemoji' ] ) : [];
				} );
				add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
					if ( $relation_type === 'dns-prefetch' ) {
						$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/12.0.0-1/svg/' );
						$urls = array_diff( $urls, [ $emoji_svg_url ] );
					}
					return $urls;
				}, 10, 2 );
			} );
		}
	}

	public function register_scripts() {
		$suffix = is_rtl() ? '-rtl' : '';

		// frontend styles
		wp_register_style( 'mylisting-vendor', c27()->template_uri( 'assets/dist/vendor'.$suffix.'.css' ), [], CASE27_THEME_VERSION );
		wp_register_style( 'mylisting-frontend', c27()->template_uri( 'assets/dist/frontend'.$suffix.'.css' ), [], CASE27_THEME_VERSION );
		wp_register_style( 'mylisting-default-fonts', c27()->template_uri( 'assets/dist/default-fonts'.$suffix.'.css' ), [], CASE27_THEME_VERSION );

		// frontend dashboard
        wp_register_style( 'mylisting-dashboard', c27()->template_uri( 'assets/dist/dashboard'.$suffix.'.css' ), [], CASE27_THEME_VERSION );
        wp_register_script( 'mylisting-dashboard', c27()->template_uri( 'assets/dist/dashboard.js' ), ['jquery'], CASE27_THEME_VERSION, true );

        // admin styles
        wp_register_style( 'mylisting-admin-general', c27()->template_uri( 'assets/dist/admin/admin'.$suffix.'.css' ), [], CASE27_THEME_VERSION );

        // backend dashboard
        wp_register_style( 'mylisting-admin-dashboard', c27()->template_uri( 'assets/dist/admin/dashboard'.$suffix.'.css' ), [], CASE27_THEME_VERSION );

        // backend shortcodes page
        wp_register_style( 'mylisting-admin-shortcodes', c27()->template_uri( 'assets/dist/admin/shortcodes'.$suffix.'.css' ), [], CASE27_THEME_VERSION );
        wp_register_script( 'mylisting-admin-shortcodes', c27()->template_uri( 'assets/dist/admin/shortcodes.js' ), [], CASE27_THEME_VERSION, true );

        // frontend add listing
		wp_register_style( 'mylisting-add-listing', c27()->template_uri( 'assets/dist/add-listing'.$suffix.'.css' ), [], CASE27_THEME_VERSION );
		wp_register_script( 'mylisting-listing-form', c27()->template_uri( 'assets/dist/add-listing.js' ), ['c27-main'], CASE27_THEME_VERSION, true );

		// backend add listing
		wp_register_style( 'mylisting-admin-form', c27()->template_uri( 'assets/dist/add-listing-admin'.$suffix.'.css' ), [], CASE27_THEME_VERSION );
		wp_register_script( 'mylisting-admin-form', c27()->template_uri( 'assets/dist/add-listing-admin.js' ), ['jquery'], CASE27_THEME_VERSION, true );

		// frontend single listing
		wp_register_script( 'mylisting-single', c27()->template_uri( 'assets/dist/single-listing.js' ), ['c27-main'], CASE27_THEME_VERSION, true );

		// authentication scripts (login, register, social-login)
		wp_register_script( 'mylisting-auth', c27()->template_uri( 'assets/dist/auth.js' ), ['c27-main'], CASE27_THEME_VERSION, true );

		// elementor editor scripts
		wp_register_script( 'mylisting-elementor', c27()->template_uri( 'assets/dist/elementor.js' ), ['jquery'], CASE27_THEME_VERSION, true );

		// theme onboarding scripts
		wp_register_script( 'mylisting-admin-onboarding', c27()->template_uri( 'assets/dist/admin/onboarding.js' ), ['jquery', 'vuejs'], CASE27_THEME_VERSION, true );

		// admin user-roles scripts
		wp_register_script( 'mylisting-admin-user-roles', c27()->template_uri( 'assets/dist/admin/user-roles.js' ), ['jquery', 'vuejs'], CASE27_THEME_VERSION, true );

		// admin type editor
        wp_register_script( 'mylisting-admin-type-editor', c27()->template_uri( 'assets/dist/admin/type-editor.js' ), ['jsoneditor', 'theme-script-vendor', 'theme-script-main'], CASE27_THEME_VERSION, true );

        // explore page
        wp_register_script( 'mylisting-explore', c27()->template_uri( 'assets/dist/explore.js' ), ['c27-main'], CASE27_THEME_VERSION, true );

        // vuejs
        wp_register_script( 'vuejs', c27()->template_uri( 'assets/vendor/vuejs/'.( CASE27_ENV === 'dev' ? 'vue.js' : 'vue.min.js' ) ), [], '2.6.11', true );

		// jsoneditor
        wp_register_script( 'jsoneditor', c27()->template_uri( 'assets/vendor/jsoneditor/jsoneditor.js' ), [], '5.13.2', true );
        wp_register_style( 'jsoneditor', c27()->template_uri( 'assets/vendor/jsoneditor/jsoneditor.css' ), [], '5.13.2' );

        // sortablejs and vue-draggable wrapper
        wp_register_script( 'sortablejs', c27()->template_uri( 'assets/vendor/sortable/sortable.js' ), [], '1.9.0', true );
        wp_register_script( 'vue-draggable', c27()->template_uri( 'assets/vendor/sortable/vue-draggable.js' ), [], '1.1.0', true );


        // custom taxonomies
        wp_register_script( 'mylisting-admin-custom-taxonomies', c27()->template_uri( 'assets/dist/admin/custom-taxonomies.js' ), ['wp-util'], CASE27_THEME_VERSION, true );

        // icons
		wp_register_style( 'mylisting-material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons' );
		wp_register_style( 'mylisting-icons', c27()->template_uri( 'assets/dist/icons'.$suffix.'.css' ), [], CASE27_THEME_VERSION );

		// recaptcha
		wp_register_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js?onload=cts_render_captcha&render=explicit', [], false, true );

		/**
		 * Select2 - first use wp_deregister_script to unset select2 loaded
		 * by other plugins, then register it again to use the latest version.
		 */
		wp_deregister_script( 'select2' );
        wp_register_script( 'select2', c27()->template_uri( 'assets/vendor/select2/select2.js' ), ['jquery'], '4.0.13', true );
        wp_register_style( 'select2', c27()->template_uri( 'assets/vendor/select2/select2.css' ), [], '4.0.13' );

        // add listing image upload
    	wp_register_script( 'jquery-iframe-transport', c27()->template_uri( 'assets/vendor/jquery-fileupload/jquery.iframe-transport.js' ), [ 'jquery' ], '1.8.3', true );
		wp_register_script( 'jquery-fileupload', c27()->template_uri( 'assets/vendor/jquery-fileupload/jquery.fileupload.js' ), [ 'jquery', 'jquery-iframe-transport', 'jquery-ui-widget' ], '9.11.2', true );
		wp_register_script( 'mylisting-ajax-file-upload', c27()->template_uri( 'assets/vendor/jquery-fileupload/ajax-file-upload.js' ), [ 'jquery', 'jquery-fileupload' ], CASE27_THEME_VERSION, true );

        // editor styles
	    $this->add_editor_style( c27()->template_uri( sprintf( 'assets/dist/editor-styles.css?ver=%s', CASE27_THEME_VERSION ) ) );

	    // load helper js scripts on dev environemnt only
	    if ( CASE27_ENV === 'dev' ) {
        	wp_enqueue_script( 'mylisting-dev', c27()->template_uri( 'assets/dist/dev.js' ), [], CASE27_THEME_VERSION, true );
	    }
	}

    /**
     * Load WP Editor custom styles.
     *
     * @since 1.0
     */
	public function add_editor_style( $stylesheet ) {
		// for backend editors
		if ( is_admin() ) {
			return add_editor_style( $stylesheet );
		}

	    global $editor_styles;
	    $stylesheet = (array) $stylesheet;
	    if ( is_rtl() ) {
	        $stylesheet[] = str_replace( '.css', '-rtl.css', $stylesheet[0] );
	    }

	    $editor_styles = array_merge( (array) $editor_styles, $stylesheet );
	}

	/**
     * Enqueue theme scripts.
     *
     * @since 1.0.0
	 */
	public function enqueue_scripts() {
		global $wp_query;

		// icons
		wp_enqueue_style( 'mylisting-icons' );
		wp_enqueue_style( 'mylisting-material-icons' );

		// sortable
		wp_enqueue_script( 'jquery-ui-sortable' );

		// moment
		wp_enqueue_script( 'moment' );
		$this->load_moment_locale();

		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'select2' );

		$dependencies = [
			'jquery',
			'moment',
			'select2',
			'vuejs',
			'jquery-ui-slider',
		];

		// Frontend scripts.
		wp_enqueue_script( 'mylisting-vendor', c27()->template_uri( 'assets/dist/vendor.js' ), $dependencies, CASE27_THEME_VERSION, true );
		wp_enqueue_script( 'c27-main', c27()->template_uri( 'assets/dist/frontend.js' ), ['mylisting-vendor'], CASE27_THEME_VERSION, true );

		// Comment reply script
		if ( is_singular() && comments_open() && get_option('thread_comments') ) {
			wp_enqueue_script( 'comment-reply' );
		}

		if ( is_singular( 'job_listing' ) ) {
			wp_enqueue_script( 'mylisting-single' );
		}

		// Custom JavaScript
		wp_add_inline_script( 'c27-main', c27()->get_setting('custom_js') );

		// frontend styles
		wp_enqueue_style( 'mylisting-vendor' );
		wp_enqueue_style( 'mylisting-frontend' );

		// theme style.css
		wp_enqueue_style( 'theme-styles-default', c27()->template_uri( 'style.css' ) );

		if ( apply_filters( 'mylisting/assets/load-default-font', true ) !== false ) {
			wp_enqueue_style( 'mylisting-default-fonts' );
		}
	}

	/**
	 * Enqueue dynamic styles.
	 *
	 * @since 2.0
	 */
	public function enqueue_dynamic_styles() {
		$upload_dir = wp_get_upload_dir();
		if ( ! is_array( $upload_dir ) || empty( $upload_dir['basedir'] ) || empty( $upload_dir['baseurl'] ) ) {
			return;
		}

		// if file does not exist, generate it
		if ( ! file_exists( trailingslashit( $upload_dir['basedir'] ) . 'mylisting-dynamic-styles.css' ) ) {
			\MyListing\generate_dynamic_styles();
		}

		wp_enqueue_style(
			'mylisting-dynamic-styles',
			esc_url( trailingslashit( $upload_dir['baseurl'] ) . 'mylisting-dynamic-styles.css' ), [],
			filemtime( trailingslashit( $upload_dir['basedir'] ) . 'mylisting-dynamic-styles.css' )
		);
	}

	public function print_element_queries() {
		$suffix = is_rtl() ? '-rtl' : '';
		printf(
			'<style type="text/css" id="mylisting-element-queries">%s</style>',
			preg_replace( '/\s+/S', ' ', file_get_contents( locate_template( 'assets/dynamic/element-queries'.$suffix.'.css' ) ) )
		);
	}

	public function maybe_generate_dynamic_styles() {
		if ( is_admin() && ! empty( $_GET['page'] ) && $_GET['page'] === 'theme-general-settings' ) {
			\MyListing\generate_dynamic_styles();
		}
	}

	public function load_moment_locale() {
		$locales = [
			'af', 'ar-dz', 'ar-kw', 'ar-ly', 'ar-ma', 'ar-sa', 'ar-tn', 'ar', 'az', 'be', 'bg', 'bm', 'bn', 'bo', 'br', 'bs', 'ca', 'cs', 'cv', 'cy',
			'da', 'de-at', 'de-ch', 'de', 'dv', 'el', 'en-au', 'en-ca', 'en-gb', 'en-ie', 'en-il', 'en-nz', 'eo', 'es-do', 'es-us', 'es', 'et', 'eu',
			'fa', 'fi', 'fo', 'fr-ca', 'fr-ch', 'fr', 'fy', 'gd', 'gl', 'gom-latn', 'gu', 'he', 'hi', 'hr', 'hu', 'hy-am', 'id', 'is', 'it', 'ja', 'jv',
			'ka', 'kk', 'km', 'kn', 'ko', 'ky', 'lb', 'lo', 'lt', 'lv', 'me', 'mi', 'mk', 'ml', 'mr', 'ms-my', 'ms', 'mt', 'my', 'nb', 'ne', 'nl-be',
			'nl', 'nn', 'pa-in', 'pl', 'pt-br', 'pt', 'ro', 'ru', 'sd', 'se', 'si', 'sk', 'sl', 'sq', 'sr-cyrl', 'sr', 'ss', 'sv', 'sw', 'ta', 'te',
			'tet', 'tg', 'th', 'tl-ph', 'tlh', 'tr', 'tzl', 'tzm-latn', 'tzm', 'ug-cn', 'uk', 'ur', 'uz-latn', 'uz', 'vi', 'x-pseudo', 'yo', 'zh-cn', 'zh-hk', 'zh-tw'
		];

		$load_locale = false;
		$locale = str_replace( '_', '-', strtolower( get_locale() ) );

		if ( in_array( $locale, $locales ) ) {
			$load_locale = $locale;
		} elseif ( strpos( $locale, '-') !== false ) {
			$locale = explode( '-', $locale );
			if ( in_array( $locale[0], $locales ) ) {
				$load_locale = $locale[0];
			}
		}

		if ( $load_locale ) {
			wp_enqueue_script( 'moment-locale-' . $load_locale, sprintf( 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/locale/%s.min.js', $load_locale ), ['moment'], '1.0', true );
			wp_add_inline_script( 'moment-locale-' . $load_locale, sprintf( 'window.MyListing_Moment_Locale = \'%s\';', $load_locale ) );
		}
	}

	/**
	 * Print content within the site <head></head>.
	 *
	 * @since 1.7.2
	 */
	public function print_head_content() {
		// MyListing object.
		$data = apply_filters( 'mylisting/localize-data', [
			'Helpers' => new \stdClass,
			'Handlers' => new \stdClass,
		] );

		foreach ( (array) $data as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$data[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		}

		printf( '<script type="text/javascript">var MyListing = %s;</script>', wp_json_encode( (object) $data ) );

		// CASE27 object.
		$case27 = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'login_url' => \MyListing\get_login_url(),
			'register_url' => \MyListing\get_register_url(),
			'mylisting_ajax_url' => \MyListing\Ajax::get_endpoint(),
			'env' => CASE27_ENV === 'dev' ? 'dev' : 'production',
			'ajax_nonce' => wp_create_nonce('c27_ajax_nonce'),
			'l10n' => [
				'selectOption' => _x( 'Select an option', 'Dropdown placeholder', 'my-listing' ),
				'errorLoading' => _x( 'The results could not be loaded.', 'Dropdown could not load results', 'my-listing' ),
				'loadingMore'  => _x( 'Loading more results…', 'Dropdown loading more results', 'my-listing' ),
				'noResults'    => _x( 'No results found', 'Dropdown no results found', 'my-listing' ),
				'searching'    => _x( 'Searching…', 'Dropdown searching', 'my-listing' ),
				'datepicker'   => mylisting()->strings()->get_datepicker_locale(),
				'irreversible_action' => _x( 'This is an irreversible action. Proceed anyway?', 'Alerts: irreversible action', 'my-listing' ),
				'delete_listing_confirm' => _x( 'Are you sure you want to delete this listing?', 'Alerts: delete listing', 'my-listing' ),
				'copied_to_clipboard' => _x( 'Copied!', 'Alerts: Copied to clipboard', 'my-listing' ),
				'nearby_listings_location_required' => _x( 'Enter a location to find nearby listings.', 'Nearby listings dialog', 'my-listing' ),
				'nearby_listings_retrieving_location' => _x( 'Retrieving location...', 'Nearby listings dialog', 'my-listing' ),
				'nearby_listings_searching' => _x( 'Searching for nearby listings...', 'Nearby listings dialog', 'my-listing' ),
				'geolocation_failed' => _x( 'You must enable location to use this feature.', 'Explore map', 'my-listing' ),
				'something_went_wrong' => __( 'Something went wrong.', 'my-listing' ),
				'all_in_category' => _x( 'All in "%s"', 'Category dropdown', 'my-listing' ),
				'invalid_file_type' => _x( 'Invalid file type. Accepted types:', 'Add listing form', 'my-listing' ),
				'file_limit_exceeded' => _x( 'You have exceeded the file upload limit (%d).', 'Add listing form', 'my-listing' ),
			],
			'woocommerce' => [],
		];

		ob_start();
		mylisting_locate_template( 'templates/add-listing/form-fields/uploaded-file-html.php', [ 'name' => '', 'value' => '', 'extension' => 'jpg' ] );
		$case27['js_field_html_img'] = esc_js( str_replace( "\n", '', ob_get_clean() ) );

		ob_start();
		mylisting_locate_template( 'templates/add-listing/form-fields/uploaded-file-html.php', [ 'name' => '', 'value' => '', 'extension' => 'zip' ] );
		$case27['js_field_html'] = esc_js( str_replace( "\n", '', ob_get_clean() ) );

		if ( is_admin() ) {
			$case27['map_skins'] = c27()->get_map_skins();
		}

		foreach ( (array) $case27 as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$case27[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		}

		printf( '<script type="text/javascript">var CASE27 = %s;</script>', wp_json_encode( (object) $case27 ) );
	}

	/**
	 * Defer some of the theme scripts.
	 *
	 * @since 2.1
	 */
	public function defer_scripts( $tag, $handle ) {
		if ( in_array( $handle, $this->deferred_scripts ) ) {
			return str_replace( '<script ', '<script async defer ', $tag );
		}

		return $tag;
	}

	/**
	 * Defer non-critical CSS.
	 *
	 * @link  https://web.dev/defer-non-critical-css/
	 * @since 2.2.3
	 */
	public function defer_styles( $tag, $handle, $href, $media ) {
		if ( in_array( $handle, $this->deferred_styles ) ) {
			return str_replace( "rel='stylesheet'", "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $tag );
		}

		return $tag;
	}

	public function dequeue_scripts() {
		// disable woocommerce pretty photo plugin
		wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
		wp_dequeue_script( 'prettyPhoto' );
		wp_dequeue_script( 'prettyPhoto-init' );
		wp_dequeue_script( 'photoswipe' );
		wp_dequeue_script( 'photoswipe-ui-default' );

		// disable password strength scripts (only needed on /my-account page)
		wp_dequeue_script( 'wc-password-strength-meter' );
	}
}
