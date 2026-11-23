<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	register_post_type( 'igp_product', array( 'labels' => array( 'name' => 'Products', 'singular_name' => 'Product', 'add_new_item' => 'Add product', 'edit_item' => 'Edit product' ), 'public' => true, 'show_in_rest' => true, 'capability_type' => array( 'igp_product','igp_products' ), 'map_meta_cap' => true, 'has_archive' => 'products', 'rewrite' => array( 'slug' => 'products', 'with_front' => false ), 'menu_icon' => 'dashicons-admin-tools', 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ), 'taxonomies' => array( 'igp_category' ) ) );
	register_taxonomy( 'igp_category', array( 'igp_product' ), array( 'labels' => array( 'name' => 'Product categories', 'singular_name' => 'Product category' ), 'public' => true, 'show_in_rest' => true, 'hierarchical' => true, 'capabilities'=>array_fill_keys( array( 'manage_terms','edit_terms','delete_terms','assign_terms' ), 'edit_igp_products' ), 'rewrite' => array( 'slug' => 'categories', 'with_front' => false ) ) );
} );
add_action( 'after_switch_theme', function () { flush_rewrite_rules(); } );
add_filter( 'posts_search', function ( $search, $query ) {
	if ( ! $query->get( 'igp_catalogue_search' ) || ! $query->get( 's' ) || ! $search ) { return $search; }
	global $wpdb;
	$term = '%' . $wpdb->esc_like( $query->get( 's' ) ) . '%';
	$extra = $wpdb->prepare( "EXISTS (SELECT 1 FROM {$wpdb->postmeta} igp_meta WHERE igp_meta.post_id = {$wpdb->posts}.ID AND igp_meta.meta_key IN ('_igp_manufacturer','_igp_part_number','_igp_oem_reference','_igp_working_principle','_igp_daily_use','_igp_compatible_brands','_igp_compatible_models','_acc_make','_acc_model','_acc_material','_acc_core','_acc_transmission','_acc_years') AND igp_meta.meta_value LIKE %s)", $term );
	if ( preg_match( '/^automatic\s+voltage\s+regulator$/i', $query->get( 's' ) ) ) { $extra .= $wpdb->prepare( " OR {$wpdb->posts}.post_title=%s", 'AVR' ); }
	$extra .= $wpdb->prepare( " OR EXISTS (SELECT 1 FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id JOIN {$wpdb->terms} t ON t.term_id=tt.term_id WHERE tr.object_id={$wpdb->posts}.ID AND tt.taxonomy IN ('igp_category','igp_tag') AND t.name LIKE %s)", $term );
	$password_guard = is_user_logged_in() ? '' : " AND {$wpdb->posts}.post_password = ''";
	return ' AND ((' . preg_replace( '/^\s*AND\s*/i', '', trim( $search ) ) . ') OR ' . $extra . ')' . $password_guard;
}, 10, 2 );

function igp_category_descriptions() {
	return array( 'engine-parts' => 'Core engine components that support combustion, timing and mechanical power transfer.', 'fuel-system' => 'Components that store, filter, meter and deliver diesel to the engine.', 'cooling-system' => 'Components that circulate coolant and manage operating temperature.', 'lubrication-system' => 'Components that circulate, filter and monitor engine lubrication.', 'air-exhaust' => 'Intake and exhaust components that manage airflow, boost and exhaust routing.', 'electrical-alternator' => 'Alternator components that generate, regulate and distribute electrical output.', 'control-protection' => 'Control, monitoring and protection equipment for safe generator operation.', 'consumables-replacement-parts' => 'Routine-service components used to maintain dependable generator operation.' );
}
function igp_categories() {
	$terms = get_terms( array( 'taxonomy' => 'igp_category', 'hide_empty' => true ) );
	if ( is_wp_error( $terms ) ) { return array(); }
	$order = array_flip( array_keys( igp_category_descriptions() ) );
	usort( $terms, function ( $a, $b ) use ( $order ) { return ( $order[ $a->slug ] ?? 999 ) <=> ( $order[ $b->slug ] ?? 999 ); } );
	return $terms;
}
function igp_category_image( $slug ) {
	$map = array( 'engine-parts' => 'engine-parts', 'fuel-system' => 'fuel-system', 'cooling-system' => 'cooling-system', 'lubrication-system' => 'lubrication-system', 'air-exhaust' => 'air-exhaust', 'electrical-alternator' => 'electrical-alternator', 'control-protection' => 'control-protection', 'consumables-replacement-parts' => 'replacement-parts' );
	return isset( $map[ $slug ] ) ? IGP_CORE_URI . 'assets/images/categories/' . $map[ $slug ] . '.webp' : '';
}
function igp_product_image( $id ) {
	$image = get_the_post_thumbnail_url( $id, 'large' );
	if ( $image ) { return $image; }
	$file = get_post_meta( $id, '_igp_source_image', true );
	return $file && is_file( IGP_CORE_DIR . 'assets/images/products/' . basename( $file ) ) ? IGP_CORE_URI . 'assets/images/products/' . rawurlencode( basename( $file ) ) : '';
}
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'igp_details', 'Product details', function ( $post ) {
		wp_nonce_field( 'igp_save_details', 'igp_details_nonce' );
		foreach ( array( 'working_principle' => 'Working principle', 'daily_use' => 'Daily use', 'manufacturer' => 'Manufacturer', 'part_number' => 'Part number', 'oem_reference' => 'OEM reference', 'compatible_brands' => 'Compatible brands', 'compatible_models' => 'Compatible models', 'engine_family' => 'Engine family', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description' ) as $key => $label ) {
			echo '<p><label for="igp_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br><input class="widefat" id="igp_' . esc_attr( $key ) . '" name="igp_' . esc_attr( $key ) . '" value="' . esc_attr( get_post_meta( $post->ID, '_igp_' . $key, true ) ) . '"></p>';
		}
		echo '<p><label><input type="checkbox" name="igp_featured" value="1" ' . checked( get_post_meta( $post->ID, '_igp_featured', true ), '1', false ) . '> Featured on homepage</label></p><p>Set a featured image to replace the supplied product image.</p>';
	}, 'igp_product', 'normal', 'high' );
} );
add_action( 'save_post_igp_product', function ( $id ) {
	if ( ! isset( $_POST['igp_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igp_details_nonce'] ) ), 'igp_save_details' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) { return; }
	foreach ( array( 'working_principle', 'daily_use', 'manufacturer', 'part_number', 'oem_reference', 'compatible_brands', 'compatible_models', 'engine_family', 'seo_title', 'seo_description' ) as $key ) { update_post_meta( $id, '_igp_' . $key, sanitize_text_field( wp_unslash( $_POST[ 'igp_' . $key ] ?? '' ) ) ); }
	update_post_meta( $id, '_igp_featured', isset( $_POST['igp_featured'] ) ? '1' : '0' );
} );
