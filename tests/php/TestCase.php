<?php

namespace ILCC\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Base test case: sets up Brain Monkey and an in-memory options table.
 */
abstract class TestCase extends PolyfillTestCase {

	/**
	 * Stored options, read by the get_option() stub.
	 *
	 * @var array
	 */
	protected $options = [];

	protected function set_up() {
		parent::set_up();
		Monkey\setUp();

		$this->options = [];
		$_COOKIE       = [];

		$test = $this;

		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\stubs( [
			'wp_json_encode'         => function ( $data ) {
				return json_encode( $data );
			},
			'wp_unslash'             => function ( $value ) {
				return is_string( $value ) ? stripslashes( $value ) : $value;
			},
			'get_permalink'          => 'https://example.test/privacy/',
			'get_privacy_policy_url' => 'https://example.test/privacy/',
			'shortcode_atts'         => function ( $defaults, $atts ) {
				return array_merge( $defaults, array_intersect_key( (array) $atts, $defaults ) );
			},
			'get_option'             => function ( $name, $default = false ) use ( $test ) {
				return array_key_exists( $name, $test->options ) ? $test->options[ $name ] : $default;
			},
		] );
	}

	protected function tear_down() {
		Monkey\tearDown();
		$_COOKIE = [];
		parent::tear_down();
	}

	/**
	 * Set the visitor's consent cookies the way WordPress exposes them
	 * (wp_magic_quotes() adds slashes to $_COOKIE).
	 *
	 * @param  string|null  $preferences
	 * @param  string|null  $categories
	 */
	protected function set_consent_cookies( $preferences, $categories = null ) {
		$_COOKIE = [];

		if ( null !== $preferences ) {
			$_COOKIE['ilcc_has_preferences'] = addslashes( $preferences );
		}

		if ( null !== $categories ) {
			$_COOKIE['ilcc_consent_categories'] = addslashes( $categories );
		}
	}
}
