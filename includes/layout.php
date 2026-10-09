<?php
/** Theme-owned layout parts generated from the archived ORWO Family site (tools/build_orwo_family_theme.py). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Resolve our controlled /assets/ tokens and root-relative links, on any (sub)directory install. */
function orwo_theme_fragment( $html ) {
    $html = orwo_media_swap( $html );
    $html = preg_replace_callback( '~(src|href)="(/assets/[^"<>]+)"~', function ( $m ) {
        return $m[1] . '="' . esc_url( get_theme_file_uri( ltrim( $m[2], '/' ) ) ) . '"';
    }, $html );
    $html = preg_replace_callback( '~url\((/assets/[^)"\'<>]+)\)~', function ( $m ) {
        return 'url(' . esc_url( get_theme_file_uri( ltrim( $m[1], '/' ) ) ) . ')';
    }, $html );
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        return 'href="' . esc_url( home_url( $m[1] ) ) . '"';
    }, $html );
}

/** Path of a generated part. Only fixed names are accepted, never user-controlled paths. */
function orwo_part_file( $name ) {
    if ( ! preg_match( '~^(?:header|footer|cta|home|contact-socials|pages/[a-z0-9-]+)$~D', $name ) ) { return ''; }
    $file = get_theme_file_path( 'parts/' . $name . '.html' );
    return is_readable( $file ) ? $file : '';
}

function orwo_theme_part( $name, $return = false ) {
    $file = orwo_part_file( $name );
    if ( ! $file ) { return ''; }
    $html = orwo_theme_fragment( (string) file_get_contents( $file ) );
    if ( $return ) { return $html; }
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated theme HTML, not user input.
    return '';
}

/** Page hero: breadcrumb, title and the original hero photograph (from the Media Library once imported). */
function orwo_page_hero( $title, $trail = '' ) {
    $file = get_theme_file_path( 'parts/page-hero.txt' );
    $image = is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : '';
    $style = preg_match( '~^/assets/[a-z0-9/._-]+$~', $image ) ? ' style="background-image:url(' . $image . ')"' : '';
    $html = '<section class="inner-hero"' . $style . '><div class="grain" aria-hidden="true"></div><div class="wrap"><div class="crumb"><a href="/">Home</a>' . ( $trail ? ' / ' . esc_html( $trail ) : '' ) . '</div><h1 class="reveal-title">' . esc_html( $title ) . '</h1></div></section>';
    echo orwo_theme_fragment( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above; fixed theme tokens.
}

/** Request path relative to the site root, e.g. "contact" or "pages/cookie-policy". */
function orwo_request_path() {
    $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- Only compared with fixed strings.
    $base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $base && str_starts_with( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    return trim( rawurldecode( $path ), '/' );
}

/**
 * Original URLs served by the theme before (or without) a WordPress page existing:
 * /contact (the contact form) and /pages/cookie-policy (the original Strikingly cookie policy).
 */
function orwo_virtual_route() {
    if ( ! is_404() ) { return ''; }
    $map = array( 'contact' => 'contact', 'pages/cookie-policy' => 'cookie-policy' );
    return $map[ orwo_request_path() ] ?? '';
}
add_action( 'template_redirect', function () {
    if ( ! orwo_virtual_route() ) { return; }
    $GLOBALS['orwo_route'] = orwo_virtual_route();
    global $wp_query;
    $wp_query->is_404 = false;
    status_header( 200 );
}, 0 );
add_filter( 'template_include', function ( $template ) {
    $route = $GLOBALS['orwo_route'] ?? '';
    if ( ! $route ) { return $template; }
    return get_theme_file_path( 'contact' === $route ? 'page-contact.php' : 'template-cookie-policy.php' );
} );

function orwo_current_route() {
    if ( is_front_page() ) { return 'home'; }
    if ( ! empty( $GLOBALS['orwo_route'] ) ) { return $GLOBALS['orwo_route']; }
    if ( is_page( 'contact' ) ) { return 'contact'; }
    return '';
}

function orwo_social_links( $class = 'socials big' ) {
    $html = orwo_theme_part( 'contact-socials', true );
    return str_replace( 'class="socials big"', 'class="' . esc_attr( $class ) . '"', $html );
}
