<?php

namespace MyListing\Ext\Maps\Platforms\Google_Maps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Google_Maps {
	use \MyListing\Src\Traits\Instantiatable;

	public
		$api_key,
		$language,
		$feature_types,
		$countries,
		$skins,
		$custom_skins;

	public function __construct() {
		$this->api_key = mylisting()->get( 'maps.gmaps_api_key' );
		$this->language = mylisting()->get( 'maps.gmaps_lang', 'default' );
		$this->feature_types = mylisting()->get( 'maps.gmaps_types', 'geocode' );
		$this->countries = mylisting()->get( 'maps.gmaps_locations', [] );
		$this->set_skins();

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 25 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 25 );
        add_filter( 'mylisting/localize-data', [ $this, 'localize_data' ], 25 );
        add_filter( 'mylisting/helpers/get_map_skins', [ $this, 'get_skins' ], 25 );
        add_filter( 'mylisting/sections/map-block/actions', [ $this, 'show_get_directions_link' ] );
	}

	public function enqueue_scripts() {
		// Google Maps config.
		$args = [];
		$args['key'] = $this->api_key;
		$args['libraries'] = 'places';
		$args['v'] = 3;
		if ( $this->language && $this->language !== 'default' ) {
			$args['language'] = $this->language;
		}

		$suffix = is_rtl() ? '-rtl' : '';

		// Load Google Maps.
		wp_enqueue_script( 'google-maps', sprintf( 'https://maps.googleapis.com/maps/api/js?%s', http_build_query( $args ) ), [], null, true );

		// Load MyListing Maps assets.
		wp_enqueue_script( 'mylisting-maps', c27()->template_uri( 'assets/dist/maps/google-maps/google-maps.js' ), ['jquery'], CASE27_THEME_VERSION, true );
		wp_enqueue_style( 'mylisting-maps', c27()->template_uri( 'assets/dist/maps/google-maps/google-maps'.$suffix.'.css' ), [], CASE27_THEME_VERSION );
	}

	public function set_skins() {
		$this->skins = [];
		$this->custom_skins = [];

		// Default skin should be the first option.
		$this->skins['skin12'] = _x( 'Standard', 'Google Maps Skin', 'my-listing' );

		// Followed by custom ones (if available).
		$custom_skins = mylisting()->get( 'maps.gmaps_skins', [] );
		foreach ( (array) $custom_skins as $skin_name => $skin ) {
			if ( empty( $skin ) ) {
				continue;
			}

			$skin_key = esc_attr( sprintf( 'custom_%s', $skin_name ) );
			$this->skins[ $skin_key ] = esc_html( $skin_name );
			$this->custom_skins[ $skin_key ] = $skin;
		}

		// Append other MyListing skins.
		$this->skins['skin1'] = _x( 'Vanilla', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin2'] = _x( 'Midnight', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin3'] = _x( 'Grayscale', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin4'] = _x( 'Blue Water', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin5'] = _x( 'Nature', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin6'] = _x( 'Light', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin7'] = _x( 'Teal', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin8'] = _x( 'Iceberg', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin9'] = _x( 'Violet', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin10'] = _x( 'Ocean', 'Google Maps Skin', 'my-listing' );
		$this->skins['skin11'] = _x( 'Dark', 'Google Maps Skin', 'my-listing' );
	}

	public function get_skins() {
		return $this->skins;
	}

	public function localize_data( $data ) {
		$data['MapConfig']['AccessToken'] = $this->api_key;
		$data['MapConfig']['Language'] = $this->language;
		$data['MapConfig']['TypeRestrictions'] = $this->feature_types;
		$data['MapConfig']['CountryRestrictions'] = $this->countries;
		$data['MapConfig']['CustomSkins'] = (object) $this->custom_skins;
		return $data;
	}

	public function show_get_directions_link( $place ) {
		if ( empty( $place['marker_lat'] ) || empty( $place['marker_lng'] ) ) {
			return;
		}

		$latlng = join( ',', [ $place['marker_lat'], $place['marker_lng'] ] );
		$query = ! empty( $place['address'] ) ? $place['address'] : $latlng;

		printf(
			'<div class="location-address"><a href="%s" target="_blank">%s</a></div>',
			sprintf( 'https://www.google.com/maps/search/?api=1&query=%s', urlencode( $place['address'] ) ),
			_x( 'Get Directions', 'Map Block', 'my-listing' )
		);
	}
}
