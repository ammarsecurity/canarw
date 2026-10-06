<?php
/** CANARW theme bootstrap. */
defined( 'ABSPATH' ) || exit;
define( 'CANARW_VERSION', '1.1.2' );
foreach ( array( 'core', 'language', 'blog', 'documents', 'render', 'admin', 'import', 'contact' ) as $canarw_module ) {
    require_once get_template_directory() . '/inc/' . $canarw_module . '.php';
}
