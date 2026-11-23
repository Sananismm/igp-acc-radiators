<?php
// WP-CLI eval-file on a disposable, local installation with the complete seed.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) || ! defined( 'IGP_LOCAL_QA' ) || ! IGP_LOCAL_QA ) {
	throw new RuntimeException( 'This check requires a disposable loopback QA site.' );
}
global $wpdb;
$row = get_page_by_path( 'suzuki-alto', OBJECT, array( 'igp_product' ) );
if ( ! $row || 'acc:suzuki-alto' !== get_post_meta( $row->ID, '_igp_source_id', true ) ) { throw new RuntimeException( 'Import the supplied catalogue first.' ); }
$test_id = wp_insert_post( array( 'post_type'=>'igp_product','post_status'=>'draft','post_title'=>'QA import collision','meta_input'=>array( '_igp_part_number'=>'ACC-SZ-0660','_igp_brand'=>'acc' ) ),true );
if ( is_wp_error( $test_id ) ) { throw new RuntimeException( 'Could not create QA fixture.' ); }
try {
	$blocked = false;
	try { igp_import_products( false ); } catch ( RuntimeException $e ) { $blocked = str_contains( $e->getMessage(),'Ambiguous catalogue match' ); }
	if ( ! $blocked ) { throw new RuntimeException( 'Conflicting source identity was accepted.' ); }
} finally { wp_delete_post( $test_id,true ); }
$db2 = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
$key = 'igp_import_' . substr( md5( DB_NAME . $wpdb->prefix ),0,32 );
$db2->get_var( $db2->prepare( 'SELECT GET_LOCK(%s,0)', $key ) );
try {
	$blocked = false;
	try { igp_import_products( false ); } catch ( RuntimeException $e ) { $blocked = str_contains( $e->getMessage(),'import is in progress' ); }
	if ( ! $blocked ) { throw new RuntimeException( 'A concurrent importer entered the write path.' ); }
} finally { $db2->get_var( $db2->prepare( 'SELECT RELEASE_LOCK(%s)', $key ) ); $db2->close(); }
$result = igp_import_products( true );
if ( 0 !== $result['create'] || 92 !== $result['preserve'] ) { throw new RuntimeException( 'Unexpected seed count after conflict checks.' ); }
echo "PASS conflicting part number, simultaneous import lock and subsequent clean dry run.\n";
