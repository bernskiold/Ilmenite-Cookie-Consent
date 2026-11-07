<?php

namespace ILCC\Tests;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use ILCC_Blocker;

/**
 * Tracker tags must reach the browser inert, or the browser fetches them
 * (and gets their cookies) before any script can block them.
 */
class BlockerTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		$this->options['ilcc_domains_necessary'] = 'cdn.necessary.example';
		$this->options['ilcc_domains_analytics'] = "google-analytics.com\ngoogletagmanager.com";
		$this->options['ilcc_domains_marketing'] = "connect.facebook.net\nfacebook.com\ndoubleclick.net";
	}

	private function rewrite( $html ) {
		return ILCC_Blocker::rewrite_html( $html );
	}

	public function test_tracker_script_becomes_inert_opt_in() {
		$this->assertSame(
			'<script type="text/plain" data-ilcc-category="analytics" src="https://www.googletagmanager.com/gtag/js?id=G-1" async></script>',
			$this->rewrite( '<script src="https://www.googletagmanager.com/gtag/js?id=G-1" async></script>' )
		);
	}

	public function test_original_type_is_kept_for_restoring() {
		$html = $this->rewrite( '<script type="module" src="https://connect.facebook.net/x.js"></script>' );

		$this->assertSame( '<script type="text/plain" data-ilcc-category="marketing" data-ilcc-type="module" src="https://connect.facebook.net/x.js"></script>', $html );
	}

	public function test_explicit_javascript_type_is_replaced() {
		$this->assertSame(
			"<script type=\"text/plain\" data-ilcc-category=\"marketing\" id='fb' src='https://connect.facebook.net/en_US/fbevents.js'></script>",
			$this->rewrite( "<script type='text/javascript' id='fb' src='https://connect.facebook.net/en_US/fbevents.js'></script>" )
		);
	}

	public function test_unquoted_and_uppercase_attributes() {
		$this->assertStringContainsString( 'type="text/plain" data-ilcc-category="analytics"', $this->rewrite( '<SCRIPT SRC=https://www.google-analytics.com/analytics.js></SCRIPT>' ) );
	}

	public function test_iframes_images_and_preloads_do_not_load() {
		$this->assertSame( '<iframe data-ilcc-category="marketing" data-src="https://www.facebook.com/plugins/page.php" width="300"></iframe>', $this->rewrite( '<iframe src="https://www.facebook.com/plugins/page.php" width="300"></iframe>' ) );
		$this->assertSame( '<img data-ilcc-category="marketing" data-src="https://www.facebook.com/tr?id=1" data-srcset="https://www.facebook.com/tr?id=1 2x" alt="">', $this->rewrite( '<img src="https://www.facebook.com/tr?id=1" srcset="https://www.facebook.com/tr?id=1 2x" alt="">' ) );
		$this->assertSame( '<link data-ilcc-category="analytics" rel="preload" as="script" data-href="https://www.googletagmanager.com/gtm.js">', $this->rewrite( '<link rel="preload" as="script" href="https://www.googletagmanager.com/gtm.js">' ) );
	}

	public function test_links_that_fetch_nothing_are_left_alone() {
		$html = '<link rel="dns-prefetch" href="//www.googletagmanager.com"><link rel="preconnect" href="https://www.google-analytics.com">';

		$this->assertSame( $html, $this->rewrite( $html ) );
	}

	/**
	 * @dataProvider untouched_html
	 */
	public function test_leaves_other_markup_alone( $html ) {
		$this->assertSame( $html, $this->rewrite( $html ) );
	}

	public function untouched_html() {
		return [
			'own scripts'               => [ '<script src="/wp-includes/js/jquery.js"></script>' ],
			'necessary domain'          => [ '<script src="https://cdn.necessary.example/googletagmanager.com.js"></script>' ],
			'inline script'             => [ '<script>gtag("config", "G-1");</script>' ],
			'tags inside inline script' => [ '<script>document.write(\'<script src="https://www.google-analytics.com/ga.js"><\/script>\');</script>' ],
			'json data block'           => [ '<script type="application/ld+json" src="https://www.google-analytics.com/x"></script>' ],
			'already opt-in'            => [ '<script type="text/plain" data-ilcc-category="analytics" src="https://www.google-analytics.com/a.js"></script>' ],
			'escape hatch'              => [ '<script data-ilcc-ignore src="https://www.google-analytics.com/a.js"></script>' ],
			'unrelated image'           => [ '<img src="https://example.test/logo.png" alt="Logo">' ],
			'plain text'                => [ 'googletagmanager.com is mentioned here' ],
		];
	}

	public function test_rewriting_twice_changes_nothing() {
		$html = '<head><script src="https://www.google-analytics.com/analytics.js"></script></head><body><iframe src="https://www.facebook.com/x"></iframe></body>';
		$once = $this->rewrite( $html );

		$this->assertNotSame( $html, $once );
		$this->assertSame( $once, $this->rewrite( $once ) );
	}

	public function test_a_full_page_keeps_its_structure() {
		$html = "<!doctype html>\n<html><head>\n<script>var a = '>';</script>\n<script src=\"https://www.googletagmanager.com/gtm.js\"></script>\n</head>\n<body class=\"x\"><p>Hi</p>\n<script src=\"https://connect.facebook.net/x.js\" defer></script></body></html>";
		$out  = $this->rewrite( $html );

		$this->assertSame( 2, substr_count( $out, 'type="text/plain"' ) );
		$this->assertStringContainsString( "<script>var a = '>';</script>", $out );
		$this->assertStringContainsString( '<body class="x"><p>Hi</p>', $out );
		$this->assertSame( substr_count( $html, '</script>' ), substr_count( $out, '</script>' ) );
	}

	public function test_categories_follow_the_lists() {
		$this->assertSame( 'analytics', ILCC_Blocker::blocked_category( 'https://www.google-analytics.com/g/collect' ) );
		$this->assertSame( 'marketing', ILCC_Blocker::blocked_category( 'https://googleads.g.doubleclick.net/pagead/id' ) );
		$this->assertNull( ILCC_Blocker::blocked_category( 'https://cdn.necessary.example/app.js' ) );
		$this->assertNull( ILCC_Blocker::blocked_category( 'https://example.test/app.js' ) );
		$this->assertNull( ILCC_Blocker::blocked_category( '' ) );
	}

	public function test_can_be_turned_off() {
		Functions\stubs( [ 'is_admin' => false, 'wp_doing_ajax' => false, 'is_feed' => false, 'is_robots' => false ] );
		$this->assertTrue( ILCC_Blocker::is_enabled() );

		Filters\expectApplied( 'ilcc_rewrite_tracker_tags' )->andReturn( false );
		$this->assertFalse( ILCC_Blocker::is_enabled() );
	}

	public function test_never_runs_in_the_admin() {
		Functions\stubs( [ 'is_admin' => true, 'wp_doing_ajax' => false, 'is_feed' => false, 'is_robots' => false ] );
		$this->assertFalse( ILCC_Blocker::is_enabled() );
	}
}
