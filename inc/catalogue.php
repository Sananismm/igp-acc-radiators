<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	register_post_type( 'igp_product', array( 'labels' => array( 'name' => 'Products', 'singular_name' => 'Product', 'add_new_item' => 'Add product', 'edit_item' => 'Edit product' ), 'public' => true, 'show_in_rest' => true, 'has_archive' => 'products', 'rewrite' => array( 'slug' => 'products', 'with_front' => false ), 'menu_icon' => 'dashicons-admin-tools', 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ), 'taxonomies' => array( 'igp_category' ) ) );
	register_taxonomy( 'igp_category', array( 'igp_product' ), array( 'labels' => array( 'name' => 'Product categories', 'singular_name' => 'Product category' ), 'public' => true, 'show_in_rest' => true, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'categories', 'with_front' => false ) ) );
} );
add_action( 'after_switch_theme', function () { flush_rewrite_rules(); } );

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
	return isset( $map[ $slug ] ) ? IGP_URI . '/assets/images/categories/' . $map[ $slug ] . '.webp' : '';
}
function igp_product_image( $id ) {
	$image = get_the_post_thumbnail_url( $id, 'large' );
	if ( $image ) { return $image; }
	$file = get_post_meta( $id, '_igp_source_image', true );
	return $file ? IGP_URI . '/assets/images/products/' . rawurlencode( basename( $file ) ) : '';
}
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'igp_details', 'Product details', function ( $post ) {
		wp_nonce_field( 'igp_save_details', 'igp_details_nonce' );
		foreach ( array( 'working_principle' => 'Working principle', 'daily_use' => 'Daily use', 'manufacturer' => 'Manufacturer', 'part_number' => 'Part number' ) as $key => $label ) {
			echo '<p><label for="igp_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br><input class="widefat" id="igp_' . esc_attr( $key ) . '" name="igp_' . esc_attr( $key ) . '" value="' . esc_attr( get_post_meta( $post->ID, '_igp_' . $key, true ) ) . '"></p>';
		}
		echo '<p><label><input type="checkbox" name="igp_featured" value="1" ' . checked( get_post_meta( $post->ID, '_igp_featured', true ), '1', false ) . '> Featured on homepage</label></p><p>Set a featured image to replace the supplied product image.</p>';
	}, 'igp_product', 'normal', 'high' );
} );
add_action( 'save_post_igp_product', function ( $id ) {
	if ( ! isset( $_POST['igp_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igp_details_nonce'] ) ), 'igp_save_details' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) { return; }
	foreach ( array( 'working_principle', 'daily_use', 'manufacturer', 'part_number' ) as $key ) { update_post_meta( $id, '_igp_' . $key, sanitize_text_field( wp_unslash( $_POST[ 'igp_' . $key ] ?? '' ) ) ); }
	update_post_meta( $id, '_igp_featured', isset( $_POST['igp_featured'] ) ? '1' : '0' );
} );
add_action( 'admin_menu', function () { add_management_page( 'IGP catalogue import', 'IGP catalogue import', 'manage_options', 'igp-import', 'igp_import_page' ); } );
function igp_import_page() { ?>
	<div class="wrap"><h1>IGP catalogue import</h1><p>Create the standard pages and import the supplied eight categories and 80 products into a new, empty WordPress catalogue. Existing products, categories and pages are not overwritten. Back up the database before importing.</p><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="igp_import"><?php wp_nonce_field( 'igp_import' ); submit_button( 'Create pages and import supplied catalogue' ); ?></form></div>
<?php }
add_action( 'admin_post_igp_import', function () {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not authorized', 403 ); }
	check_admin_referer( 'igp_import' );
	$existing = get_posts( array( 'post_type' => 'igp_product', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $existing || get_terms( array( 'taxonomy' => 'igp_category', 'hide_empty' => false, 'number' => 1, 'fields' => 'ids' ) ) ) { wp_die( 'The catalogue is not empty. Import stopped without changing existing catalogue data.' ); }
	$data = json_decode( file_get_contents( IGP_DIR . '/inc/catalogue.json' ), true );
	if ( empty( $data['categories'] ) || empty( $data['products'] ) ) { wp_die( 'Catalogue data is unavailable.' ); }
	foreach ( array( 'categories' => 'Categories', 'about' => 'About', 'contact' => 'Contact', 'request-quote' => 'Request a quote', 'privacy' => 'Privacy policy', 'terms' => 'Terms of use' ) as $slug => $title ) {
		if ( ! get_page_by_path( $slug ) ) { $content = in_array( $slug, array( 'privacy', 'terms' ), true ) ? 'Client-approved legal text is required before launch.' : ''; $page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content ), true ); if ( is_wp_error( $page_id ) ) { wp_die( esc_html( $page_id->get_error_message() ) ); } }
	}
	$terms = array(); $descriptions = igp_category_descriptions();
	foreach ( $data['categories'] as $name ) { $slug = sanitize_title( str_replace( array( '&', '/' ), ' ', $name ) ); $term = wp_insert_term( $name, 'igp_category', array( 'slug' => $slug, 'description' => $descriptions[ $slug ] ?? '' ) ); if ( is_wp_error( $term ) ) { wp_die( esc_html( $term->get_error_message() ) ); } $terms[ $name ] = (int) $term['term_id']; }
	foreach ( $data['products'] as $product ) { $category = get_term( $terms[ $product['category'] ], 'igp_category' ); $slug = $category->slug . '-' . sanitize_title( $product['name'] ); $id = wp_insert_post( array( 'post_type' => 'igp_product', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $product['name'], 'post_excerpt' => $product['dailyUse'] ), true ); if ( is_wp_error( $id ) ) { wp_die( esc_html( $id->get_error_message() ) ); } wp_set_object_terms( $id, array( $category->term_id ), 'igp_category' ); update_post_meta( $id, '_igp_working_principle', $product['workingPrinciple'] ); update_post_meta( $id, '_igp_daily_use', $product['dailyUse'] ); update_post_meta( $id, '_igp_featured', $product['featured'] ? '1' : '0' ); update_post_meta( $id, '_igp_source_image', basename( $product['image'] ) ); }
	wp_safe_redirect( admin_url( 'edit.php?post_type=igp_product' ) ); exit;
} );
