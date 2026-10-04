<?php
defined( 'ABSPATH' ) || exit;

/** English routes stay LTR even on 404 and search, before a page language exists. */
function canarw_request_is_english() {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    $base = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
    if ( $base && '/' !== $base && 0 === strpos( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    $path = '/' . trim( $path, '/' );
    return '/en' === $path || 0 === strpos( $path, '/en/' );
}
function canarw_normalize_path( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) { return ''; }
    $path = false !== strpos( $value, '://' ) ? (string) wp_parse_url( $value, PHP_URL_PATH ) : $value;
    foreach ( array( '#', '?' ) as $mark ) {
        $cut = strpos( $path, $mark );
        if ( false !== $cut ) { $path = substr( $path, 0, $cut ); }
    }
    $base = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
    if ( $base && '/' !== $base && 0 === strpos( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    $path = '/' . trim( $path, '/' );
    return '/' === $path ? '/' : untrailingslashit( $path );
}
function canarw_plain_text( $text ) {
    $text = function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( (string) $text ) : strip_tags( (string) $text );
    $text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
    $text = str_replace( array( "\r", "\n", "\t" ), ' ', $text );
    $collapsed = preg_replace( '/\s+/u', ' ', $text );
    return trim( is_string( $collapsed ) ? $collapsed : $text );
}
function canarw_menu_labels() {
    return array(
        '/' => array( 'ar' => 'الرئيسية', 'en' => 'Home' ),
        '/en' => array( 'ar' => 'الرئيسية', 'en' => 'Home' ),
        '/members-and-people' => array( 'ar' => 'الأعضاء', 'en' => 'Members' ),
        '/en/members-----------' => array( 'ar' => 'الأعضاء', 'en' => 'Members' ),
        '/climate-action' => array( 'ar' => 'العمل المناخي', 'en' => 'Climate action' ),
        '/contact-as' => array( 'ar' => 'تواصل معنا', 'en' => 'Contact' ),
        '/cop3031' => array( 'ar' => 'COP30 و COP31', 'en' => 'COP30 & COP31' ),
        '/initiatives' => array( 'ar' => 'المبادرات', 'en' => 'Initiatives' ),
        '/en/initiatives------------' => array( 'ar' => 'المبادرات', 'en' => 'Initiatives' ),
        '/activates' => array( 'ar' => 'الأنشطة', 'en' => 'Activities' ),
        '/sdgs' => array( 'ar' => 'أهداف التنمية', 'en' => 'SDGs' ),
        '/network-structure' => array( 'ar' => 'هيكل الشبكة', 'en' => 'Network structure' ),
        '/reports-and-resources' => array( 'ar' => 'التقارير والموارد', 'en' => 'Reports & resources' ),
        '/climate-justice' => array( 'ar' => 'العدالة المناخية', 'en' => 'Climate justice' ),
        '/en/about---------' => array( 'ar' => 'من نحن', 'en' => 'About' ),
        '/en/page' => array( 'ar' => 'المتجر', 'en' => 'Store' ),
    );
}
function canarw_label_for_path( $path, $fallback = '' ) {
    $path = canarw_normalize_path( $path );
    $labels = canarw_menu_labels();
    if ( isset( $labels[ $path ] ) ) { return canarw_is_english() ? $labels[ $path ]['en'] : $labels[ $path ]['ar']; }
    $localized = canarw_localize_plain( $fallback );
    return '' !== $localized ? $localized : $fallback;
}
function canarw_phrase_pairs() {
    return array(
        array( 'الرئيسية', 'Home' ),
        array( 'من نحن', 'About' ),
        array( 'الأعضاء', 'Members' ),
        array( 'المبادرات', 'Initiatives' ),
        array( 'الأنشطة', 'Activities' ),
        array( 'تواصل معنا', 'Contact Us' ),
        array( 'تواصل معنا', 'Get in Touch' ),
        array( 'تواصل', 'Contact' ),
        array( 'المتجر', 'Store' ),
        array( 'العمل المناخي الآن', 'Climate Action Now' ),
        array( 'العمل المناخي الآن', 'CLIMATE ACTION NOW' ),
        array( 'شبكة العمل المناخي — العالم العربي', 'CLIMATE ACTION NETWORK - ARAB WORLD' ),
        array( 'شبكة العمل المناخي — العالم العربي', 'CLIMATE ACTION NETWORK · ARAB WORLD' ),
        array( 'شبكة العمل المناخي — العالم العربي', 'Climate Action Network Arab World' ),
        array( 'مهمتنا', 'Our Mission' ),
        array( 'رؤيتنا', 'Our Vision' ),
        array( 'عملنا', 'Our Work' ),
        array( 'المناصرة', 'Advocacy' ),
        array( 'التعاون', 'Collaboration' ),
        array( 'الطاقة النظيفة', 'Clean Energy' ),
        array( 'أمن المياه', 'Water Security' ),
        array( 'عمل الشباب', 'Youth Action' ),
        array( 'العمل على السياسات', 'Policy Work' ),
        array( 'المشاريع', 'Projects' ),
        array( 'دفع السياسات', 'Policy Push' ),
        array( 'الطاقة المتجددة', 'Renewables' ),
        array( 'بناء القدرات', 'Capacity' ),
        array( 'العدالة', 'Justice' ),
        array( 'التزامنا المناخي', 'Our Climate Commitment' ),
        array( 'مقرنا', 'Our Hub' ),
        array( 'قسم الأنشطة والتدريب', 'About Our New Department' ),
        array( 'خبراء موثوقون', 'Trusted Experts' ),
        array( 'نتائج مثبتة', 'Proven Results' ),
        array( 'شركاء موثوقون', 'Trusted Partners' ),
        array( 'امتداد إقليمي', 'Global Reach' ),
        array( 'انضم إلينا', 'Join Us' ),
        array( 'أرسل الرسالة', 'Send Message' ),
        array( 'عن شبكة العمل المناخي', 'About Climate Action Network' ),
        array( 'المعرض', 'Gallery' ),
        array( 'الهاتف', 'Phone' ),
        array( 'البريد', 'Email' ),
        array( 'العنوان', 'Address' ),
        array( 'ساعات العمل', 'Hours' ),
        array( 'معاً', 'Together' ),
        array( 'هيكل الشبكة', 'Network Structure' ),
        array( 'هيكل شبكتنا', 'Our Network Structure' ),
        array( 'الحوكمة', 'Governance' ),
        array( 'التنسيق', 'Coordination' ),
        array( 'مجموعات العمل', 'Working Groups' ),
        array( 'القرارات', 'Decisions' ),
        array( 'التقارير والموارد', 'Reports & Resources' ),
        array( 'خدماتنا', 'Our services' ),
        array( 'قصتنا', 'Our Story' ),
        array( 'عنوان المشروع', 'Project title' ),
        array( 'عنوان الخدمة', 'Service title' ),
        array( 'موثوق به من قبل الكثيرين', 'Trusted by many' ),
        array( 'أعجبنا بالكفاءة والاحتراف. كانت التجربة واضحة ومنظمة من البداية إلى النهاية.', 'Impressed with the efficiency and professionalism. The entire process was smooth and hassle-free.' ),
        array( 'انضم إلى حركة المجتمع المدني العربي ونحن نوحّد الجهود لمواجهة تغير المناخ. اكتشف كيف يصنع العمل المناخي الجماعي مستقبلاً أكثر استدامة لمجتمعاتنا.', 'Join the movement of Arab civil society as we unite to tackle climate change together. Discover how collective climate action can create a sustainable future for our communities.' ),
        array( 'أطلقنا قسماً للأنشطة وبرامج التدريب لتمكين المجتمع وإشراكه في العمل المناخي.', 'We’ve launched a new department focused on activities and training programs designed to empower and engage our community.' ),
    );
}
function canarw_phrase_dictionary() {
    static $dict = null;
    if ( null !== $dict ) { return $dict; }
    $dict = array();
    foreach ( canarw_phrase_pairs() as $pair ) {
        $entry = array( 'ar' => $pair[0], 'en' => $pair[1] );
        $dict[ canarw_plain_text( $pair[0] ) ] = $entry;
        $dict[ canarw_plain_text( $pair[1] ) ] = $entry;
    }
    return $dict;
}
function canarw_snippet_translation( $key, $lang ) {
    $rows = array(
        array( 'needle' => 'مهمتنا هي توحيد وتنسيق جهود', 'en' => 'Our mission is to unite and coordinate civil society across the Arab world for effective and fair climate action. We influence public policy, build capacity, raise awareness, and support work that cuts climate harm and helps communities adapt. We stand with people, especially the most vulnerable, as they defend their environmental rights and move toward low-carbon, just development.' ),
        array( 'needle' => 'رؤيتنا هي بناء عالم عربي رائد', 'en' => 'Our vision is an Arab region that leads on climate action through justice, sustainability, and the ability to live with a changing climate. People and nature come first in development decisions. Communities drive the shift to renewable energy and a green economy, and civil society helps shape a safer future for the next generations.' ),
        array( 'needle' => 'نعمل في مختلف أنحاء المنطقة العربية على تعزيز العمل المناخي', 'en' => 'Across the Arab region we advance fair, lasting climate action with civil society, decision makers, young people, and academic institutions. We support ambitious climate policy, the shift to renewable energy, public awareness, and a real voice for the communities most affected by climate change.' ),
        array( 'needle' => 'Join the movement of Arab civil society', 'ar' => 'انضم إلى حركة المجتمع المدني العربي ونحن نوحّد الجهود لمواجهة تغير المناخ. اكتشف كيف يصنع العمل المناخي الجماعي مستقبلاً أكثر استدامة لمجتمعاتنا.' ),
        array( 'needle' => 'launched a new department focused on activities', 'ar' => 'أطلقنا قسماً للأنشطة وبرامج التدريب لتمكين المجتمع وإشراكه في العمل المناخي.' ),
    );
    foreach ( $rows as $row ) {
        if ( false !== strpos( $key, $row['needle'] ) && isset( $row[ $lang ] ) ) { return $row[ $lang ]; }
    }
    return null;
}
function canarw_pick_line( $line, $lang ) {
    $line = trim( preg_replace( '/\s+/u', ' ', preg_replace( '/\s*[\(\[](?:EN|AR|English|العربية)[\)\]]/iu', ' ', $line ) ) );
    if ( '' === $line ) { return ''; }
    $arabic = preg_match_all( '/\p{Arabic}/u', $line );
    $latin = preg_match_all( '/\p{Latin}/u', $line );
    $total = $arabic + $latin;
    $mixed = ( $total > 0 && $total <= 80 && $arabic >= 3 && $latin >= 3 ) || ( $arabic >= 12 && $latin >= 12 );
    if ( ! $mixed ) {
        if ( 'ar' === $lang && $arabic >= $latin ) { return $line; }
        if ( 'en' === $lang && $latin > $arabic ) { return $line; }
        if ( 'ar' === $lang && 0 === $arabic && $latin >= 8 ) { return ''; }
        if ( 'en' === $lang && 0 === $latin && $arabic >= 8 ) { return ''; }
        return $line;
    }
    if ( 'ar' === $lang ) {
        $clean = preg_replace( '/\b(?:\p{Latin}*[\p{Ll}]\p{Latin}*|\p{Latin}{7,})\b/u', ' ', $line );
    } else {
        $clean = preg_replace( '/\p{Arabic}+/u', ' ', $line );
    }
    $clean = preg_replace( '/\s+\./u', '', (string) $clean );
    $clean = preg_replace( '/\s+/u', ' ', (string) $clean );
    $clean = preg_replace( '/^[\s|\/·•\-–—:،]+|[\s|\/·•\-–—:،]+$/u', '', (string) $clean );
    $letters = preg_match_all( 'ar' === $lang ? '/\p{Arabic}/u' : '/\p{Latin}/u', (string) $clean );
    return $letters >= 2 ? $clean : '';
}
function canarw_quote_key( $text ) {
    $text = preg_replace( '/^[\"\'“”„«»\s]+|[\"\'“”„«»\s]+$/u', '', (string) $text );
    return canarw_plain_text( is_string( $text ) ? $text : '' );
}
function canarw_localize_plain( $text ) {
    if ( ! is_string( $text ) || '' === trim( $text ) ) { return $text; }
    $lang = canarw_is_english() ? 'en' : 'ar';
    $dict = canarw_phrase_dictionary();
    $key = canarw_quote_key( $text );
    if ( isset( $dict[ $key ][ $lang ] ) ) { return $dict[ $key ][ $lang ]; }
    $snippet = canarw_snippet_translation( canarw_plain_text( $text ), $lang );
    if ( null !== $snippet ) { return $snippet; }
    $lines = preg_split( '/\R/u', $text );
    $chosen = array();
    $seen = array();
    foreach ( $lines as $line ) {
        if ( '' === trim( $line ) ) { continue; }
        $exact = canarw_quote_key( $line );
        if ( isset( $dict[ $exact ][ $lang ] ) ) { $line_text = $dict[ $exact ][ $lang ]; }
        else {
            $picked = canarw_pick_line( $line, $lang );
            if ( '' === $picked ) { continue; }
            $picked_key = canarw_quote_key( $picked );
            $line_text = isset( $dict[ $picked_key ][ $lang ] ) ? $dict[ $picked_key ][ $lang ] : $picked;
        }
        $marker = strtolower( canarw_plain_text( $line_text ) );
        if ( isset( $seen[ $marker ] ) ) { continue; }
        $seen[ $marker ] = true;
        $chosen[] = $line_text;
    }
    $result = trim( implode( "\n", $chosen ) );
    return '' !== $result ? $result : trim( $text );
}
function canarw_localize_html( $html ) {
    if ( ! is_string( $html ) || '' === trim( $html ) ) { return $html; }
    $localized = preg_replace_callback( '/>([^<]+)/u', function ( $matches ) {
        return '>' . canarw_localize_plain( $matches[1] );
    }, $html );
    return is_string( $localized ) ? $localized : $html;
}
function canarw_align_url( $url ) {
    if ( ! is_string( $url ) || '' === $url || '/' !== substr( $url, 0, 1 ) || '//' === substr( $url, 0, 2 ) ) { return $url; }
    if ( canarw_is_english() && '/contact-as' === canarw_normalize_path( $url ) ) {
        $fragment = '';
        $hash = strpos( $url, '#' );
        if ( false !== $hash ) { $fragment = substr( $url, $hash ); }
        return '/en/about---------' . $fragment;
    }
    return $url;
}
function canarw_public_name() {
    return canarw_is_english() ? 'Climate Action Network Arab World' : 'شبكة العمل المناخي — العالم العربي';
}
function canarw_public_tagline() {
    $tagline = trim( (string) canarw_options()['tagline'] );
    $arabic = 'شبكة العمل المناخي — العالم العربي';
    $english = 'Climate Action Network — Arab World';
    if ( canarw_is_english() ) { return ( '' === $tagline || $tagline === $arabic || preg_match( '/\p{Arabic}/u', $tagline ) ) ? $english : $tagline; }
    return ( '' === $tagline || ! preg_match( '/\p{Arabic}/u', $tagline ) ) ? $arabic : $tagline;
}
function canarw_public_location() {
    $location = trim( (string) canarw_options()['location'] );
    if ( '' === $location || 'Arab Region' === $location ) { return canarw_label( 'المنطقة العربية', 'Arab Region' ); }
    return canarw_localize_plain( $location );
}
function canarw_public_footer_text() {
    $text = (string) canarw_options()['footer_text'];
    if ( ! canarw_is_english() && false !== strpos( $text, 'All Rights Reserved' ) ) {
        return '© ' . gmdate( 'Y' ) . ' شبكة العمل المناخي — العالم العربي. جميع الحقوق محفوظة.';
    }
    return $text;
}
function canarw_public_footer_groups() {
    $groups = canarw_options()['footer_groups'];
    if ( canarw_is_english() ) { return $groups; }
    $titles = array(
        'Climate networks & institutions' => 'الشبكات والمؤسسات المناخية',
        'Climate Data & Research' => 'بيانات وأبحاث المناخ',
        'Climate Finance & Education' => 'التمويل والتعليم المناخي',
    );
    foreach ( $groups as &$group ) {
        if ( isset( $titles[ $group['title'] ] ) ) { $group['title'] = $titles[ $group['title'] ]; }
    }
    unset( $group );
    return $groups;
}
function canarw_filter_page_title( $title, $post_id = 0 ) {
    if ( is_admin() || ! $post_id || 'page' !== get_post_type( $post_id ) ) { return $title; }
    $path = get_post_meta( $post_id, '_canarw_source_path', true );
    return $path ? canarw_label_for_path( $path, $title ) : canarw_localize_plain( $title );
}
function canarw_filter_menu_title( $title, $item ) {
    $plain = trim( wp_strip_all_tags( $title ) );
    if ( in_array( $plain, array( 'العربية', 'Arabic', 'English', 'EN', 'الإنجليزية' ), true ) ) { return $plain; }
    $path = '';
    if ( ! empty( $item->object_id ) && isset( $item->object ) && 'page' === $item->object ) {
        $path = (string) get_post_meta( $item->object_id, '_canarw_source_path', true );
    }
    if ( ! $path && ! empty( $item->url ) ) { $path = $item->url; }
    return canarw_label_for_path( $path, $title );
}
function canarw_page_id_for_source( $path ) {
    $pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 1, 'meta_key' => '_canarw_source_path', 'meta_value' => $path, 'no_found_rows' => true, 'suppress_filters' => true ) );
    return $pages ? (int) $pages[0]->ID : 0;
}
function canarw_permalink_for_source( $path ) {
    $id = canarw_page_id_for_source( $path );
    if ( $id ) { return get_permalink( $id ); }
    return canarw_resolve_url( $path );
}
function canarw_filter_menu_link( $atts, $item ) {
    $title = trim( wp_strip_all_tags( $item->title ) );
    if ( in_array( $title, array( 'العربية', 'Arabic' ), true ) ) { $atts['href'] = canarw_permalink_for_source( '/' ); }
    if ( in_array( $title, array( 'English', 'EN', 'الإنجليزية' ), true ) ) { $atts['href'] = canarw_permalink_for_source( '/en' ); }
    $path = '';
    if ( ! empty( $item->object_id ) && isset( $item->object ) && 'page' === $item->object ) {
        $path = (string) get_post_meta( $item->object_id, '_canarw_source_path', true );
    }
    if ( canarw_is_english() && '/' === $path ) { $atts['href'] = canarw_permalink_for_source( '/en' ); }
    return $atts;
}
function canarw_ensure_arabic_front() {
    if ( get_option( 'canarw_front_checked' ) ) { return; }
    $arabic = canarw_page_id_for_source( '/' );
    $english = canarw_page_id_for_source( '/en' );
    if ( ! $arabic || ! $english ) { return; }
    $front = (int) get_option( 'page_on_front' );
    $front_source = $front ? (string) get_post_meta( $front, '_canarw_source_path', true ) : '';
    if ( $front !== $arabic && ( ! $front || $front === $english || '/en' === $front_source ) ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $arabic );
    }
    update_option( 'canarw_front_checked', '1', false );
}
function canarw_persist_home_sections() {
    if ( ! is_admin() || ! current_user_can( 'edit_pages' ) || '1' === get_option( 'canarw_home_extra_v' ) ) { return; }
    $found = false;
    foreach ( array( '/', '/en' ) as $path ) {
        $pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_canarw_source_path', 'meta_value' => $path ) );
        if ( ! $pages ) { continue; }
        $found = true;
        $id = $pages[0]->ID;
        $sections = get_post_meta( $id, '_canarw_sections', true );
        $sections = is_array( $sections ) ? $sections : array();
        $merged = canarw_merge_home_sections( $sections, $id );
        if ( $merged !== $sections ) { update_post_meta( $id, '_canarw_sections', wp_slash( $merged ) ); }
    }
    if ( $found ) { update_option( 'canarw_home_extra_v', '1', false ); }
}
if ( function_exists( 'add_filter' ) ) {
    add_filter( 'the_title', 'canarw_filter_page_title', 10, 2 );
    add_filter( 'nav_menu_item_title', 'canarw_filter_menu_title', 10, 2 );
    add_filter( 'nav_menu_link_attributes', 'canarw_filter_menu_link', 10, 2 );
    add_action( 'admin_init', 'canarw_persist_home_sections' );
    add_action( 'init', 'canarw_ensure_arabic_front', 20 );
}

