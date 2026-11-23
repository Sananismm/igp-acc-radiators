<?php
defined( 'ABSPATH' ) || exit;

// A missing brand is the historical IGP value. No bulk update of live posts is required.
function igp_brand( $id ) {
	return 'acc' === get_post_meta( $id, '_igp_brand', true ) ? 'acc' : 'igp';
}
function igp_brand_clause( $brand ) {
	return 'acc' === $brand ? array( 'key' => '_igp_brand', 'value' => 'acc' ) :
		array( 'relation' => 'OR', array( 'key' => '_igp_brand', 'compare' => 'NOT EXISTS' ), array( 'key' => '_igp_brand', 'value' => 'igp' ) );
}
function igp_acc_fields() {
	return array( 'make' => 'Vehicle make', 'model' => 'Vehicle model', 'years' => 'Model years', 'material' => 'Material', 'core' => 'Core construction', 'transmission' => 'Transmission', 'thickness' => 'Core thickness (mm)' );
}
function igp_input( $source, $key, $fallback = '' ) {
	return isset( $source[ $key ] ) && is_string( $source[ $key ] ) ? sanitize_text_field( wp_unslash( $source[ $key ] ) ) : $fallback;
}
add_action( 'init', function () {
	register_taxonomy( 'acc_category', 'igp_product', array(
		'label' => 'Radiator categories', 'hierarchical' => true, 'public' => false, 'show_ui' => true,
		'show_admin_column' => true, 'rewrite' => false, 'capabilities' => array_fill_keys( array( 'manage_terms','edit_terms','delete_terms','assign_terms' ), 'edit_igp_products' ),
	) );
	add_rewrite_rule( '^acc-radiators/products/([^/]+)/?$', 'index.php?post_type=igp_product&name=$matches[1]', 'top' );
} );
add_filter( 'post_type_link', function ( $url, $post, $leave ) {
	if ( 'igp_product' === $post->post_type && 'acc' === igp_brand( $post->ID ) ) {
		return home_url( user_trailingslashit( '/acc-radiators/products/' . ( $leave ? '%igp_product%' : $post->post_name ) ) );
	}
	return $url;
}, 10, 3 );
add_action( 'template_redirect', function () {
	if ( is_singular( 'igp_product' ) && ! is_preview() && ! is_feed() && get_option( 'permalink_structure' ) ) {
		$url = get_permalink();
		if ( untrailingslashit( wp_parse_url( $url, PHP_URL_PATH ) ) !== untrailingslashit( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ) ) {
			wp_safe_redirect( $url, 301 ); exit;
		}
	}
	$path = trim( $GLOBALS['wp']->request, '/' );
	$map = array( 'acc-radiators/index.html' => 'acc-radiators/', 'acc-radiators/about.html' => 'acc-radiators/about/', 'acc-radiators/products.html' => 'acc-radiators/products/', 'products.html' => 'acc-radiators/products/', 'login' => 'wp-login.php', 'admin' => 'wp-admin/' );
	if ( isset( $map[ $path ] ) ) {
		$url = home_url( '/' . $map[ $path ] );
		if ( str_ends_with( $path, 'products.html' ) ) {
			foreach ( array( 'make', 'model', 'mat', 'q' ) as $key ) {
				if ( isset( $_GET[ $key ] ) ) { $url = add_query_arg( $key, rawurlencode( igp_input( $_GET, $key ) ), $url ); }
			}
		}
		wp_safe_redirect( $url, 301 ); exit;
	}
}, 1 );
add_action( 'pre_get_posts', function ( $q ) {
	$type = $q->get( 'post_type' );
	if ( is_admin() ) {
		if ( ! $q->is_main_query() || ! in_array( $type, array( 'igp_enquiry','igp_product' ), true ) ) { return; }
		$brand = igp_input( $_GET, 'division' );
		if ( ! in_array( $brand, array( 'acc', 'igp' ), true ) ) { return; }
	} elseif ( $q->is_tax( 'igp_category' ) || ( 'igp_product' === $type && ! $q->is_singular() && ! $q->get( 'igp_all_brands' ) && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! get_query_var( 'sitemap' ) ) ) {
		$brand = $q->get( 'igp_division' ) ?: 'igp';
		$q->set( 'has_password', false );
	} else { return; }
	$meta = array( igp_brand_clause( $brand ) );
	if ( $q->get( 'meta_query' ) ) { $meta[] = $q->get( 'meta_query' ); }
	$q->set( 'meta_query', $meta );
} );
add_filter( 'wp_sitemaps_posts_query_args', function ( $args ) { $args['igp_all_brands'] = true; return $args; } );
add_action( 'restrict_manage_posts', function ( $type ) {
	if ( ! in_array( $type, array( 'igp_product', 'igp_enquiry' ), true ) ) { return; }
	echo '<select name="division" aria-label="Filter by brand"><option value="">All brands</option>';
	foreach ( array( 'igp' => 'Innovative Generator Parts', 'acc' => 'ACC Radiators' ) as $key => $name ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( igp_input( $_GET, 'division' ), $key, false ) . '>' . esc_html( $name ) . '</option>';
	}
	echo '</select>';
} );
foreach ( array( 'igp_product', 'igp_enquiry' ) as $type ) {
	add_filter( "manage_{$type}_posts_columns", function ( $cols ) { $cols['igp_brand'] = 'Brand'; return $cols; } );
	add_action( "manage_{$type}_posts_custom_column", function ( $col, $id ) { if ( 'igp_brand' === $col ) { echo 'acc' === igp_brand( $id ) ? 'ACC Radiators' : 'IGP'; } }, 10, 2 );
}
add_action( 'add_meta_boxes_igp_product', function () {
	add_meta_box( 'igp_division', 'Brand and radiator specifications', function ( $post ) {
		wp_nonce_field( 'igp_division_edit', 'igp_division_nonce' );
		echo '<p><label>Product brand <select name="igp_brand"><option value="igp">Innovative Generator Parts</option><option value="acc" ' . selected( igp_brand( $post->ID ), 'acc', false ) . '>ACC Radiators</option></select></label></p>';
		foreach ( igp_acc_fields() as $field => $label ) {
			echo '<p><label>' . esc_html( $label ) . '<input class="widefat" name="acc_' . esc_attr( $field ) . '" value="' . esc_attr( get_post_meta( $post->ID, '_acc_' . $field, true ) ) . '"></label></p>';
		}
		echo '<p>Set a featured image for product photography. Use Add Media → Create gallery in the description for additional images.</p>';
	}, 'igp_product', 'normal', 'high' );
} );
add_action( 'save_post_igp_product', function ( $id ) {
	if ( ! wp_verify_nonce( igp_input( $_POST, 'igp_division_nonce' ), 'igp_division_edit' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) { return; }
	$brand = igp_input( $_POST, 'igp_brand' );
	if ( ! in_array( $brand, array( 'igp','acc' ), true ) ) { return; }
	update_post_meta( $id, '_igp_brand', $brand );
	wp_set_object_terms( $id, array(), 'acc' === $brand ? 'igp_category' : 'acc_category' );
	foreach ( igp_acc_fields() as $field => $label ) { update_post_meta( $id, '_acc_' . $field, igp_input( $_POST, 'acc_' . $field ) ); }
} );
function igp_install_roles() {
	$product = get_post_type_object( 'igp_product' );
	if ( ! $product || '2.2' === get_option( 'igp_role_schema' ) ) { return; }
	$caps = array_diff( array_values( (array) $product->cap ), array( 'edit_igp_product', 'read_igp_product', 'delete_igp_product' ) );
	add_role( 'igp_catalogue_editor', 'Catalogue editor', array( 'read' => true, 'upload_files' => true ) );
	add_role( 'igp_enquiry_staff', 'Enquiry staff', array( 'read' => true ) );
	foreach ( array( 'administrator','igp_catalogue_editor' ) as $role ) {
		foreach ( $caps as $cap ) {
			if ( 'igp_catalogue_editor' === $role && str_starts_with( $cap, 'delete_' ) ) { get_role( $role )->remove_cap( $cap ); }
			else { get_role( $role )->add_cap( $cap ); }
		}
	}
	foreach ( array( 'administrator','igp_enquiry_staff' ) as $role ) { get_role( $role )->add_cap( 'igp_manage_enquiries' ); }
	update_option( 'igp_role_schema', '2.2' );
}
add_action( 'init', 'igp_install_roles', 20 );



add_filter( 'request', function ( $vars ) {
	if ( 'igp_product' === ( $vars['post_type'] ?? '' ) && empty( $vars['name'] ) ) {
		// The original tag filter is evaluated by the product catalogue, not the blog.
		unset( $vars['tag'] );
	}
	if ( 'igp_product' === ( $vars['post_type'] ?? '' ) && empty( $vars['name'] ) && isset( $vars['page'] ) && is_scalar( $vars['page'] ) && ctype_digit( (string) $vars['page'] ) ) {
		$vars['paged'] = max( 1, (int) $vars['page'] ); unset( $vars['page'] );
	}
	return $vars;
} );
