<?php

if ( ! defined('ABSPATH') ) {
	exit;
}

$preview = $listing->get_preview_options();
if ( empty( $preview['info_fields'] ) ) {
	return;
} ?>

<ul class="lf-contact">
	<?php foreach ( (array) $preview['info_fields'] as $info_field ):
	    if ( empty( $info_field['icon'] ) ) {
	        $info_field['icon'] = '';
	    }

	    $field = $listing->get_field_object( $info_field[ 'show_field' ] );
	    if ( ! $field ) {
	    	continue;
	    }

		if ( $field->get_type() === 'recurring-date' ) { ?>
		    <li>
	            <i class="<?php echo esc_attr( $info_field['icon'] ) ?> sm-icon"></i>
		    	<?php require locate_template( 'templates/single-listing/previews/partials/upcoming-date.php' ) ?>
		    </li>
		<?php } else {
		    if ( ! $listing->has_field( $info_field['show_field'] ) ) {
		    	continue;
		    }
		    print_r( $field->get_type() ); 

		    if ( $field->get_type() === 'location' ) {
		    	$field_value = $listing->get_short_address();
		    } else {
		    	$field_value = $field->get_value();
		    }

			if ( is_array( $field_value ) ) {
		        $field_value = join( ', ', $field_value );
		    }

		    // Escape square brackets so any shortcode added by the listing owner won't be run.
		    $field_value = str_replace( [ "[" , "]" ] , [ "&#91;" , "&#93;" ] , $field_value );

			$GLOBALS['c27_active_shortcode_content'] = $field_value;
		    $field_content = str_replace( '[[field]]', $field_value, do_shortcode( $info_field['label'] ) );
		    if ( ! strlen( $field_content ) ) {
		    	continue;
		    } ?>
				<li>
                    <i class="<?php echo esc_attr( $info_field['icon'] ) ?> sm-icon"></i>
                    <?php if( $field->get_type() == 'texteditor' ): ?>
                    	<?php echo esc_html( wp_trim_words( $field_content, 5, false ) ) ?>
                    <?php else: ?>
	                    <?php echo esc_html( $field_content ) ?>
	                <?php endif; ?>
                </li>
			<?php }
	endforeach; ?>
</ul>
