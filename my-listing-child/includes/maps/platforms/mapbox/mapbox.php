<?php

namespace MyListing\Ext\Maps\Platforms\Mapbox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mapbox {
	use \MyListing\Src\Traits\Instantiatable;

	public
		$api_key,
		$language,
		$feature_types,
		$countries,
		$skins,
		$custom_skins;

	public function __construct() {
		$this->api_key = mylisting()->get( 'maps.mapbox_api_key' );
		$this->language = mylisting()->get( 'maps.mapbox_lang', 'default' );

		// if set to default, try to retrieve the browser language via js and use that
		if ( $this->language === 'default' ) {
			$this->language = false;
		}

		$this->feature_types = mylisting()->get( 'maps.mapbox_types', 'geocode' );
		$this->countries = mylisting()->get( 'maps.mapbox_locations', [] );
		$this->set_skins();

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 25 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 25 );
        add_filter( 'mylisting/localize-data', [ $this, 'localize_data' ], 25 );
        add_filter( 'mylisting/helpers/get_map_skins', [ $this, 'get_skins' ], 25 );
        add_filter( 'mylisting/sections/map-block/actions', [ $this, 'show_get_directions_link' ] );
	}

	public function enqueue_scripts() {
		$suffix = is_rtl() ? '-rtl' : '';

		wp_enqueue_script( 'mapbox-gl', 'https://api.tiles.mapbox.com/mapbox-gl-js/v1.9.0/mapbox-gl.js', [], CASE27_THEME_VERSION, true );
		wp_enqueue_style( 'mapbox-gl', 'https://api.tiles.mapbox.com/mapbox-gl-js/v1.9.0/mapbox-gl.css', [], CASE27_THEME_VERSION );

		wp_enqueue_script( 'mylisting-maps', c27()->template_uri( 'assets/dist/maps/mapbox/mapbox.js' ), ['jquery'], CASE27_THEME_VERSION, true );
		wp_enqueue_style( 'mylisting-maps', c27()->template_uri( 'assets/dist/maps/mapbox/mapbox'.$suffix.'.css' ), [], CASE27_THEME_VERSION );
	}

	public function set_skins() {
		$this->skins = [];
		$this->custom_skins = [];

		// Default skin should be the first option.
		$this->skins['skin12'] = _x( 'Standard', 'Mapbox Skin', 'my-listing' );

		// Followed by custom ones (if available).
		$custom_skins = mylisting()->get( 'maps.mapbox_skins', [] );
		foreach ( (array) $custom_skins as $skin_name => $skin ) {
			if ( empty( $skin ) ) {
				continue;
			}

			$skin_key = esc_attr( sprintf( 'custom_%s', $skin_name ) );
			$this->skins[ $skin_key ] = esc_html( $skin_name );
			$this->custom_skins[ $skin_key ] = $skin;
		}

		// Append other MyListing skins.
		$this->skins['skin3'] = _x( 'Light', 'Mapbox Skin', 'my-listing' );
		$this->skins['skin4'] = _x( 'Dark', 'Mapbox Skin', 'my-listing' );
		$this->skins['skin2'] = _x( 'Outdoors', 'Mapbox Skin', 'my-listing' );
		$this->skins['skin6'] = _x( 'Satellite', 'Mapbox Skin', 'my-listing' );
		$this->skins['skin7'] = _x( 'Nav Day', 'Mapbox Skin', 'my-listing' );
		$this->skins['skin8'] = _x( 'Nav Night', 'Mapbox Skin', 'my-listing' );
		$this->skins['skin9'] = _x( 'Guide Day', 'Mapbox Skin', 'my-listing' );
		$this->skins['skin10'] = _x( 'Guide Night', 'Mapbox Skin', 'my-listing' );
	}

	public function get_skins() {
		return $this->skins;
	}

	public function localize_data( $data ) {
		$data['MapConfig']['AccessToken'] = $this->api_key;
		$data['MapConfig']['Language'] = 'pl';
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

		// @todo: Use Mapbox instead of Google if possible.
		printf(
			'<div class="location-address"><a href="%s" target="_blank">%s</a></div>',
			sprintf( 'http://maps.google.com/maps?daddr=%s', urlencode( $query ) ),
			_x( 'Get Directions', 'Map Block', 'my-listing' )
		);
	}
}
