<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'IGP_DIR', get_template_directory() );
define( 'IGP_URI', get_template_directory_uri() );
require_once IGP_DIR . '/inc/division-theme.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 150, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array( 'primary' => __( 'Main navigation', 'igp' ) ) );
} );
add_action( 'widgets_init', function () {
	register_sidebar( array( 'name' => __( 'Sidebar', 'igp' ), 'id' => 'sidebar-1', 'before_widget' => '<section class="widget">', 'after_widget' => '</section>', 'before_title' => '<h2>', 'after_title' => '</h2>' ) );
} );
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'igp-theme', get_stylesheet_uri(), array(), '2.1.0' );
	wp_enqueue_style( 'igp-site', IGP_URI . '/assets/css/site.css', array( 'igp-theme' ), '2.1.0' );
	wp_enqueue_script( 'igp-site', IGP_URI . '/assets/js/site.js', array(), '2.1.0', true );
} );
add_action( 'wp_head', function () { if ( ! has_site_icon() ) { echo '<link rel="icon" href="' . esc_url( IGP_URI . '/assets/images/favicon.svg' ) . '" type="image/svg+xml">'; } } );
function igp_url( $path = '' ) { return home_url( '/' . ltrim( $path, '/' ) ); }
function igp_whatsapp( $name = '', $url = '' ) {
	$message = $name ? "Hello Innovative Generator Parts, I'm interested in the following product: {$name}. Product link: {$url} Could you please provide more information and a quotation?" : "Hello Innovative Generator Parts, I would like some information about your generator parts. Could you please help me?";
	$settings = function_exists( 'igp_core_settings' ) ? igp_core_settings() : array( 'whatsapp' => '923008877677' );
	return 'https://wa.me/' . preg_replace( '/\D+/', '', $settings['whatsapp'] ) . '?text=' . rawurlencode( $message );
}
function igp_icon() { return '<svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M16 .5A15.5 15.5 0 0 0 2.75 24.04L.55 31.5l7.66-2.08A15.5 15.5 0 1 0 16 .5Zm0 28.2a12.65 12.65 0 0 1-6.47-1.78l-.46-.27-4.55 1.24 1.3-4.42-.3-.47A12.67 12.67 0 1 1 16 28.7Zm6.96-9.49c-.38-.19-2.25-1.11-2.6-1.24-.35-.13-.6-.19-.85.19s-.98 1.24-1.2 1.49c-.22.25-.44.28-.82.09-.38-.19-1.6-.59-3.05-1.88a11.4 11.4 0 0 1-2.11-2.63c-.22-.38-.02-.59.17-.78.17-.17.38-.44.57-.66.19-.22.25-.38.38-.63.13-.25.06-.47-.03-.66-.09-.19-.85-2.05-1.17-2.81-.31-.74-.62-.64-.85-.65h-.72c-.25 0-.66.1-1.01.47-.35.38-1.32 1.29-1.32 3.15s1.36 3.66 1.55 3.91c.19.25 2.67 4.08 6.46 5.72.9.39 1.61.63 2.16.8.91.29 1.73.25 2.38.15.73-.11 2.25-.92 2.56-1.8.32-.88.32-1.64.22-1.8-.09-.16-.35-.25-.73-.44Z"/></svg>'; }
