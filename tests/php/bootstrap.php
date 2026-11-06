<?php
/**
 * PHPUnit bootstrap.
 *
 * Loads the plugin with WordPress functions stubbed by Brain Monkey, so the
 * classes can be unit tested without a WordPress install. Behaviour that
 * needs a real WordPress is covered by the end-to-end tests in tests/e2e.
 */

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

define( 'ABSPATH', __DIR__ . '/' );
define( 'ILCC_PLUGIN_DIR', dirname( __DIR__, 2 ) );

require __DIR__ . '/TestCase.php';

// Load the plugin once, with the functions its constructor needs.
Brain\Monkey\setUp();
Brain\Monkey\Functions\when( 'plugin_dir_path' )->alias( function ( $file ) {
	return dirname( $file ) . '/';
} );
Brain\Monkey\Functions\when( 'add_shortcode' )->justReturn( true );
Brain\Monkey\Functions\when( 'plugins_url' )->justReturn( 'https://example.test/wp-content/plugins' );
Brain\Monkey\Functions\when( 'untrailingslashit' )->alias( function ( $value ) {
	return rtrim( $value, '/\\' );
} );

require ILCC_PLUGIN_DIR . '/ilmenite-cookie-consent.php';

Brain\Monkey\tearDown();
