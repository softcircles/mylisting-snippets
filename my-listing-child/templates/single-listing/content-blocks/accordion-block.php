<?php
/**
 * Template for rendering an `accordion` block in single listing page.
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
            <ul class="">
                <div class="panel-group block-accordion <?php echo esc_attr( $block->get_unique_id().'-accordion' ) ?>" role="tablist" aria-multiselectable="true">

                    <?php foreach ( $rows as $key => $row ): ?>
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab">
                                <h4 class="panel-title">
                                    <a
                                        role="button"
                                        data-toggle="collapse"
                                        data-parent=".<?php echo esc_attr( $block->get_unique_id().'-accordion' ) ?>"
                                        aria-expanded="true"
                                        aria-controls="<?php echo esc_attr( $block->get_unique_id().$key ) ?>"
                                        data-target="#<?php echo esc_attr( $block->get_unique_id().$key ) ?>"
                                    ><?php echo esc_html( $row['title'] ) ?></a>
                                </h4>
                            </div>
                            <div id="<?php echo esc_attr( $block->get_unique_id().$key ) ?>" class="panel-collapse collapse in" role="tabpanel">
                                <div class="panel-body wp-editor-content">
                                    <p><?php echo $row['content'] ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>

                </div>
            </ul>
        </div>
    </div>
</div>
