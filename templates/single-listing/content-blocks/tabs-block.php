<?php
/**
 * Template for rendering a `tabs` block in single listing page.
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

<div class="<?php echo esc_attr( $block->get_wrapper_classes() ) ?>" id="<?php echo esc_attr( $block->get_wrapper_id() ) ?>">
    <div class="element">
        <div class="pf-head">
			<div class="title-style-1">
				<i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
				<h5><?php echo esc_html( $block->get_title() ) ?></h5>
			</div>
        </div>
        <div class="pf-body">
            <div class="tab-element bl-tabs">
                <div class="bl-tabs-menu">
                    <ul class="nav nav-tabs" role="tablist">

                        <?php foreach ( $rows as $key => $row ): ?>
                            <li role="presentation" class="<?php echo $key === 0 ? 'active' : '' ?>">
                                <a
                                    href="#<?php echo esc_attr( $block->get_unique_id().$key ) ?>"
                                    aria-controls="<?php echo esc_attr( $block->get_unique_id().$key ) ?>"
                                    role="tab"
                                    class="tab-switch"
                                ><?php echo esc_html( $row['title'] ) ?></a>
                            </li>
                        <?php endforeach ?>

                    </ul>
                </div>
                <div class="tab-content">

                    <?php foreach ( $rows as $key => $row ): ?>
                        <div role="tabpanel" class="tab-pane fade <?php echo $key === 0 ? 'in active' : '' ?>" id="<?php echo esc_attr( $block->get_unique_id().$key ) ?>">
                            <div class="wp-editor-content">
                                 <?php
                                 $pattern = "#<p>(\s|&nbsp;|</?\s?br\s?/?>)*</?p>#";
                                 echo preg_replace($pattern, '', $row['content'] ) ?>
                            </div>
                        </div>
                    <?php endforeach ?>

                </div>
            </div>
        </div>
    </div>
</div>
