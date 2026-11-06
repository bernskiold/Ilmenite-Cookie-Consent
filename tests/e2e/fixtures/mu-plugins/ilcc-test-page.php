<?php
/**
 * Test fixture: adds the tracker scripts a real site would load, so the
 * tests can check which ones the consent banner lets through.
 *
 * The tracker URLs never reach the network: the tests intercept them.
 */

add_action( 'wp_footer', function () {
	echo '<script src="https://www.google-analytics.com/analytics.js"></script>' . "\n";
	echo '<script src="https://connect.facebook.net/en_US/fbevents.js"></script>' . "\n";
	echo '<script src="https://cdn.necessary.example/app.js"></script>' . "\n";

	if ( shortcode_exists( 'ilcc_settings_link' ) ) {
		echo '<p class="ilcc-test-footer">' . do_shortcode( '[ilcc_settings_link]' ) . '</p>' . "\n";
	}
}, 5 );
