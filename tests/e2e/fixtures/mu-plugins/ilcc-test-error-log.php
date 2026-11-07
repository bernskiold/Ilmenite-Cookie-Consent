<?php
/**
 * Test fixture: records PHP errors so the tests can assert there are none.
 *
 * Logged: every warning, notice or deprecation raised in this plugin's files,
 * and any fatal error. Errors from WordPress core itself are left out, because
 * older WordPress versions emit their own on current PHP.
 *
 * GET /?ilcc-test-errors=1 returns the log (and clears it with &clear=1).
 */

define( 'ILCC_TEST_ERROR_LOG', WP_CONTENT_DIR . '/ilcc-test-errors.log' );

set_error_handler( function ( $number, $message, $file, $line ) {
	if ( false !== strpos( str_replace( '\\', '/', $file ), '/plugins/ilmenite-cookie-consent/' ) ) {
		file_put_contents( ILCC_TEST_ERROR_LOG, "[$number] $message in $file:$line\n", FILE_APPEND );
	}

	return false; // Let PHP handle it as usual.
} );

register_shutdown_function( function () {
	$error = error_get_last();

	if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR ], true ) ) {
		file_put_contents( ILCC_TEST_ERROR_LOG, "[fatal] {$error['message']} in {$error['file']}:{$error['line']}\n", FILE_APPEND );
	}
} );

add_action( 'plugins_loaded', function () {
	if ( ! isset( $_GET['ilcc-test-errors'] ) ) {
		return;
	}

	header( 'Content-Type: text/plain' );
	echo file_exists( ILCC_TEST_ERROR_LOG ) ? file_get_contents( ILCC_TEST_ERROR_LOG ) : '';

	if ( isset( $_GET['clear'] ) && file_exists( ILCC_TEST_ERROR_LOG ) ) {
		unlink( ILCC_TEST_ERROR_LOG );
	}

	exit;
}, 0 );
