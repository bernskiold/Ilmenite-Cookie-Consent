<?php

namespace ILCC\Tests;

use Brain\Monkey\Filters;
use ReflectionMethod;

/**
 * Guards what an automatic update must not break.
 *
 * Themes and sites call these methods, hook these filters and actions, and
 * have these options and cookies stored. Removing or renaming any of them
 * breaks sites when the plugin updates, so changes here need a deprecation
 * path instead.
 */
class BackCompatTest extends TestCase {

	/**
	 * Public API as of 3.3.0 (the last release) plus what main added since.
	 *
	 * @return array
	 */
	public function public_methods() {
		$methods = [
			'Ilmenite_Cookie_Consent' => [
				'instance', 'init_hooks', 'classes', 'load_languages', 'scripts', 'styles',
				'get_allow_and_disallowlists', 'should_load_banner', 'get_preferences_cookie_name',
				'get_categories_cookie_name', 'get_remember_me_duration', 'is_debugging',
				'get_csp_nonce', 'add_nonce_to_script_tag', 'add_nonce_to_inline_script',
			],
			'ILCC_Consent'            => [
				'hooks', 'has_set_preferences', 'has_full_consent', 'has_consented_to', 'add_consent_mode_defaults',
			],
			'ILCC_Trackers'           => [
				'get_necessary', 'get_marketing', 'get_analytics', 'get_all', 'get_allowlist_for_js', 'get_disallow_for_js',
			],
			'ILCC_Settings'           => [
				'hooks', 'get_policy_url', 'get_consent_title', 'get_consent_text', 'get_accept_text',
				'get_only_necessary_text', 'get_configure_settings_text', 'get_style',
				'get_settings_necessary_heading', 'get_settings_necessary_description',
				'get_settings_marketing_heading', 'get_settings_marketing_description',
				'get_settings_analytics_heading', 'get_settings_analytics_description',
				'get_save_settings_button_title', 'get_settings_title', 'get_settings_description',
				'is_analytics_shown', 'is_marketing_shown', 'is_consent_mode_integration_enabled',
				'customizer', 'integrity_settings', 'integrity_settings_page',
			],
		];

		$cases = [];
		foreach ( $methods as $class => $names ) {
			foreach ( $names as $name ) {
				$cases[ "$class::$name" ] = [ $class, $name ];
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider public_methods
	 */
	public function test_public_method_still_exists( $class, $method ) {
		$this->assertTrue( method_exists( $class, $method ), "$class::$method() was removed." );
		$this->assertTrue( ( new ReflectionMethod( $class, $method ) )->isPublic(), "$class::$method() is no longer public." );
	}

	public function test_global_function_still_exists() {
		$this->assertTrue( function_exists( 'ilmenite_cookie_consent' ) );
	}

	public function test_hooks_are_still_applied() {
		$source = '';
		foreach ( [ 'ilmenite-cookie-consent.php', 'classes/class-consent.php', 'classes/class-settings.php', 'classes/class-trackers.php' ] as $file ) {
			$source .= file_get_contents( ILCC_PLUGIN_DIR . '/' . $file );
		}

		$filters = [
			'ilcc_accept_text', 'ilcc_analytics_trackers', 'ilcc_categories_cookie_name', 'ilcc_configure_settings_text',
			'ilcc_consent_text', 'ilcc_consent_title', 'ilcc_dev_mode',
			'ilcc_enable_customizer', 'ilcc_is_active_on_page', 'ilcc_load_stylesheet',
			'ilcc_marketing_trackers', 'ilcc_necessary_trackers', 'ilcc_only_necessary_text', 'ilcc_policy_url',
			'ilcc_preferences_cookie_name', 'ilcc_remember_duration', 'ilcc_save_settings_text',
			'ilcc_settings_analytics_description', 'ilcc_settings_analytics_heading', 'ilcc_settings_analytics_is_shown',
			'ilcc_settings_description', 'ilcc_settings_marketing_description', 'ilcc_settings_marketing_heading',
			'ilcc_settings_marketing_is_shown', 'ilcc_settings_necessary_description', 'ilcc_settings_necessary_heading',
			'ilcc_settings_title', 'ilcc_style', 'ilcc_tracker_settings_enabled',
			// Added on main after 3.3.0.
			'ilcc_csp_nonce', 'ilcc_enable_default_consent_mode', 'ilcc_settings_google_consent_mode_enabled',
		];

		foreach ( $filters as $filter ) {
			$this->assertMatchesRegularExpression( "/apply_filters\\(\\s*'" . preg_quote( $filter, '/' ) . "'/", $source, "Filter $filter is no longer applied." );
		}

		foreach ( [ 'before_ilcc_init', 'ilcc_init', 'ilcc_loaded' ] as $action ) {
			$this->assertMatchesRegularExpression( "/do_action\\(\\s*'" . preg_quote( $action, '/' ) . "'/", $source, "Action $action is no longer fired." );
		}
	}

	public function test_cookie_names_and_duration_are_unchanged() {
		$this->assertSame( 'ilcc_has_preferences', \Ilmenite_Cookie_Consent::get_preferences_cookie_name() );
		$this->assertSame( 'ilcc_consent_categories', \Ilmenite_Cookie_Consent::get_categories_cookie_name() );
		$this->assertSame( 90, \Ilmenite_Cookie_Consent::get_remember_me_duration() );
		// Existing visitors' cookies hold "1", so the default version must stay "1".
		$this->assertSame( '1', \Ilmenite_Cookie_Consent::get_consent_version() );
	}

	public function test_customizer_still_stores_the_same_options() {
		if ( ! class_exists( 'WP_Customize_Control' ) ) {
			eval( 'class WP_Customize_Control { public $id; public $args; public function __construct( $manager, $id, $args ) { $this->id = $id; $this->args = $args; } }' );
		}

		$manager = new class() {
			public $settings = [];
			public $controls = [];
			public function add_panel( $id, $args ) {}
			public function add_section( $id, $args ) {}
			public function add_setting( $id, $args ) { $this->settings[ $id ] = $args; }
			public function add_control( $control ) { $this->controls[ $control->id ] = $control->args; }
		};

		// Capability filters, checked at runtime because they are applied from the field table.
		Filters\expectApplied( 'ilcc_edit_style_capability' )->atLeast()->once();
		Filters\expectApplied( 'ilcc_edit_title_capability' )->once();
		Filters\expectApplied( 'ilcc_edit_text_capability' )->once();
		Filters\expectApplied( 'ilcc_edit_policy_url_capability' )->once();
		Filters\expectApplied( 'ilcc_edit_button_capability' )->atLeast()->once();

		\ILCC_Settings::customizer( $manager );

		$expected = [
			'ilcc_style', 'ilcc_title', 'ilcc_text', 'ilcc_policy_url', 'ilcc_button', 'ilcc_only_necessary_text',
			'ilcc_configure_settings_text', 'ilcc_settings_title', 'ilcc_settings_description',
			'ilcc_settings_necessary_heading', 'ilcc_settings_necessary_description', 'ilcc_settings_analytics_is_shown',
			'ilcc_settings_analytics_heading', 'ilcc_settings_analytics_description', 'ilcc_settings_marketing_is_shown',
			'ilcc_settings_marketing_heading', 'ilcc_settings_marketing_description',
			'ilcc_settings_google_consent_mode_enabled', 'ilcc_save_settings_text',
		];

		$this->assertEqualsCanonicalizing( $expected, array_keys( $manager->settings ) );

		foreach ( $manager->settings as $id => $args ) {
			$this->assertSame( 'option', $args['type'], "$id must stay stored as an option." );
			$this->assertArrayHasKey( 'sanitize_callback', $args, "$id has no sanitize callback." );
			$this->assertArrayHasKey( $id, $manager->controls, "$id has no control." );
		}
	}
}
