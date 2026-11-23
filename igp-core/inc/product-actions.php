<?php
defined( 'ABSPATH' ) || exit;
add_action( 'init', function () {
	register_post_status( 'igp_archived', array( 'label'=>'Archived','internal'=>false,'protected'=>true,'exclude_from_search'=>true,'show_in_admin_all_list'=>true,'show_in_admin_status_list'=>true,'label_count'=>_n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' ) ) );
	register_taxonomy( 'igp_tag','igp_product',array( 'label'=>'Part tags','public'=>false,'show_ui'=>true,'show_in_rest'=>true,'rewrite'=>false,'capabilities'=>array_fill_keys( array( 'manage_terms','edit_terms','delete_terms','assign_terms' ),'edit_igp_products' ) ) );
} );
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'igp_product' !== $post->post_type || 'trash' === $post->post_status || ! current_user_can( 'edit_post', $post->ID ) ) { return $actions; }
	$options = array( 'copy'=>'Duplicate as draft' );
	$options += 'igp_archived' === $post->post_status ? array( 'restore'=>'Restore as draft' ) : array( 'archive'=>'Archive' );
	foreach ( $options as $key=>$label ) {
		$url = add_query_arg( array( 'action'=>'igp_product_action','operation'=>$key,'id'=>$post->ID ), admin_url( 'admin-post.php' ) );
		$actions[ 'igp_' . $key ] = '<a href="' . esc_url( wp_nonce_url( $url, 'igp_' . $key . '_' . $post->ID ) ) . '">' . esc_html( $label ) . '</a>';
	}
	return $actions;
}, 10, 2 );
add_action( 'admin_post_igp_product_action', function () {
	$id = absint( igp_input( $_GET, 'id' ) ); $action = igp_input( $_GET, 'operation' );
	$p = get_post( $id );
	if ( ! $p || 'igp_product' !== $p->post_type || ! current_user_can( 'edit_post',$id ) || ! in_array( $action,array( 'copy','restore','archive' ),true ) ) { wp_die( 'Access denied.', '',array( 'response'=>403 ) ); }
	check_admin_referer( 'igp_' . $action . '_' . $id );
	if ( 'copy' === $action ) {
		$meta = array();
		foreach ( array_keys( get_post_meta( $id ) ) as $key ) {
			if ( ! in_array( $key,array( '_igp_source_id','_igp_featured' ),true ) && ( str_starts_with( $key,'_igp_' ) || str_starts_with( $key,'_acc_' ) || '_thumbnail_id' === $key ) ) { $meta[ $key ] = get_post_meta( $id,$key,true ); }
		}
		$meta['_igp_featured'] = '0';
		$tax = array();
		foreach ( get_object_taxonomies( 'igp_product' ) as $name ) { $tax[ $name ] = wp_get_object_terms( $id, $name, array( 'fields'=>'ids' ) ); }
		$result = wp_insert_post( wp_slash( array( 'post_type'=>'igp_product','post_status'=>'draft','post_title'=>$p->post_title . ' (copy)','post_excerpt'=>$p->post_excerpt,'post_content'=>$p->post_content,'meta_input'=>$meta,'tax_input'=>$tax ) ), true );
	} else { $result = wp_update_post( array( 'ID'=>$id,'post_status'=>'restore' === $action ? 'draft' : 'igp_archived' ),true ); }
	if ( is_wp_error( $result ) || ! $result ) { wp_die( 'The product change could not be saved.' ); }
	wp_safe_redirect( admin_url( 'edit.php?post_type=igp_product' ) ); exit;
} );

add_filter( 'use_block_editor_for_post_type', function ( $use, $type ) {
	return 'igp_product' === $type ? false : $use;
}, 10, 2 );
