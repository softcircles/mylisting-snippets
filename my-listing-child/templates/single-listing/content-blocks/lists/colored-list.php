<?php
/**
 * Template for rendering a colored list.
 *
 * @since 1.0
 * @var  $items { name, icon, color, text_color, link }
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

uasort( $items, 'sort_by_name' );

function sort_by_name( $a, $b ) {

	if ( $a['name'][0] == $b['name'][0] ) {
		return 0;
	}

	return ( ord( $a['name'][0] ) < ord( $b['name'][0] ) ) ? -1 : 1;
}

?>

<div class="listing-details item-count-<?php echo count( $items ) ?>">
	<ul>

		<?php foreach ( $items as $item ): ?>
			<li>
				<a href="<?php echo esc_url( $item['link'] ) ?>">
					<span class="cat-icon" style="background-color: <?php echo esc_attr( $item['color'] ) ?>;">
                        <?php echo $item['icon'] ?>
					</span>
					<span class="category-name"><?php echo esc_html( $item['name'] ) ?></span>
				</a>
			</li>
		<?php endforeach ?>

	</ul>
</div>
