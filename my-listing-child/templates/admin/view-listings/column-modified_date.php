<?php

echo '<div><strong>' . esc_html( get_the_modified_date( get_option( 'date_format' ), $post ) ) . '</strong></div><span>';
