<?php
/*
 *	Plugin Name: 	Ilmenite Cookie Consent
 *	Plugin URI: 	https://github.com/bernskioldmedia/Ilmenite-Cookie-Consent
 *	Description: 	A simple, developer-friendly WordPress plugin that lets visitors know that the site is using cookies.
 *	Author: 		Bernskiold Media
 *	Version: 		3.3.0
 *	Author URI: 	http://www.bernskioldmedia.com/
 *	Text Domain: 	ilmenite-cookie-consent
 *	Domain Path: 	/languages
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ilmenite Cookie Consent
 */
class Ilmenite_Cookie_Consent {

	/**
	 * The Plugin Path
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * The Plugin URL
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * The Plugin Version
	 *
	 * @var string
	 */
	public $version = '3.3.0';

	/**
	 * The CSP nonce for inline scripts, generated once per request.
	 *
	 * @var string|null
	 */
	protected $csp_nonce = null;

	/**
	 * The single instance of the class
	 *
	 * @var Ilmenite_Cookie_Consent|null
	 */
	protected static $_instance = null;

	/**
	 * Instance
	 *
	 * @return Ilmenite_Cookie_Consent
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Set Developer Mode Constant.
		if ( ! defined( 'ILCC_DEV_MODE' ) ) {
			define( 'ILCC_DEV_MODE', apply_filters( 'ilcc_dev_mode', false ) );
		}

		// Set the plugin path.
		$this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );

		// Set the plugin URL.
		$this->plugin_url = untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) );

		// Load classes.
		$this->classes();

		// Hooks to run on plugin init.
		$this->init_hooks();

		do_action( 'ilcc_loaded' );
	}

	/**
	 * Hooks into various necessary hooks
	 * at the init time.
	 *
	 * @return void
	 */
	public function init_hooks() {
		do_action( 'before_ilcc_init' );

		// Add Scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ] );

		// Add Styles.
		add_action( 'wp_enqueue_scripts', [ $this, 'styles' ] );

		// Add Translation Loading.
		add_action( 'plugins_loaded', [ $this, 'load_languages' ] );

		// Boot other classes.
		ILCC_Settings::hooks();
		ILCC_Consent::hooks();

		do_action( 'ilcc_init' );
	}

	/**
	 * Load Classes
	 */
	public function classes() {
		require_once 'classes/class-consent.php';
		require_once 'classes/class-trackers.php';
		require_once 'classes/class-settings.php';
	}

