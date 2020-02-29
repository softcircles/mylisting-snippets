<?php
$data = c27()->merge_options([
	'terms' => [],
	'taxonomy' => 'job_listing_category',
	'template' => 'template_1',
    'overlay_type' => 'gradient',
    'overlay_gradient' => 'gradient1',
    'overlay_solid_color' => 'rgba(0, 0, 0, .5)',
    'columns' => ['lg' => 3, 'md' => 3, 'sm' => 2, 'xs' => 1],
    'container' => 'container-fluid',
], $data);

$term_ids = array_column((array) $data['terms'], 'category_id');
$items = [];
$explore_link = c27()->get_setting( 'general_explore_listings_page' );

if ( $data['taxonomy'] === 'listing_types' ) {
	$types = get_posts( [
		'post_type' => 'case27_listing_type',
		'posts_per_page' => -1,
		'post_status=any',
		'post__in' => $term_ids,
		'orderby' => 'post__in',
	] );

	foreach ( $types as $type ) {
		if ( ! ( $type = \MyListing\Src\Listing_Type::get( $type ) ) ) {
			continue;
		}

		$items[] = [
			'name' => $type->get_plural_name(),
			'link' => add_query_arg( 'type', $type->get_slug(), $explore_link ),
			'image' => $type->get_default_cover(),
			'count' => '',
			'color' => '#f24286',
			'text_color' => '#fff',
			'icon_template_1' => sprintf( '<i class="%s"></i>', esc_attr( $type->get_settings()['icon'] ) ),
			'icon_template_2' => sprintf( '<i class="%s"></i>', esc_attr( $type->get_settings()['icon'] ) ),
		];
	}

} elseif ( taxonomy_exists( $data['taxonomy'] ) ) {
	$terms = (array) get_terms( [
		'taxonomy' => $data['taxonomy'],
		'hide_empty' => false,
		'include' => array_filter( $term_ids ) ? : [-1],
		'orderby' => 'include',
	] );

	if ( is_wp_error( $terms ) ) {
		return;
	}

	foreach ( $terms as $term ) {
		$term = new MyListing\Src\Term( $term );
		$image = $term->get_image();
		$items[] = [
			'name' => $term->get_name(),
			'link' => $term->get_link(),
			'image' => is_array( $image ) ? $image['sizes']['large'] : false,
			'count' => $term->get_count(),
			'color' => $term->get_color(),
			'text_color' => $term->get_text_color(),
			'icon_template_1' => $term->get_icon( [ 'background' => false, 'color' => false ] ),
			'icon_template_2' => $term->get_icon( [ 'background' => false ] ),
		];
	}
}


$itemSize = sprintf( 'col-lg-%1$d col-md-%2$d col-sm-%3$d col-xs-%4$d',
					  12 / absint( $data['columns']['lg'] ), 12 / absint( $data['columns']['md'] ),
					  12 / absint( $data['columns']['sm'] ), 12 / absint( $data['columns']['xs'] ) );
?>

<?php if ( ! $data['template'] || $data['template'] == 'template_1' ): ?>

	<section class="i-section discover-places">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="row section-body">

				<?php foreach ( $items as $item ): ?>

					<div class="<?php echo esc_attr( $itemSize ) ?>">
						<div class="listing-cat" >
							<a href="<?php echo esc_url( $item['link'] ) ?>" target="_blank">
								<div class="overlay <?php echo $data['overlay_type'] == 'gradient' ? esc_attr( $data['overlay_gradient'] ) : '' ?>"
                         			 style="<?php echo $data['overlay_type'] == 'solid_color' ? 'background-color: ' . esc_attr( $data['overlay_solid_color'] ) . '; ' : '' ?>"></div>
								<div class="lc-background" style="<?php echo $item['image'] ? "background-image: url('" . esc_url( $item['image'] ) . "');" : ''; ?>">
								</div>
								<div class="lc-info">
									<h4 class="case27-secondary-text"><?php echo esc_html( $item['name'] ) ?></h4>
									<h6><?php echo esc_html( $item['count'] ) ?></h6>
								</div>
								<div class="lc-icon">
									<?php echo $item['icon_template_1'] ?>
								</div>
							</a>
						</div>
					</div>

				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>

<?php if ($data['template'] == 'template_2'): ?>

	<section class="i-section all-categories">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="row section-body">

				<?php foreach ( $items as $item ): ?>

					<div class="<?php echo esc_attr( $itemSize ) ?> ac-category">
						<div class="cat-card" >
							<a href="<?php echo esc_url( $item['link'] ) ?>" target="_blank">
								<div class="ac-front-side face">
									<div class="hovering-c">
										<span class="cat-icon" style="background-color: <?php echo esc_attr( $item['color'] ) ?>;">
											<?php echo $item['icon_template_2']; ?>
										</span>
										<span class="category-name"><?php echo esc_html( $item['name'] ) ?></span>
									</div>
								</div>
								<div class="ac-back-side face" style="background-color: <?php echo esc_attr( $item['color'] ) ?>;">
									<div class="hovering-c">
										<p style="color: <?php echo esc_attr( $item['text_color'] ) ?>;">
											<?php echo esc_html( $item['count'] ) ?>
										</p>
									</div>
								</div>
							</a>
						</div>
					</div>

				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>

<?php if ($data['template'] == 'template_3'): ?>

	<section class="i-section">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="row">

				<?php foreach ( $items as $item ): ?>

					<div class="<?php echo esc_attr( $itemSize ) ?> car-item">
						<a href="<?php echo esc_url( $item['link'] ) ?>" target="_blank">
							<div class="car-item-container">
								<div class="car-item-img" style="<?php echo $item['image'] ? "background-image: url('" . esc_url( $item['image'] ) . "');" : ''; ?>">
								</div>
								<div class="car-item-details">
									<h3><?php echo esc_html( $item['name'] ) ?></h3>
									<p><?php echo esc_html( $item['count'] ) ?></p>
								</div>
							</div>
						</a>
					</div>

				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>

<?php if ($data['template'] == 'template_4'): ?>

	<section class="i-section">
		<div class="<?php echo esc_attr( $data['container'] ) ?>">
			<div class="regions-featured row">

				<?php foreach ( $items as $item ): ?>

					<div class="<?php echo esc_attr( $itemSize ) ?> one-region">
						<a href="<?php echo esc_url( $item['link'] ) ?>" target="_blank">
							<div class="region-details">
								<h2 class="case27-secondary-text"><?php echo esc_html( $item['name'] ) ?></h2>
								<h3><?php echo esc_html( $item['count'] ) ?></h3>
							</div>
							<div class="region-image-holder">
								<div class="region-image" style="<?php echo $item['image'] ? "background-image: url('" . esc_url( $item['image'] ) . "');" : ''; ?>">
									<div class="overlay"></div>
								</div>
							</div>
						</a>
					</div>

				<?php endforeach ?>

			</div>
		</div>
	</section>

<?php endif ?>
