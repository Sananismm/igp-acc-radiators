<?php
defined( 'ABSPATH' ) || exit;
function igp_quote_error( $message, $status = 422 ) {
	wp_die( esc_html( $message ), 'Enquiry', array( 'response'=>$status,'back_link'=>true ) );
}
function igp_receipt_valid( $brand ) {
	$token = igp_input( $_GET, 'receipt' );
	if ( ! wp_is_uuid( $token, 4 ) ) { return false; }
	$record = get_option( 'igp_submission_' . $token );
	return is_array( $record ) && 'complete' === ( $record['status'] ?? '' ) && $brand === ( $record['brand'] ?? 'igp' );
}
function igp_quote_confirmation( $brand, $token ) {
	wp_safe_redirect( add_query_arg( 'receipt', $token, home_url( 'acc' === $brand ? '/acc-radiators/contact/' : '/request-quote/' ) ), 303 );
	exit;
}
function igp_quote_attachment() {
	if ( ! isset( $_FILES['attachment'] ) || UPLOAD_ERR_NO_FILE === $_FILES['attachment']['error'] ) { return null; }
	$f = $_FILES['attachment'];
	if ( UPLOAD_ERR_OK !== $f['error'] || ! is_string( $f['tmp_name'] ) || ! is_uploaded_file( $f['tmp_name'] ) || $f['size'] < 1 || $f['size'] > 5 * MB_IN_BYTES ) { igp_quote_error( 'Choose a file up to 5 MB and try again.' ); }
	$allowed = array( 'jpg|jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','pdf'=>'application/pdf' );
	$info = wp_check_filetype_and_ext( $f['tmp_name'], sanitize_file_name( $f['name'] ), $allowed );
	$mime = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $f['tmp_name'] );
	$extension = wp_check_filetype( $f['name'], $allowed );
	if ( ! $info['type'] || $extension['type'] !== $mime || $mime !== $info['type'] || ! in_array( $mime, $allowed, true ) ) { igp_quote_error( 'Select a JPG, PNG, WebP or PDF with a matching file extension.' ); }
	$bytes = file_get_contents( $f['tmp_name'] );
	if ( false === $bytes || strlen( $bytes ) !== $f['size'] ) { igp_quote_error( 'The upload was incomplete. Please try again.' ); }
	return array( 'name'=>sanitize_file_name( $f['name'] ),'type'=>$mime,'data'=>base64_encode( $bytes ) );
}
function igp_submit_quote() {
	global $wpdb;
	$brand = igp_input( $_POST, 'brand' );
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! in_array( $brand, array( 'acc','igp' ), true ) || ! wp_verify_nonce( igp_input( $_POST, '_wpnonce' ), 'igp_quote_' . $brand ) ) { igp_quote_error( 'This form has expired. Reload the enquiry page and try again.', 403 ); }
	foreach ( $_POST as $value ) { if ( ! is_string( $value ) ) { igp_quote_error( 'Please enter one value per field.' ); } }
	if ( igp_input( $_POST, 'website' ) !== '' ) { igp_quote_error( 'Unable to submit the enquiry.' ); }
	$name = igp_input( $_POST, 'name' ); $email = igp_input( $_POST, 'email' );
	$token = strtolower( igp_input( $_POST, 'submission_id' ) );
	if ( ! $name || mb_strlen( $name ) > 120 || ! is_email( $email ) || strlen( $email ) > 254 || ! wp_is_uuid( $token, 4 ) ) { igp_quote_error( 'Check your name and email. If the form has expired, reload it.' ); }
	$qty = igp_input( $_POST, 'quantity' ); $year = igp_input( $_POST, 'vehicle_year' );
	if ( '' !== $qty && ( ! ctype_digit( $qty ) || (int) $qty < 1 || (int) $qty > 999999 ) ) { igp_quote_error( 'Enter a quantity between 1 and 999999.' ); }
	if ( '' !== $year && ( ! ctype_digit( $year ) || (int) $year < 1886 || (int) $year > 2199 ) ) { igp_quote_error( 'Enter a four-digit model year.' ); }
	$product_id = igp_input( $_POST, 'product_id' );
	if ( '' !== $product_id && ( ! ctype_digit( $product_id ) || 'igp_product' !== get_post_type( $product_id ) || 'publish' !== get_post_status( $product_id ) || get_post_field( 'post_password', $product_id ) || igp_brand( $product_id ) !== $brand ) ) { igp_quote_error( 'That product is unavailable. Open the enquiry form again or enter the part manually.' ); }
	$fields = array( 'company'=>'Company','phone'=>'Phone','product'=>'Product','part_number'=>'Part number','quantity'=>'Quantity' );
	$fields += 'acc' === $brand ? array( 'vehicle_make'=>'Vehicle make','vehicle_model'=>'Vehicle model','vehicle_year'=>'Model year','transmission'=>'Transmission' ) : array( 'generator_brand'=>'Generator manufacturer','generator_model'=>'Generator model','engine_manufacturer'=>'Engine manufacturer','serial_number'=>'Serial number' );
	$fields['message'] = 'Message';
	$body = "Name: $name\nEmail: $email\n";
	foreach ( $fields as $key=>$label ) {
		$value = 'message' === $key ? sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ) : igp_input( $_POST, $key );
		if ( mb_strlen( $value ) > ( 'message' === $key ? 4000 : 254 ) ) { igp_quote_error( 'Please shorten the ' . $label . ' field.' ); }
		if ( '' !== $value ) { $body .= "$label: $value\n"; }
	}
	$attachment = igp_quote_attachment();
	$reservation = 'igp_submission_' . $token;
	$email_hash = hash_hmac( 'sha256', strtolower( $email ), wp_salt( 'auth' ) );
	$lock = 'igp_q_' . substr( hash( 'sha256', DB_NAME . $wpdb->prefix . $token ), 0, 40 );
	if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock ) ) ) { igp_quote_error( 'The request is processing. Retry with this form.', 503 ); }
	// The connection also releases its lock when an HTTP request terminates.
	$prior = get_option( $reservation );
	if ( $prior && 'complete' === ( $prior['status'] ?? '' ) && hash_equals( $prior['email_hash'] ?? '', $email_hash ) && $brand === ( $prior['brand'] ?? 'igp' ) ) { igp_quote_confirmation( $brand, $token ); }
	if ( $prior ) { igp_quote_error( 'This request is already being processed. Please contact the team before resubmitting.', 409 ); }
	$ip_hash = hash_hmac( 'sha256', $_SERVER['REMOTE_ADDR'] ?? '', wp_salt( 'nonce' ) );
	$rate_lock = 'igp_rate_' . substr( $ip_hash, 0, 40 );
	if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $rate_lock ) ) ) { igp_quote_error( 'Please wait before retrying.', 429 ); }
	$rate = 'igp_rate_' . substr( $ip_hash, 0, 30 );
	$pair = 'igp_pair_' . substr( hash( 'sha256', $ip_hash . $email_hash ), 0, 30 );
	if ( (int) get_transient( $rate ) >= 12 || get_transient( $pair ) ) { igp_quote_error( 'Please wait a minute before sending another enquiry.', 429 ); }
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) { igp_quote_error( 'Storage is unavailable. Please retry.', 503 ); }
	try {
		if ( ! add_option( $reservation, array( 'status'=>'pending','email_hash'=>$email_hash ), '', false ) ) { throw new RuntimeException(); }
		$id = wp_insert_post( wp_slash( array( 'post_type'=>'igp_enquiry','post_status'=>'private','post_title'=>$name . ' — ' . current_time( 'mysql' ),'post_content'=>$body ) ), true );
		if ( ! $id || is_wp_error( $id ) ) { throw new RuntimeException(); }
		$metadata = array( '_igp_submission_id'=>$token,'_igp_brand'=>$brand,'_igp_status'=>'new' );
		if ( $product_id ) { $metadata['_igp_product_id'] = (int) $product_id; }
		if ( $attachment ) {
			$stored = igp_store_private_data( $attachment );
			if ( is_wp_error( $stored ) ) { throw new RuntimeException(); }
			$metadata['_igp_attachment'] = $stored;
		}
		foreach ( $metadata as $key=>$value ) { if ( ! add_post_meta( $id, $key, $value, true ) ) { throw new RuntimeException(); } }
		if ( ! update_option( $reservation, array( 'status'=>'complete','email_hash'=>$email_hash,'enquiry_id'=>$id,'brand'=>$brand,'created'=>time() ), false ) || false === $wpdb->query( 'COMMIT' ) ) { throw new RuntimeException(); }
	} catch ( Throwable $error ) {
		$wpdb->query( 'ROLLBACK' );
		wp_cache_delete( $reservation, 'options' );
		if ( isset( $id ) && is_numeric( $id ) ) { clean_post_cache( $id ); }
		igp_quote_error( 'We could not confirm receipt. Retry using the same form or contact the team.', 503 );
	}
	set_transient( $rate, (int) get_transient( $rate ) + 1, 60 ); set_transient( $pair, 1, 60 );
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $rate_lock ) );
	$recipient = igp_core_settings()['notification_email'];
	$status = 'not_configured';
	if ( is_email( $recipient ) ) {
		$status = 'failed';
		try { if ( wp_mail( $recipient, 'New ' . strtoupper( $brand ) . ' enquiry', 'Review it in WordPress: ' . admin_url( 'post.php?post=' . $id . '&action=edit' ) ) ) { $status = 'accepted_by_mailer'; } } catch ( Throwable $error ) { /* Staff can follow up in the dashboard. */ }
	}
	update_post_meta( $id, '_igp_notification_status', $status );
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	igp_quote_confirmation( $brand, $token );
}
add_action( 'admin_post_nopriv_igp_quote', 'igp_submit_quote' );
add_action( 'admin_post_igp_quote', 'igp_submit_quote' );
add_action( 'template_redirect', function () {
	if ( is_page() && in_array( get_page_uri( get_queried_object_id() ), array( 'request-quote','acc-radiators/contact' ), true ) ) { nocache_headers(); header( 'Cache-Control: private, no-store, max-age=0' ); }
} );
