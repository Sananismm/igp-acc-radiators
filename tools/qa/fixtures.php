<?php
// Install only in the mu-plugins folder of a disposable local QA site.
defined( 'ABSPATH' ) || exit;
if ( ! defined( 'IGP_LOCAL_QA' ) || ! IGP_LOCAL_QA || '127.0.0.1' !== wp_parse_url( get_option( 'home' ), PHP_URL_HOST ) ) { return; }
add_filter( 'pre_wp_mail', '__return_false' );
add_filter( 'query', function ( $sql ) {
	// Exercise the wp_insert_post() database error path for one synthetic customer only.
	global $wpdb;
	if ( isset( $_POST['action'], $_POST['name'] ) && 'igp_quote' === $_POST['action'] && 'QA storage failure' === $_POST['name'] && str_starts_with( $sql, 'INSERT INTO `' . $wpdb->posts . '`' ) ) {
		return str_replace( 'INSERT INTO `' . $wpdb->posts . '`', 'INSERT INTO `igp_qa_absent_table`', $sql );
	}
	return $sql;
} );
