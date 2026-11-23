<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'init', function () { register_post_type( 'igp_enquiry', array( 'labels' => array( 'name' => 'Enquiries', 'singular_name' => 'Enquiry' ), 'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'menu_icon' => 'dashicons-email-alt', 'supports' => array( 'title', 'editor' ), 'map_meta_cap' => false, 'capabilities' => array( 'edit_post' => 'edit_others_posts', 'read_post' => 'read_private_posts', 'delete_post' => 'edit_others_posts', 'edit_posts' => 'edit_others_posts', 'edit_others_posts' => 'edit_others_posts', 'publish_posts' => 'edit_others_posts', 'read_private_posts' => 'read_private_posts', 'delete_posts' => 'edit_others_posts', 'delete_private_posts' => 'edit_others_posts', 'delete_published_posts' => 'edit_others_posts', 'delete_others_posts' => 'edit_others_posts', 'edit_private_posts' => 'edit_others_posts', 'edit_published_posts' => 'edit_others_posts', 'create_posts' => 'edit_others_posts' ) ) ); } );
add_action( 'admin_post_nopriv_igp_quote', 'igp_submit_quote' );
add_action( 'admin_post_igp_quote', 'igp_submit_quote' );
function igp_submit_quote() {
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'igp_quote' ) ) { wp_die( 'Your form expired. Please go back and try again.', 'Invalid request', array( 'response' => 403 ) ); }
	if ( ! empty( $_POST['website'] ) ) { wp_die( 'Unable to submit request.' ); }
	$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! $name || strlen( $name ) > 120 || ! is_email( $email ) || strlen( $email ) > 254 ) { wp_die( 'Please provide a valid name and email address. Go back to correct the form.' ); }
	$quantity = sanitize_text_field( wp_unslash( $_POST['quantity'] ?? '' ) ); if ( '' !== $quantity && ( ! ctype_digit( $quantity ) || (int) $quantity < 1 ) ) { wp_die( 'Please enter a valid quantity.' ); }
	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ); $key = 'igp_quote_' . hash_hmac( 'sha256', $ip . '|' . strtolower( $email ), wp_salt( 'nonce' ) );
	if ( get_transient( $key ) ) { wp_die( 'Please wait before sending another enquiry.', 'Please wait', array( 'response' => 429 ) ); }
	$fields = array( 'company' => 'Company', 'phone' => 'Phone', 'product' => 'Product or part', 'part_number' => 'Part number', 'quantity' => 'Quantity', 'generator_brand' => 'Generator brand', 'generator_model' => 'Generator model', 'engine_manufacturer' => 'Engine manufacturer', 'serial_number' => 'Serial number', 'message' => 'Message' );
	$body = "Name: {$name}\nEmail: {$email}\n"; foreach ( $fields as $key_name => $label ) { $value = sanitize_textarea_field( wp_unslash( $_POST[ $key_name ] ?? '' ) ); if ( strlen( $value ) > 4000 ) { wp_die( 'A field is too long.' ); } if ( $value !== '' ) { $body .= $label . ': ' . $value . "\n"; } }
	$file_data = null;
	if ( isset( $_FILES['attachment'] ) && UPLOAD_ERR_NO_FILE !== $_FILES['attachment']['error'] ) {
		$file = $_FILES['attachment']; if ( UPLOAD_ERR_OK !== $file['error'] || $file['size'] < 1 || $file['size'] > 5 * 1024 * 1024 || ! is_uploaded_file( $file['tmp_name'] ) ) { wp_die( 'Attachment upload failed. Choose a file up to 5 MB and try again.' ); }
		$head = file_get_contents( $file['tmp_name'], false, null, 0, 12 ); $type = '';
		if ( str_starts_with( $head, "\xFF\xD8\xFF" ) ) { $type = 'image/jpeg'; } elseif ( str_starts_with( $head, "\x89PNG\r\n\x1a\n" ) ) { $type = 'image/png'; } elseif ( str_starts_with( $head, 'RIFF' ) && substr( $head, 8, 4 ) === 'WEBP' ) { $type = 'image/webp'; } elseif ( str_starts_with( $head, '%PDF-' ) ) { $type = 'application/pdf'; }
		if ( ! $type ) { wp_die( 'Please upload a valid JPG, PNG, WebP or PDF file.' ); }
		$bytes = file_get_contents( $file['tmp_name'] ); if ( false === $bytes ) { wp_die( 'Unable to read the uploaded file.' ); } $file_data = array( 'name' => sanitize_file_name( $file['name'] ), 'type' => $type, 'data' => base64_encode( $bytes ) );
	}
	$id = wp_insert_post( array( 'post_type' => 'igp_enquiry', 'post_status' => 'private', 'post_title' => $name . ' — ' . current_time( 'mysql' ), 'post_content' => $body ), true );
	if ( is_wp_error( $id ) || ! $id ) { wp_die( 'Your request could not be saved. Please try again.' ); }
	if ( $file_data && ! add_post_meta( $id, '_igp_attachment', $file_data, true ) ) {
		// Keep the enquiry: the metadata write may have succeeded even if its result was lost.
		// Do not tell the customer that the complete request was saved.
		wp_die( 'We could not confirm that your attachment was saved. Please contact us before resubmitting your request.' );
	}
	set_transient( $key, 1, 60 ); wp_safe_redirect( add_query_arg( 'submitted', '1', igp_url( 'request-quote/' ) ) ); exit;
}
add_action( 'add_meta_boxes_igp_enquiry', function () { add_meta_box( 'igp_enquiry_file', 'Private attachment', function ( $post ) { if ( get_post_meta( $post->ID, '_igp_attachment', true ) ) { echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=igp_attachment&id=' . $post->ID ), 'igp_attachment_' . $post->ID ) ) . '">Download attachment</a>'; } else { echo 'No attachment.'; } }, 'igp_enquiry', 'side' ); } );
add_action( 'add_meta_boxes_igp_enquiry', function () { add_meta_box( 'igp_enquiry_status', 'Enquiry status', function ( $post ) { wp_nonce_field( 'igp_enquiry_status', 'igp_enquiry_status_nonce' ); $status = get_post_meta( $post->ID, '_igp_status', true ) ?: 'new'; echo '<label for="igp_status">Status</label> <select id="igp_status" name="igp_status">'; foreach ( array( 'new' => 'New', 'reviewing' => 'Reviewing', 'responded' => 'Responded', 'closed' => 'Closed' ) as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '" ' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>'; } echo '</select>'; }, 'igp_enquiry', 'side' ); } );
add_action( 'save_post_igp_enquiry', function ( $id ) { if ( ! isset( $_POST['igp_enquiry_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igp_enquiry_status_nonce'] ) ), 'igp_enquiry_status' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) { return; } $status = sanitize_key( wp_unslash( $_POST['igp_status'] ?? '' ) ); if ( in_array( $status, array( 'new', 'reviewing', 'responded', 'closed' ), true ) ) { update_post_meta( $id, '_igp_status', $status ); } } );
add_action( 'admin_post_igp_attachment', function () {
	$id = absint( $_GET['id'] ?? 0 ); if ( ! $id || 'igp_enquiry' !== get_post_type( $id ) || ! current_user_can( 'edit_others_posts' ) || ! current_user_can( 'edit_post', $id ) ) { wp_die( 'Not authorized', 'Forbidden', array( 'response' => 403 ) ); }
	check_admin_referer( 'igp_attachment_' . $id ); $file = get_post_meta( $id, '_igp_attachment', true ); if ( ! is_array( $file ) || empty( $file['data'] ) ) { wp_die( 'Not found', 'Not found', array( 'response' => 404 ) ); }
	$data = base64_decode( $file['data'], true ); if ( false === $data ) { wp_die( 'Attachment unavailable.' ); } nocache_headers(); header( 'Content-Type: application/octet-stream' ); header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file['name'] ) . '"' ); header( 'X-Content-Type-Options: nosniff' ); header( 'Content-Length: ' . strlen( $data ) ); echo $data; exit;
} );
