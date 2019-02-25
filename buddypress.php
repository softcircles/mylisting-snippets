<?php

namespace CASE27\Integrations\BuddyPress;

class BuddyPress {
	use \MyListing\Src\Traits\Instantiatable;

	protected $bp;

	public function __construct() {
		if ( ! $this->requirements() ) {
			return false;
		}

		$this->bp = buddypress();

		add_action( 'bp_setup_nav', [ $this, 'add_listings_page' ] );
		add_action( 'template_redirect', [ $this, 'redirect_author_page' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'case27/user-menu/dashboard/before', array( $this, 'user_menu_items' ) );
	}

	public function requirements() {
		if ( ! function_exists( 'buddypress' ) ) {
			return false;
		}

		return true;
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'c27-buddypress-style', c27()->template_uri( 'includes/integrations/buddypress/assets/styles/buddypress.css' ), [], CASE27_THEME_VERSION );
	}

	public function add_listings_page() {
		bp_core_new_nav_item([
			'name' => __( 'Listings', 'my-listing' ),
			'slug' => 'listings',
			'default_subnav_slug' => 'listings',
			'position' => 22,
			'show_for_displayed_user' => true,
			'screen_function' => function() {
				add_action( 'wp_enqueue_scripts', function() {
					wp_enqueue_style( 'c27-buddypress-listings', get_stylesheet_directory_uri() .'/includes/integrations/buddypress/assets/styles/listings.css', [], CASE27_THEME_VERSION );
					wp_enqueue_script( 'c27-buddypress-script', get_stylesheet_directory_uri() . '/includes/integrations/buddypress/assets/scripts/buddypress.js', array('jquery'), CASE27_THEME_VERSION, true );
				}, 30);

				add_action( 'bp_template_content', function() {
					require_once get_stylesheet_directory() . '/includes/integrations/buddypress/views/profile/listings.php';
					});

				bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
			},
			'item_css_id' => 'c27-bp-listings',
			]);
	}

	public function redirect_author_page() {
		if ( is_author() &&  defined( 'BP_MEMBERS_SLUG' ) && ( $author_id = get_query_var( 'author' ) ) ) {
			wp_safe_redirect( bp_core_get_user_domain( $author_id ), 301 );
			exit;
		}
	}

	public function user_menu_items() {
		do_action( "case27/user-menu/buddypress-profile/before" ); ?>
			<li class="user-menu-buddypress-profile">
				<a href="<?php echo esc_url( bp_core_get_user_domain( get_current_user_id() ) ); ?>"><?php _e( 'My Profile', 'my-listing' ) ?></a>
			</li>
		<?php do_action( "case27/user-menu/buddypress-profile/after" );
	}
}

BuddyPress::instance();
