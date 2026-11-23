<?php
/**
 * Plugin Name: IGP Unified Core
 * Description: Catalogue and enquiry data types for Innovative Generator Parts.
 * Version: 2.1.0
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// During activation WordPress may already have loaded the theme's fallback.
// The plugin takes ownership on the next request, before the theme is loaded.
if ( function_exists( 'igp_categories' ) ) { return; }

// Old releases of the theme load the same functions unconditionally. Avoid a fatal
// error and instruct the administrator to update the theme before enabling core.
$theme_functions = get_template_directory() . '/functions.php';
if ( is_readable( $theme_functions ) ) {
	$theme_source = file_get_contents( $theme_functions );
	if ( false !== strpos( $theme_source, "'/inc/catalogue.php'" ) && false === strpos( $theme_source, 'IGP_CORE_ACTIVE' ) ) {
		add_action( 'admin_notices', function () { echo '<div class="notice notice-error"><p>IGP Core is inactive: update the IGP theme to a compatible release first.</p></div>'; } );
		return;
	}
}
define( 'IGP_CORE_ACTIVE', true );
define( 'IGP_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'IGP_CORE_URI', plugin_dir_url( __FILE__ ) );
require_once IGP_CORE_DIR . 'inc/catalogue.php';
require_once IGP_CORE_DIR . 'inc/settings.php';
require_once IGP_CORE_DIR . 'inc/private-storage.php';
require_once IGP_CORE_DIR . 'inc/enquiries.php';
require_once IGP_CORE_DIR . 'inc/divisions.php';
require_once IGP_CORE_DIR . 'inc/vehicle-lookup.php';
require_once IGP_CORE_DIR . 'inc/submit.php';
require_once IGP_CORE_DIR . 'inc/import.php';
require_once IGP_CORE_DIR . 'inc/product-actions.php';
