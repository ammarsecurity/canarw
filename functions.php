<?php
/** CANARW theme bootstrap. */
defined( 'ABSPATH' ) || exit;
define( 'CANARW_VERSION', '1.1.0' );
foreach ( array( 'core', 'language', 'render', 'admin', 'import', 'contact' ) as $canarw_module ) {
    require_once get_template_directory() . '/inc/' . $canarw_module . '.php';
}
