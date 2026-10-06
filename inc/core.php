<?php
defined( 'ABSPATH' ) || exit;

function canarw_setup() {
    load_theme_textdomain( 'canarw', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 120, 'flex-height' => true, 'flex-width' => true ) );
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/site.css' );
    register_nav_menus( array( 'primary' => 'القائمة العربية', 'english' => 'English menu', 'footer' => 'قائمة الفوتر' ) );
}
add_action( 'after_setup_theme', 'canarw_setup' );

function canarw_defaults() {
    return array( 'name' => 'Climate Action Network Arab World', 'tagline' => 'شبكة العمل المناخي — العالم العربي', 'logo' => '', 'email' => 'info@canarw.org', 'phone' => '', 'location' => 'Arab Region', 'primary' => '#153b36', 'accent' => '#bcda76', 'footer_text' => '© ' . gmdate( 'Y' ) . ' Climate Action Network Arab World. All Rights Reserved.', 'footer_groups' => array(), 'partner_images' => array(), 'socials' => array(), 'contact_retention_days' => 90 );
}
function canarw_options() { return wp_parse_args( (array) get_option( 'canarw_options', array() ), canarw_defaults() ); }
function canarw_seed() {
    static $seed;
    if ( null === $seed ) {
        $file = get_template_directory() . '/assets/demo/content.json';
        $seed = file_exists( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
    }
    return is_array( $seed ) ? $seed : array();
}
/** Resolve one language for the complete document, including unmanaged pages. */
function canarw_language( $id = 0 ) {
    if ( ! $id ) { $queried = get_queried_object(); $id = $queried instanceof WP_Post ? $queried->ID : 0; }
    if ( $id && get_post( $id ) ) {
        $source = (string) get_post_meta( $id, '_canarw_source_path', true );
        if ( '/en' === $source || 0 === strpos( $source, '/en/' ) ) { return 'en'; }
        if ( '/' === $source ) { return 'ar'; }
        foreach ( array_merge( array( $id ), get_post_ancestors( $id ) ) as $page_id ) {
            $language = get_post_meta( $page_id, '_canarw_language', true );
            if ( in_array( $language, array( 'ar', 'en' ), true ) ) { return $language; }
        }
        $path = get_page_uri( $id );
        if ( $path && preg_match( '~(?:^|/)en(?:/|$)~', $path ) ) { return 'en'; }
    }
    $requested = get_query_var( 'canarw_lang' );
    if ( in_array( $requested, array( 'ar', 'en' ), true ) ) { return $requested; }
    if ( function_exists( 'canarw_request_is_english' ) && canarw_request_is_english() ) { return 'en'; }
    $front_language = get_post_meta( (int) get_option( 'page_on_front' ), '_canarw_language', true );
    return in_array( $front_language, array( 'ar', 'en' ), true ) ? $front_language : 'ar';
}
function canarw_is_english( $id = 0 ) { return 'en' === canarw_language( $id ); }
function canarw_direction( $id = 0 ) { return canarw_is_english( $id ) ? 'ltr' : 'rtl'; }
add_filter( 'query_vars', function( $vars ) { $vars[] = 'canarw_lang'; return $vars; } );
function canarw_label( $ar, $en ) { return canarw_is_english() ? $en : $ar; }
function canarw_image_url( $value ) {
    if ( is_numeric( $value ) && (int) $value > 0 ) { return wp_get_attachment_image_url( (int) $value, 'large' ) ?: ''; }
    if ( is_string( $value ) && preg_match( '/^[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|webp|gif)$/', $value ) && file_exists( get_template_directory() . '/assets/demo/' . $value ) ) {
        return get_template_directory_uri() . '/assets/demo/' . $value;
    }
    return is_string( $value ) ? esc_url_raw( $value ) : '';
}
function canarw_image_value( $value ) {
    if ( is_numeric( $value ) ) { return absint( $value ); }
    if ( ! is_string( $value ) ) { return ''; }
    if ( preg_match( '/^[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|webp|gif)$/', $value ) ) { return $value; }
    return esc_url_raw( $value );
}
function canarw_resolve_url( $url ) {
    if ( ! $url ) { return ''; }
    $parts = wp_parse_url( $url );
    if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
        $path = trim( isset( $parts['path'] ) ? $parts['path'] : $url, '/' );
        $page = $path ? get_page_by_path( $path ) : get_post( (int) get_option( 'page_on_front' ) );
        $resolved = $page ? get_permalink( $page ) : home_url( '/' . $path . '/' );
        if ( ! empty( $parts['fragment'] ) ) { $resolved .= '#' . $parts['fragment']; }
        return $resolved;
    }
    if ( isset( $parts['host'] ) && in_array( $parts['host'], array( 'www.canarw.org', 'canarw.org' ), true ) ) {
        return canarw_resolve_url( isset( $parts['path'] ) ? $parts['path'] : '/' );
    }
    return esc_url_raw( $url );
}
function canarw_rewrite_content_links( $html ) {
    return preg_replace_callback( '/href=([\'"])([^\'"]+)\1/i', function ( $m ) {
        return 'href=' . $m[1] . esc_url( canarw_resolve_url( html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' ) ) ) . $m[1];
    }, $html );
}
/** Preserve deliberate paragraph formatting, while old auto-direction follows the page. */
function canarw_content_html( $html ) {
    $html = wp_kses_post( canarw_rewrite_content_links( $html ) );
    $html = preg_replace( '/\sdir=([\'"])auto\1/i', '', $html );
    $html = do_shortcode( $html );
    $html = preg_replace( '/(<table\b[^>]*>)/i', '<div class="canarw-table-scroll" tabindex="0">$1', $html );
    return str_ireplace( '</table>', '</table></div>', $html );
}
function canarw_sanitize_sections( $sections ) {
    if ( ! is_array( $sections ) ) { return array(); }
    $result = array();
    $layouts = array( 'hero', 'split', 'text', 'editorial', 'cards', 'gallery', 'testimonials', 'contact', 'stats', 'news' );
    foreach ( array_slice( $sections, 0, 60 ) as $s ) {
        if ( ! is_array( $s ) ) { continue; }
        $clean = array(
            'layout' => in_array( $s['layout'] ?? '', $layouts, true ) ? $s['layout'] : 'text',
            'title' => sanitize_textarea_field( $s['title'] ?? '' ),
            'body' => wp_kses_post( $s['body'] ?? '' ),
            'tone' => in_array( $s['tone'] ?? '', array( 'light', 'soft', 'dark' ), true ) ? $s['tone'] : 'light',
            'hidden' => ! empty( $s['hidden'] ),
            'kicker' => sanitize_text_field( $s['kicker'] ?? '' ),
            'button_label' => sanitize_text_field( $s['button_label'] ?? '' ),
            'button_url' => esc_url_raw( $s['button_url'] ?? '' ),
            'items' => array(),
        );
        foreach ( array_slice( (array) ( $s['items'] ?? array() ), 0, 100 ) as $i ) {
            if ( ! is_array( $i ) || ! in_array( $i['type'] ?? '', array( 'text', 'image', 'card', 'link', 'contact' ), true ) ) { continue; }
            $clean['items'][] = array(
                'type' => $i['type'], 'title' => sanitize_text_field( $i['title'] ?? '' ),
                'html' => wp_kses_post( $i['html'] ?? '' ), 'image' => canarw_image_value( $i['image'] ?? '' ),
                'alt' => sanitize_text_field( $i['alt'] ?? '' ), 'caption' => sanitize_text_field( $i['caption'] ?? '' ),
                'url' => esc_url_raw( $i['url'] ?? '' ), 'label' => sanitize_text_field( $i['label'] ?? '' ),
                'button' => sanitize_text_field( $i['button'] ?? '' ), 'style' => 'primary',
            );
        }
        $result[] = $clean;
    }
    return $result;
}
function canarw_fallback_content( $sections ) {
    $html = '';
    foreach ( $sections as $s ) {
        if ( ! empty( $s['hidden'] ) ) { continue; }
        if ( $s['title'] ) { $html .= '<h2>' . esc_html( $s['title'] ) . '</h2>'; }
        $html .= $s['body'];
        foreach ( $s['items'] as $i ) {
            if ( ! empty( $i['title'] ) ) { $html .= '<h3>' . esc_html( $i['title'] ) . '</h3>'; }
            $html .= $i['html'] ?? '';
            if ( ! empty( $i['image'] ) ) { $html .= '<figure><img src="' . esc_url( canarw_image_url( $i['image'] ) ) . '" alt="' . esc_attr( $i['alt'] ?? '' ) . '"></figure>'; }
            if ( ! empty( $i['label'] ) && ! empty( $i['url'] ) ) { $html .= '<p><a href="' . esc_url( canarw_resolve_url( $i['url'] ) ) . '">' . esc_html( $i['label'] ) . '</a></p>'; }
        }
    }
    return wp_kses_post( $html );
}
function canarw_enqueue() {
    wp_enqueue_style( 'canarw', get_template_directory_uri() . '/assets/site.css', array(), CANARW_VERSION );
    wp_enqueue_script( 'canarw', get_template_directory_uri() . '/assets/site.js', array(), CANARW_VERSION, true );
    $o = canarw_options();
    $primary = sanitize_hex_color( $o['primary'] ) ?: '#153b36';
    $accent = sanitize_hex_color( $o['accent'] ) ?: '#bcda76';
    wp_add_inline_style( 'canarw', ':root{--brand:' . $primary . ';--accent:' . $accent . ';}' );
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) { wp_enqueue_script( 'comment-reply' ); }
}
add_action( 'wp_enqueue_scripts', 'canarw_enqueue' );
function canarw_register_meta() {
    register_post_meta( 'page', '_canarw_sections', array( 'type' => 'array', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'canarw_sanitize_sections' ) );
    register_post_meta( 'page', '_canarw_language', array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_key' ) );
}
add_action( 'init', 'canarw_register_meta' );
function canarw_head_meta() {
    if ( is_singular() ) {
        $d = get_post_meta( get_queried_object_id(), '_canarw_description', true );
        if ( $d && ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) ) { echo '<meta name="description" content="' . esc_attr( canarw_localize_plain( $d ) ) . '">' . "\n"; }
    }
}
add_action( 'wp_head', 'canarw_head_meta', 2 );
function canarw_document_attributes( $attributes ) {
    if ( is_admin() ) { return $attributes; }
    $attributes = preg_replace( '/\b(?:lang|xml:lang|dir)=([\'"])[^\'"]*\1\s*/i', '', $attributes );
    return trim( $attributes ) . ' lang="' . canarw_language() . '" dir="' . canarw_direction() . '" translate="no"';
}
add_filter( 'language_attributes', 'canarw_document_attributes' );
function canarw_body_direction( $classes ) {
    if ( is_admin() ) { return $classes; }
    $classes = array_values( array_diff( $classes, array( 'rtl', 'canarw-ar', 'canarw-en' ) ) );
    $classes[] = 'canarw-' . canarw_language();
    if ( ! canarw_is_english() ) { $classes[] = 'rtl'; }
    return $classes;
}
add_filter( 'body_class', 'canarw_body_direction' );
function canarw_document_title( $parts ) {
    $name = canarw_public_name();
    if ( is_front_page() ) { $parts['title'] = $name; }
    else {
        if ( is_singular( 'page' ) ) {
            $path = get_post_meta( get_queried_object_id(), '_canarw_source_path', true );
            $label = canarw_label_for_path( $path, isset( $parts['title'] ) ? $parts['title'] : '' );
            if ( $label ) { $parts['title'] = $label; }
        }
        if ( isset( $parts['site'] ) ) { $parts['site'] = $name; }
    }
    return $parts;
}
add_filter( 'document_title_parts', 'canarw_document_title' );
function canarw_admin_notice() {
    if ( current_user_can( 'manage_options' ) && ! get_option( 'canarw_import_complete' ) ) {
        echo '<div class="notice notice-info"><p><strong>CANARW</strong> — القالب جاهز. <a href="' . esc_url( admin_url( 'admin.php?page=canarw-import' ) ) . '">استورد بيانات الموقع والصور المرفقة</a> أو <a href="' . esc_url( admin_url( 'admin.php?page=canarw' ) ) . '">افتح لوحة الإدارة</a>.</p></div>';
    }
}
add_action( 'admin_notices', 'canarw_admin_notice' );
