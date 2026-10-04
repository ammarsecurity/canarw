<?php
defined( 'ABSPATH' ) || exit;

function canarw_import_public_state() {
    $s = (array) get_option( 'canarw_import_state', array() );
    return array( 'cursor' => (int) ( $s['cursor'] ?? 0 ), 'total' => (int) ( $s['total'] ?? 0 ), 'done' => ! empty( $s['done'] ), 'label' => $s['label'] ?? '' );
}
function canarw_import_permission() {
    check_ajax_referer( 'canarw_import' );
    if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'ليس لديك صلاحية لاستيراد المحتوى.' ), 403 ); }
}
function canarw_import_begin() {
    canarw_import_permission();$seed = canarw_seed();
    if ( empty( $seed['pages'] ) ) { wp_send_json_error( array( 'message' => 'ملف البيانات الافتراضية مفقود.' ), 400 ); }
    $state = get_option( 'canarw_import_state', array() );
    if ( empty( $state ) || ! empty( $state['done'] ) ) {
        $state = array( 'cursor' => 0, 'total' => count( $seed['media'] ) + count( $seed['pages'] ) + 1, 'done' => false, 'label' => 'تجهيز الصور', 'set_front' => ! empty( $_POST['set_front'] ), 'started' => time() );
        update_option( 'canarw_import_state', $state, false );
    }
    wp_send_json_success( canarw_import_public_state() );
}
add_action( 'wp_ajax_canarw_import_begin', 'canarw_import_begin' );
function canarw_import_asset( $asset ) {
    $map = (array) get_option( 'canarw_media_map', array() );$file = $asset['file'];
    if ( ! empty( $map[ $file ] ) && 'attachment' === get_post_type( (int) $map[ $file ] ) && is_file( get_attached_file( $map[ $file ] ) ) ) { return (int) $map[ $file ]; }
    $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'meta_key' => '_canarw_asset', 'meta_value' => $file ) );
    if ( $existing && is_file( get_attached_file( $existing[0]->ID ) ) ) { $map[ $file ] = $existing[0]->ID;update_option( 'canarw_media_map', $map, false );return $existing[0]->ID; }
    $base = realpath( get_template_directory() . '/assets/demo' );$source = realpath( get_template_directory() . '/assets/demo/' . basename( $file ) );
    if ( ! $source || 0 !== strpos( $source, $base . DIRECTORY_SEPARATOR ) ) { return new WP_Error( 'missing_asset', 'الصورة المرفقة مفقودة: ' . $file ); }
    require_once ABSPATH . 'wp-admin/includes/file.php';require_once ABSPATH . 'wp-admin/includes/image.php';require_once ABSPATH . 'wp-admin/includes/media.php';
    $temp = wp_tempnam( $file );
    if ( ! $temp || ! copy( $source, $temp ) ) { return new WP_Error( 'copy_failed', 'تعذر تجهيز الصورة. تحقق من صلاحيات مجلد الملفات المؤقتة.' ); }
    $upload_file = array( 'name' => basename( $file ), 'tmp_name' => $temp, 'error' => 0, 'size' => filesize( $temp ) );
    $upload = wp_handle_sideload( $upload_file, array( 'test_form' => false ) );
    if ( isset( $upload['error'] ) ) { @unlink( $temp );return new WP_Error( 'upload_failed', $upload['error'] ); }
    $attachment = wp_insert_attachment( array( 'post_title' => sanitize_text_field( $asset['title'] ?? pathinfo( $file, PATHINFO_FILENAME ) ), 'post_mime_type' => $upload['type'], 'post_status' => 'inherit' ), $upload['file'], 0, true );
    if ( is_wp_error( $attachment ) ) { @unlink( $upload['file'] );return $attachment; }
    update_post_meta( $attachment, '_canarw_asset', $file );update_post_meta( $attachment, '_canarw_source', esc_url_raw( $asset['source'] ) );
    if ( ! empty( $asset['alt'] ) ) { update_post_meta( $attachment, '_wp_attachment_image_alt', sanitize_text_field( $asset['alt'] ) ); }
    $metadata = wp_generate_attachment_metadata( $attachment, $upload['file'] );
    if ( is_array( $metadata ) ) { wp_update_attachment_metadata( $attachment, $metadata ); }
    $map[ $file ] = $attachment;update_option( 'canarw_media_map', $map, false );return $attachment;
}
function canarw_import_map_images( $sections, $map ) {
    foreach ( $sections as &$section ) {
        foreach ( $section['items'] as &$item ) {
            if ( ! empty( $item['image'] ) && isset( $map[ $item['image'] ] ) ) { $item['image'] = (int) $map[ $item['image'] ]; }
        }unset( $item );
    }unset( $section );return $sections;
}
function canarw_import_page( $page ) {
    $map = (array) get_option( 'canarw_page_map', array() );$path = $page['path'];
    $existing = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ), 'numberposts' => 1, 'meta_key' => '_canarw_source_path', 'meta_value' => $path ) );
    if ( $existing ) { $map[ $path ] = $existing[0]->ID;update_option( 'canarw_page_map', $map, false );return $existing[0]->ID; }
    $slug = '/' === $path ? 'canarw-home' : trim( $path, '/' );
    $by_path = get_page_by_path( $slug );
    if ( $by_path ) { $map[ $path ] = $by_path->ID;update_option( 'canarw_page_map', $map, false );return $by_path->ID; }
    $parent = 0;
    if ( 0 === strpos( $path, '/en/' ) ) { $parent = (int) ( $map['/en'] ?? 0 );$slug = basename( $path ); }
    $sections = canarw_sanitize_sections( canarw_import_map_images( $page['sections'], (array) get_option( 'canarw_media_map', array() ) ) );
    $id = wp_insert_post( wp_slash( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page['title'], 'post_name' => $slug, 'post_parent' => $parent, 'post_content' => canarw_fallback_content( $sections ), 'post_excerpt' => $page['description'], 'comment_status' => 'closed' ) ), true );
    if ( is_wp_error( $id ) ) { return $id; }
    update_post_meta( $id, '_canarw_managed', 1 );update_post_meta( $id, '_canarw_source_path', $path );update_post_meta( $id, '_canarw_language', $page['language'] );update_post_meta( $id, '_canarw_description', $page['description'] );update_post_meta( $id, '_canarw_sections', wp_slash( $sections ) );
    foreach ( $sections as $s ) { foreach ( $s['items'] as $item ) { if ( ! empty( $item['image'] ) && is_numeric( $item['image'] ) ) { set_post_thumbnail( $id, (int) $item['image'] );break 2; } } }
    $map[ $path ] = $id;update_option( 'canarw_page_map', $map, false );return $id;
}
function canarw_import_finish( $set_front ) {
    $seed = canarw_seed();$map = (array) get_option( 'canarw_page_map', array() );$media = (array) get_option( 'canarw_media_map', array() );
    if ( ! get_option( 'canarw_options', false ) ) {
        $o = wp_parse_args( $seed['options'], canarw_defaults() );$o['logo'] = (int) ( $media[ $o['logo'] ] ?? 0 );
        $o['partner_images'] = array_values( array_filter( array_map( function( $f ) use ( $media ) { return $media[ $f ] ?? 0; }, $o['partner_images'] ) ) );
        update_option( 'canarw_options', $o, false );
    }
    $locations = (array) get_theme_mod( 'nav_menu_locations', array() );$menu_map = (array) get_option( 'canarw_menu_map', array() );
    foreach ( array( 'ar' => 'primary', 'en' => 'english' ) as $lang => $location ) {
        $name = 'CANARW ' . strtoupper( $lang );$menu = wp_get_nav_menu_object( $name );
        $menu_id = $menu ? $menu->term_id : wp_create_nav_menu( $name );
        if ( is_wp_error( $menu_id ) ) { return $menu_id; }
        if ( ! wp_get_nav_menu_items( $menu_id ) ) {
            $position = 0;
            foreach ( $seed['menus'][ $lang ] as $item ) {
                if ( in_array( $item['label'], array( 'العربية', 'English' ), true ) ) { continue; }
                $id = $map[ $item['url'] ] ?? 0;
                $args = array( 'menu-item-title' => $item['label'], 'menu-item-status' => 'publish', 'menu-item-position' => ++$position, 'menu-item-type' => $id ? 'post_type' : 'custom', 'menu-item-object' => $id ? 'page' : 'custom', 'menu-item-object-id' => $id, 'menu-item-url' => $id ? '' : canarw_resolve_url( $item['url'] ) );
                $result = wp_update_nav_menu_item( $menu_id, 0, $args );if ( is_wp_error( $result ) ) { return $result; }
            }
        }
        $menu_map[ $location ] = $menu_id;
        if ( $set_front ) { $locations[ $location ] = $menu_id; }
    }
    update_option( 'canarw_menu_map', $menu_map, false );
    if ( $set_front ) {
        set_theme_mod( 'nav_menu_locations', $locations );
        if ( ! empty( $map['/'] ) ) { update_option( 'page_on_front', $map['/'] );update_option( 'show_on_front', 'page' ); }
    }
    update_option( 'canarw_import_complete', gmdate( 'c' ), false );flush_rewrite_rules( false );return true;
}
function canarw_import_step() {
    canarw_import_permission();
    $lock = get_option( 'canarw_import_lock', 0 );
    if ( $lock && (float) $lock < microtime( true ) - 120 ) { delete_option( 'canarw_import_lock' ); }
    if ( ! add_option( 'canarw_import_lock', microtime( true ), '', false ) ) { wp_send_json_error( array( 'message' => 'يوجد استيراد قيد التنفيذ. انتظر لحظات ثم استأنف.' ), 409 ); }
    $state = (array) get_option( 'canarw_import_state', array() );$seed = canarw_seed();
    if ( empty( $state['total'] ) ) { delete_option( 'canarw_import_lock' );wp_send_json_error( array( 'message' => 'ابدأ الاستيراد أولاً.' ), 400 ); }
    if ( ! empty( $state['done'] ) ) { delete_option( 'canarw_import_lock' );wp_send_json_success( canarw_import_public_state() ); }
    $start = microtime( true );$count = count( $seed['media'] );$pages = count( $seed['pages'] );
    try {
        for ( $batch = 0; $batch < 3 && $state['cursor'] < $state['total']; $batch++ ) {
            $cursor = (int) $state['cursor'];
            if ( $cursor < $count ) { $result = canarw_import_asset( $seed['media'][ $cursor ] );$state['label'] = 'الصور'; }
            elseif ( $cursor < $count + $pages ) { $page = $seed['pages'][ $cursor - $count ];$result = canarw_import_page( $page );$state['label'] = $page['title']; }
            else { $result = canarw_import_finish( ! empty( $state['set_front'] ) );$state['done'] = true;$state['label'] = 'اكتمل'; }
            if ( is_wp_error( $result ) ) { delete_option( 'canarw_import_lock' );wp_send_json_error( array( 'message' => $result->get_error_message() ) ); }
            $state['cursor']++;update_option( 'canarw_import_state', $state, false );
            if ( microtime( true ) - $start > 12 ) { break; }
        }
    } catch ( Throwable $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( 'CANARW import: ' . $e->getMessage() ); }
        delete_option( 'canarw_import_lock' );wp_send_json_error( array( 'message' => 'توقف الاستيراد مؤقتاً. تحقق من مساحة التخزين وصلاحيات رفع الصور، ثم استأنف.' ) );
    }
    delete_option( 'canarw_import_lock' );wp_send_json_success( canarw_import_public_state() );
}
add_action( 'wp_ajax_canarw_import_step', 'canarw_import_step' );
