<?php
defined( 'ABSPATH' ) || exit;

function igp_seed_rows() {
	$igp = json_decode( file_get_contents( IGP_CORE_DIR . 'inc/catalogue.json' ), true, 512, JSON_THROW_ON_ERROR );
	$acc = json_decode( file_get_contents( IGP_CORE_DIR . 'inc/radiators.json' ), true, 512, JSON_THROW_ON_ERROR );
	$rows = array();
	foreach ( $igp['products'] as $p ) {
		$category = sanitize_title( str_replace( array( '&', '/' ), ' ', $p['category'] ) );
		$rows[] = array( 'source' => $p['id'], 'slug' => $category . '-' . sanitize_title( $p['name'] ), 'title' => $p['name'], 'excerpt' => $p['dailyUse'], 'category' => $p['category'], 'taxonomy' => 'igp_category', 'category_slug' => $category,
			'meta' => array( '_igp_brand' => 'igp','_igp_daily_use' => $p['dailyUse'], '_igp_working_principle' => $p['workingPrinciple'], '_igp_featured' => $p['featured'] ? '1' : '0', '_igp_source_image' => basename( $p['image'] ) ) );
	}
	foreach ( $acc as $p ) {
		$meta = array( '_igp_brand' => 'acc', '_igp_part_number' => $p['part'], '_igp_source_image' => $p['image'] );
		foreach ( igp_acc_fields() as $key => $label ) { $meta[ '_acc_' . $key ] = (string) $p[ $key ]; }
		$rows[] = array( 'source' => 'acc:' . $p['slug'], 'slug' => $p['slug'], 'title' => $p['name'], 'excerpt' => '', 'category' => $p['material'], 'taxonomy' => 'acc_category', 'category_slug' => sanitize_title( $p['material'] ), 'meta' => $meta );
	}
	return $rows;
}
function igp_match_seed( $row ) {
	global $wpdb;
	// Check all statuses, including renamed, archived and trashed source records.
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON p.ID=m.post_id WHERE p.post_type='igp_product' AND (p.post_name=%s OR (m.meta_key='_igp_source_id' AND m.meta_value=%s) OR (m.meta_key IN ('_wp_desired_post_slug','_wp_old_slug') AND m.meta_value=%s) OR (m.meta_key='_igp_part_number' AND m.meta_value=%s AND %s<>''))",
		$row['slug'], $row['source'], $row['slug'], $row['meta']['_igp_part_number'] ?? '', $row['meta']['_igp_part_number'] ?? '' ) );
	if ( $wpdb->last_error || count( $ids ) > 1 ) { throw new RuntimeException( 'Ambiguous catalogue match or lookup failure for ' . $row['source'] ); }
	if ( ! $ids ) { return 0; }
	$id = (int) $ids[0];
	$source = get_post_meta( $id, '_igp_source_id', true );
	if ( $source === $row['source'] ) { return $id; }
	// Only adopt a foundation record carrying its original image identifier.
	if ( $source || 'igp' !== $row['meta']['_igp_brand'] || igp_brand( $id ) !== 'igp' || $row['meta']['_igp_source_image'] !== get_post_meta( $id, '_igp_source_image', true ) ) {
		throw new RuntimeException( 'Conflicting existing product for ' . $row['source'] . '. Review its source identity before continuing.' );
	}
	return $id;
}
function igp_import_products( $dry = true ) {
	global $wpdb;
	$lock = 'igp_import_' . substr( md5( DB_NAME . $wpdb->prefix ), 0, 32 );
	if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,0)', $lock ) ) ) { throw new RuntimeException( 'An import is in progress or the database lock is unavailable.' ); }
	$report = array( 'create' => 0, 'preserve' => 0, 'pages' => 0 );
	try {
		foreach ( array( $wpdb->options, $wpdb->posts, $wpdb->postmeta, $wpdb->terms, $wpdb->term_taxonomy, $wpdb->term_relationships ) as $table ) {
			$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $table ) );
			if ( 'InnoDB' !== $engine ) { throw new RuntimeException( 'Transactional InnoDB tables are required for this import.' ); }
		}
		$jobs = array();
		foreach ( igp_seed_rows() as $row ) {
			$file = IGP_CORE_DIR . 'assets/images/products/' . basename( $row['meta']['_igp_source_image'] );
			if ( ! is_readable( $file ) ) { throw new RuntimeException( 'Missing bundled image for ' . $row['source'] ); }
			$id = igp_match_seed( $row );
			$report[ $id ? 'preserve' : 'create' ]++;
			$jobs[] = array( $id, $row );
		}
		foreach ( array( 'categories'=>'Categories','about'=>'About','contact'=>'Contact','request-quote'=>'Request a quote','privacy'=>'Privacy policy','terms'=>'Terms of use','acc-radiators'=>'ACC Radiators','acc-radiators/products'=>'Radiator catalogue','acc-radiators/about'=>'About ACC Radiators','acc-radiators/contact'=>'Contact ACC Radiators' ) as $path => $title ) {
			$page = get_page_by_path( $path, OBJECT, array( 'page' ) );
			if ( $page ) {
				if ( 'publish' !== $page->post_status && ! in_array( $path, array( 'privacy','terms' ), true ) ) { throw new RuntimeException( 'Review the existing unpublished page at ' . $path ); }
				continue;
			}
			$report['pages']++;
			if ( $dry ) { continue; }
			$parent = str_contains( $path, '/' ) ? get_page_by_path( dirname( $path ), OBJECT, array( 'page' ) )->ID : 0;
			$page_id = wp_insert_post( array( 'post_type'=>'page', 'post_name'=>basename( $path ), 'post_parent'=>$parent, 'post_title'=>$title, 'post_status'=>in_array( $path, array( 'privacy','terms' ), true ) ? 'draft' : 'publish' ), true );
			if ( is_wp_error( $page_id ) || get_page_uri( $page_id ) !== $path ) { throw new RuntimeException( 'Could not create page ' . $path ); }
		}
		if ( $dry ) { return $report; }
		foreach ( $jobs as list( $id, $row ) ) {
			if ( $id && get_post_meta( $id, '_igp_source_id', true ) === $row['source'] ) { continue; }
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new RuntimeException( 'Could not start import transaction.' ); }
			try {
				if ( ! $id ) {
					$term = term_exists( $row['category_slug'], $row['taxonomy'] );
					if ( ! $term ) { $term = wp_insert_term( $row['category'], $row['taxonomy'], array( 'slug' => $row['category_slug'], 'description' => igp_category_descriptions()[ $row['category_slug'] ] ?? '' ) ); }
					if ( is_wp_error( $term ) ) { throw new RuntimeException( $term->get_error_message() ); }
					$id = wp_insert_post( array( 'post_type'=>'igp_product', 'post_status'=>'publish', 'post_title'=>$row['title'], 'post_excerpt'=>$row['excerpt'], 'post_name'=>$row['slug'] ), true );
					if ( is_wp_error( $id ) || ! $id || $row['slug'] !== get_post_field( 'post_name', $id ) ) { throw new RuntimeException( 'Unable to create product ' . $row['source'] ); }
					$set = wp_set_object_terms( $id, array( (int) $term['term_id'] ), $row['taxonomy'] );
					if ( is_wp_error( $set ) || ! $set || ! has_term( (int) $term['term_id'], $row['taxonomy'], $id ) ) { throw new RuntimeException( 'Category assignment failed for ' . $row['source'] ); }
					foreach ( $row['meta'] as $key => $value ) {
						if ( ! add_post_meta( $id, $key, $value, true ) ) { throw new RuntimeException( 'Unable to save metadata for ' . $row['source'] ); }
					}
				}
				if ( ! add_post_meta( $id, '_igp_source_id', $row['source'], true ) ) { throw new RuntimeException( 'Unable to record source identity.' ); }
				if ( false === $wpdb->query( 'COMMIT' ) ) { throw new RuntimeException( 'Could not confirm the import transaction.' ); }
			} catch ( Throwable $e ) { $wpdb->query( 'ROLLBACK' ); if ( is_numeric( $id ) ) { clean_post_cache( $id ); } throw $e; }
		}
		flush_rewrite_rules( false );
		return $report;
	} finally { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
}
add_action( 'admin_menu', function () { add_management_page( 'Catalogue import', 'Catalogue import', 'manage_options', 'igp-import', 'igp_import_page' ); } );
function igp_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.', '', array( 'response'=>403 ) ); }
	echo '<div class="wrap"><h1>Catalogue import</h1><p>Check the bundled IGP and ACC catalogues against your site. Existing products retain their IDs, status and editorial changes. Back up the database before importing.</p>';
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		check_admin_referer( 'igp_import' );
		$dry = 'import' !== igp_input( $_POST, 'operation' );
		try {
			$r = igp_import_products( $dry );
			echo '<div class="notice notice-success"><p>' . esc_html( sprintf( '%s · Products to create / created: %d. Existing products preserved: %d. Pages to create / created: %d.', $dry ? 'Dry run' : 'Import complete', $r['create'], $r['preserve'], $r['pages'] ) ) . '</p></div>';
		} catch ( Throwable $e ) { echo '<div class="notice notice-error"><p>' . esc_html( $e->getMessage() ) . '</p></div>'; }
	}
	echo '<form method="post">'; wp_nonce_field( 'igp_import' );
	echo '<button class="button" name="operation" value="preview">Dry run</button> <button class="button button-primary" name="operation" value="import">Import catalogues and pages</button></form></div>';
}
