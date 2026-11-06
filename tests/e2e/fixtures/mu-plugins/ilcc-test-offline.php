<?php
/**
 * Test fixture: keeps WordPress's own HTTP requests (update checks, news
 * feeds, ...) off the network so admin pages load quickly and the tests do
 * not depend on wordpress.org being reachable from inside the site.
 *
 * Requests to the site itself, such as the fatal-error check WordPress runs
 * after an automatic update, are allowed.
 */

add_filter( 'pre_http_request', function ( $response, $args, $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );

	if ( in_array( $host, [ '127.0.0.1', 'localhost', wp_parse_url( home_url(), PHP_URL_HOST ) ], true ) ) {
		return $response;
	}

	return new WP_Error( 'ilcc_test_offline', 'External HTTP requests are disabled in the end-to-end tests.' );
}, 10, 3 );
