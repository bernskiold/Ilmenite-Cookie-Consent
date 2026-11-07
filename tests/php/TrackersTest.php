<?php

namespace ILCC\Tests;

use Brain\Monkey\Filters;
use ILCC_Trackers;

class TrackersTest extends TestCase {

	public function test_clean_domains_trims_strips_protocol_and_dedupes() {
		$input = " https://a.example \r\n\r\nb.example/x.js\nb.example/x.js\n//c.example\rhttp://D.example ";

		$this->assertSame(
			[ 'a.example', 'b.example/x.js', 'c.example', 'D.example' ],
			ILCC_Trackers::clean_domains( $input )
		);
	}

	public function test_clean_domains_accepts_arrays_and_ignores_garbage() {
		$this->assertSame( [ 'a.example' ], ILCC_Trackers::clean_domains( [ ' a.example ', '', null, [ 'x' ], 5 ] ) );
		$this->assertSame( [], ILCC_Trackers::clean_domains( null ) );
		$this->assertSame( [], ILCC_Trackers::clean_domains( false ) );
	}

	public function test_sanitize_option_returns_newline_separated_string() {
		$this->assertSame( "a.example\nb.example", ILCC_Trackers::sanitize_domains_option( "a.example\r\n  b.example  \r\n" ) );
	}

	public function test_sanitize_filters_are_registered_for_every_list() {
		ILCC_Trackers::hooks();

		foreach ( [ 'necessary', 'analytics', 'marketing' ] as $category ) {
			$this->assertNotFalse( has_filter( 'sanitize_option_ilcc_domains_' . $category, [ ILCC_Trackers::class, 'sanitize_domains_option' ] ) );
		}
	}

	public function test_defaults_when_nothing_is_saved() {
		$this->assertContains( 'connect.facebook.net', ILCC_Trackers::get_marketing() );
		$this->assertContains( 'google-analytics.com', ILCC_Trackers::get_analytics() );
		$this->assertSame( [], ILCC_Trackers::get_necessary() );
	}

	public function test_values_saved_by_older_versions_are_cleaned_on_read() {
		// 3.3.0 saved the textarea unsanitized, with Windows line endings.
		$this->options['ilcc_domains_marketing'] = "facebook.com\r\n connect.facebook.net \r\n\r\n";

		$this->assertSame( [ 'facebook.com', 'connect.facebook.net' ], ILCC_Trackers::get_marketing() );
	}

	public function test_tracker_filters_still_apply() {
		Filters\expectApplied( 'ilcc_analytics_trackers' )->once()->andReturn( [ 'stats.example', ' ' ] );

		$this->assertSame( [ 'stats.example' ], ILCC_Trackers::get_analytics() );
	}

	public function test_blocker_script_is_the_same_for_every_visitor() {
		$without = ILCC_Trackers::get_blocker_script();

		$this->set_consent_cookies( '1', '["necessary","analytics","marketing"]' );
		$with = ILCC_Trackers::get_blocker_script();

		$this->assertSame( $without, $with );
	}

	public function test_blocker_script_cannot_break_out_of_the_script_tag() {
		$this->options['ilcc_domains_marketing'] = 'evil.example/</script><script>alert(1)</script>';

		$this->assertStringNotContainsString( '</script>', ILCC_Trackers::get_blocker_script() );
	}

	public function test_deprecated_lists_escape_regex_characters() {
		$this->options['ilcc_domains_marketing'] = "a.example/x.js\nb+c.example";
		$this->options['ilcc_domains_analytics'] = '';

		$this->assertSame( '/a\.example\/x\.js/,/b\+c\.example/', ILCC_Trackers::get_disallow_for_js() );
	}

	public function test_deprecated_allowlist_respects_saved_consent() {
		$this->options['ilcc_domains_necessary'] = 'cdn.example';
		$this->options['ilcc_domains_analytics'] = 'stats.example';
		$this->options['ilcc_domains_marketing'] = 'ads.example';
		$this->set_consent_cookies( '1', '["necessary","analytics"]' );

		$this->assertSame( '/cdn\.example/,/stats\.example/', ILCC_Trackers::get_allowlist_for_js() );
		$this->assertSame( '/ads\.example/', ILCC_Trackers::get_disallow_for_js() );
	}
}
