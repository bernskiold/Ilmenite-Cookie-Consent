<?php

namespace ILCC\Tests;

use ILCC_Settings;

class SettingsTest extends TestCase {

	public function test_sanitize_style() {
		$this->assertSame( 'top', ILCC_Settings::sanitize_style( 'top' ) );
		$this->assertSame( 'takeover', ILCC_Settings::sanitize_style( 'takeover' ) );
		$this->assertSame( 'overlay', ILCC_Settings::sanitize_style( 'bogus' ) );
	}

	public function test_sanitize_checkbox() {
		$this->assertSame( '1', ILCC_Settings::sanitize_checkbox( true ) );
		$this->assertSame( '1', ILCC_Settings::sanitize_checkbox( '1' ) );
		$this->assertSame( '', ILCC_Settings::sanitize_checkbox( '' ) );
		$this->assertSame( '', ILCC_Settings::sanitize_checkbox( false ) );
		$this->assertSame( '', ILCC_Settings::sanitize_checkbox( 'false' ) );
	}

	public function test_getters_fall_back_to_shared_defaults() {
		$this->assertSame( ILCC_Settings::get_default( 'ilcc_title' ), ILCC_Settings::get_consent_title() );
		$this->assertSame( ILCC_Settings::get_default( 'ilcc_button' ), ILCC_Settings::get_accept_text() );
		$this->assertSame( 'overlay', ILCC_Settings::get_style() );
	}

	public function test_saved_values_are_used() {
		$this->options['ilcc_title']  = 'My title';
		$this->options['ilcc_style']  = 'top';
		$this->options['ilcc_button'] = 'OK';

		$this->assertSame( 'My title', ILCC_Settings::get_consent_title() );
		$this->assertSame( 'top', ILCC_Settings::get_style() );
		$this->assertSame( 'OK', ILCC_Settings::get_accept_text() );
	}

	public function test_policy_link_placeholders() {
		$this->options['ilcc_policy_url'] = 'https://example.test/cookies/';
		$this->options['ilcc_text']       = 'Read our %linkstart%policy%linkend%.';

		$this->assertSame( 'Read our <a href="https://example.test/cookies/" rel="nofollow">policy</a>.', ILCC_Settings::get_consent_text() );
	}

	public function test_sections_are_hidden_unless_enabled() {
		$this->assertFalse( ILCC_Settings::is_analytics_shown() );
		$this->assertFalse( ILCC_Settings::is_marketing_shown() );
		$this->assertFalse( ILCC_Settings::is_consent_mode_integration_enabled() );

		$this->options['ilcc_settings_analytics_is_shown']          = '1';
		$this->options['ilcc_settings_google_consent_mode_enabled'] = 'true';

		$this->assertTrue( ILCC_Settings::is_analytics_shown() );
		$this->assertTrue( ILCC_Settings::is_consent_mode_integration_enabled() );
	}
}
