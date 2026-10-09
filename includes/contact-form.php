<?php
/**
 * Contact form recreated from the original Strikingly email form (fields, order, labels, submit
 * label and thank-you text in parts/contact-form.json). Submissions are saved as private
 * "Enquiries" in wp-admin and emailed to the configured address. No third-party service is used.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function orwo_form_definition() {
    static $form = null;
    if ( null === $form ) {
        $file = get_theme_file_path( 'parts/contact-form.json' );
        $form = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return is_array( $form ) ? $form : array();
}

function orwo_contact_recipient() {
    $email = sanitize_email( (string) get_option( 'orwo_contact_recipient', '' ) );
    return is_email( $email ) ? $email : 'hello@orwo.family';
}

add_action( 'init', function () {
    register_post_type( 'orwo_enquiry', array(
        'labels'          => array( 'name' => 'Enquiries', 'singular_name' => 'Enquiry', 'menu_name' => 'Enquiries', 'all_items' => 'All enquiries', 'edit_item' => 'Enquiry' ),
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => false,
        'menu_icon'       => 'dashicons-email-alt',
        'menu_position'   => 26,
        'supports'        => array( 'title' ),
        'capability_type' => 'post',
        'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
        'map_meta_cap'    => true,
    ) );
} );

/** Recipient address under Settings > General. */
add_action( 'admin_init', function () {
    register_setting( 'general', 'orwo_contact_recipient', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '' ) );
    add_settings_field( 'orwo_contact_recipient', 'ORWO contact form recipient', function () {
        echo '<input type="email" class="regular-text" name="orwo_contact_recipient" value="' . esc_attr( get_option( 'orwo_contact_recipient', '' ) ) . '" placeholder="hello@orwo.family"><p class="description">Enquiries from the Contact page are emailed here and always saved under Enquiries. Leave empty for hello@orwo.family.</p>';
    }, 'general' );
} );

add_action( 'add_meta_boxes_orwo_enquiry', function () {
    add_meta_box( 'orwo_enquiry_fields', 'Submitted details', function ( $post ) {
        $values = (array) get_post_meta( $post->ID, '_orwo_fields', true );
        echo '<table class="widefat striped"><tbody>';
        foreach ( orwo_form_definition()['fields'] ?? array() as $field ) {
            echo '<tr><th style="width:180px">' . esc_html( $field['label'] ) . '</th><td style="white-space:pre-wrap">' . esc_html( $values[ $field['key'] ] ?? '' ) . '</td></tr>';
        }
        $mail = get_post_meta( $post->ID, '_orwo_mail', true );
        echo '<tr><th>Email notification</th><td>' . esc_html( $mail ? $mail : 'unknown' ) . '</td></tr><tr><th>Page</th><td>' . esc_html( get_post_meta( $post->ID, '_orwo_page', true ) ) . '</td></tr></tbody></table>';
    }, 'orwo_enquiry', 'normal', 'high' );
} );
add_filter( 'manage_orwo_enquiry_posts_columns', function ( $columns ) {
    return array( 'cb' => $columns['cb'], 'title' => 'Name', 'orwo_email' => 'Email', 'orwo_mail' => 'Email sent', 'date' => 'Received' );
} );
add_action( 'manage_orwo_enquiry_posts_custom_column', function ( $column, $id ) {
    $values = (array) get_post_meta( $id, '_orwo_fields', true );
    if ( 'orwo_email' === $column ) { echo esc_html( $values['email'] ?? '' ); }
    if ( 'orwo_mail' === $column ) { echo esc_html( get_post_meta( $id, '_orwo_mail', true ) ); }
}, 10, 2 );

function orwo_form_respond( $ok, $message, $errors = array(), $values = array() ) {
    if ( str_contains( (string) ( $_SERVER['HTTP_ACCEPT'] ?? '' ), 'application/json' ) ) {
        wp_send_json( array( 'ok' => $ok, 'message' => $message, 'errors' => $errors ), $ok ? 200 : 422 );
    }
    $back = wp_get_referer() ? wp_get_referer() : home_url( '/contact' );
    $back = remove_query_arg( array( 'orwo_form', 'orwo_token' ), $back );
    if ( $ok ) {
        wp_safe_redirect( add_query_arg( 'orwo_form', 'sent', $back ) . '#enquiry' );
        exit;
    }
    $token = wp_generate_password( 20, false );
    set_transient( 'orwo_form_' . $token, array( 'message' => $message, 'errors' => $errors, 'values' => $values ), 10 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( array( 'orwo_form' => 'error', 'orwo_token' => $token ), $back ) . '#enquiry' );
    exit;
}

