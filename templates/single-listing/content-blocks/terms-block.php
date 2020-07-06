<?php
/**
 * Template for rendering a `terms` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

// Keys = taxonomy name
// Value = taxonomy field name
$taxonomies = array_merge( [
    'job_listing_category' => 'category',
    'case27_job_listing_tags' => 'tags',
    'region' => 'region',
], mylisting_custom_taxonomies( 'slug', 'slug' ) );

$field_key = isset( $taxonomies[ $block->get_prop('taxonomy') ] ) ? $taxonomies[ $block->get_prop('taxonomy') ] : false;

// get the field instance
if ( ! $field_key || ! ( $field = $listing->get_field_object( $field_key ) ) ) {
	return;
}

// get list of terms
$terms = $field->get_value();

// validate
if ( empty( $terms ) || is_wp_error( $terms ) ) {
	return;
}

// format for display
// $formatted_terms = array_filter( array_map( function( $term ) {
// 	if ( ! $term = \MyListing\Src\Term::get( $term ) ) {
// 		return false;
// 	}

// 	return [
// 		'link' => $term->get_link(),
// 		'name' => $term->get_full_name(),
// 		'color' => $term->get_color(),
// 		'text_color' => $term->get_text_color(),
// 		'icon' => $term->get_icon( [ 'background' => false, 'color' => false ] ),
// 	];
// }, $terms ) );
?>

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
	<div class="element">
		<div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
		</div>
		<div class="pf-body">

			<?php if ( $block->get_prop('style') === 'list-block' ): ?>

				<?php
				
				foreach ( $terms as $key => $item ) {
					if ( ! $term = \MyListing\Src\Term::get( $item ) ) {
						continue;
					}

					$item_id = 'li_'.\MyListing\Utils\Random_Id::generate(7);

					\MyListing\Helpers::add_custom_style( ".details-list .{$item_id} a:hover i, .details-list .{$item_id} a:hover .term-icon {
						background-color: {$term->get_color()} !important;
						border-color: {$term->get_color()} !important;
						color: {$term->get_text_color()};
					}" );
				}
				?>

				<ul class="outlined-list details-list social-nav item-count-<?php echo count( $terms ) ?>">

					<?php foreach ( $terms as $item ) :
						if ( ! $term = \MyListing\Src\Term::get( $item ) ) {
							continue;
						}
						?>
						<li>
							<?php
							if ( $item->parent && ( $parent = get_term( $item->parent, $item->taxonomy ) ) ) {
								if ( $parent_object = \MyListing\Src\Term::get( $parent ) ) { ?>
									<a href="<?php echo esc_url( $parent_object->get_link() ) ?>">
				                        <?php echo $parent_object->get_icon( [ 'background' => false, 'color' => false ] ) ?>
										<span><?php echo esc_html( $parent_object->get_name() ) ?></span>
									</a>
								<?php
								}
							}
							?>
							<a href="<?php echo esc_url( $term->get_link() ) ?>">
		                        <?php echo $term->get_icon( [ 'background' => false, 'color' => false ] ) ?>
								<span><?php echo esc_html( $term->get_name() ) ?></span>
							</a>
						</li>
					<?php endforeach ?>

				</ul>

			<?php else: ?>

				<div class="listing-details item-count-<?php echo count( $terms ) ?>">
					<ul>

						<?php foreach ( $terms as $item ) :
							if ( ! $term = \MyListing\Src\Term::get( $item ) ) {
								continue;
							}
							?>
							<li>
								<?php
								if ( $item->parent && ( $parent = get_term( $item->parent, $item->taxonomy ) ) ) {
									if ( $parent_object = \MyListing\Src\Term::get( $parent ) ) { ?>
										<a href="<?php echo esc_url( $parent_object->get_link() ) ?>">
											<span class="cat-icon" style="background-color: <?php echo esc_attr( $parent_object->get_color() ) ?>;">
						                        <?php echo $parent_object->get_icon( [ 'background' => false, 'color' => false ] ) ?>
											</span>
											<span class="category-name"><?php echo esc_html( $parent_object->get_name() ) ?></span>
										</a>
									<?php
									}
								}
								?>

								<a href="<?php echo esc_url( $term->get_link() ) ?>">
									<span class="cat-icon" style="background-color: <?php echo esc_attr( $term->get_color() ) ?>;">
				                        <?php echo $term->get_icon( [ 'background' => false, 'color' => false ] ) ?>
									</span>
									<span class="category-name"><?php echo esc_html( $term->get_name() ) ?></span>
								</a>
							</li>
						<?php endforeach ?>

					</ul>
				</div>

			<?php endif ?>

		</div>
	</div>
</div>
