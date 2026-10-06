<?php
defined( 'ABSPATH' ) || exit;

function canarw_blog_category_defs() {
    return array(
        'network-news' => array( 'أخبار الشبكة', 'Network news', 'مستجدات الشبكة وبياناتها.', 'Updates and statements from the network.' ),
        'climate' => array( 'المناخ', 'Climate', 'قضايا المناخ في المنطقة العربية.', 'Climate issues across the Arab region.' ),
        'initiatives-blog' => array( 'المبادرات', 'Initiatives', 'مبادرات العمل المناخي.', 'Climate initiatives and projects.' ),
        'activities-blog' => array( 'الأنشطة', 'Activities', 'الأنشطة والتدريب.', 'Activities and training.' ),
        'reports-blog' => array( 'التقارير', 'Reports', 'التقارير والموارد.', 'Reports and resources.' ),
    );
}
function canarw_category_name( $term ) {
    if ( ! $term || is_wp_error( $term ) ) { return ''; }
    $defs = canarw_blog_category_defs();
    if ( isset( $defs[ $term->slug ] ) ) { return canarw_label( $defs[ $term->slug ][0], $defs[ $term->slug ][1] ); }
    return $term->name;
}
function canarw_blog_terms() {
    $terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
    if ( is_wp_error( $terms ) ) { return array(); }
    $order = array_keys( canarw_blog_category_defs() );
    usort( $terms, function( $a, $b ) use ( $order ) {
        $ai = array_search( $a->slug, $order, true );
        $bi = array_search( $b->slug, $order, true );
        $ai = false === $ai ? 99 : $ai;
        $bi = false === $bi ? 99 : $bi;
        return $ai - $bi;
    } );
    $result = array();
    foreach ( $terms as $term ) {
        if ( 'uncategorized' === $term->slug ) { continue; }
        $result[] = $term;
    }
    return $result;
}
function canarw_term_url( $term ) {
    $url = get_term_link( $term );
    if ( is_wp_error( $url ) ) { return ''; }
    if ( canarw_is_english() ) { $url = add_query_arg( 'canarw_lang', 'en', $url ); }
    return $url;
}
function canarw_post_url( $post_id ) {
    $url = get_permalink( $post_id );
    if ( canarw_is_english() ) { $url = add_query_arg( 'canarw_lang', 'en', $url ); }
    return $url;
}
function canarw_post_title_text( $post_id ) {
    if ( canarw_is_english() ) {
        $english = get_post_meta( $post_id, '_canarw_title_en', true );
        if ( $english ) { return $english; }
    }
    return get_the_title( $post_id );
}
function canarw_post_excerpt_text( $post_id ) {
    if ( canarw_is_english() ) {
        $english = get_post_meta( $post_id, '_canarw_excerpt_en', true );
        if ( $english ) { return $english; }
    }
    $excerpt = get_post_field( 'post_excerpt', $post_id );
    if ( ! $excerpt ) { $excerpt = get_post_field( 'post_content', $post_id ); }
    return wp_trim_words( canarw_plain_text( $excerpt ), 28 );
}
function canarw_post_image( $post_id ) {
    if ( has_post_thumbnail( $post_id ) ) { return get_the_post_thumbnail_url( $post_id, 'large' ); }
    return canarw_image_url( get_post_meta( $post_id, '_canarw_image', true ) );
}
function canarw_post_category_label( $post_id ) {
    $terms = get_the_category( $post_id );
    if ( ! $terms ) { return ''; }
    return canarw_category_name( $terms[0] );
}
function canarw_news_query_args( $count, $paged = 1 ) {
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $count,
        'paged' => max( 1, (int) $paged ),
        'ignore_sticky_posts' => true,
        'no_found_rows' => false,
    );
    $hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
    if ( $hello ) { $args['post__not_in'] = array( $hello->ID ); }
    return $args;
}
function canarw_render_post_card( $post ) {
    $id = $post->ID;
    $url = canarw_post_url( $id );
    $title = canarw_post_title_text( $id );
    $image = canarw_post_image( $id );
    $category = canarw_post_category_label( $id );
    echo '<article class="post-card">';
    if ( $image ) { echo '<a href="' . esc_url( $url ) . '"><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $title ) . '"></a>'; }
    echo '<div><span class="eyebrow">' . esc_html( trim( $category . ( $category ? ' · ' : '' ) . get_the_date( '', $post ) ) ) . '</span>';
    echo '<h2><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h2>';
    echo '<p>' . esc_html( canarw_post_excerpt_text( $id ) ) . '</p>';
    echo '<a class="text-link" href="' . esc_url( $url ) . '">' . esc_html( canarw_label( 'اقرأ المزيد ←', 'Read more →' ) ) . '</a></div></article>';
}
function canarw_render_news( $count = 10 ) {
    $query = new WP_Query( canarw_news_query_args( $count ) );
    echo '<div class="post-grid">';
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) { $query->the_post(); canarw_render_post_card( get_post() ); }
    } else {
        echo '<p class="news-empty">' . esc_html( canarw_label( 'لا توجد أخبار منشورة بعد.', 'No news published yet.' ) ) . '</p>';
    }
    echo '</div>';
    wp_reset_postdata();
}
function canarw_render_blog_categories() {
    $terms = canarw_blog_terms();
    if ( ! $terms ) { return; }
    echo '<section class="blog-categories"><h2>' . esc_html( canarw_label( 'أقسام المدونة', 'Blog categories' ) ) . '</h2><ul class="blog-cats">';
    foreach ( $terms as $term ) {
        $defs = canarw_blog_category_defs();
        $text = '';
        if ( isset( $defs[ $term->slug ] ) ) { $text = canarw_is_english() ? $defs[ $term->slug ][3] : $defs[ $term->slug ][2]; }
        echo '<li><a class="blog-cat" href="' . esc_url( canarw_term_url( $term ) ) . '"><strong>' . esc_html( canarw_category_name( $term ) ) . '</strong>';
        if ( $text ) { echo '<span class="blog-cat-text">' . esc_html( $text ) . '</span>'; }
        echo '<span class="blog-cat-count">' . esc_html( sprintf( canarw_label( '%d خبر', '%d posts' ), (int) $term->count ) ) . '</span></a></li>';
    }
    echo '</ul></section>';
}
function canarw_render_blog_page() {
    $paged = (int) get_query_var( 'page' );
    if ( $paged < 1 ) { $paged = (int) get_query_var( 'paged' ); }
    $query = new WP_Query( canarw_news_query_args( 10, max( 1, $paged ) ) );
    echo '<div class="page-title"><div class="container"><span class="eyebrow">' . esc_html( canarw_public_name() ) . '</span><h1>' . esc_html( canarw_label( 'المدونة', 'Blog' ) ) . '</h1>';
    echo '<p>' . esc_html( canarw_label( 'أخبار الشبكة ومقالاتها، مرتبة من الأحدث.', 'Network news and articles, newest first.' ) ) . '</p></div></div>';
    echo '<div class="container section-pad">';
    canarw_render_blog_categories();
    echo '<div class="section-heading"><h2>' . esc_html( canarw_label( 'آخر الأخبار', 'Latest news' ) ) . '</h2></div><div class="post-grid">';
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) { $query->the_post(); canarw_render_post_card( get_post() ); }
    } else {
        echo '<p class="news-empty">' . esc_html( canarw_label( 'لا توجد أخبار منشورة بعد.', 'No news published yet.' ) ) . '</p>';
    }
    echo '</div>';
    $links = paginate_links( array(
        'base' => trailingslashit( get_permalink( get_queried_object_id() ) ) . '%_%',
        'format' => 'page/%#%/',
        'total' => (int) $query->max_num_pages,
        'current' => max( 1, $paged ),
        'mid_size' => 1,
        'prev_text' => canarw_label( 'السابق', 'Previous' ),
        'next_text' => canarw_label( 'التالي', 'Next' ),
    ) );
    if ( $links ) { echo '<nav class="blog-pagination" aria-label="' . esc_attr( canarw_label( 'صفحات المدونة', 'Blog pages' ) ) . '">' . wp_kses_post( $links ) . '</nav>'; }
    echo '</div>';
    wp_reset_postdata();
}
function canarw_render_blog_archive() {
    $term = is_category() ? get_queried_object() : null;
    $title = $term ? canarw_category_name( $term ) : ( is_search() ? sprintf( canarw_label( 'نتائج البحث: %s', 'Search: %s' ), get_search_query() ) : canarw_label( 'آخر الأخبار', 'Latest news' ) );
    echo '<div class="page-title"><div class="container"><span class="eyebrow">' . esc_html( canarw_label( 'المدونة', 'Blog' ) ) . '</span><h1>' . esc_html( $title ) . '</h1></div></div>';
    echo '<div class="container section-pad">';
    canarw_render_blog_categories();
    echo '<div class="post-grid">';
    if ( have_posts() ) {
        while ( have_posts() ) { the_post(); canarw_render_post_card( get_post() ); }
    } else {
        echo '<p class="news-empty">' . esc_html( canarw_label( 'لا توجد نتائج حالياً.', 'No results yet.' ) ) . '</p>';
        get_search_form();
    }
    echo '</div>';
    the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => canarw_label( 'السابق', 'Previous' ), 'next_text' => canarw_label( 'التالي', 'Next' ) ) );
    echo '</div>';
}
function canarw_filter_post_title( $title, $post_id = 0 ) {
    if ( is_admin() || ! $post_id || 'post' !== get_post_type( $post_id ) || ! canarw_is_english() ) { return $title; }
    $english = get_post_meta( $post_id, '_canarw_title_en', true );
    return $english ? $english : $title;
}
function canarw_filter_post_content( $content ) {
    if ( is_admin() || ! is_singular( 'post' ) || ! in_the_loop() || ! canarw_is_english() ) { return $content; }
    $english = get_post_meta( get_the_ID(), '_canarw_content_en', true );
    return $english ? wpautop( wp_kses_post( $english ) ) : $content;
}
function canarw_filter_post_excerpt( $excerpt, $post = null ) {
    if ( is_admin() || ! $post || ! canarw_is_english() ) { return $excerpt; }
    $english = get_post_meta( $post->ID, '_canarw_excerpt_en', true );
    return $english ? $english : $excerpt;
}
function canarw_blog_main_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) { return; }
    if ( ! ( $query->is_category() || $query->is_tag() || $query->is_date() || $query->is_author() ) ) { return; }
    $query->set( 'posts_per_page', 10 );
    $hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
    if ( $hello ) { $query->set( 'post__not_in', array( $hello->ID ) ); }
}
function canarw_ensure_blog_page( $path, $title, $language, $parent ) {
    $existing = canarw_page_id_for_source( $path );
    if ( ! $existing ) {
        $by_path = get_page_by_path( trim( $path, '/' ) );
        if ( $by_path ) { $existing = (int) $by_path->ID; }
    }
    if ( $existing ) {
        update_post_meta( $existing, '_canarw_blog', 1 );
        update_post_meta( $existing, '_canarw_managed', 1 );
        if ( ! get_post_meta( $existing, '_canarw_source_path', true ) ) { update_post_meta( $existing, '_canarw_source_path', $path ); }
        if ( ! get_post_meta( $existing, '_canarw_language', true ) ) { update_post_meta( $existing, '_canarw_language', $language ); }
        return (int) $existing;
    }
    $id = wp_insert_post( array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => basename( trim( $path, '/' ) ),
        'post_parent' => (int) $parent,
        'post_content' => '',
        'comment_status' => 'closed',
    ), true );
    if ( is_wp_error( $id ) ) { return 0; }
    update_post_meta( $id, '_canarw_managed', 1 );
    update_post_meta( $id, '_canarw_blog', 1 );
    update_post_meta( $id, '_canarw_source_path', $path );
    update_post_meta( $id, '_canarw_language', $language );
    update_post_meta( $id, '_canarw_sections', array() );
    return (int) $id;
}
function canarw_ensure_blog_terms() {
    foreach ( canarw_blog_category_defs() as $slug => $labels ) {
        $exists = term_exists( $slug, 'category' );
        if ( $exists ) { continue; }
        wp_insert_term( $labels[0], 'category', array( 'slug' => $slug, 'description' => $labels[2] ) );
    }
}
function canarw_ensure_blog_menu( $page_id ) {
    if ( ! $page_id ) { return false; }
    $locations = get_nav_menu_locations();
    $menu_id = ! empty( $locations['primary'] ) ? (int) $locations['primary'] : 0;
    if ( ! $menu_id ) {
        $menu = wp_get_nav_menu_object( 'CANARW AR' );
        $menu_id = $menu ? (int) $menu->term_id : 0;
    }
    if ( ! $menu_id ) { return false; }
    $items = wp_get_nav_menu_items( $menu_id );
    foreach ( (array) $items as $item ) {
        if ( (int) $item->object_id === (int) $page_id ) { return true; }
        if ( in_array( $item->title, array( 'المدونة', 'Blog' ), true ) ) { return true; }
    }
    $result = wp_update_nav_menu_item( $menu_id, 0, array(
        'menu-item-title' => 'المدونة',
        'menu-item-object' => 'page',
        'menu-item-object-id' => $page_id,
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
        'menu-item-position' => 20,
    ) );
    return ! is_wp_error( $result );
}
function canarw_blog_seed_posts() {
    return array(
        array( 'network-news', '68ceb2e9a6c26b.jpg', 'العمل المناخي الآن: صوت المجتمعات في قلب الحل', 'Climate action now: communities at the center', 'لم يعد العمل المناخي خياراً مؤجلاً. الشبكة تجمع المجتمع المدني العربي ليكون التحرك عادلاً وتبقى المجتمعات الأكثر تأثراً في قلب الحل.', 'Climate action can no longer wait. The network brings Arab civil society together so the response stays fair and the most affected communities stay at the center.' ),
        array( 'climate', '86a876e2c8624c.jpg', 'COP31 في أنطاليا على مسار المتابعة', 'COP31 in Antalya stays on the agenda', 'تستعد أنطاليا في تركيا لاستضافة مؤتمر الأمم المتحدة لتغير المناخ من 9 إلى 20 نوفمبر 2026. تتابع الشبكة هذا المسار لتبقى العدالة المناخية حاضرة في المفاوضات.', 'Antalya, Türkiye, is preparing to host the UN Climate Change Conference from 9 to 20 November 2026. The network follows this track so climate justice stays present in the negotiations.' ),
        array( 'initiatives-blog', '50f013d718ac64.jpg', 'الطاقة النظيفة مسار عملي للمنطقة', 'Clean energy is a practical path for the region', 'مشاريع الشمس والرياح تخفض الانبعاثات وتوسّع الوصول إلى طاقة أنظف. المبادرات في هذا المسار تنقل العمل من البيان إلى الممارسة.', 'Solar and wind projects cut emissions and widen access to cleaner power. This is one of the paths that moves climate action from statements into practice.' ),
        array( 'climate', 'fa9c282fecda61.jpg', 'أمن المياه في مناخ أحرّ وأجف', 'Water security in a hotter, drier climate', 'ندرة المياه من أوضح آثار تغير المناخ في المنطقة. العمل المناخي هنا يعني حلولاً عملية تحمي المجتمعات ومواردها.', 'Water scarcity is one of the clearest climate impacts in the region. Climate action here means practical responses that protect communities and their resources.' ),
        array( 'activities-blog', '1d83574fc37692.jpg', 'الشباب شركاء في القرار المناخي', 'Young people as partners in climate decisions', 'عمل الشباب لا يقف عند حضور الجلسات. الشبكة تفسح المجال لأصوات شابة تشارك في صياغة العمل المناخي.', 'Youth action is more than attending meetings. The network makes room for young voices to help shape climate work.' ),
        array( 'activities-blog', '3181e066b7a515.jpg', 'أكثر من 150 نشاطاً وبرنامج تدريب', 'More than 150 activities and training programs', 'من الأرقام المنشورة على موقع الشبكة: أكثر من 150 نشاطاً وبرنامج تدريب، إلى جانب 15 خبيراً وشريكاً موثوقاً.', 'Figures published by the network include more than 150 activities and training programs, alongside 15 trusted experts and partners.' ),
        array( 'reports-blog', '70dabaf1531cd9.jpg', 'المناصرة في السياسات والتمويل والمفاوضات', 'Advocacy on policy, finance, and negotiations', 'تعمل الشبكة على صوت عربي منسّق في السياسات المناخية والتمويل ومفاوضات الأمم المتحدة.', 'The network works for a coordinated Arab voice in climate policy, finance, and the UN negotiations.' ),
        array( 'initiatives-blog', '90466d21bdd1e1.jpg', 'التعاون بين المجتمع المدني والمؤسسات', 'Collaboration across civil society and institutions', 'الشراكات تجمع منظمات المجتمع المدني والباحثين والشباب والمؤسسات العامة حول عمل مناخي مشترك.', 'Partnerships bring civil society, researchers, youth, and public institutions together around shared climate work.' ),
        array( 'reports-blog', '33b0967f66d96e.jpg', 'أهداف التنمية والعمل المناخي معاً', 'Climate action alongside the Sustainable Development Goals', 'تربط الشبكة العمل المناخي بأهداف التنمية المستدامة، حتى لا ينفصل المناخ عن حياة الناس والتنمية.', 'The network links climate action with the Sustainable Development Goals, so climate work stays connected to people’s lives and development.' ),
        array( 'network-news', 'fc89f0a367b4f5.jpg', 'دعوة للانضمام إلى الشبكة', 'An invitation to join the network', 'المنظمات والباحثون والشباب العاملون من أجل العدالة المناخية مدعوون إلى هذا العمل الجماعي.', 'Organizations, researchers, and young people working for climate justice are invited into this collective effort.' ),
    );
}
function canarw_seed_blog_posts() {
    $existing = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => '_canarw_news', 'fields' => 'ids', 'no_found_rows' => true ) );
    if ( $existing ) { return; }
    $now = current_time( 'timestamp' );
    $index = 0;
    foreach ( canarw_blog_seed_posts() as $item ) {
        $post_date = wp_date( 'Y-m-d H:i:s', $now - ( $index * DAY_IN_SECONDS ) );
        $id = wp_insert_post( array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_title' => $item[2],
            'post_excerpt' => $item[4],
            'post_content' => $item[4],
            'post_date' => $post_date,
            'post_date_gmt' => get_gmt_from_date( $post_date ),
            'comment_status' => 'closed',
        ), true );
        $index++;
        if ( is_wp_error( $id ) ) { continue; }
        $term = get_term_by( 'slug', $item[0], 'category' );
        if ( $term ) { wp_set_object_terms( $id, array( (int) $term->term_id ), 'category' ); }
        update_post_meta( $id, '_canarw_news', 1 );
        update_post_meta( $id, '_canarw_image', $item[1] );
        update_post_meta( $id, '_canarw_title_en', $item[3] );
        update_post_meta( $id, '_canarw_excerpt_en', $item[5] );
        update_post_meta( $id, '_canarw_content_en', $item[5] );
    }
}
function canarw_ensure_blog() {
    if ( get_option( 'canarw_blog_ready' ) ) { return; }
    canarw_ensure_blog_terms();
    $arabic = canarw_ensure_blog_page( '/blog', 'المدونة', 'ar', 0 );
    if ( ! $arabic ) { return; }
    canarw_seed_blog_posts();
    $english_home = canarw_page_id_for_source( '/en' );
    if ( ! $english_home ) { return; }
    $english = canarw_ensure_blog_page( '/en/blog', 'Blog', 'en', $english_home );
    if ( ! $english ) { return; }
    $locations = get_nav_menu_locations();
    $menu_expected = ! empty( $locations['primary'] ) || wp_get_nav_menu_object( 'CANARW AR' );
    if ( $menu_expected && ! canarw_ensure_blog_menu( $arabic ) ) { return; }
    update_option( 'canarw_blog_ready', '1', false );
    flush_rewrite_rules( false );
}
add_action( 'init', 'canarw_ensure_blog', 30 );
add_action( 'pre_get_posts', 'canarw_blog_main_query' );
add_filter( 'the_title', 'canarw_filter_post_title', 11, 2 );
add_filter( 'the_content', 'canarw_filter_post_content', 11 );
add_filter( 'get_the_excerpt', 'canarw_filter_post_excerpt', 10, 2 );