function orwo_handle_contact() {
    $form = orwo_form_definition();
    $values = array();
    $errors = array();
    foreach ( $form['fields'] ?? array() as $field ) {
        $raw = isset( $_POST[ 'orwo_' . $field['key'] ] ) ? wp_unslash( $_POST[ 'orwo_' . $field['key'] ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidationSanitization.InputNotSanitized -- Public form; sanitised below; see anti-spam checks.
        $value = 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
        $values[ $field['key'] ] = $value;
        if ( $field['required'] && '' === trim( $value ) ) { $errors[ $field['key'] ] = $field['label'] . ' is required.'; }
        elseif ( 'email' === $field['type'] && '' !== $value && ! is_email( $value ) ) { $errors[ $field['key'] ] = 'Please enter a valid email address.'; }
        elseif ( mb_strlen( $value ) > (int) $field['max'] ) { $errors[ $field['key'] ] = $field['label'] . ' is too long (maximum ' . (int) $field['max'] . ' characters).'; }
    }
    // Spam protection that survives page caching: honeypot, minimum time on page (JavaScript), per-visitor rate limit.
    if ( ! empty( $_POST['orwo_website'] ) ) { orwo_form_respond( true, $form['thanks'] ?? '' ); } // phpcs:ignore WordPress.Security.NonceVerification.Missing
    // Milliseconds the visitor spent on the page, measured in the browser (independent of clock differences).
    $elapsed = isset( $_POST['orwo_elapsed'] ) ? (int) $_POST['orwo_elapsed'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( $elapsed < 3000 ) {
        orwo_form_respond( false, 'Your message could not be verified. Please wait a moment and submit again, or email hello@orwo.family.', array(), $values );
    }
    $visitor = 'orwo_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
    $count = (int) get_transient( $visitor );
    if ( $count >= 5 ) { orwo_form_respond( false, 'Too many messages in a short time. Please try again later or email hello@orwo.family.', array(), $values ); }
    if ( $errors ) { orwo_form_respond( false, 'Please check the highlighted fields.', $errors, $values ); }
    set_transient( $visitor, $count + 1, 10 * MINUTE_IN_SECONDS );

    $page = esc_url_raw( (string) wp_get_referer() );
    $id = wp_insert_post( array( 'post_type' => 'orwo_enquiry', 'post_status' => 'private', 'post_title' => $values['name'] ?? 'Enquiry' ), true );
    if ( ! is_wp_error( $id ) ) {
        update_post_meta( $id, '_orwo_fields', $values );
        update_post_meta( $id, '_orwo_page', $page );
    }
    $lines = array();
    foreach ( $form['fields'] as $field ) { $lines[] = $field['label'] . ': ' . ( '' === $values[ $field['key'] ] ? '-' : $values[ $field['key'] ] ); }
    $lines[] = '';
    $lines[] = 'Sent from: ' . $page;
    $headers = array( 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $values['name'] ?? '' ) . ' <' . $values['email'] . '>' );
    $sent = wp_mail( orwo_contact_recipient(), 'ORWO Family website enquiry from ' . ( $values['name'] ?? '' ), implode( "\n", $lines ), $headers );
    if ( ! is_wp_error( $id ) ) { update_post_meta( $id, '_orwo_mail', $sent ? 'sent to ' . orwo_contact_recipient() : 'FAILED - check site email settings' ); }
    orwo_form_respond( true, $form['thanks'] ?? 'Thank you.' );
}
add_action( 'admin_post_nopriv_orwo_contact', 'orwo_handle_contact' );
add_action( 'admin_post_orwo_contact', 'orwo_handle_contact' );

function orwo_contact_form() {
    $form = orwo_form_definition();
    if ( ! $form ) { return; }
    $state = array( 'message' => '', 'errors' => array(), 'values' => array() );
    $status = isset( $_GET['orwo_form'] ) ? sanitize_key( wp_unslash( $_GET['orwo_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( 'error' === $status && isset( $_GET['orwo_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $saved = get_transient( 'orwo_form_' . sanitize_key( wp_unslash( $_GET['orwo_token'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( is_array( $saved ) ) { $state = $saved; }
    }
    echo '<form class="orwo-form" id="enquiry" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate data-orwo-form>';
    echo '<div class="form-status" role="status" aria-live="polite">';
    if ( 'sent' === $status ) { echo '<p class="form-success">' . esc_html( $form['thanks'] ) . '</p>'; }
    elseif ( $state['message'] ) { echo '<p class="form-error">' . esc_html( $state['message'] ) . '</p>'; }
    echo '</div><input type="hidden" name="action" value="orwo_contact"><input type="hidden" name="orwo_elapsed" value="" data-elapsed>';
    echo '<div class="hp" aria-hidden="true"><label>Website <input type="text" name="orwo_website" tabindex="-1" autocomplete="off"></label></div><div class="form-grid">';
    foreach ( $form['fields'] as $field ) {
        $name = 'orwo_' . $field['key'];
        $value = $state['values'][ $field['key'] ] ?? '';
        $error = $state['errors'][ $field['key'] ] ?? '';
        $attrs = ' id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" maxlength="' . (int) $field['max'] . '"' . ( $field['required'] ? ' required aria-required="true"' : '' ) . ( $error ? ' aria-invalid="true"' : '' );
        $auto = array( 'name' => 'name', 'email' => 'email', 'phone' => 'tel' );
        if ( isset( $auto[ $field['key'] ] ) ) { $attrs .= ' autocomplete="' . esc_attr( $auto[ $field['key'] ] ) . '"'; }
        echo '<div class="field field-' . esc_attr( $field['key'] ) . ( 'textarea' === $field['type'] ? ' wide' : '' ) . '"><label for="' . esc_attr( $name ) . '">' . esc_html( $field['label'] ) . ( $field['required'] ? ' <span class="req" aria-hidden="true">*</span>' : '' ) . '</label>';
        if ( 'textarea' === $field['type'] ) { echo '<textarea rows="6"' . $attrs . '>' . esc_textarea( $value ) . '</textarea>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes escaped above.
        else { echo '<input type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $value ) . '"' . $attrs . '>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span class="field-error">' . esc_html( $error ) . '</span></div>';
    }
    echo '</div><div class="form-actions"><button class="button" type="submit">' . esc_html( strtoupper( $form['submit'] ) ) . ' ↗</button><span class="form-note">* required</span></div>';
    echo '<noscript><p class="form-error">Please enable JavaScript to send this form, or email <a href="mailto:hello@orwo.family">hello@orwo.family</a>.</p></noscript></form>';
}
