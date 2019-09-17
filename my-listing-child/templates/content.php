<?php global $page, $numpages;
$image = c27()->featured_image(get_the_ID(), 'full');
$categories = c27()->get_terms(get_the_ID(), 'category');

$default_image = '';
?>

<section class="featured-section profile-cover parallax-bg <?php echo !$default_image ? 'profile-cover-no-bg' : '' ?>" style="background-image: url('<?php echo esc_url( $default_image ) ?>')" data-bg="<?php echo esc_url( $default_image ) ?>">
	<div class="overlay"></div>
	<div class="profile-cover-content">
		<div class="container">
			<div class="cover-buttons">
				<ul>

					<?php foreach ( (array) $categories as $category ): ?>
						<a href="<?php echo esc_url( $category['link'] ) ?>">
							<li>
								<div class="buttons button-outlined medium">
									<i class="mi bookmark_border"></i>
									<?php echo esc_html( $category['name'] ) ?>
								</div>
							</li>
						</a>
					<?php endforeach ?>

					<li>
						<div class="event-date inside-date button-secondary">
							<span class="e-month"><?php echo get_the_date('M') ?></span>
							<span class="e-day"><?php echo get_the_date('d') ?></span>
						</div>
					</li>

					<li class="dropdown">
						<?php $links = mylisting()->sharer()->get_links([
							'permalink' => get_permalink(),
							'image' => $image,
							'title' => get_the_title(),
							'description' => get_the_content(),
						] ) ?>
						<a href="#" class="buttons button-outlined icon-only medium" data-toggle="modal" data-target="#social-share-modal">
						   <i class="mi share"></i>
						</a>

						<?php
						/**
						 * Output the markup for the share modal in the site footer,
						 * to prevent layout issues/cutout modal.
						 */
						add_action( 'mylisting/get-footer', function() use ( $links ) { ?>
						    <div id="social-share-modal" class="modal modal-27">
						        <ul class="share-options">
						            <?php foreach ( $links as $link ):
						                if ( empty( trim( $link ) ) ) continue; ?>
						                <li><?php mylisting()->sharer()->print_link( $link ) ?></li>
						            <?php endforeach ?>
						        </ul>
						    </div>
						<?php } ) ?>

						<ul class="i-dropdown share-options dropdown-menu" aria-labelledby="share-links">
							<?php foreach ($links as $link): ?>
								<li><?php mylisting()->sharer()->print_link( $link ) ?></li>
							<?php endforeach ?>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</div>
</section>

<section class="i-section blogpost-section" style="background:#fff;">
	<div class="container">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="row blog-title">
				<div class="col-md-12">
					<?php the_post_thumbnail( 'full' ); ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="row blog-title">
			<div class="col-md-12">
				<h1 class="case27-primary-text"><?php the_title() ?></h1>
			</div>
		</div>
		<div class="row section-body">
			<div class="col-md-12 c27-content-wrapper">
				<?php the_content() ?>
			</div>
		</div>
		<div class="row tags-list">
			<div class="col-md-12">
				<ul class="tags">
					<li><?php the_tags('', '<li>', '') ?></li>
				</ul>
			</div>
		</div>

		<?php if ($numpages > 1): ?>
			<div class="row c27-post-pages">
				<?php if ($page == 1): ?>
					<div class="col-md-6 text-left"></div>
				<?php endif ?>

				<?php wp_link_pages([
					'before'           => '',
					'after'            => '',
					'link_before'      => '',
					'link_after'       => '',
					'next_or_number'   => 'next',
					'separator'        => ' ',
					'nextpagelink'     => '<div class="col-md-6 text-right">Next page</div>',
					'previouspagelink' => '<div class="col-md-6 text-left">Previous page</div>',
					'pagelink'         => '%',
					'echo'             => 1
				]) ?>
			</div>
		<?php endif ?>

		<div class="row c27-post-changer">
			<div class="col-xs-4 col-sm-5 text-left">
				<?php previous_post_link('%link', esc_html__('Previous Post', 'my-listing')) ?>
			</div>
			<div class="col-xs-4 col-sm-2 text-center">
				<?php if ( get_option( 'page_for_posts' ) ): ?>
					<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ) ?>">
						<i class="material-icons mi grid_on"></i>
					</a>
				<?php endif ?>
			</div>
			<div class="col-xs-4 col-sm-5 text-right">
				<?php next_post_link('%link', esc_html__('Next Post', 'my-listing')) ?>
			</div>
		</div>

		<div><?php the_author(); ?></div>
	</div>
</section>

<?php if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) :
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
