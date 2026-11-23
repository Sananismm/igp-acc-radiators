<?php
defined( 'ABSPATH' ) || exit;
function igp_acc_choices() {
	global $wpdb;
	// One bounded-column query for the form, independent of the current results page.
	$rows = $wpdb->get_results( "SELECT DISTINCT make.meta_value AS make, model.meta_value AS model, material.meta_value AS material
		FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} b ON b.post_id=p.ID AND b.meta_key='_igp_brand' AND b.meta_value='acc'
		LEFT JOIN {$wpdb->postmeta} make ON make.post_id=p.ID AND make.meta_key='_acc_make'
		LEFT JOIN {$wpdb->postmeta} model ON model.post_id=p.ID AND model.meta_key='_acc_model'
		LEFT JOIN {$wpdb->postmeta} material ON material.post_id=p.ID AND material.meta_key='_acc_material'
		WHERE p.post_type='igp_product' AND p.post_status='publish' AND p.post_password='' ORDER BY make.meta_value, model.meta_value" );
	$choices = array( 'make'=>array(), 'model'=>array(), 'mat'=>array(), 'pairs'=>array() );
	foreach ( $rows as $row ) {
		foreach ( array( 'make'=>'make', 'model'=>'model', 'mat'=>'material' ) as $key=>$field ) {
			if ( ! empty( $row->$field ) ) { $choices[ $key ][ $row->$field ] = $row->$field; }
		}
		if ( $row->make && $row->model ) { $choices['pairs'][ $row->make ][] = $row->model; }
	}
	foreach ( array( 'make','model','mat' ) as $key ) { natcasesort( $choices[ $key ] ); }
	return $choices;
}
function igp_acc_filters( $choices ) {
	$state = array(); $valid = true; $seen = array();
	foreach ( explode( '&', wp_unslash( $_SERVER['QUERY_STRING'] ?? '' ) ) as $param ) {
		if ( '' === $param ) { continue; }
		$key = urldecode( explode( '=', $param, 2 )[0] );
		if ( isset( $seen[ $key ] ) || ! in_array( $key, array( 'make','model','q','mat','pg' ), true ) ) { $valid = false; }
		$seen[ $key ] = true;
	}
	foreach ( array( 'make','model','mat','q' ) as $key ) {
		$value = igp_input( $_GET, $key );
		if ( 'All' === $value && in_array( $key, array( 'make','mat' ), true ) ) { $value = ''; }
		$state[ $key ] = $value;
		if ( $value !== '' && 'q' !== $key && ! isset( $choices[ $key ][ $value ] ) ) { $valid = false; }
	}
	if ( $state['make'] && $state['model'] && ! in_array( $state['model'], $choices['pairs'][ $state['make'] ] ?? array(), true ) ) { $valid = false; }
	$page = igp_input( $_GET, 'pg', '1' );
	$state['pg'] = ctype_digit( $page ) && strlen( $page ) < 6 && (int) $page >= 1 ? (int) $page : 0;
	$state['valid'] = $valid && $state['pg'] && mb_strlen( $state['q'] ) <= 200;
	return $state;
}
function igp_radiator_query( $state ) {
	$args = array( 'post_type'=>'igp_product','post_status'=>'publish','has_password'=>false,'igp_division'=>'acc','posts_per_page'=>24,'paged'=>$state['pg'],'orderby'=>array( 'title'=>'ASC','ID'=>'ASC' ),'meta_query'=>array() );
	foreach ( array( 'make'=>'make','model'=>'model','mat'=>'material' ) as $key=>$field ) {
		if ( '' !== $state[ $key ] ) { $args['meta_query'][] = array( 'key'=>'_acc_' . $field, 'value'=>$state[ $key ] ); }
	}
	if ( $state['q'] ) { $args['s'] = $state['q']; $args['igp_catalogue_search'] = true; }
	if ( ! $state['valid'] ) { $args['post__in'] = array( 0 ); }
	return new WP_Query( $args );
}
