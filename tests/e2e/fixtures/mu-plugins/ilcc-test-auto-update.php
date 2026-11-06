<?php
/**
 * Test fixture: runs WordPress's automatic plugin updater against the
 * packaged build, the same code path a site's background auto-update takes.
 *
 * GET /?ilcc-test-auto-update=1 offers the build in tests/e2e/.cache as an
 * update for the installed plugin, runs WP_Automatic_Updater and returns
 * the result as JSON.
 */

add_action( 'init', function () {
	if ( ! isset( $_GET['ilcc-test-auto-update'] ) ) {
		return;
	}

	// Background auto-updates run in WP-Cron, which keeps the plugin active
	// during the update instead of deactivating it.
	if ( ! defined( 'DOING_CRON' ) ) {
		define( 'DOING_CRON', true );
	}

	require_once ABSPATH . 'wp-admin/includes/admin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$plugin = 'ilmenite-cookie-consent/ilmenite-cookie-consent.php';

	// The upgrader deletes the package after unpacking it, so hand it a copy.
	$package = get_temp_dir() . 'ilcc-test-update-' . wp_generate_password( 8, false ) . '.zip';
	copy( WP_CONTENT_DIR . '/ilcc-test-package/ilmenite-cookie-consent.zip', $package );

	$item = (object) [
		'id'          => 'w.org/plugins/ilmenite-cookie-consent',
		'slug'        => 'ilmenite-cookie-consent',
		'plugin'      => $plugin,
		'new_version' => '99.0.0',
		'url'         => 'https://wordpress.org/plugins/ilmenite-cookie-consent/',
		'package'     => $package,
	];

	$update_cache = get_site_transient( 'update_plugins' );

	$offer_update = function ( $transient ) use ( $item, $plugin ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}
		$transient->response            = isset( $transient->response ) ? (array) $transient->response : [];
		$transient->response[ $plugin ] = $item;

		return $transient;
	};
	add_filter( 'site_transient_update_plugins', $offer_update );
	add_filter( 'auto_update_plugin', '__return_true' );

	$updater = new WP_Automatic_Updater();
	$result  = $updater->update( 'plugin', $item );

	// Put the update cache back so the fake offer does not linger.
	remove_filter( 'site_transient_update_plugins', $offer_update );
	set_site_transient( 'update_plugins', $update_cache );

	wp_send_json( [
		'updated' => true === $result || ( ! is_wp_error( $result ) && ! empty( $result ) ),
		'error'   => is_wp_error( $result ) ? $result->get_error_message() : null,
		'active'  => is_plugin_active( $plugin ),
	] );
} );
