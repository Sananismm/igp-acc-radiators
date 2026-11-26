<?php
defined( 'ABSPATH' ) || exit;
add_action( 'phpmailer_init', function ( $phpmailer ) {
	if ( ! defined( 'IGP_SMTP_HOST' ) || ! defined( 'IGP_SMTP_USER' ) || ! defined( 'IGP_SMTP_PASSWORD' ) ) { return; }
	$phpmailer->isSMTP();
	$phpmailer->Host = IGP_SMTP_HOST;
	$phpmailer->Port = defined( 'IGP_SMTP_PORT' ) ? (int) IGP_SMTP_PORT : 465;
	$phpmailer->SMTPSecure = defined( 'IGP_SMTP_ENCRYPTION' ) ? IGP_SMTP_ENCRYPTION : 'ssl';
	$phpmailer->SMTPAuth = true;
	$phpmailer->SMTPAutoTLS = false;
	$phpmailer->Username = IGP_SMTP_USER;
	$phpmailer->Password = IGP_SMTP_PASSWORD;
	$phpmailer->CharSet = 'UTF-8';
	$phpmailer->Timeout = 20;
	if ( defined( 'IGP_SMTP_FROM' ) && is_email( IGP_SMTP_FROM ) ) {
		$phpmailer->setFrom( IGP_SMTP_FROM, defined( 'IGP_SMTP_FROM_NAME' ) ? IGP_SMTP_FROM_NAME : get_bloginfo( 'name' ) );
	}
} );
add_filter( 'wp_mail_from', function ( $from ) {
	return defined( 'IGP_SMTP_FROM' ) && is_email( IGP_SMTP_FROM ) ? IGP_SMTP_FROM : $from;
} );
add_filter( 'wp_mail_from_name', function ( $name ) {
	return defined( 'IGP_SMTP_FROM_NAME' ) ? IGP_SMTP_FROM_NAME : $name;
} );
