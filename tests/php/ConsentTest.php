<?php

namespace ILCC\Tests;

use Brain\Monkey\Filters;
use ILCC_Consent;

class ConsentTest extends TestCase {

	public function test_no_cookies_means_no_consent() {
		$this->assertFalse( ILCC_Consent::has_set_preferences() );
		$this->assertFalse( ILCC_Consent::has_consented_to( 'analytics' ) );
		$this->assertFalse( ILCC_Consent::has_full_consent() );
	}

	public function test_cookies_written_by_older_versions_stay_valid() {
		// 3.3.0 wrote "1" and a raw JSON array.
		$this->set_consent_cookies( '1', '["necessary","marketing","analytics"]' );

		$this->assertTrue( ILCC_Consent::has_set_preferences() );
		$this->assertTrue( ILCC_Consent::has_full_consent() );
	}

	public function test_partial_consent() {
		$this->set_consent_cookies( '1', '["necessary","analytics"]' );

		$this->assertTrue( ILCC_Consent::has_consented_to( 'analytics' ) );
		$this->assertFalse( ILCC_Consent::has_consented_to( 'marketing' ) );
		$this->assertFalse( ILCC_Consent::has_full_consent() );
	}

	public function test_categories_do_not_count_before_preferences_are_saved() {
		$this->set_consent_cookies( null, '["necessary","analytics","marketing"]' );

		$this->assertFalse( ILCC_Consent::has_consented_to( 'marketing' ) );
	}

	/**
	 * @dataProvider malformed_cookies
	 */
	public function test_malformed_category_cookies_never_error( $value ) {
		$this->set_consent_cookies( '1' );
		$_COOKIE['ilcc_consent_categories'] = $value;

		$this->assertFalse( ILCC_Consent::has_consented_to( 'marketing' ) );
	}

	public function malformed_cookies() {
		return [
			'json string'  => [ '"marketing"' ],
			'json object'  => [ '{"marketing":true}' ],
			'json number'  => [ '1' ],
			'not json'     => [ '{broken' ],
			'empty'        => [ '' ],
			'array cookie' => [ [ 'marketing' ] ],
			'non strings'  => [ '[1,true,null,["marketing"]]' ],
		];
	}

	public function test_preferences_cookie_must_match_the_consent_version() {
		Filters\expectApplied( 'ilcc_consent_version' )->andReturn( '2' );

		$this->set_consent_cookies( '1', '["necessary","analytics"]' );
		$this->assertFalse( ILCC_Consent::has_set_preferences() );
		$this->assertFalse( ILCC_Consent::has_consented_to( 'analytics' ) );

		$this->set_consent_cookies( '2', '["necessary","analytics"]' );
		$this->assertTrue( ILCC_Consent::has_consented_to( 'analytics' ) );
	}

	public function test_consent_mode_script_is_empty_when_integration_is_off() {
		$this->assertSame( '', ILCC_Consent::get_consent_mode_script() );
	}

	public function test_consent_mode_script_sets_denied_defaults() {
		$this->options['ilcc_settings_google_consent_mode_enabled'] = '1';

		$script = ILCC_Consent::get_consent_mode_script();

		$this->assertStringContainsString( "gtag('consent', 'default'", $script );
		$this->assertStringContainsString( '"analytics_storage":"denied"', $script );
		$this->assertStringContainsString( '"wait_for_update":500', $script );
		$this->assertStringContainsString( "gtag('consent', 'update'", $script );
	}

	public function test_consent_mode_defaults_can_be_turned_off() {
		$this->options['ilcc_settings_google_consent_mode_enabled'] = '1';
		Filters\expectApplied( 'ilcc_enable_default_consent_mode' )->andReturn( false );

		$script = ILCC_Consent::get_consent_mode_script();

		$this->assertStringNotContainsString( "gtag('consent', 'default'", $script );
		$this->assertStringContainsString( "gtag('consent', 'update'", $script );
	}

	public function test_settings_link_shortcode_is_escaped() {
		$html = ILCC_Consent::settings_link_shortcode( [ 'text' => 'Cookie <b>settings</b>', 'class' => 'x" onclick="y' ] );

		$this->assertStringStartsWith( '<a href="#ilcc-settings"', $html );
		$this->assertStringContainsString( 'data-ilcc-open-settings', $html );
		$this->assertStringNotContainsString( '<b>', $html );
		$this->assertStringNotContainsString( '" onclick="', $html );
	}
}
