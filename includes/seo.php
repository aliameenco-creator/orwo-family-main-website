<?php
/**
 * Original SEO data (title, description, canonical, Open Graph) for the theme-served routes,
 * from parts/seo.json. When an SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress) is active it owns the
 * homepage tags; the theme still titles the routes it serves itself (/contact, /pages/cookie-policy).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function orwo_seo_data( $route ) {
    static $data = null;
    if ( null === $data ) {
        $file = get_theme_file_path( 'parts/seo.json' );
        $data = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return $data[ $route ] ?? array();
}

function orwo_seo_plugin_active() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function orwo_route_url( $route ) {
    $paths = array( 'home' => '/', 'contact' => '/contact', 'cookie-policy' => '/pages/cookie-policy' );
    return home_url( $paths[ $route ] ?? '/' );
}

add_filter( 'pre_get_document_title', function ( $title ) {
    $route = orwo_current_route();
    $virtual = ! empty( $GLOBALS['orwo_route'] );
    if ( ! $route || ( orwo_seo_plugin_active() && ! $virtual ) ) { return $title; }
    $seo = orwo_seo_data( $route );
    return ! empty( $seo['title'] ) ? $seo['title'] : $title;
}, 99 );

add_action( 'wp_head', function () {
    $route = orwo_current_route();
    if ( ! $route || orwo_seo_plugin_active() ) { return; }
    $seo = orwo_seo_data( $route );
    if ( ! $seo ) { return; }
    $url = orwo_route_url( $route );
    $title = $seo['title'] ?? get_bloginfo( 'name' );
    echo '<meta name="description" content="' . esc_attr( $seo['description'] ?? '' ) . '">' . "\n";
    if ( ! is_singular() ) { echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; }
    echo '<meta property="og:type" content="website"><meta property="og:site_name" content="ORWO Family"><meta property="og:title" content="' . esc_attr( $title ) . '"><meta property="og:description" content="' . esc_attr( $seo['description'] ?? '' ) . '"><meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
    $image = get_theme_file_uri( 'assets/orwo-share.png' );
    echo '<meta property="og:image" content="' . esc_url( $image ) . '"><meta name="twitter:card" content="summary"><meta name="twitter:title" content="' . esc_attr( $title ) . '"><meta name="twitter:description" content="' . esc_attr( $seo['description'] ?? '' ) . '"><meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
    if ( 'home' === $route ) {
        echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'ORWO Family', 'url' => home_url( '/' ) ), JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}, 3 );

/** Add the theme-served original URLs to WordPress's XML sitemap. */
add_action( 'init', function () {
    if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) { return; }
    wp_register_sitemap_provider( 'orwo', new class() extends WP_Sitemaps_Provider {
        public function __construct() { $this->name = 'orwo'; $this->object_type = 'orwo'; }
        public function get_url_list( $page_num, $object_subtype = '' ) {
            $urls = array( array( 'loc' => home_url( '/' ) ) );
            if ( ! get_page_by_path( 'contact' ) ) { $urls[] = array( 'loc' => home_url( '/contact' ) ); }
            $urls[] = array( 'loc' => home_url( '/pages/cookie-policy' ) );
            return $urls;
        }
        public function get_max_num_pages( $object_subtype = '' ) { return 1; }
    } );
} );
