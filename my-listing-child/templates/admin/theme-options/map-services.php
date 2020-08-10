<?php
/**
 * Template for rendering map-services settings.
 *
 * @since 2.4
 * @var $config
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

if ( ! empty( $_GET['success'] ) ) {
	echo '<div class="updated"><p>'.esc_html__( 'Settings successfully saved!', 'my-listing' ).'</p></div>';
}
?>

<div class="wrap mapsettings">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>" method="POST">
		<h1 class="m-heading mb30">Map Services</h1>

		<div class="form-group mb20 set-provider">
			<h4 class="m-heading mb5">Provider</h4>
			<p class="mt0 mb15">Choose what service to use for displaying maps, place suggestions, and geocoding.</p>
			<label class="dibvt" style="margin-right:30px;">
				<input type="radio" name="provider" value="google-maps" class="form-radio" style="margin-top:0;" <?php checked( $config['provider'] === 'google-maps' ) ?>>
				<img src="<?php echo c27()->image( 'google-maps.png' ) ?>" alt="Google Maps" height="32" class="dibvm">
			</label>
			<label class="dibvt">
				<input type="radio" name="provider" value="mapbox" class="form-radio" style="margin-top:0;" <?php checked( $config['provider'] === 'mapbox' ) ?>>
				<img src="<?php echo c27()->image( 'mapbox.png' ) ?>" alt="Mapbox" height="32" class="dibvm">
			</label>
		</div>

		<div class="gmaps-settings mt60 <?php echo $config['provider'] === 'google-maps' ? '' : 'hide' ?>">
			<div class="form-group mb30" style="max-width:420px;">
				<h4 class="m-heading mb5">Google Maps API Key</h4>
				<p class="mt0 mb10">
					An API key is required to use Google Maps.
					<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
						Click here to learn more.
					</a>
				</p>
				<input type="text" name="gmaps_api_key" class="m-input" value="<?php echo esc_attr( $config['gmaps_api_key'] ) ?>">
			</div>

			<div class="form-group mb30">
				<h4 class="m-heading mb5">Language</h4>
				<p class="mt0 mb10">Set what language to load maps in.</p>
				<div class="select-wrapper dib" style="width:200px;">
					<select name="gmaps_lang">
						<?php foreach ( self::get_gmaps_lang_choices() as $lang => $label ): ?>
							<option value="<?php echo esc_attr( $lang ) ?>" <?php selected( $lang, $config['gmaps_lang'] ) ?>>
								<?php echo $label ?>
							</option>
						<?php endforeach ?>
					</select>
				</div>
			</div>

			<div class="form-group mb30">
				<h4 class="m-heading mb5">Autocomplete returns results for</h4>
				<p class="mt0 mb10">Determine what kind of features should be searched by autocomplete.</p>
				<label>
					<input type="radio" class="form-radio" name="gmaps_types" value="geocode" <?php checked( $config['gmaps_types'] === 'geocode' ) ?>> Geocode &nbsp;
				</label>
				<label>
					<input type="radio" class="form-radio" name="gmaps_types" value="address" <?php checked( $config['gmaps_types'] === 'address' ) ?>> Address &nbsp;
				</label>
				<label>
					<input type="radio" class="form-radio" name="gmaps_types" value="establishment" <?php checked( $config['gmaps_types'] === 'establishment' ) ?>> Establishment &nbsp;
				</label>
				<label>
					<input type="radio" class="form-radio" name="gmaps_types" value="(regions)" <?php checked( $config['gmaps_types'] === '(regions)' ) ?>> Regions &nbsp;
				</label>
				<label>
					<input type="radio" class="form-radio" name="gmaps_types" value="(cities)" <?php checked( $config['gmaps_types'] === '(cities)' ) ?>> Cities
				</label>
			</div>

			<div class="form-group mb30">
				<h4 class="m-heading mb5">Autocomplete returns results in</h4>
				<p class="mt0 mb10">Limit autocomplete results to one or more countries.</p>
				<div style="width:420px;">
					<select name="gmaps_locations[]" multiple="multiple" class="custom-select">
						<?php foreach ( mylisting()->strings()->get_countries() as $country_code => $label ): ?>
							<option value="<?php echo esc_attr( $country_code ) ?>" <?php selected( in_array( $country_code, $config['gmaps_locations'] ) ) ?>>
								<?php echo $label ?>
							</option>
						<?php endforeach ?>
					</select>
				</div>
			</div>

			<div class="form-group mb30">
				<h4 class="m-heading mb5">Custom Map Skins</h4>
				<p class="mt0 mb10">
					You can create custom map skins using the <a href="https://mapstyle.withgoogle.com/" target="_blank">
					Google Maps Styling Wizard</a>. Paste the generated JSON below.<br>
					These skins will appear alongside default skin options when creating and editing maps.
				</p>
				<div class="custom-skins mt10">
					<script type="text/template" class="skintpl">
						<div class="custom-skin mb5">
							<input type="text" class="m-input dibvt" name="gmaps_skinkeys[]" placeholder="Label this skin">
							<input type="text" class="m-input dibvt" name="gmaps_skins[]" placeholder="Paste the JSON code here">
							<div class="btn btn-outline btn-xxs">Remove</div>
						</div>
					</script>
					<div class="custom-skin-list"><?php
						foreach ( $config['gmaps_skins'] as $label => $value ): ?>
							<div class="custom-skin mb5">
								<input type="text" class="m-input dibvt" name="gmaps_skinkeys[]" value="<?php echo esc_attr( $label ) ?>" placeholder="Label this skin">
								<input type="text" class="m-input dibvt" name="gmaps_skins[]" value="<?php echo esc_attr( $value ) ?>" placeholder="Paste the JSON code here">
								<div class="btn btn-outline btn-xxs">Remove</div>
							</div>
						<?php endforeach;
					?></div>
					<div class="btn btn-secondary btn-xs mt10 add-new"><i class="icon-add-circle-1"></i> Add Custom Skin</div>
				</div>
			</div>
		</div>

		<div class="mapbox-settings mt60 <?php echo $config['provider'] === 'mapbox' ? '' : 'hide' ?>">
			<div class="form-group mb30" style="max-width:420px;">
				<h4 class="m-heading mb5">Mapbox API Access Token</h4>
				<p class="mt0 mb10">
					A Mapbox API Access Token is required to load maps. You can get it in
					<a href="https://www.mapbox.com/account/" target="_blank">your Mapbox user dashboard</a>.
				</p>
				<input type="text" name="mapbox_api_key" class="m-input" value="<?php echo esc_attr( $config['mapbox_api_key'] ) ?>">
			</div>

			<div class="form-group mb30">
				<h4 class="m-heading mb5">Language</h4>
				<p class="mt0 mb10">Set what language to load maps in.</p>
				<div class="select-wrapper dib" style="width:200px;">
					<select name="mapbox_lang">
						<option value="default" <?php selected( 'default', $config['mapbox_lang'] ) ?>>Default (Browser Detected)</option>
						<option value="mul" <?php selected( 'mul', $config['mapbox_lang'] ) ?>>Local language on each place</option>
						<option value="en" <?php selected( 'en', $config['mapbox_lang'] ) ?>>English</option>
						<option value="es" <?php selected( 'es', $config['mapbox_lang'] ) ?>>Spanish</option>
						<option value="fr" <?php selected( 'fr', $config['mapbox_lang'] ) ?>>French</option>
						<option value="de" <?php selected( 'de', $config['mapbox_lang'] ) ?>>German</option>
						<option value="ru" <?php selected( 'ru', $config['mapbox_lang'] ) ?>>Russian</option>
						<option value="zh" <?php selected( 'zh', $config['mapbox_lang'] ) ?>>Chinese</option>
						<option value="pt" <?php selected( 'pt', $config['mapbox_lang'] ) ?>>Portuguese</option>
						<option value="ar" <?php selected( 'ar', $config['mapbox_lang'] ) ?>>Arabic</option>
						<option value="ja" <?php selected( 'ja', $config['mapbox_lang'] ) ?>>Japanese</option>
						<option value="ko" <?php selected( 'ko', $config['mapbox_lang'] ) ?>>Korean</option>
						<option value="pl" <?php selected( 'pl', $config['mapbox_lang'] ) ?>>Polish</option>
					</select>
				</div>
			</div>

			<div class="form-group mb30">
				<h4 class="m-heading mb5">Autocomplete returns results for</h4>
				<p class="mt0 mb10">Determine what kind of features should be searched by autocomplete. Leave blank to include all.</p>
				<div style="width:420px;">
					<select name="mapbox_types[]" class="custom-select" multiple="multiple">
						<option value="country" <?php selected( in_array( 'country', $config['mapbox_types'] ) ) ?>>Countries</option>
						<option value="region" <?php selected( in_array( 'region', $config['mapbox_types'] ) ) ?>>Regions</option>
						<option value="postcode" <?php selected( in_array( 'postcode', $config['mapbox_types'] ) ) ?>>Postcodes</option>
						<option value="district" <?php selected( in_array( 'district', $config['mapbox_types'] ) ) ?>>Districts</option>
						<option value="place" <?php selected( in_array( 'place', $config['mapbox_types'] ) ) ?>>Places</option>
						<option value="locality" <?php selected( in_array( 'locality', $config['mapbox_types'] ) ) ?>>Localities</option>
						<option value="neighborhood" <?php selected( in_array( 'neighborhood', $config['mapbox_types'] ) ) ?>>Neighborhoods</option>
						<option value="address" <?php selected( in_array( 'address', $config['mapbox_types'] ) ) ?>>Addresses</option>
						<option value="poi" <?php selected( in_array( 'poi', $config['mapbox_types'] ) ) ?>>Points of interest</option>
						<option value="poi.landmark" <?php selected( in_array( 'poi.landmark', $config['mapbox_types'] ) ) ?>>Landmarks</option>
					</select>
				</div>
			</div>

			<div class="form-group mb30">
				<h4 class="m-heading mb5">Autocomplete returns results in</h4>
				<p class="mt0 mb10">Limit autocomplete results to one or more countries.</p>
				<div style="width:420px;">
					<select name="mapbox_locations[]" multiple="multiple" class="custom-select">
						<?php foreach ( mylisting()->strings()->get_countries() as $country_code => $label ): ?>
							<option value="<?php echo esc_attr( $country_code ) ?>" <?php selected( in_array( $country_code, $config['mapbox_locations'] ) ) ?>>
								<?php echo $label ?>
							</option>
						<?php endforeach ?>
					</select>
				</div>
			</div>

			<div class="form-group mb30">
				<h4 class="m-heading mb5">Custom Map Styles</h4>
				<p class="mt0 mb10">
					You can create custom map styles in your <a href="https://www.mapbox.com/studio/" target="_blank">Mapbox Studio</a>.
					Paste the style URL (recommended) or the generated JSON below.
					These skins will appear alongside default skin options when creating and editing maps.
				</p>
				<div class="custom-skins mt10">
					<script type="text/template" class="skintpl">
						<div class="custom-skin mb5">
							<input type="text" class="m-input dibvt" name="mapbox_skinkeys[]" placeholder="Label this style">
							<input type="text" class="m-input dibvt" name="mapbox_skins[]" placeholder="Paste the style URL here">
							<div class="btn btn-outline btn-xxs">Remove</div>
						</div>
					</script>
					<div class="custom-skin-list"><?php
						foreach ( $config['mapbox_skins'] as $label => $value ): ?>
							<div class="custom-skin mb5">
								<input type="text" class="m-input dibvt" name="mapbox_skinkeys[]" value="<?php echo esc_attr( $label ) ?>" placeholder="Label this style">
								<input type="text" class="m-input dibvt" name="mapbox_skins[]" value="<?php echo esc_attr( $value ) ?>" placeholder="Paste the style URL here">
								<div class="btn btn-outline btn-xxs">Remove</div>
							</div>
						<?php endforeach;
					?></div>
					<div class="btn btn-secondary btn-xs mt10 add-new"><i class="icon-add-circle-1"></i> Add Custom Skin</div>
				</div>
			</div>
		</div>

		<div class="mt60">
			<input type="hidden" name="action" value="mylisting_update_mapservices">
			<input type="hidden" name="page" value="theme-mapservice-settings">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'mylisting_update_mapservices' ) ) ?>">
			<button type="submit" class="btn btn-primary-alt btn-xs">Save settings</button>
		</div>
	</form>
</div>
