<?php

/*
* Remove Submenu 
*/

add_action( 'admin_menu', 'mylisting_remove_menu', 999 );

function mylisting_remove_menu() {
    remove_submenu_page( 'case27/tools.php', 'case27-tools-docs' );
}
