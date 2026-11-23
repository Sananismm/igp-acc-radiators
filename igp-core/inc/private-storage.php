<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** An explicitly configured directory must be outside both public roots. */
function igp_private_directory() {
	if ( ! defined( 'IGP_PRIVATE_STORAGE_DIR' ) || ! IGP_PRIVATE_STORAGE_DIR ) { return null; }
	$dir = realpath( IGP_PRIVATE_STORAGE_DIR ); $wp = realpath( ABSPATH ); $web = realpath( $_SERVER['DOCUMENT_ROOT'] ?? '' );
	if ( ! $dir || ! $wp || ! $web || ! is_dir( $dir ) || ! is_writable( $dir ) ) { return false; }
	$dir_compare = strtolower( wp_normalize_path( $dir ) . '/' );
	foreach ( array( $wp, $web ) as $root ) { if ( str_starts_with( $dir_compare, strtolower( trailingslashit( wp_normalize_path( $root ) ) ) ) || untrailingslashit( $dir_compare ) === strtolower( untrailingslashit( wp_normalize_path( $root ) ) ) ) { return false; } }
	return $dir;
}
function igp_store_private_data( $file_data ) {
	$dir = igp_private_directory();
	if ( null === $dir ) { return $file_data; } // Non-public database fallback.
	if ( false === $dir ) { return new WP_Error( 'igp_private_dir', 'Private attachment storage is not available.' ); }
	$name = bin2hex( random_bytes( 24 ) ) . '.bin'; $path = $dir . DIRECTORY_SEPARATOR . $name;
	$handle = @fopen( $path, 'xb' ); if ( ! $handle ) { return new WP_Error( 'igp_storage', 'Private attachment storage is not available.' ); }
	$bytes = base64_decode( $file_data['data'], true ); $written = false === $bytes ? false : fwrite( $handle, $bytes ); fclose( $handle ); @chmod( $path, 0600 );
	if ( false === $bytes || $written !== strlen( $bytes ) ) { @unlink( $path ); return new WP_Error( 'igp_storage_write', 'The attachment could not be stored completely.' ); }
	unset( $file_data['data'] ); $file_data['private_file'] = $name; return $file_data;
}
function igp_read_private_data( $file ) {
	if ( ! empty( $file['data'] ) ) { return base64_decode( $file['data'], true ); }
	$name = $file['private_file'] ?? ''; $dir = igp_private_directory();
	if ( ! $dir || ! preg_match( '/^[0-9a-f]{48}\.bin$/', $name ) ) { return false; }
	$path = realpath( $dir . DIRECTORY_SEPARATOR . $name );
	if ( ! $path || dirname( $path ) !== $dir || ! is_file( $path ) || filesize( $path ) > 5 * 1024 * 1024 ) { return false; }
	return file_get_contents( $path );
}
