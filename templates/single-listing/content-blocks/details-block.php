<?php
/**
 * Template for rendering a `details` block in single listing page.
 *
 * @since 1.0
 */
if ( ! defined('ABSPATH') ) {
    exit;
}

$rows = $block->get_formatted_rows( $listing );
if ( empty( $rows ) ) {
    return;
}
?>
<?php //print_r($block); exit('im in details block on single listing') ?>
<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
    <div class="element">
        <div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
        </div>
        <div class="pf-body">
            <ul class="outlined-list details-list social-nav item-count-<?php echo count( $rows ) ?>">
                <?php foreach ( $rows as $row ): ?>
                    <li>
                        <?php if( $row['icon'] ) : ?>
                            <i class="<?php echo esc_attr( $row['icon'] ) ?>"></i>
                        <?php endif; ?>
                        <span><?php echo $row['content'] ?></span>
                    </li>
                <?php endforeach ?>

            </ul>
        </div>
    </div>
</div>
