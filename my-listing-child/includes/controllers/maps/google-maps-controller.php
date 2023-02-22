<?php

namespace MyListing\Controllers\Maps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Google_Maps_Controller extends \MyListing\Controllers\Base_Controller {

	protected function is_active() {
		return mylisting()->get( 'maps.provider', 'google-maps' ) === 'google-maps';
	}

	protected function hooks() {
		$this->on( 'wp_enqueue_scripts', '@enqueue_scripts', 25 );
        $this->on( 'admin_enqueue_scripts', '@enqueue_scripts', 25 );
        $this->filter( 'mylisting/localize-data', '@localize_data', 25 );
        $this->filter( 'mylisting/helpers/get_map_skins', '@set_skins', 25 );
	}

	protected function enqueue_scripts() {
		$args = [
			'key' => mylisting()->get( 'maps.gmaps_api_key' ),
			'libraries' => 'places',
			'callback'	=> 'initMap',
			'v' => 3,
		];

		$language = mylisting()->get( 'maps.gmaps_lang', 'default' );
		if ( $language && $language !== 'default' ) {
			$args['language'] = $language;
		}

		wp_enqueue_script( 'google-maps', sprintf( 'https://maps.googleapis.com/maps/api/js?%s', http_build_query( $args ) ), [], null, true );
		wp_enqueue_script( 'mylisting-google-maps' );
		wp_enqueue_style( 'mylisting-google-maps' );
		?>
		<script type="text/javascript">
			function initMap() {
				
			}
			
			window.initMap = initMap;
</script>
<?php
	}

	protected function set_skins() {
		$skins = [];
		$custom_skins = [];

		// default skin should be the first option
		$skins['skin12'] = _x( 'Standard', 'Google Maps Skin', 'my-listing' );

		// followed by custom ones (if available)
		$custom_skins = mylisting()->get( 'maps.gmaps_skins', [] );
		foreach ( (array) $custom_skins as $skin_name => $skin ) {
			if ( empty( $skin ) ) {
				continue;
			}

			$skin_key = esc_attr( sprintf( 'custom_%s', $skin_name ) );
			$skins[ $skin_key ] = esc_html( $skin_name );
			$custom_skins[ $skin_key ] = $skin;
		}

		// append other MyListing skins
		$skins['skin1'] = _x( 'Vanilla', 'Google Maps Skin', 'my-listing' );
		$skins['skin2'] = _x( 'Midnight', 'Google Maps Skin', 'my-listing' );
		$skins['skin3'] = _x( 'Grayscale', 'Google Maps Skin', 'my-listing' );
		$skins['skin4'] = _x( 'Blue Water', 'Google Maps Skin', 'my-listing' );
		$skins['skin5'] = _x( 'Nature', 'Google Maps Skin', 'my-listing' );
		$skins['skin6'] = _x( 'Light', 'Google Maps Skin', 'my-listing' );
		$skins['skin7'] = _x( 'Teal', 'Google Maps Skin', 'my-listing' );
		$skins['skin8'] = _x( 'Iceberg', 'Google Maps Skin', 'my-listing' );
		$skins['skin9'] = _x( 'Violet', 'Google Maps Skin', 'my-listing' );
		$skins['skin10'] = _x( 'Ocean', 'Google Maps Skin', 'my-listing' );
		$skins['skin11'] = _x( 'Dark', 'Google Maps Skin', 'my-listing' );
		return $skins;
	}

	protected function localize_data( $data ) {
		$custom_skins = mylisting()->get( 'maps.gmaps_skins', [] );
		foreach ( (array) $custom_skins as $skin_name => $skin ) {
			if ( empty( $skin ) ) {
				continue;
			}

			$skin_key = esc_attr( sprintf( 'custom_%s', $skin_name ) );
			$custom_skins[ $skin_key ] = $skin;
		}

		$data['MapConfig']['AccessToken'] = mylisting()->get( 'maps.gmaps_api_key' );
		$data['MapConfig']['Language'] = mylisting()->get( 'maps.gmaps_lang', 'default' );
		$data['MapConfig']['TypeRestrictions'] = mylisting()->get( 'maps.gmaps_types', 'geocode' );
		$data['MapConfig']['CountryRestrictions'] = mylisting()->get( 'maps.gmaps_locations', [] );
		$data['MapConfig']['CustomSkins'] = (object) $custom_skins;
		return $data;
	}
}
