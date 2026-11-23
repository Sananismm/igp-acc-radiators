<?php
defined( 'ABSPATH' ) || exit;
add_action( 'init', function () {
    $cap = 'igp_manage_enquiries';
    register_post_type( 'igp_enquiry', array(
        'labels'=>array( 'name'=>'Enquiries','singular_name'=>'Enquiry' ), 'public'=>false, 'publicly_queryable'=>false,
        'show_ui'=>true,'show_in_rest'=>false,'exclude_from_search'=>true,'menu_icon'=>'dashicons-email-alt','supports'=>array( 'title','editor' ),
        'map_meta_cap'=>false,'capabilities'=>array_merge( array_fill_keys( array( 'edit_post','read_post','edit_posts','edit_others_posts','read_private_posts','edit_private_posts','edit_published_posts' ), $cap ), array( 'publish_posts'=>'do_not_allow','create_posts'=>'do_not_allow','delete_post'=>'manage_options','delete_posts'=>'manage_options','delete_private_posts'=>'manage_options','delete_others_posts'=>'manage_options','delete_published_posts'=>'manage_options' ) ),
    ) );
} );
add_filter( 'wp_insert_post_data', function ( $data ) {
    if ( 'igp_enquiry' === $data['post_type'] && ! in_array( $data['post_status'], array( 'trash','auto-draft' ), true ) ) { $data['post_status'] = 'private'; }
    return $data;
} );
add_action( 'add_meta_boxes_igp_enquiry', function () { add_meta_box( 'igp_notification', 'Email notification', function ( $post ) { $status = get_post_meta( $post->ID, '_igp_notification_status', true ); $labels = array( 'accepted_by_mailer' => 'Accepted by the mailer; delivery is not guaranteed.', 'failed' => 'Sending failed. Check SMTP and follow up manually.', 'not_configured' => 'Notifications are not configured.' ); echo esc_html( $labels[ $status ] ?? 'No notification status is recorded.' ); }, 'igp_enquiry', 'side' ); } );
add_action( 'add_meta_boxes_igp_enquiry', function () { add_meta_box( 'igp_enquiry_file', 'Private attachment', function ( $post ) { if ( get_post_meta( $post->ID, '_igp_attachment', true ) ) { echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=igp_attachment&id=' . $post->ID ), 'igp_attachment_' . $post->ID ) ) . '">Download attachment</a>'; } else { echo 'No attachment.'; } }, 'igp_enquiry', 'side' ); } );
add_action( 'add_meta_boxes_igp_enquiry', function () { add_meta_box( 'igp_enquiry_status', 'Enquiry status', function ( $post ) { wp_nonce_field( 'igp_enquiry_status', 'igp_enquiry_status_nonce' ); $status = get_post_meta( $post->ID, '_igp_status', true ) ?: 'new'; echo '<label for="igp_status">Status</label> <select id="igp_status" name="igp_status">'; foreach ( array( 'new' => 'New', 'reviewing' => 'Reviewing', 'responded' => 'Responded', 'closed' => 'Closed' ) as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>'; } echo '</select>'; }, 'igp_enquiry', 'side' ); } );
add_action( 'save_post_igp_enquiry', function ( $id ) { if ( ! isset( $_POST['igp_enquiry_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igp_enquiry_status_nonce'] ) ), 'igp_enquiry_status' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) { return; } $status = sanitize_key( wp_unslash( $_POST['igp_status'] ?? '' ) ); if ( in_array( $status, array( 'new', 'reviewing', 'responded', 'closed' ), true ) ) { update_post_meta( $id, '_igp_status', $status ); } } );
add_action( 'admin_post_igp_attachment', function () {
	$id = absint( $_GET['id'] ?? 0 ); if ( ! $id || 'igp_enquiry' !== get_post_type( $id ) || ! current_user_can( 'igp_manage_enquiries' ) || ! current_user_can( 'edit_post', $id ) ) { wp_die( 'Not authorized', 'Forbidden', array( 'response' => 403 ) ); }
	check_admin_referer( 'igp_attachment_' . $id ); $file = get_post_meta( $id, '_igp_attachment', true ); if ( ! is_array( $file ) ) { wp_die( 'Not found', 'Not found', array( 'response' => 404 ) ); }
	$data = igp_read_private_data( $file ); if ( false === $data ) { wp_die( 'Attachment unavailable.' ); } nocache_headers(); header( 'Content-Type: application/octet-stream' ); header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file['name'] ) . '"' ); header( 'X-Content-Type-Options: nosniff' ); header( 'Content-Length: ' . strlen( $data ) ); echo $data; exit;
} );
add_action( 'admin_menu', function () {
	$parent = 'edit.php?post_type=igp_enquiry';
	foreach ( array( 'igp'=>'IGP enquiries', 'acc'=>'ACC enquiries' ) as $brand=>$label ) {
		add_submenu_page( $parent, $label, $label, 'igp_manage_enquiries', $parent . '&division=' . $brand );
	}
} );
