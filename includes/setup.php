<?php
/** One-click setup: pretty permalinks (needed for the original URLs) and the original site title. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function orwo_setup_missing() {
    $missing = array();
    if ( '' === (string) get_option( 'permalink_structure' ) ) { $missing['permalinks'] = 'turn on pretty permalinks so /contact and /pages/cookie-policy work'; }
    if ( 'ORWO Family' !== get_option( 'blogname' ) ) { $missing['title'] = 'set the site title to "ORWO Family" (the original title)'; }
    if ( orwo_media_pending() ) { $missing['media'] = 'add the website images to the Media Library with descriptive file names and alt text (SEO)'; }
    return $missing;
}

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $missing = orwo_setup_missing();
    if ( ! $missing ) { return; }
    $url = wp_nonce_url( admin_url( 'admin-post.php?action=orwo_setup' ), 'orwo_setup' );
    echo '<div class="notice notice-warning"><p><strong>ORWO Family setup:</strong> ' . esc_html( implode( '; ', $missing ) ) . '.</p><p><a class="button button-primary" href="' . esc_url( $url ) . '">Complete ORWO Family setup</a></p></div>';
} );

add_action( 'admin_post_orwo_setup', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'orwo_setup' );
    if ( '' === (string) get_option( 'permalink_structure' ) ) {
        update_option( 'permalink_structure', '/%postname%/' );
        flush_rewrite_rules( true );
    }
    if ( 'ORWO Family' !== get_option( 'blogname' ) ) {
        update_option( 'blogname', 'ORWO Family' );
        update_option( 'blogdescription', '' );
    }
    if ( orwo_media_pending() ) { wp_safe_redirect( orwo_media_import_url() ); exit; }
    wp_safe_redirect( home_url( '/' ) );
    exit;
} );
