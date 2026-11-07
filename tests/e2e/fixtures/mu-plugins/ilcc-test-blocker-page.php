<?php
/**
 * Test fixture: on /?ilcc-test-blocker=1, adds every kind of script the
 * blocker has to handle. The URLs never reach the network: the tests
 * intercept them and record which ones ran.
 */

add_action( 'wp_head', function () {
	if ( ! isset( $_GET['ilcc-test-blocker'] ) ) {
		return;
	}
	?>
	<!-- A preload hint for a tracker: makes the browser fetch it. -->
	<link rel="preload" as="script" href="https://www.googletagmanager.com/preloaded.js">
	<!-- Parser-inserted, two in a row: must run in this order once allowed. -->
	<script src="https://www.googletagmanager.com/first.js"></script>
	<script src="https://www.googletagmanager.com/second.js"></script>
	<!-- Added by other scripts. -->
	<script>
		( function () {
			var a = document.createElement( 'script' );
			a.src = 'https://static.hotjar.com/c/hotjar.js';
			document.head.appendChild( a );

			var b = document.createElement( 'script' );
			b.setAttribute( 'src', 'https://googleads.g.doubleclick.net/pagead/ads.js' );
			document.head.appendChild( b );
		} )();
	</script>
	<?php
}, 20 );

add_action( 'wp_footer', function () {
	if ( ! isset( $_GET['ilcc-test-blocker'] ) ) {
		return;
	}
	?>
	<!-- Opt-in markup. -->
	<script type="text/plain" data-ilcc-category="marketing" src="https://optin.example/marketing.js"></script>
	<script type="text/plain" data-ilcc-category="analytics">window.ilccTestInline = ( window.ilccTestInline || 0 ) + 1;</script>
	<iframe id="ilcc-test-iframe" data-ilcc-category="marketing" data-src="https://video.example/embed"></iframe>
	<!-- Plain tracker embeds, as a theme or plugin would print them. -->
	<img id="ilcc-test-pixel" src="https://www.facebook.com/tr?id=1&amp;ev=PageView" width="1" height="1" alt="">
	<iframe id="ilcc-test-like" src="https://www.facebook.com/plugins/like.php?href=x"></iframe>
	<?php
}, 1 );
