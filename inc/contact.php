<?php
defined( 'ABSPATH' ) || exit;

function canarw_register_messages() {
    register_post_type( 'canarw_message', array( 'label' => 'رسائل التواصل', 'public' => false, 'show_ui' => false, 'show_in_rest' => false, 'exclude_from_search' => true, 'rewrite' => false, 'query_var' => false, 'can_export' => false, 'supports' => array( 'title', 'editor' ), 'map_meta_cap' => false, 'capabilities' => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ), 'manage_options' ) ) );
    if ( ! wp_next_scheduled( 'canarw_clean_messages' ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'canarw_clean_messages' ); }
}
add_action( 'init', 'canarw_register_messages' );
function canarw_contact_form( $item ) {
    $id = get_queried_object_id();$english = canarw_is_english();
    $status = sanitize_key( $_GET['canarw_contact'] ?? '' );
    $messages = array( 'success' => array( 'تم حفظ رسالتك بنجاح. شكراً لتواصلك.', 'Your message has been received. Thank you.' ), 'invalid' => array( 'تحقق من الاسم والبريد ونص الرسالة وحاول مجدداً.', 'Please check your name, email and message.' ), 'rate' => array( 'وصلت للحد المؤقت للإرسال. حاول لاحقاً.', 'Too many submissions. Please try again later.' ), 'expired' => array( 'انتهت صلاحية النموذج. حدّث الصفحة وحاول مجدداً.', 'The form expired. Refresh this page and try again.' ), 'failed' => array( 'تعذر حفظ الرسالة. حاول مرة أخرى.', 'Could not save your message. Please try again.' ) );
    echo '<form id="canarw-contact" class="contact-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    if ( isset( $messages[ $status ] ) ) { echo '<div class="contact-result' . ( 'success' === $status ? '' : ' error' ) . '" role="status">' . esc_html( $messages[ $status ][ $english ? 1 : 0 ] ) . '</div>'; }
    wp_nonce_field( 'canarw_contact_' . $id, 'canarw_nonce' );
    echo '<input type="hidden" name="action" value="canarw_contact"><input type="hidden" name="page_id" value="' . $id . '"><label>' . esc_html( canarw_label( 'الاسم الكامل', 'Your Name' ) ) . '<input name="contact_name" type="text" required maxlength="120" autocomplete="name"></label><label>' . esc_html( canarw_label( 'البريد الإلكتروني', 'Email Address' ) ) . '<input name="contact_email" type="email" required maxlength="254" autocomplete="email" dir="ltr"></label><label>' . esc_html( canarw_label( 'الرسالة', 'Message' ) ) . '<textarea name="contact_message" required maxlength="10000" rows="6"></textarea></label><div class="canarw-hp" aria-hidden="true"><label>Website<input name="contact_website" tabindex="-1" autocomplete="off"></label></div><p class="contact-note">' . esc_html( canarw_label( 'بإرسال النموذج، توافق على استخدام اسمك وبريدك ورسالتك للرد على طلبك.', 'By submitting, you agree to the use of your name, email and message to respond to your request.' ) ) . '</p><button class="button" type="submit">' . esc_html( $item['button'] ?: canarw_label( 'إرسال الرسالة', 'Send Message' ) ) . '</button></form>';
}
function canarw_contact_return( $id, $status ) {
    $url = $id && 'publish' === get_post_status( $id ) && 'page' === get_post_type( $id ) ? get_permalink( $id ) : home_url( '/' );
    wp_safe_redirect( add_query_arg( 'canarw_contact', $status, $url ) . '#canarw-contact' );exit;
}
function canarw_contact_submit() {
    $id = absint( $_POST['page_id'] ?? 0 );
    if ( ! $id || 'page' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['canarw_nonce'] ?? '' ) ), 'canarw_contact_' . $id ) ) { canarw_contact_return( $id, 'expired' ); }
    $has_form = false;
    foreach ( (array) get_post_meta( $id, '_canarw_sections', true ) as $s ) { if ( ! empty( $s['hidden'] ) ) { continue; }foreach ( (array) $s['items'] as $i ) { if ( 'contact' === $i['type'] ) { $has_form = true;break 2; } } }
    if ( ! $has_form ) { canarw_contact_return( $id, 'invalid' ); }
    if ( ! empty( $_POST['contact_website'] ) ) { canarw_contact_return( $id, 'success' ); }
    $name = sanitize_text_field( wp_unslash( $_POST['contact_name'] ?? '' ) );$email = sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) );$message = sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ?? '' ) );
    if ( ! $name || strlen( $name ) > 480 || ! is_email( $email ) || strlen( $email ) > 254 || ! $message || strlen( $message ) > 40000 ) { canarw_contact_return( $id, 'invalid' ); }
    $key = 'canarw_rate_' . hash_hmac( 'sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', wp_salt( 'nonce' ) );$count = (int) get_transient( $key );
    if ( $count >= 3 ) { canarw_contact_return( $id, 'rate' ); }
    $saved = wp_insert_post( wp_slash( array( 'post_type' => 'canarw_message', 'post_status' => 'private', 'post_title' => $name, 'post_content' => $message ) ), true );
    if ( is_wp_error( $saved ) ) { canarw_contact_return( $id, 'failed' ); }
    update_post_meta( $saved, '_canarw_email', $email );update_post_meta( $saved, '_canarw_page', $id );set_transient( $key, $count + 1, HOUR_IN_SECONDS );
    $o = canarw_options();
    if ( ! empty( $o['notify_messages'] ) && is_email( $o['email'] ) ) {
        $sent = wp_mail( $o['email'], '[' . $o['name'] . '] رسالة تواصل جديدة', $name . "\n" . $email . "\n\n" . $message, array( 'Reply-To: ' . $email ) );
        update_post_meta( $saved, '_canarw_notification_sent', $sent ? 1 : 0 );
    }
    canarw_contact_return( $id, 'success' );
}
add_action( 'admin_post_canarw_contact', 'canarw_contact_submit' );add_action( 'admin_post_nopriv_canarw_contact', 'canarw_contact_submit' );
function canarw_delete_message() {
    $id = absint( $_POST['message_id'] ?? 0 );check_admin_referer( 'canarw_delete_message_' . $id );
    if ( ! current_user_can( 'manage_options' ) || 'canarw_message' !== get_post_type( $id ) ) { wp_die( 'ليس لديك صلاحية.' ); }
    wp_delete_post( $id, true );wp_safe_redirect( admin_url( 'admin.php?page=canarw-messages&saved=1' ) );exit;
}
add_action( 'admin_post_canarw_delete_message', 'canarw_delete_message' );
function canarw_clean_messages() {
    $days = max( 7, min( 365, (int) canarw_options()['contact_retention_days'] ) );
    $ids = get_posts( array( 'post_type' => 'canarw_message', 'post_status' => 'private', 'fields' => 'ids', 'numberposts' => 100, 'date_query' => array( array( 'before' => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ), 'column' => 'post_date_gmt' ) ) ) );
    foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
}
add_action( 'canarw_clean_messages', 'canarw_clean_messages' );
add_action( 'switch_theme', function() { wp_clear_scheduled_hook( 'canarw_clean_messages' ); } );