function canarw_home_card( $title, $html, $image, $alt, $url ) {
    return array( 'type' => 'card', 'title' => $title, 'html' => $html, 'image' => $image, 'alt' => $alt, 'caption' => '', 'url' => $url, 'label' => '', 'button' => '', 'style' => 'primary' );
}
function canarw_home_stat( $title, $html ) {
    return array( 'type' => 'card', 'title' => $title, 'html' => '<p>' . $html . '</p>', 'image' => '', 'alt' => '', 'caption' => '', 'url' => '', 'label' => '', 'button' => '', 'style' => 'primary' );
}
function canarw_home_extra_sections( $english ) {
    if ( $english ) {
        return array(
            array(
                'layout' => 'cards', 'tone' => 'soft', 'hidden' => false, 'kicker' => 'FOCUS', 'title' => 'Focus areas',
                'body' => '<p>Four paths where Arab civil society can move climate action from statements to practice.</p>',
                'button_label' => 'Explore initiatives', 'button_url' => '/en/initiatives------------',
                'items' => array(
                    canarw_home_card( 'Clean Energy', '<p>Solar and wind projects that cut emissions and widen access to clean power.</p>', '50f013d718ac64.jpg', 'People planting trees in a dry landscape at sunrise', '/en/initiatives------------' ),
                    canarw_home_card( 'Water Security', '<p>Practical responses to scarce water in a hotter, drier region.</p>', 'fa9c282fecda61.jpg', 'Solar panels under a clear sky in a rural village', '/en/initiatives------------' ),
                    canarw_home_card( 'Youth Action', '<p>Young people shaping climate decisions, not only attending them.</p>', '1d83574fc37692.jpg', 'A community workshop on water conservation', '/en/initiatives------------' ),
                    canarw_home_card( 'Policy Work', '<p>Stronger climate laws, fair finance, and a united Arab voice.</p>', '3181e066b7a515.jpg', 'Young people holding banners at a climate march', '/en/initiatives------------' ),
                ),
            ),
            array(
                'layout' => 'split', 'tone' => 'light', 'hidden' => false, 'kicker' => 'COP31', 'title' => 'COP31 in Antalya',
                'body' => '<p>Antalya, Türkiye, is preparing to host the UN Climate Change Conference, COP31, from 9 to 20 November 2026. CANARW follows this process so climate justice and community voices stay in the negotiations.</p>',
                'button_label' => 'Our approach', 'button_url' => '/en/about---------',
                'items' => array(
                    array( 'type' => 'image', 'image' => '86a876e2c8624c.jpg', 'alt' => 'A regional gathering connected to the climate negotiations', 'caption' => '', 'url' => '' ),
                ),
            ),
            array(
                'layout' => 'stats', 'tone' => 'soft', 'hidden' => false, 'kicker' => 'IMPACT', 'title' => 'Climate impact',
                'body' => '<p>Figures already published by the network, alongside the work they point to.</p>',
                'button_label' => 'About the network', 'button_url' => '/en/about---------',
                'items' => array(
                    canarw_home_stat( '150+', 'Activities and training programs' ),
                    canarw_home_stat( '15', 'Trusted experts and partners' ),
                    canarw_home_stat( 'COP31', 'A negotiation track the region is preparing for' ),
                    canarw_home_stat( 'SDGs', 'Climate action linked to sustainable development' ),
                ),
            ),
            array(
                'layout' => 'text', 'tone' => 'dark', 'hidden' => false, 'kicker' => 'JOIN', 'title' => 'Join the network',
                'body' => '<p>Whether you are a civil society organization, a researcher, or a young person working for climate justice, there is a place for you in this collective effort.</p>',
                'button_label' => 'Contact us', 'button_url' => '/en/about---------',
                'items' => array(),
            ),
        );
    }
    return array(
        array(
            'layout' => 'cards', 'tone' => 'soft', 'hidden' => false, 'kicker' => 'مجالات العمل', 'title' => 'مجالات التركيز',
            'body' => '<p>أربعة مسارات ينتقل فيها المجتمع المدني العربي بالعمل المناخي من البيان إلى الممارسة.</p>',
            'button_label' => 'استكشف المبادرات', 'button_url' => '/initiatives',
            'items' => array(
                canarw_home_card( 'الطاقة النظيفة', '<p>مشاريع الشمس والرياح التي تخفض الانبعاثات وتوسّع الوصول إلى طاقة أنظف.</p>', '50f013d718ac64.jpg', 'مجموعة تزرع الأشجار في أرض جافة عند الشروق', '/initiatives' ),
                canarw_home_card( 'أمن المياه', '<p>استجابات عملية لندرة المياه في منطقة أحرّ وأجف.</p>', 'fa9c282fecda61.jpg', 'ألواح شمسية تحت سماء صافية في قرية ريفية', '/initiatives' ),
                canarw_home_card( 'عمل الشباب', '<p>شباب يصنعون القرار المناخي، ولا يكتفون بحضور جلساته.</p>', '1d83574fc37692.jpg', 'ورشة مجتمعية حول الحفاظ على المياه', '/initiatives' ),
                canarw_home_card( 'العمل على السياسات', '<p>قوانين مناخية أقوى، وتمويل عادل، وصوت عربي موحّد.</p>', '3181e066b7a515.jpg', 'شباب يرفعون لافتات في مسيرة مناخية', '/initiatives' ),
            ),
        ),
        array(
            'layout' => 'split', 'tone' => 'light', 'hidden' => false, 'kicker' => 'المؤتمر', 'title' => 'COP31 في أنطاليا',
            'body' => '<p>تستعد أنطاليا في تركيا لاستضافة مؤتمر الأمم المتحدة لتغير المناخ COP31 من 9 إلى 20 نوفمبر 2026. تتابع الشبكة هذا المسار لتبقى العدالة المناخية وصوت المجتمعات حاضرة في المفاوضات.</p>',
            'button_label' => 'تفاصيل المؤتمر', 'button_url' => '/cop3031',
            'items' => array(
                array( 'type' => 'image', 'image' => '86a876e2c8624c.jpg', 'alt' => 'محطة من مسار المفاوضات المناخية', 'caption' => '', 'url' => '' ),
            ),
        ),
        array(
            'layout' => 'stats', 'tone' => 'soft', 'hidden' => false, 'kicker' => 'الأثر', 'title' => 'أثر العمل المناخي',
            'body' => '<p>أرقام منشورة على موقع الشبكة، وإلى جانبها العمل الذي تشير إليه.</p>',
            'button_label' => 'الأنشطة والتدريب', 'button_url' => '/activates',
            'items' => array(
                canarw_home_stat( '150+', 'أنشطة وبرامج تدريب' ),
                canarw_home_stat( '15', 'خبراء وشركاء موثوقون' ),
                    canarw_home_stat( 'COP31', 'مسار تفاوض تستعد له المنطقة' ),
                    canarw_home_stat( 'SDGs', 'عمل مناخي مرتبط بأهداف التنمية المستدامة' ),
            ),
        ),
        array(
            'layout' => 'text', 'tone' => 'dark', 'hidden' => false, 'kicker' => 'الدعوة', 'title' => 'انضم إلى الشبكة',
            'body' => '<p>سواء كنت منظمة مجتمع مدني أو باحثاً أو شاباً يعمل من أجل العدالة المناخية، هناك مكان لك في هذا العمل الجماعي.</p>',
            'button_label' => 'تواصل معنا', 'button_url' => '/contact-as',
            'items' => array(),
        ),
    );
}
function canarw_home_quotes( $english ) {
    $people = array(
        array( 'Dr. Sami Al Mabruk', '226ee7a78e0fb2.jpg' ),
        array( 'Mohamed Kamel', '634c5066fb9ef8.jpg' ),
        array( 'Eng. Raad Kurdi', 'df72e0b0ee9b4a.jpg' ),
        array( 'Dr. Rola Al Haji', '83134a22571507.png' ),
    );
    $quote = $english
        ? 'Impressed with the efficiency and professionalism. The entire process was smooth and hassle-free.'
        : 'أعجبنا بالكفاءة والاحتراف. كانت التجربة واضحة ومنظمة من البداية إلى النهاية.';
    $items = array();
    foreach ( $people as $person ) {
        $items[] = canarw_home_card( $person[0], '<p><strong>' . $quote . '</strong></p>', $person[1], $person[0], '' );
    }
    return array(
        'layout' => 'testimonials', 'tone' => 'light', 'hidden' => false,
        'kicker' => $english ? 'VOICES' : 'أصوات',
        'title' => $english ? 'Voices from the network' : 'أصوات من الشبكة',
        'body' => '', 'button_label' => '', 'button_url' => '', 'items' => $items,
    );
}
function canarw_merge_home_sections( $sections, $id ) {
    $path = (string) get_post_meta( $id, '_canarw_source_path', true );
    if ( ! in_array( $path, array( '/', '/en' ), true ) ) { return $sections; }
    $english = canarw_is_english( $id );
    $known = array();
    $has_quotes = false;
    foreach ( $sections as &$section ) {
        if ( ! is_array( $section ) ) { continue; }
        if ( 'testimonials' === ( $section['layout'] ?? '' ) ) {
            $has_quotes = true;
            if ( '' === trim( (string) ( $section['title'] ?? '' ) ) ) {
                $section['title'] = $english ? 'Voices from the network' : 'أصوات من الشبكة';
                if ( '' === trim( (string) ( $section['kicker'] ?? '' ) ) ) { $section['kicker'] = $english ? 'VOICES' : 'أصوات'; }
            }
        }
        $known[] = trim( (string) ( $section['title'] ?? '' ) );
        $known[] = canarw_localize_plain( $section['title'] ?? '' );
    }
    unset( $section );
    if ( ! $has_quotes ) {
        $quotes = canarw_home_quotes( $english );
        $insert_at = count( $sections );
        foreach ( $sections as $index => $section ) {
            if ( in_array( $section['title'] ?? '', array( 'مجالات التركيز', 'Focus areas' ), true ) ) { $insert_at = $index; break; }
        }
        array_splice( $sections, $insert_at, 0, array( $quotes ) );
        $known[] = $quotes['title'];
    }
    foreach ( canarw_home_extra_sections( $english ) as $extra ) {
        if ( in_array( $extra['title'], $known, true ) ) { continue; }
        $sections[] = $extra;
        $known[] = $extra['title'];
    }
    return $sections;
}
function canarw_first_section_image( $sections ) {
    foreach ( $sections as $section ) {
        if ( ! is_array( $section ) ) { continue; }
        foreach ( (array) ( $section['items'] ?? array() ) as $item ) {
            if ( is_array( $item ) && ! empty( $item['image'] ) ) { return $item['image']; }
        }
    }
    return '68ceb2e9a6c26b.jpg';
}
function canarw_curated_home_sections( $english, $image ) {
    if ( $english ) {
        $base = array(
            array(
                'layout' => 'hero', 'tone' => 'dark', 'hidden' => false, 'kicker' => 'CLIMATE ACTION NETWORK · ARAB WORLD',
                'title' => "Climate Action\nNow",
                'body' => '<p>Uniting voices across the Arab world for a greener, fairer future.</p>',
                'button_label' => 'Join us', 'button_url' => '/en/about---------',
                'items' => array( array( 'type' => 'image', 'image' => $image, 'alt' => 'People taking part in climate action', 'caption' => '', 'url' => '' ) ),
            ),
            array(
                'layout' => 'split', 'tone' => 'light', 'hidden' => false, 'kicker' => 'WHO WE ARE',
                'title' => 'Our mission and vision',
                'body' => '<p>CANARW coordinates Arab civil society so climate action is fair, practical, and led with the communities most affected.</p>',
                'button_label' => '', 'button_url' => '',
                'items' => array(
                    array( 'type' => 'text', 'html' => '<h3>Our mission</h3><p>We unite civil society across the Arab world to shape climate policy, build skills, and support a just shift to renewable energy.</p>' ),
                    array( 'type' => 'text', 'html' => '<h3>Our vision</h3><p>An Arab region that protects people and nature, puts climate justice at the center of development, and gives civil society a real voice.</p>' ),
                    array( 'type' => 'image', 'image' => 'fc89f0a367b4f5.jpg', 'alt' => 'People collaborating on climate work', 'caption' => '', 'url' => '' ),
                ),
            ),
            array(
                'layout' => 'cards', 'tone' => 'soft', 'hidden' => false, 'kicker' => 'WHAT WE DO',
                'title' => 'Our work',
                'body' => '<p>Advocacy and collaboration, carried out with organizations across the region.</p>',
                'button_label' => '', 'button_url' => '',
                'items' => array(
                    canarw_home_card( 'Advocacy', '<p>A coordinated Arab voice in climate policy, finance, and the UN climate negotiations.</p>', '70dabaf1531cd9.jpg', 'Industrial landscape', '/en/about---------' ),
                    canarw_home_card( 'Collaboration', '<p>Partnerships among civil society, researchers, youth, and public institutions.</p>', '90466d21bdd1e1.jpg', 'Community tree planting', '/en/initiatives------------' ),
                ),
            ),
        );
    } else {
        $base = array(
            array(
                'layout' => 'hero', 'tone' => 'dark', 'hidden' => false, 'kicker' => 'شبكة العمل المناخي — العالم العربي',
                'title' => "العمل المناخي\nالآن",
                'body' => '<p>لم يعد العمل المناخي خياراً مؤجلاً. منطقتنا تحتاج تحركاً عادلاً يضع المجتمعات الأكثر تأثراً في قلب الحل.</p>',
                'button_label' => 'تعرّف على عملنا', 'button_url' => '/climate-action',
                'items' => array( array( 'type' => 'image', 'image' => $image, 'alt' => 'مشاركة مجتمعية في العمل المناخي', 'caption' => '', 'url' => '' ) ),
            ),
            array(
                'layout' => 'split', 'tone' => 'light', 'hidden' => false, 'kicker' => 'من نحن',
                'title' => 'مهمتنا ورؤيتنا',
                'body' => '<p>تنسّق الشبكة جهود المجتمع المدني العربي ليكون العمل المناخي عادلاً وعملياً، وتقود المجتمعات المتأثرة هذا المسار.</p>',
                'button_label' => '', 'button_url' => '',
                'items' => array(
                    array( 'type' => 'text', 'html' => '<h3>مهمتنا</h3><p>نوحّد منظمات المجتمع المدني في العالم العربي للتأثير في السياسات، وبناء القدرات، ودعم التحول العادل نحو الطاقة المتجددة.</p>' ),
                    array( 'type' => 'text', 'html' => '<h3>رؤيتنا</h3><p>منطقة عربية تحمي الإنسان والطبيعة، وتضع العدالة المناخية في قلب التنمية، وتُبقي للمجتمع المدني صوتاً فاعلاً.</p>' ),
                    array( 'type' => 'image', 'image' => '33b0967f66d96e.jpg', 'alt' => 'مشهد من المنطقة العربية', 'caption' => '', 'url' => '' ),
                ),
            ),
            array(
                'layout' => 'cards', 'tone' => 'soft', 'hidden' => false, 'kicker' => 'ماذا نفعل',
                'title' => 'عملنا',
                'body' => '<p>مناصرة وتعاون مع المنظمات والمؤسسات في مختلف أنحاء المنطقة.</p>',
                'button_label' => '', 'button_url' => '',
                'items' => array(
                    canarw_home_card( 'المناصرة', '<p>صوت عربي منسّق في السياسات المناخية والتمويل ومفاوضات الأمم المتحدة.</p>', '70dabaf1531cd9.jpg', 'مشهد صناعي', '/climate-action' ),
                    canarw_home_card( 'التعاون', '<p>شراكات بين المجتمع المدني والباحثين والشباب والمؤسسات العامة.</p>', '90466d21bdd1e1.jpg', 'مشاركة مجتمعية في زراعة الأشجار', '/initiatives' ),
                ),
            ),
        );
    }
    return array_merge( $base, array( canarw_home_quotes( $english ) ), canarw_home_extra_sections( $english ) );
}
function canarw_prepare_sections( $sections, $id ) {
    $sections = array_values( array_filter( (array) $sections, 'is_array' ) );
    $path = (string) get_post_meta( $id, '_canarw_source_path', true );
    if ( in_array( $path, array( '/', '/en' ), true ) ) {
        return canarw_curated_home_sections( '/en' === $path, canarw_first_section_image( $sections ) );
    }
    $sections = canarw_merge_home_sections( $sections, $id );
    foreach ( $sections as &$section ) {
        $section['title'] = canarw_localize_plain( $section['title'] ?? '' );
        $section['kicker'] = canarw_localize_plain( $section['kicker'] ?? '' );
        $section['button_label'] = canarw_localize_plain( $section['button_label'] ?? '' );
        $section['button_url'] = canarw_align_url( $section['button_url'] ?? '' );
        $section['body'] = canarw_localize_html( $section['body'] ?? '' );
        if ( empty( $section['items'] ) || ! is_array( $section['items'] ) ) { $section['items'] = array(); continue; }
        foreach ( $section['items'] as &$item ) {
            if ( ! is_array( $item ) ) { continue; }
            foreach ( array( 'title', 'label', 'caption', 'alt', 'button' ) as $key ) {
                if ( isset( $item[ $key ] ) && is_string( $item[ $key ] ) ) { $item[ $key ] = canarw_localize_plain( $item[ $key ] ); }
            }
            if ( isset( $item['html'] ) ) { $item['html'] = canarw_localize_html( $item['html'] ); }
            if ( isset( $item['url'] ) ) { $item['url'] = canarw_align_url( $item['url'] ); }
        }
        unset( $item );
    }
    unset( $section );
    return $sections;
}
