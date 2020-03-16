<?php
/**
 * Template for rendering a `table` block in single listing page.
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
    <div class="element table-block">
        <div class="pf-head">
            <div class="title-style-1">
                <i class="<?php echo esc_attr( $block->get_icon() ) ?>"></i>
                <h5><?php echo esc_html( $block->get_title() ) ?></h5>
            </div>
        </div>
        <div class="pf-body">
            <ul class="extra-details">

                <?php foreach ( $rows as $row ) :

                    $url = preg_match_all('/<a href="(.*)">/', $row['content'], $a );

                    if ( $url ) {
                        $content = $row['content'];
                    } else {
                        $tags = array( "<p>", "</p>" );
                        $row_content = str_replace( $tags, "", $row['content'] );
                        $content = ucwords( trim(preg_replace( "#([^a-z0-9])#i", " ", strtolower( $row_content ) ) ) );
                    }
                    
                ?>
                    <li>
                        <div class="item-attr"><?php echo $row['title'] ?></div>
                        <div class="item-property"><?php echo $content;?></div>
                    </li>
                <?php endforeach ?>

            </ul>
        </div>
    </div>
</div>
