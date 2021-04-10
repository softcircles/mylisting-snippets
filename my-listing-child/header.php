<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php esc_attr( bloginfo( 'charset' ) ) ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<link rel="pingback" href="<?php esc_attr( bloginfo( 'pingback_url' ) ) ?>">

	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
/**
 * Action hook immediately after the opening <body> tag.
 *
 * @since 1.6.6
 */
do_action( 'mylisting/body/start' ) ?>

<?php
// Initialize custom styles global.
$GLOBALS['case27_custom_styles'] = '';

// Wrap site in #c27-site-wrapper div.
printf( '<div id="c27-site-wrapper">' );

// Include loading screen animation.
c27()->get_partial( 'loading-screens/' . c27()->get_setting( 'general_loading_overlay', 'none' ) ); ?>

<?php
if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'header' ) ) {
	/**
	 * An Elementor Pro custom header is available.
	 *
	 * @link  https://developers.elementor.com/theme-locations-api/migrating-themes/
	 * @since 2.0
	 */
} elseif ( function_exists( 'hfe_render_header' ) && function_exists( 'get_hfe_header_id' ) && get_hfe_header_id() ) {
	/**
	 * An "Header, Footer & Blocks for Elementor" header is available.
	 *
	 * @link  https://github.com/Nikschavan/header-footer-elementor/wiki/Adding-Header-Footer-Elementor-support-for-your-theme
	 * @since 2.1
	 */
	hfe_render_header();
} else {
	/**
	 * No custom headers detected, use the default theme header.
	 *
	 * @since 1.0
	 */
	$pageTop = apply_filters( 'mylisting/header-config', [
		'header' => [ 'show' => true, 'args' => [] ],
		'title-bar' => [
			'show' => c27()->get_setting( 'header_show_title_bar', false ),
			'args' => [ 'title' => get_the_archive_title(), 'ref' => 'default-title-bar' ],
		],
	] );

	if ( $pageTop['header']['show'] ) {
		c27()->get_section( 'header', $pageTop['header']['args'] );
		if ( $pageTop['title-bar']['show'] ) {
			c27()->get_section( 'title-bar', $pageTop['title-bar']['args'] );
		}
	}
}

echo do_shortcode( '[adning id="70038"]' );