	/**
	 * Load translations in the right order.
	 */
	public function load_languages() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'ilmenite-cookie-consent' );

		unload_textdomain( 'ilmenite-cookie-consent' );

		// Start checking in the main language dir.
		load_textdomain( 'ilmenite-cookie-consent', WP_LANG_DIR . '/ilmenite-cookie-consent/ilmenite-cookie-consent-' . $locale . '.mo' );

		// Otherwise, load from the plugin.
		load_plugin_textdomain( 'ilmenite-cookie-consent', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Load necessary scripts for the plugin.
	 */
	public function scripts() {

		/**
		 * If the banner shouldn't load on this page, bail early.
		 */
		if ( ! self::should_load_banner() ) {
			return;
		}

		add_filter( 'script_loader_tag', [ $this, 'add_nonce_to_script_tag' ], 10, 2 );
		add_filter( 'wp_inline_script_attributes', [ $this, 'add_nonce_to_inline_script' ] );

		wp_register_script( 'ilmenite-cookie-consent', $this->plugin_url . '/assets/scripts/dist/cookie-banner.js', [ 'ilcc-vendor' ], $this->version, true );

		wp_register_script( 'ilcc-vendor', $this->plugin_url . '/assets/scripts/dist/cookie-banner-vendor.js', [], $this->version, false );

		/**
		 * We localize the script to add our texts.
		 * These are changeable by filters. See the functions
		 * that get the texts below.
		 */
		wp_localize_script( 'ilmenite-cookie-consent', 'ilcc', [
			'cookieConsentTitle'            => ILCC_Settings::get_consent_title(),
			'cookieConsentText'             => ILCC_Settings::get_consent_text(),
			'acceptText'                    => ILCC_Settings::get_accept_text(),
			'style'                         => ILCC_Settings::get_style(),
			'configureSettingsText'         => ILCC_Settings::get_configure_settings_text(),
			'necessaryText'                 => ILCC_Settings::get_only_necessary_text(),
			'rememberDuration'              => self::get_remember_me_duration(),
			'preferencesCookieName'         => self::get_preferences_cookie_name(),
			'consentedCategoriesCookieName' => self::get_categories_cookie_name(),
			'necessaryHeading'              => ILCC_Settings::get_settings_necessary_heading(),
			'necessaryDescription'          => ILCC_Settings::get_settings_necessary_description(),
			'isAnalyticsShown'              => ILCC_Settings::is_analytics_shown() ? '1' : '0',
			'analyticsHeading'              => ILCC_Settings::get_settings_analytics_heading(),
			'analyticsDescription'          => ILCC_Settings::get_settings_analytics_description(),
			'isMarketingShown'              => ILCC_Settings::is_marketing_shown() ? '1' : '0',
			'marketingHeading'              => ILCC_Settings::get_settings_marketing_heading(),
			'marketingDescription'          => ILCC_Settings::get_settings_marketing_description(),
			'saveSettingsText'              => ILCC_Settings::get_save_settings_button_title(),
			'settingsTitle'                 => ILCC_Settings::get_settings_title(),
			'settingsDescription'           => ILCC_Settings::get_settings_description(),
			'consentModeEnabled' 			=> ILCC_Settings::is_consent_mode_integration_enabled() ? '1' : '0',
			'debug'                         => $this->is_debugging(),
		] );

		/**
		 * Add the whitelist and blacklist.
		 */
		wp_add_inline_script( 'ilcc-vendor', $this->get_allow_and_disallowlists(), 'before' );

		/**
		 * Add consent mode defaults.
		 */
		wp_add_inline_script('ilmenite-cookie-consent', ILCC_Consent::add_consent_mode_defaults(), 'before');

		// Finally, enqueue!
		wp_enqueue_script( 'ilcc-vendor' );
		wp_enqueue_script( 'ilmenite-cookie-consent' );
	}

	/**
	 * Returns the CSP nonce for this request, generating one if needed.
	 *
	 * Hook 'ilcc_csp_nonce' to supply an externally-generated nonce (e.g. from a
	 * CSP plugin) so the same value ends up in both the CSP header and script tags.
	 *
	 * @return string
	 */
	public function get_csp_nonce() {
		if ( null === $this->csp_nonce ) {
			$this->csp_nonce = apply_filters( 'ilcc_csp_nonce', bin2hex( random_bytes( 16 ) ) );
		}
		return $this->csp_nonce;
	}

	/**
	 * Adds nonce attribute to the plugin's external <script src> tags.
	 *
	 * @param string $tag    The full script tag HTML.
	 * @param string $handle The script handle.
	 * @return string
	 */
	public function add_nonce_to_script_tag( $tag, $handle ) {
		if ( ! in_array( $handle, [ 'ilmenite-cookie-consent', 'ilcc-vendor' ], true ) ) {
			return $tag;
		}
		return str_replace( '<script ', '<script nonce="' . esc_attr( $this->get_csp_nonce() ) . '" ', $tag );
	}

	/**
	 * Adds nonce attribute to inline <script> blocks (WordPress 6.3+).
	 *
	 * @param array $attributes Script element attributes.
	 * @return array
	 */
	public function add_nonce_to_inline_script( $attributes ) {
		$attributes['nonce'] = $this->get_csp_nonce();
		return $attributes;
	}

	/**
	 * Get the black- and whitelist HTML.
	 *
	 * @return string
	 */
	public function get_allow_and_disallowlists() {
		$output = "window.YETT_BLACKLIST = [" . esc_js( ILCC_Trackers::get_disallow_for_js() ) . "];\n";

		if ( ! empty( ILCC_Trackers::get_allowlist_for_js() ) ) {
			$output .= 'window.YETT_WHITELIST = [' . esc_js( ILCC_Trackers::get_allowlist_for_js() ) . '];';
		}

		return $output;
	}

	/**
	 * Load the built-in styles for the plugin.
	 */
	public function styles() {
		/**
		 * If the banner shouldn't load on this page, bail early.
		 */
		if ( ! self::should_load_banner() ) {
			return;
		}

		/**
		 * Don't load anything if we are asked not
		 * to load the stylesheet.
		 */
		if ( false === apply_filters( 'ilcc_load_stylesheet', true ) || true === ILCC_DEV_MODE ) {
			return;
		}

		/**
		 * Register the main stylesheet.
		 */
		wp_register_style( 'ilmenite-cookie-consent', $this->plugin_url . '/assets/styles/dist/cookie-banner.css', false, $this->version, 'all' );

		// Finally, enqueue!
		wp_enqueue_style( 'ilmenite-cookie-consent' );
	}

	/**
	 * Check if we should  be loading the banner on this page.
	 * This hook allows overriding to hide the banner on certain pages or templates.
	 *
	 * @return bool
	 */
	public static function should_load_banner() {
		return apply_filters( 'ilcc_is_active_on_page', true );
	}

	/**
	 * Get the name of the cookie.
	 *
	 * @return string
	 */
	public static function get_preferences_cookie_name() {
		return apply_filters( 'ilcc_preferences_cookie_name', 'ilcc_has_preferences' );
	}

	/**
	 * Get Categories Cookie Name
	 *
	 * @return string
	 */
	public static function get_categories_cookie_name() {
		return apply_filters( 'ilcc_categories_cookie_name', 'ilcc_consent_categories' );
	}

	/**
	 * Get how many days the user should be remembered.
	 *
	 * @return int
	 */
	public static function get_remember_me_duration() {
		return apply_filters( 'ilcc_remember_duration', 90 );
	}

	/**
	 * Check if we are debugging or not.
	 *
	 * @return bool
	 */
	public function is_debugging() {
		if ( defined( 'ILCC_DEBUG' ) ) {
			return ILCC_DEBUG;
		}

		return false;
	}

}

/**
 * Returns an instance of the plugin class.
 *
 * @return Ilmenite_Cookie_Consent
 */
function ilmenite_cookie_consent() {
	return Ilmenite_Cookie_Consent::instance();
}

// Initialize the class instance only once
ilmenite_cookie_consent();
