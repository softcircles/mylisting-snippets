<?php
/**
 * Single listing cover details template.
 *
 * @since 2.0
 */

$details = [];
$actions = [];

foreach ( (array) $layout['cover_details'] as $detail ) {
    if ( empty( $detail['id'] ) ) {
        $detail['id'] = sprintf( 'cd-%s', substr( md5( json_encode( $detail ) ), 0, 6 ) );
    }

    $detail['content'] = '';
    if ( ! empty( $detail['prefix'] ) ) {
        $detail['content'] .= sprintf( '<span class="prefix">%s</span>', $detail['prefix'] );
    }

    $field_value = $listing->get_field( $detail['field'] );
    if ( ! empty( $field_value ) || in_array( $field_value, [ 0, '0', 0.0 ], true ) ) {
        switch ( $detail['format'] ) {
            case 'number':
                $field_value = number_format_i18n( $field_value );
                break;
            case 'date':
                $field_value = date_i18n( get_option( 'date_format' ), strtotime( $field_value ) );
                break;
            case 'datetime':
                $field_value = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $field_value ) );
                break;
            case 'time':
                $field_value = date_i18n( get_option( 'time_format' ), strtotime( $field_value ) );
                break;
        }
        $detail['content'] .= $field_value;
    }
    if ( ! is_array( $field_value ) ) {
        if ( empty( trim( $field_value ) ) && ! in_array( $field_value, [ 0, '0', 0.0 ], true ) ) {
            continue;
        }
    }

    if ( ! empty( $detail['suffix'] ) ) {
        $detail['content'] .= sprintf( '<span class="suffix">%s</span>', $detail['suffix'] );
    }

    if ( ! empty( $detail['content'] ) || in_array( $detail['content'], [ 0, '0', 0.0 ], true ) ) {
        $details[] = $detail;
    }
}

?>
<div class="col-md-6">
    <div class="listing-main-buttons <?php printf( 'detail-count-%d', count( (array) $layout['cover_actions'] ) + count( $details ) ) ?>">
        <ul>
            <?php foreach ( $details as $detail ): ?>
                <li class="price-or-date">
                    <div class="lmb-label"><?php echo $detail['label'] ?></div>
                    <div class="value"><?php echo $detail['content'] ?></div>
                </li>
            <?php endforeach ?>

            <?php foreach ( $layout['cover_actions'] as $action ):
                if ( empty( $action['id'] ) ) {
                    $action['id'] = sprintf( 'cta-%s', substr( md5( json_encode( $action ) ), 0, 6 ) );
                }

                $action['class'] .= 'lmb-calltoaction';
                $action['original_label'] = $action['label'];
                $action['label'] = do_shortcode( $listing->compile_string( $action['label'] ) );

                $template = sprintf( 'templates/single-listing/quick-actions/%s.php', $action['action'] ); ?>
                <?php if ( locate_template( $template ) ): ?>
                    <?php require locate_template( $template ) ?>
                <?php elseif ( has_action( sprintf( 'mylisting/single/quick-actions/%s', $action['action'] ) ) ): ?>
                    <?php do_action( sprintf( 'mylisting/single/quick-actions/%s', $action['action'] ), $action, $listing ) ?>
                <?php else: ?>
                    <?php // dump($action) ?>
                <?php endif ?>
            <?php endforeach ?>
        </ul>
    </div>
</div>
