<?php

namespace MyListing\Ext\Sharer;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Sharer {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		add_action( 'wp_head', [ $this, 'add_opengraph_tags' ], 5 );
		add_action( 'wpseo_opengraph', [ $this, 'remove_yoast_duplicate_og_tags' ] );
	}

	public function add_opengraph_tags() {
    	global $post;

    	if ( is_singular( 'job_listing' ) && ( $listing = \MyListing\Src\Listing::get( $post ) ) ) {
    		$tags = [];

    		$tags['og:title'] = $listing->get_name();
    		$tags['og:url'] = $listing->get_link();
    		$tags['og:site_name'] = get_bloginfo();
    		$tags['og:type'] = 'profile';
    		$tags['og:description'] = $listing->get_share_description();

    		if ( $logo = $listing->get_share_image() ) {
    			$tags['og:image'] = esc_url( $logo );
    		}

    		$tags = apply_filters( 'mylisting\single\og:tags', $tags, $listing );

    		foreach ( $tags as $property => $content ) {
    			printf( "<meta property=\"%s\" content=\"%s\" />\n", esc_attr( $property ), esc_attr( $content ) );
    		}
		}
	}

	public function remove_yoast_duplicate_og_tags() {
		if ( ! is_singular( 'job_listing' ) ) {
			return false;
		}

		add_filter( 'wpseo_og_og_title',       '__return_false', 50 );
    	add_filter( 'wpseo_og_og_description', '__return_false', 50 );
    	add_filter( 'wpseo_og_og_url',         '__return_false', 50 );
    	add_filter( 'wpseo_og_og_type',        '__return_false', 50 );
    	add_filter( 'wpseo_og_og_site_name',   '__return_false', 50 );
    	add_filter( 'wpseo_og_og_image',       '__return_false', 50 );
	}

	public function get_links( $options = [] ) {
		$options = c27()->merge_options([
			'title' => false,
			'image' => false,
			'permalink' => false,
			'description' => false,
			'icons' => false,
		], $options);

		$options['title'] = wp_kses( $options['title'], [] );
		$options['description'] = wp_kses( $options['description'], [] );

		return apply_filters( 'mylisting\share\get-links', [
			'facebook' 	=> $this->facebook($options),
			'twitter'  	=> $this->twitter($options),
			'whatsapp'	=> $this->whatsapp($options),
			'telegram'	=> $this->telegram($options),
			'pinterest'	=> $this->pinterest($options),
			'linkedin'	=> $this->linkedin($options),
			'tumblr'	=> $this->tumblr($options),
			'vkontakte'	=> $this->vkontakte($options),
			'mail'		=> $this->mail($options),
			'copy_link' => $this->copy_link($options),
		] );
	}

	public function facebook($options) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'http://www.facebook.com/share.php';
		$url .= '?u=' . urlencode($options['permalink']);
		$url .= '&title=' . urlencode($options['title']);

		if ($options['description']) $url .= '&description=' . urlencode($options['description']);
		if ($options['image']) $url .= '&picture=' . urlencode($options['image']);

		return $this->get_link_template( [
			'title' => _x( 'Facebook', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-facebook',
			'color' => '#3b5998',
		] );
	}

	public function twitter( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = sprintf(
			'http://twitter.com/share?text=%s&url=%s',
			urlencode( $options['title'] ),
			urlencode( $options['permalink'] )
		);

		return $this->get_link_template( [
			'title' => _x( 'Twitter', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-twitter',
			'color' => '#4099FF',
		] );
	}

	public function pinterest( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) || empty( $options['image'] ) ) {
			return;
		}

		$url = 'https://pinterest.com/pin/create/button/';
		$url .= '?url=' . urlencode($options['permalink']);
		$url .= '&media=' . urlencode($options['image']);
		$url .= '&description=' . urlencode($options['title']);

		return $this->get_link_template( [
			'title' => _x( 'Pinterest', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-pinterest',
			'color' => '#C92228',
		] );
	}

	public function linkedin( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'http://www.linkedin.com/shareArticle?mini=true';
		$url .= '&url=' . urlencode($options['permalink']);
		$url .= '&title=' . urlencode($options['title']);

		return $this->get_link_template( [
			'title' => _x( 'LinkedIn', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-linkedin',
			'color' => '#0077B5',
		] );
	}

	public function tumblr( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'http://www.tumblr.com/share?v=3';
		$url .= '&u=' . urlencode($options['permalink']);
		$url .= '&t=' . urlencode($options['title']);

		return $this->get_link_template( [
			'title' => _x( 'Tumblr', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-tumblr',
			'color' => '#35465c',
		] );
	}

	public function whatsapp( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = sprintf( 'https://api.whatsapp.com/?text=%s+%s', urlencode( $options['title'] ), urlencode( $options['permalink'] ) );

		return $this->get_link_template( [
			'title' => _x( 'WhatsApp', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-whatsapp',
			'color' => '#128c7e',
		] );
	}

	public function telegram( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = sprintf( 'https://telegram.me/share/url?url=%s&text=%s', $options['permalink'], $options['title'] );

		return $this->get_link_template( [
			'title' => _x( 'Telegram', 'Share dialog', 'my-listing' ),
			'permalink' => esc_url( $url ),
			'icon' => 'fa fa-telegram',
			'color' => '#0088cc',
		] );
	}

	public function vkontakte( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = 'http://vk.com/share.php?url=' . urlencode( $options['permalink'] );
		$url .= '&title=' . urlencode( $options['title'] );

		return $this->get_link_template( [
			'title' => _x( 'VKontakte', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-vk',
			'color' => '#5082b9',
		] );
	}

	public function mail( $options ) {
		if ( empty( $options['title'] ) || empty( $options['permalink'] ) ) {
			return;
		}

		$url = sprintf(
			'mailto:?subject=%s&body=%s',
			rawurlencode( '['.get_bloginfo('name').'] ' . $options['title'] ),
			rawurlencode( $options['permalink'] )
		);

		return $this->get_link_template( [
			'title' => _x( 'Mail', 'Share dialog', 'my-listing' ),
			'permalink' => $url,
			'icon' => 'fa fa-envelope-o',
			'color' => '#e74c3c',
			'popup' => false,
		] );
	}

	public function print_link( $link ) {
		if ( ! is_string( $link ) || empty( trim( $link ) ) ) {
			return;
		}

		echo $link;
	}

	public function get_link_template( $data ) {
		$has_popup = isset( $data['popup'] ) && $data['popup'] === false ? false : true;

		ob_start(); ?>
		<a href="<?php echo esc_url( $data['permalink'] ) ?>" class="<?php echo esc_attr( $has_popup ? 'cts-open-popup' : '' ) ?>">
			<i class="<?php echo esc_attr( $data['icon'] ) ?>" style="background-color: <?php echo esc_attr( $data['color'] ) ?>;"></i>
			<?php echo esc_html( $data['title'] ) ?>
		</a>
		<?php return trim( ob_get_clean() );
	}

	public function copy_link( $options ) {
		if ( empty( $options['permalink'] ) ) {
			return;
		}

		$title = _x( 'Copy link', 'Share dialog', 'my-listing' );
		return sprintf(
			'<a class="c27-copy-link" href="%s" title="%s">'.
				'<i class="fa fa-clone" style="background-color:#95a5a6;"></i>'.
				'<span>%s</span>'.
			'</a>',
			esc_url( $options['permalink'] ), $title, $title
		);
	}
}
