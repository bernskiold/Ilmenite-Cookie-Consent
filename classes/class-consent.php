<?php

class ILCC_Consent {

	public static function hooks() {
		add_action('wp_enqueue_scripts', [self::class, 'add_consent_mode_defaults']);
	}

	/**
	 * Check if preferences have been set.
	 *
	 * @return bool
	 */
	public static function has_set_preferences() {
		$name = Ilmenite_Cookie_Consent::get_preferences_cookie_name();

		if ( ! isset( $_COOKIE[ $name ] ) || ! is_string( $_COOKIE[ $name ] ) ) {
			return false;
		}

		return '1' === $_COOKIE[ $name ];
	}

	/**
	 * Check if we have full consent to all categories.
	 *
	 * @return bool
	 */
	public static function has_full_consent() {
		return self::has_consented_to( 'analytics' ) && self::has_consented_to( 'marketing' );
	}

	/**
	 * Check if we have consent for a specific category.
	 *
	 * @param  string  $category
	 *
	 * @return bool
	 */
	public static function has_consented_to( $category ) {
		return in_array( $category, self::get_consented_list(), true );
	}

	/**
	 * Get the list of consented categories from the cookie.
	 *
	 * Categories only count once the visitor has saved their preferences.
	 *
	 * @return string[]
	 */
	protected static function get_consented_list() {
		$name = Ilmenite_Cookie_Consent::get_categories_cookie_name();

		if ( ! self::has_set_preferences() || ! isset( $_COOKIE[ $name ] ) || ! is_string( $_COOKIE[ $name ] ) ) {
			return [];
		}

		$value = json_decode( wp_unslash( $_COOKIE[ $name ] ), true );

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_filter( $value, 'is_string' ) );
	}

	public static function add_consent_mode_defaults() {
		if(!ILCC_Settings::is_consent_mode_integration_enabled()) {
			return;
		}

		if(false === apply_filters('ilcc_enable_default_consent_mode', false)) {
			return '';
		}

		return <<<JS
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}

gtag('consent', 'default', {
  'ad_storage': 'denied',
  'ad_user_data': 'denied',
  'ad_personalization': 'denied',
  'analytics_storage': 'denied'
});
JS;
	}

}
