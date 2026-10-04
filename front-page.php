<?php defined( 'ABSPATH' ) || exit;
if ( 'page' === get_option( 'show_on_front' ) ) { require get_template_directory() . '/page.php'; }
else { require get_template_directory() . '/index.php'; }
