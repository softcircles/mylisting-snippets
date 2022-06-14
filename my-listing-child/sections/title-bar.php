<?php
	$data = c27()->merge_options([
			'title' => '',
			'show_breadcrumbs' => true,
			'ref' => '',
		], $data);
	wp_print_styles('mylisting-page-heading-widget');
?>

<section class="<?php echo esc_attr( apply_filters( 'case27_title_bar_classes', 'page-head ph-type-2', $data ) ) ?>">
	<div class="ph-details">
		<div class="container">
			<div class="row">

				<div class="col-md-6 col-sm-4 col-xs-12">
					<h1><?php echo $data['title'] ? $data['title'] : the_title() ?></h1>
				</div>

				<?php if ($data['show_breadcrumbs']): ?>

					<div class="col-md-6 col-sm-8 col-xs-6">

						<?php new MyListing\Utils\Breadcrumbs( [
								'before' => '<ul class="page-directory" itemscope itemtype="https://schema.org/BreadcrumbList">',
								'after' => '</ul>',
								'standard' => '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">%s</li>',
								'current' => '<li class="current">%s</li>',
								'link' => '<a href="%s" itemprop="url"><div itemprop="title">%s</div></a>',
							], [
								'show_htfpt' => true,
								'separator' => '',
							], [
								'home' => '<span class="icon-places-home-3"></span>' . __( 'Home', 'my-listing' ),
							]
						) ?>

					</div>

				<?php endif ?>

			</div>
		</div>
	</div>
</section>
