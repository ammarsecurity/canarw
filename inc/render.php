<?php
defined( 'ABSPATH' ) || exit;

function canarw_language_url() {
    $id = get_queried_object_id();
    $path = (string) get_post_meta( $id, '_canarw_source_path', true );
    if ( ! $path && $id ) {
        $uri = trim( (string) get_page_uri( $id ), '/' );
        $path = ( 'en' === $uri ) ? '/en' : ( 0 === strpos( $uri, 'en/' ) ? '/' . $uri : ( $uri ? '/' . $uri : '/' ) );
    }
    $pairs = array(
        '/' => '/en',
        '/members-and-people' => '/en/members-----------',
        '/initiatives' => '/en/initiatives------------',
        '/climate-action' => '/en/about---------',
        '/contact-as' => '/en/about---------',
        '/activates' => '/en/about---------',
    );
    $english = canarw_is_english( $id ) || '/en' === $path || 0 === strpos( $path, '/en/' );
    if ( $english ) {
        $reverse = array_flip( $pairs );
        $target = $reverse[ $path ] ?? '/';
    } else {
        $target = $pairs[ $path ] ?? '/en';
    }
    $url = canarw_permalink_for_source( $target );
    $current = $id ? get_permalink( $id ) : '';
    if ( $current && untrailingslashit( $url ) === untrailingslashit( $current ) ) {
        $url = canarw_permalink_for_source( $english ? '/' : '/en' );
    }
    return $url;
}
function canarw_nav_fallback() {
    $menu = canarw_seed()['menus']['ar'] ?? array();
    echo '<ul class="nav-menu">';
    foreach ( $menu as $item ) {
        if ( in_array( $item['label'], array( 'العربية', 'English' ), true ) ) { continue; }
        echo '<li><a href="' . esc_url( canarw_resolve_url( $item['url'] ) ) . '">' . esc_html( canarw_label_for_path( $item['url'], $item['label'] ) ) . '</a></li>';
    }
    echo '</ul>';
}
function canarw_render_image( $item, $eager = false, $class = '' ) {
    $src = canarw_image_url( $item['image'] ?? '' );
    if ( ! $src ) { return; }
    echo '<figure class="' . esc_attr( 'section-image ' . $class ) . '">';
    if ( ! empty( $item['url'] ) ) { echo '<a href="' . esc_url( canarw_resolve_url( $item['url'] ) ) . '">'; }
    if ( is_numeric( $item['image'] ) ) {
        $size = $eager || 'image/gif' === get_post_mime_type( (int) $item['image'] ) ? 'full' : 'large';
        echo wp_get_attachment_image( (int) $item['image'], $size, false, array( 'alt' => $item['alt'] ?? '', 'loading' => $eager ? 'eager' : 'lazy', 'fetchpriority' => $eager ? 'high' : 'auto' ) );
    } else {
        echo '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $item['alt'] ?? '' ) . '" loading="' . ( $eager ? 'eager' : 'lazy' ) . '"' . ( $eager ? ' fetchpriority="high"' : '' ) . '>';
    }
    if ( ! empty( $item['url'] ) ) { echo '</a>'; }
    if ( ! empty( $item['caption'] ) ) { echo '<figcaption>' . esc_html( $item['caption'] ) . '</figcaption>'; }
    echo '</figure>';
}
function canarw_render_item( $item, $layout = '' ) {
    switch ( $item['type'] ) {
        case 'text':
            echo '<div class="prose section-copy">' . canarw_content_html( $item['html'] ) . '</div>';
            break;
        case 'image':
            canarw_render_image( $item );
            break;
        case 'card':
            echo '<article class="content-card">';
            canarw_render_image( $item );
            echo '<div class="card-copy">';
            if ( ! empty( $item['title'] ) ) { echo '<h3>' . esc_html( $item['title'] ) . '</h3>'; }
            echo '<div class="prose">' . canarw_content_html( $item['html'] ) . '</div>';
            if ( ! empty( $item['url'] ) ) { echo '<a class="text-link" href="' . esc_url( canarw_resolve_url( $item['url'] ) ) . '">' . esc_html( canarw_label( 'اقرأ المزيد ←', 'Read more →' ) ) . '</a>'; }
            echo '</div></article>';
            break;
        case 'link':
            if ( $item['url'] && $item['label'] ) { echo '<div class="section-action"><a class="button" href="' . esc_url( canarw_resolve_url( $item['url'] ) ) . '">' . esc_html( $item['label'] ) . '</a></div>'; }
            break;
        case 'contact':
            canarw_contact_form( $item );
            break;
    }
}
function canarw_render_page( $id ) {
    $sections = canarw_prepare_sections( get_post_meta( $id, '_canarw_sections', true ), $id );
    if ( empty( $sections ) ) {
        echo '<div class="container section-pad empty-page"><span class="eyebrow">CANARW</span><h1>' . esc_html( get_the_title( $id ) ) . '</h1>';
        if ( current_user_can( 'edit_post', $id ) ) { echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=canarw-content&id=' . $id ) ) . '">أضف محتوى لهذه الصفحة</a></p>'; }
        echo '</div>';
        return;
    }
    $has_hero = false;
    foreach ( $sections as $s ) { if ( 'hero' === $s['layout'] && empty( $s['hidden'] ) ) { $has_hero = true; break; } }
    if ( ! $has_hero ) {
        echo '<div class="page-title"><div class="container"><span class="eyebrow">' . esc_html( canarw_public_name() ) . '</span><h1>' . esc_html( get_the_title( $id ) ) . '</h1></div></div>';
    }
    foreach ( $sections as $n => $s ) {
        if ( ! empty( $s['hidden'] ) ) { continue; }
        $layout = $s['layout'];
        echo '<section id="section-' . (int) ( $n + 1 ) . '" class="site-section layout-' . esc_attr( $layout ) . ' tone-' . esc_attr( $s['tone'] ) . '"><div class="container">';
        if ( 'hero' === $layout ) {
            $images = array_values( array_filter( $s['items'], function( $i ) { return 'image' === $i['type']; } ) );
            $extras = array_values( array_filter( $s['items'], function( $i ) { return 'image' !== $i['type']; } ) );
            echo '<div class="hero-grid' . ( $images ? '' : ' hero-no-image' ) . '"><div class="hero-copy">';
            if ( ! empty( $s['kicker'] ) ) { echo '<span class="eyebrow">' . esc_html( $s['kicker'] ) . '</span>'; }
            echo '<h1>';
            foreach ( explode( "\n", $s['title'] ) as $line ) { echo '<span>' . esc_html( $line ) . '</span>'; }
            echo '</h1><div class="prose">' . canarw_content_html( $s['body'] ) . '</div>';
            if ( ! empty( $s['button_label'] ) && ! empty( $s['button_url'] ) ) { echo '<a class="button" href="' . esc_url( canarw_resolve_url( $s['button_url'] ) ) . '">' . esc_html( $s['button_label'] ) . '<span aria-hidden="true"> ↗</span></a>'; }
            foreach ( $extras as $i ) { canarw_render_item( $i, $layout ); }
            echo '</div><div class="hero-visual">';
            foreach ( $images as $i ) { canarw_render_image( $i, true ); }
            echo '<span class="visual-tag"><bdi dir="ltr">CANARW</bdi> / ' . esc_html( canarw_label( 'العمل المناخي', 'CLIMATE ACTION' ) ) . '</span></div></div>';
        } else {
            if ( $s['title'] || $s['body'] || ! empty( $s['kicker'] ) ) {
                echo '<div class="section-heading">';
                if ( ! empty( $s['kicker'] ) ) { echo '<span class="eyebrow">' . esc_html( $s['kicker'] ) . '</span>'; }
                if ( $s['title'] ) { echo '<h2>' . esc_html( $s['title'] ) . '</h2>'; }
                $canarw_body = $s['body'];
                if ( $canarw_body && $s['title'] && trim( wp_strip_all_tags( $canarw_body ) ) === trim( $s['title'] ) ) { $canarw_body = ''; }
                if ( $canarw_body ) { echo '<div class="prose">' . canarw_content_html( $canarw_body ) . '</div>'; }
                echo '</div>';
            }
            if ( 'split' === $layout ) {
                $text = array_filter( $s['items'], function( $i ) { return 'image' !== $i['type']; } );
                $images = array_filter( $s['items'], function( $i ) { return 'image' === $i['type']; } );
                echo '<div class="split-grid' . ( ! $images || ! $text ? ' single-column' : '' ) . '"><div class="split-copy">';
                foreach ( $text as $i ) { canarw_render_item( $i, $layout ); }
                echo '</div><div class="split-visual">';
                foreach ( $images as $i ) { canarw_render_item( $i, $layout ); }
                echo '</div></div>';
            } else {
                echo '<div class="section-items">';
                foreach ( $s['items'] as $i ) { canarw_render_item( $i, $layout ); }
                echo '</div>';
            }
            if ( ! empty( $s['button_label'] ) && ! empty( $s['button_url'] ) ) { echo '<a class="button" href="' . esc_url( canarw_resolve_url( $s['button_url'] ) ) . '">' . esc_html( $s['button_label'] ) . '</a>'; }
        }
        echo '</div></section>';
    }
}
