<?php global $page, $numpages;
$image = c27()->featured_image(get_the_ID(), 'full');
$categories = c27()->get_terms(get_the_ID(), 'category'); ?>

<section class="featured-section profile-cover parallax-bg <?php echo !$image ? 'profile-cover-no-bg' : '' ?>" style="background-image: url('<?php echo esc_url( $image ) ?>')" data-bg="<?php echo esc_url( $image ) ?>">
	<div class="overlay"></div>
	<div class="profile-cover-content reveal">
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
							<!-- <span class="e-month"><?php //echo get_the_date('M') ?></span>
							<span class="e-day"><?php //echo get_the_date('d') ?></span> -->
							<?php echo get_the_date('d M Y') ?>
						</div>
					</li>

					<li class="dropdown">
						<?php $links = mylisting()->sharer()->get_links([
							'permalink' => get_permalink(),
							'image' => $image,
							'title' => get_the_title(),
							'description' => get_the_content(),
							]) ?>
						<a href="#" class="buttons button-outlined icon-only medium show-dropdown"
						   type="button" id="share-links" data-toggle="dropdown" aria-haspopup="true"
						   aria-expanded="true">
						   <i class="material-icons">redo</i>
						</a>
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
		<div class="row blog-meta reveal">
			<div class="col-md-12">
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 24 ); ?>
				<?php the_author_posts_link(); ?>
			</div>
		</div>

		<div class="row blog-title reveal">
			<div class="col-md-12">
				<h1 class="case27-primary-text"><?php the_title() ?></h1>
			</div>
		</div>
		<div class="row section-body reveal">
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
						<i class="material-icons">grid_on</i>
					</a>
				<?php endif ?>
			</div>
			<div class="col-xs-4 col-sm-5 text-right">
				<?php next_post_link('%link', esc_html__('Next Post', 'my-listing')) ?>
			</div>
		</div>
	</div>
</section>

<?php if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) :
	$GLOBALS['case27_reviews_allow_rating'] = false; ?>
	<section class="i-section">
		<div class="container">
			<div class="row section-title reveal">
				<p><?php _e( 'Comments', 'my-listing' ) ?></p>
				<h2 class="case27-primary-text"><?php _e( 'Add a comment', 'my-listing' ) ?></h2>
			</div>
		</div>
		<?php comments_template() ?>
	</section>
<?php endif ?>
