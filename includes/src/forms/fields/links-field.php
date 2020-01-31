<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Links_Field extends Base_Field {

	public function get_posted_value() {
		$value = ! empty( $_POST[ $this->key ] ) ? (array) $_POST[ $this->key ] : [];
		$links = array_map( function( $val ) {
			if ( ! is_array( $val ) || empty( $val['network'] ) || empty( $val['url'] ) ) {
				return false;
			}

			return [
				'network' => sanitize_text_field( stripslashes( $val['network'] ) ),
				'url' => esc_url_raw( $val['url'] ),
			];
		}, $value );

		return array_filter( $links );
	}

	public function validate() {
		$value = $this->get_posted_value();
		//
	}

	public function field_props() {
		$this->props['type'] = 'links';
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
	}

	/**
	 * List of social networks that will be shown in the Add Listing form.
	 *
	 * @since 1.6.0
	 * @param name  Network name, wrapped in _x() for being compatible with l10n plugins.
	 * @param key   Network name, but static. Will be stored in database based on this value.
	 * @param icon  Network icon classname. Will be shown in the single listing page.
	 * @param color Hex color value, used for styling in single listing page.
	 */
	public static function allowed_networks() {
		return apply_filters( 'mylisting\links-list', [
			'Facebook' => [
				'name' => _x( 'Facebook', 'Listing social networks', 'my-listing' ),
				'key' => 'Facebook',
				'icon' => 'fab fa-facebook',
				'color' => '#3b5998',
			],
			'Twitter' => [
				'name' => _x( 'Twitter', 'Listing social networks', 'my-listing' ),
				'key' => 'Twitter',
				'icon' => 'fab fa-twitter',
				'color' => '#4099FF',
			],
			'Instagram' => [
				'name' => _x( 'Instagram', 'Listing social networks', 'my-listing' ),
				'key' => 'Instagram',
				'icon' => 'fab fa-instagram',
				'color' => '#e1306c',
			],
			'YouTube' => [
				'name' => _x( 'YouTube', 'Listing social networks', 'my-listing' ),
				'key' => 'YouTube',
				'icon' => 'fab fa-youtube',
				'color' => '#ff0000',
			],
			'Snapchat' => [
				'name' => _x( 'Snapchat', 'Listing social networks', 'my-listing' ),
				'key' => 'Snapchat',
				'icon' => 'fab fa-snapchat-ghost',
				'color' => '#fffc00',
			],
			'Tumblr' => [
				'name' => _x( 'Tumblr', 'Listing social networks', 'my-listing' ),
				'key' => 'Tumblr',
				'icon' => 'fab fa-tumblr',
				'color' => '#35465c',
			],
			'Reddit' => [
				'name' => _x( 'Reddit', 'Listing social networks', 'my-listing' ),
				'key' => 'Reddit',
				'icon' => 'fab fa-reddit',
				'color' => '#ff4500',
			],
			'LinkedIn' => [
				'name' => _x( 'LinkedIn', 'Listing social networks', 'my-listing' ),
				'key' => 'LinkedIn',
				'icon' => 'fab fa-linkedin',
				'color' => '#0077B5',
			],
			'Pinterest' => [
				'name' => _x( 'Pinterest', 'Listing social networks', 'my-listing' ),
				'key' => 'Pinterest',
				'icon' => 'fab fa-pinterest',
				'color' => '#C92228',
			],
			'DeviantArt' => [
				'name' => _x( 'DeviantArt', 'Listing social networks', 'my-listing' ),
				'key' => 'DeviantArt',
				'icon' => 'fab fa-deviantart',
				'color' => '#05cc47',
			],
			'VKontakte' => [
				'name' => _x( 'VKontakte', 'Listing social networks', 'my-listing' ),
				'key' => 'VKontakte',
				'icon' => 'fab fa-vk',
				'color' => '#5082b9',
			],
			'SoundCloud' => [
				'name' => _x( 'SoundCloud', 'Listing social networks', 'my-listing' ),
				'key' => 'SoundCloud',
				'icon' => 'fab fa-soundcloud',
				'color' => '#ff5500',
			],
			'Website' => [
				'name' => _x( 'Website', 'Listing social networks', 'my-listing' ),
				'key' => 'Website',
				'icon' => 'fas fa-link',
				'color' => '#70ada5',
			],
			'Other' => [
				'name' => _x( 'Other', 'Listing social networks', 'my-listing' ),
				'key' => 'Other',
				'icon' => 'fas fa-link',
				'color' => '#70ada5',
			],
		] );
	}
}
