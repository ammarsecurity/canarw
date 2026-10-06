<?php
defined( 'ABSPATH' ) || exit;

function canarw_register_document_type() {
    register_post_type( 'canarw_document', array(
        'labels' => array(
            'name' => 'وثائق وبحوث',
            'singular_name' => 'وثيقة أو بحث',
            'add_new_item' => 'إضافة وثيقة أو بحث',
            'edit_item' => 'تعديل الوثيقة أو البحث',
        ),
        'public' => true,
        'show_ui' => false,
        'show_in_menu' => false,
        'show_in_rest' => false,
        'exclude_from_search' => false,
        'has_archive' => false,
        'rewrite' => array( 'slug' => 'document', 'with_front' => false ),
        'query_var' => true,
        'capability_type' => 'page',
        'map_meta_cap' => true,
        'supports' => array( 'title', 'excerpt', 'editor' ),
    ) );
}
add_action( 'init', 'canarw_register_document_type', 5 );

function canarw_document_mimes() {
    return array(
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/x-zip-compressed',
        'text/plain',
    );
}
function canarw_document_kind_label( $kind ) {
    return 'research' === $kind ? canarw_label( 'بحث', 'Research' ) : canarw_label( 'وثيقة', 'Document' );
}
function canarw_document_title_text( $post_id ) {
    if ( canarw_is_english() ) {
        $english = get_post_meta( $post_id, '_canarw_title_en', true );
        if ( $english ) { return $english; }
    }
    return get_the_title( $post_id );
}
function canarw_document_text( $post_id, $field ) {
    if ( canarw_is_english() ) {
        $english = get_post_meta( $post_id, '_canarw_' . $field . '_en', true );
        if ( $english ) { return $english; }
    }
    if ( 'excerpt' === $field ) { return get_post_field( 'post_excerpt', $post_id ); }
    return get_post_field( 'post_content', $post_id );
}
function canarw_document_url( $post_id ) {
    $url = get_permalink( $post_id );
    if ( canarw_is_english() ) { $url = add_query_arg( 'canarw_lang', 'en', $url ); }
    return $url;
}
function canarw_document_file( $post_id ) {
    $file_id = (int) get_post_meta( $post_id, '_canarw_doc_file', true );
    if ( ! $file_id || 'attachment' !== get_post_type( $file_id ) ) { return array(); }
    $url = wp_get_attachment_url( $file_id );
    if ( ! $url ) { return array(); }
    $path = get_attached_file( $file_id );
    $type = wp_check_filetype( $path ? $path : $url );
    return array(
        'id' => $file_id,
        'url' => $url,
        'name' => basename( (string) ( $path ? $path : $url ) ),
        'ext' => strtoupper( $type['ext'] ? $type['ext'] : 'FILE' ),
        'size' => ( $path && is_file( $path ) ) ? size_format( filesize( $path ) ) : '',
    );
}
function canarw_document_count( $kind = '' ) {
    $args = array( 'post_type' => 'canarw_document', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids' );
    if ( $kind ) { $args['meta_query'] = array( array( 'key' => '_canarw_doc_kind', 'value' => $kind ) ); }
    $query = new WP_Query( $args );
    return (int) $query->found_posts;
}
function canarw_render_documents_page() {
    $kind = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
    if ( ! in_array( $kind, array( 'document', 'research' ), true ) ) { $kind = ''; }
    $paged = (int) get_query_var( 'page' );
    if ( $paged < 1 ) { $paged = (int) get_query_var( 'paged' ); }
    $args = array(
        'post_type' => 'canarw_document',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'paged' => max( 1, $paged ),
        'meta_key' => '_canarw_doc_year',
        'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
    );
    if ( $kind ) { $args['meta_query'] = array( array( 'key' => '_canarw_doc_kind', 'value' => $kind ) ); }
    $query = new WP_Query( $args );
    $base = get_permalink( get_queried_object_id() );
    echo '<div class="page-title"><div class="container"><span class="eyebrow">' . esc_html( canarw_public_name() ) . '</span><h1>' . esc_html( canarw_label( 'وثائق وبحوث', 'Documents and research' ) ) . '</h1>';
    echo '<p>' . esc_html( canarw_label( 'مكتبة وثائق الشبكة وبحوثها، قابلة للتنزيل والقراءة.', 'The network library of documents and research, ready to read and download.' ) ) . '</p></div></div>';
    echo '<div class="container section-pad"><nav class="doc-filters" aria-label="' . esc_attr( canarw_label( 'تصفية المكتبة', 'Filter the library' ) ) . '">';
    $filters = array( '' => canarw_label( 'الكل', 'All' ), 'document' => canarw_label( 'وثائق', 'Documents' ), 'research' => canarw_label( 'بحوث', 'Research' ) );
    foreach ( $filters as $value => $label ) {
        $url = $value ? add_query_arg( 'type', $value, $base ) : remove_query_arg( 'type', $base );
        $count = canarw_document_count( $value );
        echo '<a class="' . ( $kind === $value ? 'is-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . ' <span>' . (int) $count . '</span></a>';
    }
    echo '</nav><div class="doc-list">';
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) { $query->the_post(); canarw_render_document_row( get_post() ); }
    } else {
        echo '<p class="news-empty">' . esc_html( canarw_label( 'لا توجد وثائق أو بحوث منشورة في هذا القسم بعد.', 'No published documents or research in this section yet.' ) ) . '</p>';
        if ( current_user_can( 'edit_pages' ) ) { echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=canarw-documents&new=1' ) ) . '">' . esc_html( canarw_label( 'أضف أول وثيقة', 'Add the first document' ) ) . '</a></p>'; }
    }
    echo '</div>';
    $links = paginate_links( array(
        'base' => trailingslashit( $base ) . '%_%',
        'format' => 'page/%#%/',
        'total' => (int) $query->max_num_pages,
        'current' => max( 1, $paged ),
        'add_args' => $kind ? array( 'type' => $kind ) : false,
        'mid_size' => 1,
        'prev_text' => canarw_label( 'السابق', 'Previous' ),
        'next_text' => canarw_label( 'التالي', 'Next' ),
    ) );
    if ( $links ) { echo '<nav class="blog-pagination" aria-label="' . esc_attr( canarw_label( 'صفحات المكتبة', 'Library pages' ) ) . '">' . wp_kses_post( $links ) . '</nav>'; }
    echo '</div>';
    wp_reset_postdata();
}
function canarw_render_document_row( $post ) {
    $id = $post->ID;
    $kind = get_post_meta( $id, '_canarw_doc_kind', true );
    $year = get_post_meta( $id, '_canarw_doc_year', true );
    $source = get_post_meta( $id, '_canarw_doc_source', true );
    $file = canarw_document_file( $id );
    $summary = canarw_document_text( $id, 'excerpt' );
    echo '<article class="doc-row"><span class="doc-mark">' . esc_html( $file ? $file['ext'] : canarw_document_kind_label( $kind ) ) . '</span><div>';
    echo '<p class="doc-meta"><span class="doc-kind">' . esc_html( canarw_document_kind_label( $kind ) ) . '</span>';
    if ( $year ) { echo '<span>' . esc_html( $year ) . '</span>'; }
    if ( $source ) { echo '<span>' . esc_html( $source ) . '</span>'; }
    if ( $file && $file['size'] ) { echo '<span>' . esc_html( $file['size'] ) . '</span>'; }
    echo '</p><h2><a href="' . esc_url( canarw_document_url( $id ) ) . '">' . esc_html( canarw_document_title_text( $id ) ) . '</a></h2>';
    if ( $summary ) { echo '<p>' . esc_html( $summary ) . '</p>'; }
    echo '</div><div class="doc-actions">';
    if ( $file ) { echo '<a class="button" href="' . esc_url( $file['url'] ) . '" download>' . esc_html( canarw_label( 'تحميل', 'Download' ) ) . '</a>'; }
    echo '<a class="text-link" href="' . esc_url( canarw_document_url( $id ) ) . '">' . esc_html( canarw_label( 'التفاصيل', 'Details' ) ) . '</a></div></article>';
}
function canarw_render_document_single() {
    while ( have_posts() ) {
        the_post();
        $id = get_the_ID();
        $kind = get_post_meta( $id, '_canarw_doc_kind', true );
        $year = get_post_meta( $id, '_canarw_doc_year', true );
        $source = get_post_meta( $id, '_canarw_doc_source', true );
        $file = canarw_document_file( $id );
        $body = canarw_document_text( $id, 'content' );
        echo '<div class="page-title"><div class="container"><span class="eyebrow">' . esc_html( canarw_document_kind_label( $kind ) ) . '</span><h1>' . esc_html( canarw_document_title_text( $id ) ) . '</h1>';
        echo '<p class="doc-meta">';
        if ( $year ) { echo '<span>' . esc_html( $year ) . '</span>'; }
        if ( $source ) { echo '<span>' . esc_html( $source ) . '</span>'; }
        if ( $file ) { echo '<span>' . esc_html( trim( $file['ext'] . ( $file['size'] ? ' · ' . $file['size'] : '' ) ) ) . '</span>'; }
        echo '</p></div></div><article class="container prose section-pad">';
        if ( $file ) { echo '<p><a class="button" href="' . esc_url( $file['url'] ) . '" download>' . esc_html( canarw_label( 'تحميل الملف', 'Download file' ) ) . '</a></p>'; }
        if ( $body ) { echo wp_kses_post( wpautop( $body ) ); }
        echo '<p><a class="text-link" href="' . esc_url( canarw_permalink_for_source( canarw_is_english() ? '/en/documents' : '/documents' ) ) . '">' . esc_html( canarw_label( 'العودة إلى الوثائق والبحوث', 'Back to documents and research' ) ) . '</a></p></article>';
    }
}
function canarw_filter_document_title( $title, $post_id = 0 ) {
    if ( is_admin() || ! $post_id || 'canarw_document' !== get_post_type( $post_id ) || ! canarw_is_english() ) { return $title; }
    $english = get_post_meta( $post_id, '_canarw_title_en', true );
    return $english ? $english : $title;
}
add_filter( 'the_title', 'canarw_filter_document_title', 12, 2 );

function canarw_ensure_documents_page( $path, $title, $language, $parent ) {
    $existing = canarw_page_id_for_source( $path );
    if ( ! $existing ) {
        $by_path = get_page_by_path( trim( $path, '/' ) );
        if ( $by_path ) { $existing = (int) $by_path->ID; }
    }
    if ( $existing ) {
        update_post_meta( $existing, '_canarw_documents', 1 );
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
    update_post_meta( $id, '_canarw_documents', 1 );
    update_post_meta( $id, '_canarw_source_path', $path );
    update_post_meta( $id, '_canarw_language', $language );
    update_post_meta( $id, '_canarw_sections', array() );
    return (int) $id;
}
function canarw_ensure_documents_menu( $page_id ) {
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
        if ( in_array( $item->title, array( 'وثائق وبحوث', 'Documents and research', 'Documents & research' ), true ) ) { return true; }
    }
    $result = wp_update_nav_menu_item( $menu_id, 0, array(
        'menu-item-title' => 'وثائق وبحوث',
        'menu-item-object' => 'page',
        'menu-item-object-id' => $page_id,
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
        'menu-item-position' => 21,
    ) );
    return ! is_wp_error( $result );
}
function canarw_ensure_documents() {
    if ( get_option( 'canarw_documents_ready' ) ) { return; }
    $arabic = canarw_ensure_documents_page( '/documents', 'وثائق وبحوث', 'ar', 0 );
    if ( ! $arabic ) { return; }
    $english_home = canarw_page_id_for_source( '/en' );
    if ( ! $english_home ) { return; }
    $english = canarw_ensure_documents_page( '/en/documents', 'Documents and research', 'en', $english_home );
    if ( ! $english ) { return; }
    $locations = get_nav_menu_locations();
    $menu_expected = ! empty( $locations['primary'] ) || wp_get_nav_menu_object( 'CANARW AR' );
    if ( $menu_expected && ! canarw_ensure_documents_menu( $arabic ) ) { return; }
    update_option( 'canarw_documents_ready', '1', false );
    flush_rewrite_rules( false );
}
add_action( 'init', 'canarw_ensure_documents', 31 );

function canarw_admin_documents() {
    if ( ! current_user_can( 'edit_pages' ) ) { wp_die( 'ليس لديك صلاحية.' ); }
    $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $new = isset( $_GET['new'] );
    if ( ! $id && ! $new ) {
        canarw_admin_start( 'canarw-documents', 'وثائق وبحوث', 'أضف الوثائق والبحوث، وعدّل عناوينها وملفاتها ونشرها.' );
        if ( isset( $_GET['removed'] ) ) { echo '<div class="ca-alert" role="status">تم حذف العنصر.</div>'; }
        $items = get_posts( array( 'post_type' => 'canarw_document', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'orderby' => 'modified', 'order' => 'DESC' ) );
        echo '<div class="ca-list-toolbar"><a class="ca-button" href="' . esc_url( admin_url( 'admin.php?page=canarw-documents&new=1' ) ) . '">+ إضافة وثيقة أو بحث</a></div><div class="ca-page-list">';
        foreach ( $items as $item ) {
            $kind = get_post_meta( $item->ID, '_canarw_doc_kind', true );
            $file = canarw_document_file( $item->ID );
            echo '<article class="ca-page-row"><span class="ca-page-icon dashicons dashicons-media-document"></span><div class="ca-page-row-copy"><h2>' . esc_html( $item->post_title ) . '</h2><span>' . esc_html( canarw_document_kind_label( $kind ) . ' · ' . get_post_meta( $item->ID, '_canarw_doc_year', true ) . ( $file ? ' · ' . $file['ext'] : '' ) ) . '</span></div><span class="ca-status">' . esc_html( 'publish' === $item->post_status ? 'منشور' : 'مسودة' ) . '</span><a class="ca-button secondary" href="' . esc_url( admin_url( 'admin.php?page=canarw-documents&id=' . $item->ID ) ) . '">تعديل</a>';
            if ( 'publish' === $item->post_status ) { echo '<a class="ca-icon-link" href="' . esc_url( get_permalink( $item ) ) . '" target="_blank" rel="noopener" aria-label="معاينة">↗</a>'; }
            echo '</article>';
        }
        if ( ! $items ) { echo '<section class="ca-panel"><h2>لا توجد وثائق بعد.</h2><p>أضف وثيقة أو بحثاً، ثم انشره ليظهر في صفحة المكتبة.</p></section>'; }
        echo '</div>';
        canarw_admin_end();
        return;
    }
    if ( $id && ( 'canarw_document' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) ) { wp_die( 'ليس لديك صلاحية لتعديل هذا العنصر.' ); }
    $item = $id ? get_post( $id ) : null;
    $file = $id ? canarw_document_file( $id ) : array();
    canarw_admin_start( 'canarw-documents', $item ? 'تعديل: ' . $item->post_title : 'إضافة وثيقة أو بحث', 'العنوان والملخص والملف يظهرون في صفحة المكتبة. الحقول الإنجليزية اختيارية.' );
    echo '<form class="ca-content-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'canarw_save_document_' . $id );
    echo '<input type="hidden" name="action" value="canarw_save_document"><input type="hidden" name="document_id" value="' . (int) $id . '"><div class="ca-editor-toolbar"><a class="ca-button secondary" href="' . esc_url( admin_url( 'admin.php?page=canarw-documents' ) ) . '">→ كل الوثائق والبحوث</a><button class="ca-button" type="submit">حفظ</button></div>';
    echo '<section class="ca-panel"><h2>بيانات الوثيقة أو البحث</h2><div class="ca-fields-grid">';
    canarw_admin_field( 'title', 'العنوان', $item ? $item->post_title : '', 'text', true );
    canarw_admin_field( 'title_en', 'العنوان بالإنجليزية', $id ? get_post_meta( $id, '_canarw_title_en', true ) : '', 'text', false, 'يظهر في النسخة الإنجليزية. اتركه فارغاً لاستخدام العنوان العربي.' );
    echo '<label class="ca-field">النوع<select name="kind"><option value="document"' . selected( $id ? get_post_meta( $id, '_canarw_doc_kind', true ) : 'document', 'document', false ) . '>وثيقة</option><option value="research"' . selected( $id ? get_post_meta( $id, '_canarw_doc_kind', true ) : '', 'research', false ) . '>بحث</option></select></label>';
    canarw_admin_field( 'year', 'سنة الإصدار', $id ? get_post_meta( $id, '_canarw_doc_year', true ) : gmdate( 'Y' ), 'number', true );
    canarw_admin_field( 'source', 'الجهة أو المؤلف', $id ? get_post_meta( $id, '_canarw_doc_source', true ) : '' );
    echo '<label class="ca-field">الحالة<select name="status">';
    foreach ( array( 'publish' => 'منشور', 'draft' => 'مسودة' ) as $status => $label ) { echo '<option value="' . esc_attr( $status ) . '"' . selected( $item ? $item->post_status : 'draft', $status, false ) . '>' . esc_html( $label ) . '</option>'; }
    echo '</select></label></div>';
    canarw_admin_field( 'excerpt', 'الملخص', $item ? $item->post_excerpt : '', 'textarea' );
    canarw_admin_field( 'excerpt_en', 'الملخص بالإنجليزية', $id ? get_post_meta( $id, '_canarw_excerpt_en', true ) : '', 'textarea' );
    canarw_admin_field( 'content', 'الوصف التفصيلي', $item ? $item->post_content : '', 'textarea', false, '', 8 );
    canarw_admin_field( 'content_en', 'الوصف التفصيلي بالإنجليزية', $id ? get_post_meta( $id, '_canarw_content_en', true ) : '', 'textarea', false, '', 8 );
    echo '<div class="ca-field"><span>الملف</span><input type="hidden" name="doc_file" id="ca-doc-file" value="' . esc_attr( $file ? $file['id'] : '' ) . '"><p id="ca-doc-file-name">' . esc_html( $file ? $file['name'] : 'لم يُختر ملف بعد.' ) . '</p><p><button type="button" class="ca-button secondary" id="ca-doc-pick">اختيار ملف</button> <button type="button" class="ca-button secondary" id="ca-doc-clear">إزالة الملف</button></p><small>PDF أو Word أو PowerPoint أو Excel أو ZIP.</small></div>';
    echo '</section><button type="submit" class="ca-button">حفظ</button></form>';
    if ( $id ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ca-restore-form" onsubmit="return confirm(\'حذف هذه الوثيقة أو هذا البحث؟\');">';
        wp_nonce_field( 'canarw_delete_document_' . $id );
        echo '<input type="hidden" name="action" value="canarw_delete_document"><input type="hidden" name="document_id" value="' . (int) $id . '"><button type="submit" class="ca-button danger secondary">حذف</button></form>';
    }
    echo '<script>(function(){const pick=document.getElementById("ca-doc-pick"),clear=document.getElementById("ca-doc-clear"),input=document.getElementById("ca-doc-file"),name=document.getElementById("ca-doc-file-name");if(!pick||!window.wp||!wp.media)return;pick.addEventListener("click",function(){const frame=wp.media({title:"اختر ملف الوثيقة أو البحث",button:{text:"استخدام هذا الملف"},multiple:false});frame.on("select",function(){const file=frame.state().get("selection").first().toJSON();input.value=file.id;name.textContent=file.filename||file.title||"ملف محدد";});frame.open();});clear.addEventListener("click",function(){input.value="";name.textContent="لم يُختر ملف بعد.";});})();</script>';
    canarw_admin_end();
}
function canarw_save_document() {
    $id = absint( $_POST['document_id'] ?? 0 );
    check_admin_referer( 'canarw_save_document_' . $id );
    if ( $id ? ( 'canarw_document' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) : ! current_user_can( 'edit_pages' ) ) { wp_die( 'ليس لديك صلاحية.' ); }
    $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
    if ( ! $title ) { wp_die( 'أدخل العنوان.' ); }
    $status = 'publish' === ( $_POST['status'] ?? '' ) ? 'publish' : 'draft';
    if ( 'publish' === $status && ! current_user_can( 'publish_pages' ) ) { wp_die( 'ليس لديك صلاحية النشر.' ); }
    $kind = 'research' === ( $_POST['kind'] ?? '' ) ? 'research' : 'document';
    $year = absint( $_POST['year'] ?? 0 );
    if ( $year < 1900 || $year > 2100 ) { $year = (int) gmdate( 'Y' ); }
    $file_id = absint( $_POST['doc_file'] ?? 0 );
    if ( $file_id ) {
        $mime = (string) get_post_mime_type( $file_id );
        $checked = wp_check_filetype( (string) get_attached_file( $file_id ) );
        $allowed = canarw_document_mimes();
        $valid = in_array( $mime, $allowed, true ) || in_array( $checked['type'] ?? '', $allowed, true );
        if ( 'attachment' !== get_post_type( $file_id ) || ! $valid ) { wp_die( 'نوع الملف غير مدعوم. استخدم PDF أو Word أو PowerPoint أو Excel أو ZIP.' ); }
    }
    $args = array(
        'post_type' => 'canarw_document',
        'post_title' => $title,
        'post_status' => $status,
        'post_excerpt' => sanitize_textarea_field( wp_unslash( $_POST['excerpt'] ?? '' ) ),
        'post_content' => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
    );
    if ( $id ) { $args['ID'] = $id; }
    $saved = wp_insert_post( wp_slash( $args ), true );
    if ( is_wp_error( $saved ) ) { wp_die( esc_html( $saved->get_error_message() ) ); }
    update_post_meta( $saved, '_canarw_doc_kind', $kind );
    update_post_meta( $saved, '_canarw_doc_year', (string) $year );
    update_post_meta( $saved, '_canarw_doc_source', sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) ) );
    update_post_meta( $saved, '_canarw_doc_file', $file_id );
    update_post_meta( $saved, '_canarw_title_en', sanitize_text_field( wp_unslash( $_POST['title_en'] ?? '' ) ) );
    update_post_meta( $saved, '_canarw_excerpt_en', sanitize_textarea_field( wp_unslash( $_POST['excerpt_en'] ?? '' ) ) );
    update_post_meta( $saved, '_canarw_content_en', wp_kses_post( wp_unslash( $_POST['content_en'] ?? '' ) ) );
    wp_safe_redirect( admin_url( 'admin.php?page=canarw-documents&id=' . $saved . '&saved=1' ) );
    exit;
}
add_action( 'admin_post_canarw_save_document', 'canarw_save_document' );
function canarw_delete_document() {
    $id = absint( $_POST['document_id'] ?? 0 );
    check_admin_referer( 'canarw_delete_document_' . $id );
    if ( 'canarw_document' !== get_post_type( $id ) || ! current_user_can( 'delete_post', $id ) ) { wp_die( 'ليس لديك صلاحية.' ); }
    wp_trash_post( $id );
    wp_safe_redirect( admin_url( 'admin.php?page=canarw-documents&removed=1' ) );
    exit;
}
add_action( 'admin_post_canarw_delete_document', 'canarw_delete_document' );
