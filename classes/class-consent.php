<?php

class ILCC_Consent {

	public static function hooks() {
		// Print as early as possible so the defaults are set before any Google tag loads.
		add_action( 'wp_head', [ self::class, 'print_consent_mode_script' ], 1 );

		add_shortcode( 'ilcc_settings_link', [ self::class, 'settings_link_shortcode' ] );
	}

	/**
	 * Shortcode: a link that opens the cookie settings.
	 *
	 * Lets visitors change or withdraw their consent at any time, e.g. from
	 * the footer or the cookie policy page. Usage: [ilcc_settings_link text="Cookie settings"]
	 *
	 * Any element with a data-ilcc-open-settings attribute, or a link to
	 * #ilcc-settings (such as a custom menu item), works the same way.
	 *
	 * @param  array|string  $atts
	 *
	 * @return string
	 */
	public static function settings_link_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'text'  => __( 'Cookie settings', 'ilmenite-cookie-consent' ),
			'class' => '',
		], $atts, 'ilcc_settings_link' );

		return sprintf(
			'<a href="#ilcc-settings" class="%s" data-ilcc-open-settings>%s</a>',
			esc_attr( trim( 'ilcc-settings-link ' . $atts['class'] ) ),
			esc_html( $atts['text'] )
		);
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

		return Ilmenite_Cookie_Consent::get_consent_version() === wp_unslash( $_COOKIE[ $name ] );
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

	/**
	 * Print the Google Consent Mode script in the head.
	 *
	 * @return void
	 */
	public static function print_consent_mode_script() {
		if ( ! Ilmenite_Cookie_Consent::should_load_banner() ) {
			return;
		}

		$script = self::get_consent_mode_script();

		if ( '' === $script ) {
			return;
		}

		printf(
			"<script nonce=\"%s\">\n%s\n</script>\n",
			esc_attr( ilmenite_cookie_consent()->get_csp_nonce() ),
			$script // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static JS with JSON-encoded config.
		);
	}

	/**
	 * Get the Google Consent Mode script.
	 *
	 * Sets every signal to denied by default, then, if the visitor has already
	 * made a choice, applies it straight away from the consent cookie. Both
	 * happen in the browser, so the output is cache-safe.
	 *
	 * The defaults can be turned off with the 'ilcc_enable_default_consent_mode'
	 * filter when they are set elsewhere, e.g. in a Google Tag Manager template.
	 *
	 * @return string
	 */
	public static function get_consent_mode_script() {
		if ( ! ILCC_Settings::is_consent_mode_integration_enabled() ) {
			return '';
		}

		$script = "window.dataLayer = window.dataLayer || [];\nfunction gtag(){dataLayer.push(arguments);}\n";

		if ( apply_filters( 'ilcc_enable_default_consent_mode', true ) ) {
			$defaults = apply_filters( 'ilcc_consent_mode_defaults', [
				'ad_storage'         => 'denied',
				'ad_user_data'       => 'denied',
				'ad_personalization' => 'denied',
				'analytics_storage'  => 'denied',
				'wait_for_update'    => 500,
			] );

			$script .= "gtag('consent', 'default', " . wp_json_encode( $defaults ) . ");\n";
		}

		$config = wp_json_encode( [
			'preferencesCookieName'         => Ilmenite_Cookie_Consent::get_preferences_cookie_name(),
			'consentedCategoriesCookieName' => Ilmenite_Cookie_Consent::get_categories_cookie_name(),
			'consentVersion'                => Ilmenite_Cookie_Consent::get_consent_version(),
		] );

		$script .= <<<JS
(function () {
	var config = {$config};
	function readCookie(name) {
		var parts = document.cookie ? document.cookie.split(';') : [];
		for (var i = 0; i < parts.length; i++) {
			var part = parts[i].replace(/^\s+/, '');
			if (part.indexOf(name + '=') === 0) {
				try { return decodeURIComponent(part.substring(name.length + 1)); } catch (e) { return null; }
			}
		}
		return null;
	}
	if (readCookie(config.preferencesCookieName) !== config.consentVersion) {
		return;
	}
	var categories = [];
	try { categories = JSON.parse(readCookie(config.consentedCategoriesCookieName)) || []; } catch (e) {}
	if (!Array.isArray(categories)) {
		categories = [];
	}
	var analytics = categories.indexOf('analytics') !== -1 ? 'granted' : 'denied';
	var marketing = categories.indexOf('marketing') !== -1 ? 'granted' : 'denied';
	gtag('consent', 'update', {
		ad_storage: marketing,
		ad_user_data: marketing,
		ad_personalization: marketing,
		analytics_storage: analytics
	});
})();
JS;

		return $script;
	}

	/**
	 * Get the Google Consent Mode defaults script.
	 *
	 * @deprecated 3.4.0 The script is printed in the head. See get_consent_mode_script().
	 *
	 * @return string
	 */
	public static function add_consent_mode_defaults() {
		return self::get_consent_mode_script();
	}

}
