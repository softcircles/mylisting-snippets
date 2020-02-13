<?php
$data = c27()->merge_options([
	'logo'                    => c27()->get_site_logo(),
    'skin'                    => c27()->get_setting('header_skin', 'dark'),
    'style'                   => c27()->get_setting('header_style', 'default'),
	'fixed'                   => c27()->get_setting('header_fixed', true),
    'scroll_skin'             => c27()->get_setting('header_scroll_skin', 'dark'),
    'scroll_logo'             => c27()->get_setting('header_scroll_logo') ? c27()->get_setting('header_scroll_logo')['sizes']['medium'] : false,
	'border_color'            => c27()->get_setting('header_border_color', 'rgba(29, 29, 31, 0.95)'),
	'menu_location'           => c27()->get_setting('header_menu_location', 'right'),
	'background_color'        => c27()->get_setting('header_background_color', 'rgba(29, 29, 31, 0.95)'),
	'show_search_form'        => c27()->get_setting('header_show_search_form', true),
	'show_call_to_action'     => c27()->get_setting('header_show_call_to_action_button', false),
	'scroll_border_color'     => c27()->get_setting('header_scroll_border_color', 'rgba(29, 29, 31, 0.95)'),
	'search_form_placeholder' => c27()->get_setting('header_search_form_placeholder', 'Type your search...'),
	'scroll_background_color' => c27()->get_setting('header_scroll_background_color', 'rgba(29, 29, 31, 0.95)'),
	'blend_to_next_section'   => false,
    'is_edit_mode'            => false,
], $data);

$header_classes = ['c27-main-header', 'header', "header-style-{$data['style']}", "header-{$data['skin']}-skin", "header-scroll-{$data['scroll_skin']}-skin", 'hide-until-load', 'header-scroll-hide'];

if ( $data['fixed'] ) {
	$header_classes[] = 'header-fixed';
}

$header_classes[] = sprintf( 'header-menu-%s', $data['menu_location'] === 'right' ? 'right' : 'left' );

$GLOBALS['case27_custom_styles'] .= '.c27-main-header .logo img { height: ' . c27()->get_setting( 'header_logo_height', 38 ) . 'px; }';

if ($data['background_color']) {
	if (!isset($GLOBALS['case27_custom_styles'])) $GLOBALS['case27_custom_styles'] = '';

	$GLOBALS['case27_custom_styles'] .= '.c27-main-header:not(.header-scroll) .header-skin ';
	$GLOBALS['case27_custom_styles'] .= '{ background: ' . $data['background_color'] . ' !important; }';
}

if ($data['border_color']) {
	if (!isset($GLOBALS['case27_custom_styles'])) $GLOBALS['case27_custom_styles'] = '';

	$GLOBALS['case27_custom_styles'] .= '.c27-main-header:not(.header-scroll) .header-skin { border-bottom: 1px solid ' . $data['border_color'] . ' !important; } ';
}

if ($data['scroll_background_color']) {
	if (!isset($GLOBALS['case27_custom_styles'])) $GLOBALS['case27_custom_styles'] = '';

	$GLOBALS['case27_custom_styles'] .= '.c27-main-header.header-scroll .header-skin';
	$GLOBALS['case27_custom_styles'] .= '{ background: ' . $data['scroll_background_color'] . ' !important; }';
}

if ($data['scroll_border_color']) {
	if (!isset($GLOBALS['case27_custom_styles'])) $GLOBALS['case27_custom_styles'] = '';

	$GLOBALS['case27_custom_styles'] .= '.c27-main-header.header-scroll .header-skin { border-bottom: 1px solid ' . $data['scroll_border_color'] . ' !important; } ';
}
?>

