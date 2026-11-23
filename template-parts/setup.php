<?php
defined( 'ABSPATH' ) || exit;
status_header( 503 );
nocache_headers();
wp_die( current_user_can( 'activate_plugins' ) ? 'Activate IGP Unified Core to open the catalogue.' : 'The catalogue is being prepared. Please return shortly.', 'Catalogue setup', array( 'response'=>503 ) );
