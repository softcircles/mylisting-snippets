<?php get_header();

while( have_posts() ) {
	the_post();

	if ( get_post_type() === 'job_listing' ) {

		if ( post_password_required() ) { ?>
			<section id="post-<?php echo esc_attr( get_the_ID() ) ?>" <?php post_class('i-section'); ?>>
				<div class="container">
					<div class="i-section blogpost-section">
						<div class="element">
							<div class="pf-body c27-content-wrapper">
								<?php echo get_the_password_form( get_the_ID() ); ?>
							</div>
						</div>
					</div>
				</div>
			</section>

		<?php
		} else {
			get_template_part( 'templates/listing' );
		}
	} elseif ( get_post_type() === 'elementor_library' ) {
		the_content();
	} else {
		get_template_part( 'templates/content' );
	}

}

get_footer();