<header class="<?php echo esc_attr( join( ' ', $header_classes ) ) ?>">
	<div class="header-skin"></div>
	<div class="header-container">
		<div class="header-top container-fluid">
			<div class="mobile-menu">
				<a href="#main-menu">
					<div class="mobile-menu-lines"><i class="mi menu"></i></div>
				</a>
			</div>

			<div class="logo">
				<?php if ( $data['logo'] ): ?>
					<?php if ( $data['scroll_logo'] ): ?>
						<a href="<?php echo esc_url( home_url('/') ) ?>" class="scroll-logo">
							<img src="<?php echo esc_url( $data['scroll_logo'] ) ?>" alt="YOUR-ALT-TEXT">
						</a>
					<?php endif ?>

					<a href="<?php echo esc_url( home_url('/') ) ?>" class="static-logo" >
						<img src="<?php echo esc_url( $data['logo'] ) ?>" alt="YOUR-ALT-TEXT">
					</a>
				<?php else: ?>
					<a href="<?php echo esc_url( home_url('/') ) ?>" class="header-logo-text">
						<?php echo esc_attr( get_bloginfo('sitename') ) ?>
					</a>
				<?php endif ?>
			</div>

			<div class="header-right">
				<?php if ( is_user_logged_in() ): $current_user = wp_get_current_user(); ?>
					<div class="user-area">
						<div class="user-profile-dropdown dropdown">
							<a class="user-profile-name" href="#" type="button" id="user-dropdown-menu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
								<div class="avatar">
									<?php echo get_avatar( $current_user->ID ) ?>
								</div>
								<?php echo esc_attr( $current_user->display_name ) ?>
								<?php if ( class_exists('WooCommerce') ): ?>
									<div class="submenu-toggle"><i class="material-icons">arrow_drop_down</i></div>
								<?php endif; ?>
							</a>

							<?php if ( has_nav_menu( 'mylisting-user-menu' ) ) : ?>
								<?php wp_nav_menu([
								    'theme_location' => 'mylisting-user-menu',
								    'container' 	 => false,
								    'depth'     	 => 0,
								    'menu_class'	 => 'i-dropdown dropdown-menu',
								    'items_wrap' 	 => '<ul class="%2$s" aria-labelledby="user-dropdown-menu">%3$s</ul>'
								    ]); ?>
							<?php elseif ( class_exists('WooCommerce') ) : ?>
								<ul class="i-dropdown dropdown-menu" aria-labelledby="user-dropdown-menu">
									<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
										<?php do_action( "case27/user-menu/{$endpoint}/before" ) ?>
										<li class="user-menu-<?php echo esc_attr( $endpoint ) ?>">
											<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $label ); ?></a>
										</li>
										<?php do_action( "case27/user-menu/{$endpoint}/after" ) ?>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>

						<?php if ( c27()->get_setting( 'header_show_cart', true ) !== false ): ?>
							<?php c27()->get_partial( 'header-cart' ) ?>
						<?php endif ?>

						<?php if ( c27()->get_setting( 'messages_enabled', true ) !== false ): ?>
							<div class="messaging-center inbox-header-icon">
								<a href="#" id="messages-modal-toggle" class="icon-btn" data-toggle="modal" data-target="#ml-messages-modal">
									<i class="material-icons">inbox</i>
									<div class="chat-counter-container" id="ml-chat-activities"></div>
								</a>
							</div>
						<?php endif ?>

					</div>
				<?php else: ?>
					<div class="user-area signin-area">
						<i class="mi person user-area-icon"></i>
						<a href="#" data-toggle="modal" data-target="#sign-in-modal"><?php _e( 'Sign in', 'my-listing' ) ?></a>
						<?php if (get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes'): ?>
							<span><?php _e( 'or', 'my-listing' ) ?></span>
							<a href="#" data-toggle="modal" data-target="#sign-up-modal"><?php _e( 'Register', 'my-listing' ) ?></a>
						<?php endif ?>
					</div>
					<div class="mob-sign-in">
						<a href="#" data-toggle="modal" data-target="#sign-in-modal"><i class="mi person"></i></a>
					</div>

					<?php if ( c27()->get_setting( 'header_show_cart', true ) !== false ): ?>
						<?php c27()->get_partial( 'header-cart' ) ?>
					<?php endif ?>
				<?php endif ?>

				<?php require locate_template( 'partials/header/call-to-action.php' ) ?>

				<?php if ( $data['show_search_form'] ): ?>
					<div class="search-trigger" data-toggle="modal" data-target="#quicksearch-mobile-modal">
						<a href="#"><i class="mi search"></i></a>
					</div>
				<?php endif ?>
			</div>
		</div>
		<div class="container-fluid header-bottom">

			<div class="header-bottom-wrapper row">
				<?php if ( $data['show_search_form'] ): ?>
					<?php c27()->get_partial( 'quick-search', [
						'instance-id' => 'c27-header-search-form',
						'placeholder' => $data['search_form_placeholder'],
						'align' => 'left',
					] ) ?>

					<?php add_action( 'mylisting/get-footer', function() use ( $data ) { ?>
					    <div id="quicksearch-mobile-modal" class="modal modal-27">
					    	<div class="modal-dialog modal-md">
					    		<div class="modal-content">
					    			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					    				<span aria-hidden="true">&times;</span>
					    			</button>
									<?php c27()->get_partial( 'quick-search', [
										'instance-id' => 'quicksearch-mobile',
										'placeholder' => $data['search_form_placeholder'],
										'align' => 'left',
										'focus' => 'always',
									] ) ?>
								</div>
							</div>
						</div>
					<?php } ) ?>
				<?php endif ?>

				<div class="i-nav">
					<div class="mobile-nav-head">
						<div class="mnh-close-icon">
							<a href="#close-main-menu">
								<i class="mi menu"></i>
							</a>
						</div>

						<?php if ( is_user_logged_in() ): $current_user = wp_get_current_user(); ?>
							<div class="user-profile-dropdown">
								<a class="user-profile-name" href="#">
									<div class="avatar">
										<?php echo get_avatar( $current_user->ID ) ?>
									</div>
									<?php echo esc_attr( $current_user->display_name ) ?>
									<?php if ( class_exists('WooCommerce') ): ?>
										<div class="submenu-toggle"><i class="mi arrow_drop_down"></i></div>
									<?php endif; ?>
								</a>
							</div>
						<?php endif ?>
					</div>

					<?php if ( is_user_logged_in() ): ?>
						<div class="mobile-user-menu">
							<?php if ( has_nav_menu( 'mylisting-user-menu' ) ) : ?>
								<?php wp_nav_menu( [
								    'theme_location' => 'mylisting-user-menu',
								    'container' 	 => false,
								    'depth'     	 => 0,
								    'menu_class'	 => '',
								    'items_wrap' 	 => '<ul class="%2$s">%3$s</ul>'
								] ) ?>
							<?php elseif ( class_exists( 'WooCommerce' ) ) : ?>
								<ul>
									<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
										<?php do_action( "case27/user-menu/{$endpoint}/before" ) ?>
										<li class="user-menu-<?php echo esc_attr( $endpoint ) ?>">
											<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $label ); ?></a>
										</li>
										<?php do_action( "case27/user-menu/{$endpoint}/after" ) ?>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endif ?>

					<?php echo str_replace(
						'<ul class="sub-menu"',
						'<div class="submenu-toggle"><i class="material-icons">arrow_drop_down</i></div><ul class="sub-menu i-dropdown"',
						wp_nav_menu( [
							'echo' => false,
							'theme_location' => 'primary',
							'container' => false,
							'menu_class' => 'main-menu',
							'items_wrap' => '<ul id="%1$s" class="%2$s main-nav">%3$s</ul>'
						]
					) ) ?>

					<div class="mobile-nav-button">
						<?php require locate_template( 'partials/header/call-to-action.php' ) ?>
					</div>

				</div>
				<div class="i-nav-overlay"></div>
			</div>
		</div>
	</div>
</header>

<?php if ( ! $data['blend_to_next_section'] ): ?>
	<div class="c27-top-content-margin"></div>
<?php endif ?>

<?php if ( $data['is_edit_mode'] ): ?>
    <script type="text/javascript">case27_ready_script(jQuery);</script>
<?php endif ?>
