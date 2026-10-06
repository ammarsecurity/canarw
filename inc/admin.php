<?php
defined( 'ABSPATH' ) || exit;

function canarw_admin_menu() {
    add_menu_page( 'CANARW', 'CANARW', 'edit_pages', 'canarw', 'canarw_admin_dashboard', 'dashicons-admin-site-alt3', 3 );
    add_submenu_page( 'canarw', 'لوحة الموقع', 'لوحة الموقع', 'edit_pages', 'canarw', 'canarw_admin_dashboard' );
    add_submenu_page( 'canarw', 'الصفحات والمحتوى', 'الصفحات والمحتوى', 'edit_pages', 'canarw-content', 'canarw_admin_content' );
    add_submenu_page( 'canarw', 'وثائق وبحوث', 'وثائق وبحوث', 'edit_pages', 'canarw-documents', 'canarw_admin_documents' );
    add_submenu_page( 'canarw', 'هوية الموقع', 'هوية الموقع', 'manage_options', 'canarw-identity', 'canarw_admin_identity' );
    add_submenu_page( 'canarw', 'الفوتر والروابط', 'الفوتر والروابط', 'manage_options', 'canarw-footer', 'canarw_admin_footer' );
    add_submenu_page( 'canarw', 'البيانات الافتراضية', 'البيانات الافتراضية', 'manage_options', 'canarw-import', 'canarw_admin_import' );
    add_submenu_page( 'canarw', 'رسائل التواصل', 'رسائل التواصل', 'manage_options', 'canarw-messages', 'canarw_admin_messages' );
}
add_action( 'admin_menu', 'canarw_admin_menu' );

function canarw_admin_assets( $hook ) {
    if ( false === strpos( $hook, 'canarw' ) ) { return; }
    wp_enqueue_media();
    $editor_screen = 'canarw-content' === ( $_GET['page'] ?? '' ) && ( isset( $_GET['id'] ) || isset( $_GET['new'] ) );
    $dependencies = array( 'jquery', 'media-editor' );
    if ( $editor_screen ) {
        wp_enqueue_editor();
        wp_enqueue_script( 'canarw-editor-tools', get_template_directory_uri() . '/assets/editor-tools.js', array( 'editor' ), CANARW_VERSION, true );
        $dependencies[] = 'canarw-editor-tools';
    }
    wp_enqueue_style( 'canarw-admin', get_template_directory_uri() . '/assets/admin.css', array(), CANARW_VERSION );
    wp_enqueue_script( 'canarw-admin', get_template_directory_uri() . '/assets/admin.js', $dependencies, CANARW_VERSION, true );
    $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $sections = array();
    if ( $id && 'page' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ) {
        $sections = (array) get_post_meta( $id, '_canarw_sections', true );
        if ( ! get_post_meta( $id, '_canarw_managed', true ) && get_post_field( 'post_content', $id ) ) {
            $sections = array( array( 'layout' => 'text', 'title' => '', 'body' => get_post_field( 'post_content', $id ), 'tone' => 'light', 'items' => array() ) );
        }
    }
    $images = array();
    foreach ( $sections as $section ) {
        foreach ( (array) ( $section['items'] ?? array() ) as $item ) {
            if ( ! empty( $item['image'] ) ) { $images[ (string) $item['image'] ] = canarw_image_url( $item['image'] ); }
        }
    }
    $o = canarw_options();
    foreach ( array_merge( array( $o['logo'] ), (array) $o['partner_images'] ) as $image ) { if ( $image ) { $images[ (string) $image ] = canarw_image_url( $image ); } }
    wp_localize_script( 'canarw-admin', 'CanarwAdmin', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'canarw_import' ), 'sections' => $sections, 'images' => $images, 'footer' => $o, 'importState' => canarw_import_public_state(), 'language' => $id ? canarw_language( $id ) : 'ar', 'editorCss' => get_template_directory_uri() . '/assets/editor.css?ver=' . CANARW_VERSION, 'richEditing' => user_can_richedit(), 'canUpload' => current_user_can( 'upload_files' ) ) );
}
add_action( 'admin_enqueue_scripts', 'canarw_admin_assets' );

