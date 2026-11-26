<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function igp_core_defaults() {
	return array( 'company' => 'Innovative Generator Parts', 'phone' => '+92 300 8877677', 'whatsapp' => '923008877677', 'email' => 'usm_tra@yahoo.com', 'service_area' => 'All over Pakistan', 'address' => '', 'notification_email' => '' );
}
function igp_core_settings() { return wp_parse_args( get_option( 'igp_core_settings', array() ), igp_core_defaults() ); }
add_action( 'admin_init', function () {
	register_setting( 'igp_core', 'igp_core_settings', array( 'sanitize_callback' => function ( $input ) {
		$input = is_array( $input ) ? array_filter( $input, 'is_string' ) : array(); $out = array();
		foreach ( array( 'company', 'phone', 'service_area', 'address' ) as $field ) { $out[ $field ] = sanitize_text_field( $input[ $field ] ?? '' ); }
		$out['whatsapp'] = preg_replace( '/\D+/', '', $input['whatsapp'] ?? '' );
		foreach ( array( 'email', 'notification_email' ) as $field ) { $raw = trim( $input[ $field ] ?? '' ); if ( $raw && ! is_email( $raw ) ) { add_settings_error( 'igp_core_settings', $field, 'Enter a valid email address.' ); $out[ $field ] = igp_core_settings()[ $field ]; } else { $out[ $field ] = sanitize_email( $raw ); } }
		return $out;
	} ) );
} );
add_action( 'admin_menu', function () { add_options_page( 'IGP Settings', 'IGP Settings', 'manage_options', 'igp-settings', 'igp_core_settings_page' ); } );
function igp_core_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not authorized', '', array( 'response' => 403 ) ); }
	$settings = igp_core_settings(); echo '<div class="wrap"><h1>IGP Settings</h1><form method="post" action="options.php">'; settings_fields( 'igp_core' ); echo '<table class="form-table">';
	foreach ( array( 'company' => 'Company name', 'phone' => 'Public phone', 'whatsapp' => 'WhatsApp international digits', 'email' => 'Public email', 'service_area' => 'Service area', 'address' => 'Public address', 'notification_email' => 'Enquiry notification email' ) as $key => $label ) {
		echo '<tr><th><label for="igp_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input class="regular-text" id="igp_' . esc_attr( $key ) . '" name="igp_core_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $settings[ $key ] ) . '"></td></tr>';
	}
	echo '</table><p>Authenticated SMTP is configured on the server. Leave the notification address blank to disable notification emails.</p>'; submit_button(); echo '</form></div>';
}
