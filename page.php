<?php get_header(); the_post(); ?>

<?php the_content() ?>

<?php if ( ! is_front_page() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) :
	$GLOBALS['case27_reviews_allow_rating'] = false; ?>
	<section class="i-section">
		<div class="container">
			<div class="row section-title">
				<h2 class="case27-primary-text"><?php _e( 'Comments', 'my-listing' ) ?></h2>
			</div>
		</div>
		<?php comments_template() ?>
	</section>
<?php endif ?>

<?php get_footer() ?>