function canarw_admin_start( $active, $title, $subtitle = '' ) {
    $tabs = array( 'canarw' => array( 'لوحة الموقع', 'dashboard' ), 'canarw-content' => array( 'الصفحات والمحتوى', 'edit-page' ), 'canarw-documents' => array( 'وثائق وبحوث', 'media-document' ) );
    if ( current_user_can( 'manage_options' ) ) {
        $tabs += array( 'canarw-identity' => array( 'هوية الموقع', 'art' ), 'canarw-footer' => array( 'الفوتر والروابط', 'admin-links' ), 'canarw-import' => array( 'البيانات الافتراضية', 'download' ), 'canarw-messages' => array( 'رسائل التواصل', 'email-alt' ) );
    }
    echo '<div class="canarw-admin" dir="rtl"><aside class="ca-sidebar"><a class="ca-admin-brand" href="' . esc_url( admin_url( 'admin.php?page=canarw' ) ) . '"><span>CANARW</span><small>إدارة الموقع</small></a><nav>';
    foreach ( $tabs as $slug => $tab ) { echo '<a class="' . ( $slug === $active ? 'active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . $slug ) ) . '"><span class="dashicons dashicons-' . esc_attr( $tab[1] ) . '"></span>' . esc_html( $tab[0] ) . '</a>'; }
    if ( current_user_can( 'edit_posts' ) ) { echo '<a href="' . esc_url( admin_url( 'edit.php' ) ) . '"><span class="dashicons dashicons-admin-post"></span>المدونة</a>'; }
    if ( current_user_can( 'manage_categories' ) ) { echo '<a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=category' ) ) . '"><span class="dashicons dashicons-category"></span>أقسام المدونة</a>'; }
    echo '</nav><a class="ca-site-link" href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">زيارة الموقع ↗</a></aside><div class="ca-main"><header class="ca-page-heading"><div><span class="ca-eyebrow">CANARW / CONTENT MANAGEMENT</span><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $subtitle ) . '</p></div><span class="ca-version">v' . esc_html( CANARW_VERSION ) . '</span></header>';
    if ( isset( $_GET['saved'] ) ) { echo '<div class="ca-alert" role="status">تم حفظ التغييرات بنجاح.</div>'; }
    if ( isset( $_GET['restored'] ) ) { echo '<div class="ca-alert" role="status">تمت استعادة آخر نسخة محفوظة.</div>'; }
    if ( ! empty( $_GET['error'] ) ) { echo '<div class="ca-alert error" role="alert">تعذر حفظ التغييرات. ' . esc_html( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) . '</div>'; }
}
function canarw_admin_end() { echo '<footer class="ca-admin-footer">CANARW · شبكة العمل المناخي للعالم العربي</footer></div></div>'; }
function canarw_admin_pages() { return get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ), 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) ); }
function canarw_admin_dashboard() {
    canarw_admin_start( 'canarw', 'كل محتوى الموقع، بمكان واحد.', 'عدّل الصفحات والصور، وحدّث هوية الشبكة من لوحة واضحة وسهلة.' );
    $pages = canarw_admin_pages(); $sections = 0;
    foreach ( $pages as $p ) { $sections += count( (array) get_post_meta( $p->ID, '_canarw_sections', true ) ); }
    $msgs = wp_count_posts( 'canarw_message' );
    echo '<div class="ca-stats">';
    foreach ( array( array( count( $pages ), 'صفحة', 'edit-page' ), array( $sections, 'قسم قابل للتعديل', 'screenoptions' ), array( count( (array) get_option( 'canarw_media_map', array() ) ), 'صورة مستوردة', 'format-image' ), array( $msgs->private ?? 0, 'رسالة تواصل', 'email' ) ) as $stat ) {
        echo '<div class="ca-stat"><span class="dashicons dashicons-' . esc_attr( $stat[2] ) . '"></span><strong>' . (int) $stat[0] . '</strong><span>' . esc_html( $stat[1] ) . '</span></div>';
    }
    echo '</div><div class="ca-dashboard-grid"><section class="ca-panel"><h2>ابدأ من هنا</h2><div class="ca-quicklinks"><a href="' . esc_url( admin_url( 'admin.php?page=canarw-content' ) ) . '"><strong>الصفحات والمحتوى ←</strong><span>النصوص، الصور، الأقسام وترتيبها</span></a><a href="' . esc_url( admin_url( 'admin.php?page=canarw-documents' ) ) . '"><strong>وثائق وبحوث ←</strong><span>أضف الوثائق والبحوث وعدّلها</span></a><a href="' . esc_url( admin_url( 'edit.php' ) ) . '"><strong>المدونة ←</strong><span>آخر الأخبار والمقالات</span></a><a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=category' ) ) . '"><strong>أقسام المدونة ←</strong><span>تصنيفات الأخبار</span></a>';
    if ( current_user_can( 'manage_options' ) ) { echo '<a href="' . esc_url( admin_url( 'admin.php?page=canarw-identity' ) ) . '"><strong>هوية الموقع ←</strong><span>الشعار، الألوان وبيانات التواصل</span></a><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '"><strong>قوائم الموقع ←</strong><span>العربية، الإنجليزية وروابط الفوتر</span></a><a href="' . esc_url( admin_url( 'admin.php?page=canarw-import' ) ) . '"><strong>استيراد بيانات الموقع ←</strong><span>الصفحات والصور الموجودة في الموقع الأصلي</span></a>'; }
    echo '</div></section><section class="ca-panel"><h2>آخر الصفحات المحدّثة</h2>';
    $recent = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => 6, 'orderby' => 'modified' ) );
    foreach ( $recent as $p ) { echo '<a class="ca-recent" href="' . esc_url( admin_url( 'admin.php?page=canarw-content&id=' . $p->ID ) ) . '"><strong>' . esc_html( $p->post_title ) . '</strong><span>' . esc_html( get_the_modified_date( 'Y/m/d', $p ) ) . '</span></a>'; }
    if ( ! $recent ) { echo '<p>استورد البيانات الافتراضية لتظهر الصفحات هنا.</p>'; }
    echo '</section></div>'; canarw_admin_end();
}
function canarw_admin_content() {
    $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $new = isset( $_GET['new'] );
    if ( ! $id && ! $new ) {
        canarw_admin_start( 'canarw-content', 'الصفحات والمحتوى', 'افتح أي صفحة لتعديل النصوص والصور والأقسام.' );
        echo '<div class="ca-list-toolbar"><input id="ca-page-search" type="search" placeholder="ابحث باسم الصفحة…" aria-label="ابحث باسم الصفحة"><a class="ca-button" href="' . esc_url( admin_url( 'admin.php?page=canarw-content&new=1' ) ) . '">+ إضافة صفحة</a></div><div class="ca-page-list">';
        foreach ( canarw_admin_pages() as $p ) {
            if ( ! current_user_can( 'edit_post', $p->ID ) ) { continue; }
            echo '<article class="ca-page-row" data-title="' . esc_attr( $p->post_title ) . '"><span class="ca-page-icon dashicons dashicons-media-document"></span><div class="ca-page-row-copy"><h2>' . esc_html( $p->post_title ) . '</h2><span dir="ltr">' . esc_html( get_post_meta( $p->ID, '_canarw_source_path', true ) ?: $p->post_name ) . '</span></div><span class="ca-badge">' . ( canarw_is_english( $p->ID ) ? 'EN' : 'AR' ) . '</span><span class="ca-status">' . esc_html( 'publish' === $p->post_status ? 'منشورة' : $p->post_status ) . '</span><a class="ca-button secondary" href="' . esc_url( admin_url( 'admin.php?page=canarw-content&id=' . $p->ID ) ) . '">تعديل المحتوى</a><a class="ca-icon-link" href="' . esc_url( get_permalink( $p ) ) . '" target="_blank" rel="noopener" aria-label="معاينة الصفحة">↗</a></article>';
        }
        echo '</div>';canarw_admin_end();return;
    }
    if ( $id && ( 'page' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) ) { wp_die( 'ليس لديك صلاحية لتعديل هذه الصفحة.' ); }
    $p = $id ? get_post( $id ) : null;
    canarw_admin_start( 'canarw-content', $p ? 'تعديل: ' . $p->post_title : 'إضافة صفحة', 'اكتب وعدّل بصرياً، وأضف الصور والتنسيق من شريط الأدوات. احفظ التغييرات لمعاينة الصفحة كاملة.' );
    echo '<form id="ca-content-form" class="ca-content-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'canarw_save_page_' . $id );
    echo '<input type="hidden" name="action" value="canarw_save_page"><input type="hidden" name="page_id" value="' . (int) $id . '"><input type="hidden" id="ca-sections-json" name="sections_json"><div class="ca-editor-toolbar"><a class="ca-button secondary" href="' . esc_url( admin_url( 'admin.php?page=canarw-content' ) ) . '">→ كل الصفحات</a><div>';
    if ( $p ) { echo '<a class="ca-button secondary" href="' . esc_url( get_permalink( $p ) ) . '" target="_blank" rel="noopener">معاينة ↗</a> '; }
    echo '<button class="ca-button" type="submit">حفظ التغييرات</button></div></div><section class="ca-panel"><h2>إعدادات الصفحة</h2><div class="ca-fields-grid">';
    canarw_admin_field( 'title', 'عنوان الصفحة', $p ? $p->post_title : '', 'text', true );
    canarw_admin_field( 'slug', 'الرابط المختصر', $p ? $p->post_name : '', 'text', false, 'مثال: climate-action' );
    echo '<label class="ca-field">لغة الصفحة<select name="language"><option value="ar"' . selected( $id ? canarw_is_english( $id ) : false, false, false ) . '>العربية / RTL</option><option value="en"' . selected( $id ? canarw_is_english( $id ) : false, true, false ) . '>English / LTR</option></select></label><label class="ca-field">الحالة<select name="status">';
    foreach ( array( 'publish' => 'منشورة', 'draft' => 'مسودة', 'private' => 'خاصة' ) as $status => $label ) { echo '<option value="' . esc_attr( $status ) . '"' . selected( $p ? $p->post_status : 'draft', $status, false ) . '>' . esc_html( $label ) . '</option>'; }
    echo '</select></label>'; canarw_admin_field( 'description', 'وصف الصفحة لمحركات البحث', $id ? get_post_meta( $id, '_canarw_description', true ) : '', 'textarea' );
    echo '</div></section><div class="ca-builder-heading"><h2>أقسام الصفحة</h2><span>↑ ↓ لترتيب الأقسام والعناصر</span></div><div id="ca-sections-builder"></div><div class="ca-add-section"><label>نوع القسم <select id="ca-new-layout">';
    foreach ( canarw_layout_labels() as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>'; }
    echo '</select></label><button id="ca-add-section" type="button" class="ca-button secondary">+ إضافة قسم</button></div><div class="ca-bottom-save"><button type="submit" class="ca-button">حفظ التغييرات</button><span id="ca-dirty-indicator" aria-live="polite"></span></div></form>';
    if ( $id && get_post_meta( $id, '_canarw_previous', true ) ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ca-restore-form">';wp_nonce_field( 'canarw_restore_' . $id );echo '<input type="hidden" name="action" value="canarw_restore_page"><input type="hidden" name="page_id" value="' . $id . '"><button type="submit" class="ca-button secondary">استعادة نسخة المحتوى السابقة</button><small>تستعيد النصوص والصور والأقسام من آخر حفظ سابق.</small></form>';
    }
    canarw_admin_end();
}
function canarw_layout_labels() { return array( 'hero' => 'واجهة رئيسية', 'split' => 'نص وصورة', 'text' => 'نص ومحتوى', 'editorial' => 'محتوى بعمودين', 'cards' => 'بطاقات', 'gallery' => 'معرض صور', 'testimonials' => 'شهادات وآراء', 'contact' => 'تواصل', 'stats' => 'أرقام ومؤشرات', 'news' => 'أخبار ومدونة' ); }
function canarw_admin_field( $name, $label, $value, $type = 'text', $required = false, $hint = '', $rows = 3 ) {
    echo '<label class="ca-field">' . esc_html( $label );
    if ( 'textarea' === $type ) { echo '<textarea name="' . esc_attr( $name ) . '" rows="' . (int) $rows . '">' . esc_textarea( $value ) . '</textarea>'; }
    else { echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . ( $required ? ' required' : '' ) . '>'; }
    if ( $hint ) { echo '<small>' . esc_html( $hint ) . '</small>'; } echo '</label>';
}
function canarw_admin_identity() {
    canarw_admin_start( 'canarw-identity', 'هوية الموقع', 'الشعار، الألوان الأساسية وبيانات التواصل.' );
    $o = canarw_options();echo '<form class="ca-settings-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';wp_nonce_field( 'canarw_save_settings' );echo '<input type="hidden" name="action" value="canarw_save_settings"><input type="hidden" name="tab" value="identity"><section class="ca-panel"><h2>الهوية البصرية</h2><div class="ca-fields-grid">';
    canarw_admin_field( 'name', 'اسم الموقع', $o['name'], 'text', true );canarw_admin_field( 'tagline', 'الاسم العربي / الوصف المختصر', $o['tagline'] );
    echo '<div class="ca-field"><span>شعار الموقع</span><div class="ca-single-media" data-value="' . esc_attr( $o['logo'] ) . '" data-src="' . esc_url( canarw_image_url( $o['logo'] ) ) . '"></div><input type="hidden" name="logo" class="ca-logo-value" value="' . esc_attr( $o['logo'] ) . '"></div>';
    canarw_admin_field( 'primary', 'اللون الأساسي', $o['primary'], 'color' );canarw_admin_field( 'accent', 'اللون المساعد', $o['accent'], 'color' );
    echo '</div></section><section class="ca-panel"><h2>بيانات التواصل</h2><div class="ca-fields-grid">';
    canarw_admin_field( 'email', 'البريد الإلكتروني', $o['email'], 'email', true );canarw_admin_field( 'phone', 'رقم الهاتف', $o['phone'], 'tel' );canarw_admin_field( 'location', 'الموقع / العنوان', $o['location'] );
    echo '</div><label class="ca-checkbox"><input type="checkbox" name="notify_messages" value="1"' . checked( ! empty( $o['notify_messages'] ), true, false ) . '>إرسال إشعار بالبريد عند وصول رسالة جديدة</label><p class="ca-muted">الرسائل تحفظ في اللوحة. إشعارات البريد تتطلب إعداد إرسال البريد في الاستضافة.</p>';
    canarw_admin_field( 'contact_retention_days', 'حذف الرسائل تلقائياً بعد عدد الأيام', $o['contact_retention_days'], 'number', true, 'من 7 إلى 365 يوماً' );
    echo '</section><button type="submit" class="ca-button">حفظ الهوية</button></form>';canarw_admin_end();
}
function canarw_admin_footer() {
    canarw_admin_start( 'canarw-footer', 'الفوتر والروابط', 'روابط المصادر، الشبكات الاجتماعية وشعارات الشركاء.' );
    $o = canarw_options();echo '<form id="ca-footer-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';wp_nonce_field( 'canarw_save_settings' );echo '<input type="hidden" name="action" value="canarw_save_settings"><input type="hidden" name="tab" value="footer"><input type="hidden" name="footer_json" id="ca-footer-json"><section class="ca-panel">';
    canarw_admin_field( 'footer_text', 'نص الحقوق والسياسات', $o['footer_text'], 'textarea' );
    echo '</section><section class="ca-panel"><h2>مجموعات روابط الفوتر</h2><div id="ca-footer-groups"></div><button type="button" id="ca-add-footer-group" class="ca-button secondary">+ إضافة مجموعة روابط</button></section><section class="ca-panel"><h2>الشبكات الاجتماعية</h2><div id="ca-socials"></div><button type="button" id="ca-add-social" class="ca-button secondary">+ إضافة رابط</button></section><section class="ca-panel"><h2>شعارات الشركاء والشبكات</h2><div id="ca-partner-images"></div><button type="button" id="ca-add-partner" class="ca-button secondary">+ إضافة شعار</button></section><button type="submit" class="ca-button">حفظ الفوتر</button></form>';canarw_admin_end();
}
function canarw_admin_import() {
    canarw_admin_start( 'canarw-import', 'البيانات الافتراضية', 'استيراد المحتوى العام من canarw.org، كما جرى جمعه بتاريخ 4 أكتوبر 2026.' );
    $seed = canarw_seed();
    echo '<section class="ca-panel ca-import-panel"><div class="ca-import-icon dashicons dashicons-download"></div><h2>الموقع جاهز ببياناته الأصلية</h2><p>' . count( $seed['pages'] ?? array() ) . ' صفحة و' . count( $seed['media'] ?? array() ) . ' صورة مرفقة، مع القوائم والهوية وروابط الفوتر.</p><p class="ca-muted">الاستيراد يضيف الصفحات المفقودة ويستأنف العمل إذا انقطع. الصفحات المعدّلة أو الموجودة سابقاً تُحفظ، ولن يستبدلها الاستيراد. بعض الصفحات الأصلية تحتوي نصوصاً تجريبية أو محتوى فارغاً؛ تم الاحتفاظ بها كما هي. صفحة Store الإنجليزية تعرض محتواها المرئي الملتقط فقط، ولا تضيف نظام بيع أو دفع.</p><label class="ca-checkbox"><input type="checkbox" id="ca-import-front" checked>تعيين الصفحة المستوردة كصفحة رئيسية وربط القوائم الجديدة</label><div id="ca-import-progress" aria-live="polite"><div class="ca-progress"><span></span></div><p id="ca-import-label">جاهز للاستيراد</p></div><button type="button" class="ca-button" id="ca-import-start">استيراد / استئناف البيانات</button><p id="ca-import-error" role="alert"></p><a class="ca-button secondary" href="' . esc_url( admin_url( 'admin.php?page=canarw-content' ) ) . '">افتح الصفحات</a></section>';canarw_admin_end();
}
function canarw_admin_messages() {
    canarw_admin_start( 'canarw-messages', 'رسائل التواصل', 'رسائل نماذج التواصل الواردة من الموقع.' );
    $paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
    $query = new WP_Query( array( 'post_type' => 'canarw_message', 'post_status' => 'private', 'posts_per_page' => 20, 'paged' => $paged ) );
    echo '<div class="ca-message-list">';
    foreach ( $query->posts as $m ) {
        $email = get_post_meta( $m->ID, '_canarw_email', true );
        echo '<article class="ca-panel ca-message"><div class="ca-message-heading"><h2>' . esc_html( $m->post_title ) . '</h2><time>' . esc_html( get_the_date( 'Y/m/d H:i', $m ) ) . '</time></div><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a><div class="ca-message-body">' . nl2br( esc_html( $m->post_content ) ) . '</div><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';wp_nonce_field( 'canarw_delete_message_' . $m->ID );echo '<input type="hidden" name="action" value="canarw_delete_message"><input type="hidden" name="message_id" value="' . $m->ID . '"><button type="submit" class="ca-button danger secondary">حذف الرسالة</button></form></article>';
    }
    if ( ! $query->posts ) { echo '<section class="ca-panel"><h2>لا توجد رسائل حالياً.</h2><p>ستظهر الرسائل هنا عند إرسالها من نموذج التواصل.</p></section>'; }
    echo '</div>';echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=canarw-messages' ) ), 'format' => '', 'current' => $paged, 'total' => $query->max_num_pages ) ) );canarw_admin_end();
}
function canarw_save_page() {
    $id = absint( $_POST['page_id'] ?? 0 );
    check_admin_referer( 'canarw_save_page_' . $id );
    if ( $id ? ( 'page' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) : ! current_user_can( 'edit_pages' ) ) { wp_die( 'ليس لديك صلاحية.' ); }
    $json = wp_unslash( $_POST['sections_json'] ?? '' );
    $raw = json_decode( $json, true );
    if ( strlen( $json ) > 1500000 || ! is_array( $raw ) ) { wp_die( 'بيانات الأقسام غير صالحة. لم يتم تغيير المحتوى.' ); }
    $sections = canarw_sanitize_sections( $raw );
    $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
    if ( ! $title ) { wp_die( 'أدخل عنوان الصفحة.' ); }
    $status = sanitize_key( $_POST['status'] ?? 'draft' );
    if ( ! in_array( $status, array( 'publish', 'draft', 'private' ), true ) ) { $status = 'draft'; }
    if ( 'draft' !== $status && ! current_user_can( 'publish_pages' ) ) { wp_die( 'ليس لديك صلاحية نشر الصفحات.' ); }
    $old = $id ? get_post_meta( $id, '_canarw_sections', true ) : false;
    $args = array( 'post_type' => 'page', 'post_title' => $title, 'post_status' => $status, 'post_content' => canarw_fallback_content( $sections ), 'post_excerpt' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ) );
    if ( $id ) { $args['ID'] = $id; }
    $slug = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
    if ( $slug ) { $args['post_name'] = $slug; }
    $saved = wp_insert_post( wp_slash( $args ), true );
    if ( is_wp_error( $saved ) ) { wp_die( esc_html( $saved->get_error_message() ) ); }
    if ( is_array( $old ) ) { update_post_meta( $saved, '_canarw_previous', wp_slash( $old ) ); }
    update_post_meta( $saved, '_canarw_sections', wp_slash( $sections ) );
    update_post_meta( $saved, '_canarw_managed', 1 );
    update_post_meta( $saved, '_canarw_language', 'en' === ( $_POST['language'] ?? '' ) ? 'en' : 'ar' );
    update_post_meta( $saved, '_canarw_description', sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ) );
    wp_safe_redirect( admin_url( 'admin.php?page=canarw-content&id=' . $saved . '&saved=1' ) );exit;
}
add_action( 'admin_post_canarw_save_page', 'canarw_save_page' );
function canarw_restore_page() {
    $id = absint( $_POST['page_id'] ?? 0 );check_admin_referer( 'canarw_restore_' . $id );
    if ( 'page' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) { wp_die( 'ليس لديك صلاحية.' ); }
    $old = get_post_meta( $id, '_canarw_previous', true );
    if ( ! is_array( $old ) ) { wp_die( 'لا توجد نسخة سابقة.' ); }
    $current = get_post_meta( $id, '_canarw_sections', true );$old = canarw_sanitize_sections( $old );
    wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => canarw_fallback_content( $old ) ) ) );
    update_post_meta( $id, '_canarw_sections', wp_slash( $old ) );update_post_meta( $id, '_canarw_previous', wp_slash( $current ) );
    wp_safe_redirect( admin_url( 'admin.php?page=canarw-content&id=' . $id . '&restored=1' ) );exit;
}
add_action( 'admin_post_canarw_restore_page', 'canarw_restore_page' );
function canarw_save_settings() {
    check_admin_referer( 'canarw_save_settings' );if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'ليس لديك صلاحية.' ); }
    $o = canarw_options();$tab = sanitize_key( $_POST['tab'] ?? '' );
    if ( 'identity' === $tab ) {
        foreach ( array( 'name', 'tagline', 'phone', 'location' ) as $key ) { $o[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) ); }
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );if ( ! is_email( $email ) ) { wp_die( 'البريد الإلكتروني غير صالح.' ); }
        $o['email'] = $email;$o['logo'] = canarw_image_value( wp_unslash( $_POST['logo'] ?? '' ) );
        $o['primary'] = sanitize_hex_color( $_POST['primary'] ?? '' ) ?: '#153b36';$o['accent'] = sanitize_hex_color( $_POST['accent'] ?? '' ) ?: '#bcda76';
        $o['notify_messages'] = ! empty( $_POST['notify_messages'] );$o['contact_retention_days'] = max( 7, min( 365, absint( $_POST['contact_retention_days'] ?? 90 ) ) );
    } elseif ( 'footer' === $tab ) {
        $data = json_decode( wp_unslash( $_POST['footer_json'] ?? '' ), true );if ( ! is_array( $data ) ) { wp_die( 'بيانات الفوتر غير صالحة.' ); }
        $o['footer_text'] = sanitize_textarea_field( wp_unslash( $_POST['footer_text'] ?? '' ) );$o['footer_groups'] = array();
        foreach ( array_slice( (array) ( $data['footer_groups'] ?? array() ), 0, 8 ) as $g ) {
            if ( ! is_array( $g ) ) { continue; }$links = array();
            foreach ( array_slice( (array) ( $g['links'] ?? array() ), 0, 30 ) as $l ) { if ( is_array( $l ) && ! empty( $l['url'] ) ) { $links[] = array( 'label' => sanitize_text_field( $l['label'] ?? '' ), 'url' => esc_url_raw( $l['url'] ) ); } }
            $o['footer_groups'][] = array( 'title' => sanitize_text_field( $g['title'] ?? '' ), 'links' => $links );
        }
        $o['socials'] = array();foreach ( array_slice( (array) ( $data['socials'] ?? array() ), 0, 20 ) as $l ) { if ( is_array( $l ) && ! empty( $l['url'] ) ) { $o['socials'][] = array( 'label' => sanitize_text_field( $l['label'] ?? '' ), 'url' => esc_url_raw( $l['url'] ) ); } }
        $o['partner_images'] = array_values( array_filter( array_map( 'canarw_image_value', array_slice( (array) ( $data['partner_images'] ?? array() ), 0, 30 ) ) ) );
    } else { wp_die( 'طلب غير صالح.' ); }
    update_option( 'canarw_options', $o );wp_safe_redirect( admin_url( 'admin.php?page=canarw-' . $tab . '&saved=1' ) );exit;
}
add_action( 'admin_post_canarw_save_settings', 'canarw_save_settings' );
function canarw_page_row_actions( $actions, $post ) {
    if ( 'page' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) { $actions['canarw'] = '<a href="' . esc_url( admin_url( 'admin.php?page=canarw-content&id=' . $post->ID ) ) . '">تعديل أقسام CANARW</a>'; }return $actions;
}
add_filter( 'page_row_actions', 'canarw_page_row_actions', 10, 2 );
function canarw_native_editor_notice( $post ) {
    if ( 'page' === $post->post_type && get_post_meta( $post->ID, '_canarw_managed', true ) ) { echo '<div class="notice notice-info inline"><p>هذه الصفحة تستخدم أقسام CANARW. <a href="' . esc_url( admin_url( 'admin.php?page=canarw-content&id=' . $post->ID ) ) . '">عدّل المحتوى والصور من محرر الأقسام</a>. المحتوى أدناه نسخة نصية للاحتفاظ بالمحتوى عند تبديل القالب.</p></div>'; }
}
add_action( 'edit_form_after_title', 'canarw_native_editor_notice' );
add_filter( 'use_block_editor_for_post', function( $use, $post ) { return get_post_meta( $post->ID, '_canarw_managed', true ) ? false : $use; }, 10, 2 );
