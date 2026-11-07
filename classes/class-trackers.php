<?php

/**
 * Class ILCC_Trackers
 */
class ILCC_Trackers {

	/**
	 * Any external URLs that load scripts may be allowlisted
	 * when put in the necessary section.
	 *
	 * @var array
	 */
	protected static $necessary = [];

	/**
	 * Add a list of marketing trackers.
	 *
	 * @var array
	 */
	protected static $marketing = [
		'facebook.com',
		'connect.facebook.net',
		'doubleclick.net',
		'hs-scripts.com',
		'linkedin.com',
		'licdn.com',
		'bing.com',
		'googleadservices.com',
	];

	/**
	 * A list of analytics tracking domains.
	 *
	 * @var array
	 */
	protected static $analytics = [
		'google-analytics.com',
		'googletagmanager.com',
		'hotjar.com',
	];

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		foreach ( [ 'necessary', 'analytics', 'marketing' ] as $category ) {
			add_filter( 'sanitize_option_ilcc_domains_' . $category, [ self::class, 'sanitize_domains_option' ] );
		}
	}

	/**
	 * Sanitize a domain list option before it is saved.
	 *
	 * @param  mixed  $value  The submitted value. A newline separated string or an array.
	 *
	 * @return string Newline separated list of cleaned domains.
	 */
	public static function sanitize_domains_option( $value ) {
		return implode( "\n", self::clean_domains( $value ) );
	}

	/**
	 * Clean a list of domains.
	 *
	 * Trims whitespace, removes the protocol, drops empty lines and duplicates.
	 * Anything else is kept as-is because the list is matched as a literal
	 * substring of the script URL, so paths (example.com/script.js) are allowed.
	 *
	 * @param  mixed  $domains  A newline separated string or an array.
	 *
	 * @return array
	 */
	public static function clean_domains( $domains ) {
		if ( is_string( $domains ) ) {
			$domains = preg_split( '/\r\n|\r|\n/', $domains );
		}

		if ( ! is_array( $domains ) ) {
			return [];
		}

		$clean = [];

		foreach ( $domains as $domain ) {
			if ( ! is_string( $domain ) ) {
				continue;
			}

			$domain = trim( $domain );
			$domain = preg_replace( '#^(https?:)?//#i', '', $domain );
			$domain = preg_replace( '/\s+/', '', $domain );

			if ( '' === $domain ) {
				continue;
			}

			$clean[] = $domain;
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Get Necessary trackers.
	 *
	 * @return array
	 */
	public static function get_necessary() {
		$option = get_option( 'ilcc_domains_necessary', self::$necessary );

		return self::clean_domains( apply_filters( 'ilcc_necessary_trackers', self::clean_domains( $option ) ) );
	}

	/**
	 * Get Marketing Trackers
	 *
	 * @return array
	 */
	public static function get_marketing() {
		$option = get_option( 'ilcc_domains_marketing', self::$marketing );

		return self::clean_domains( apply_filters( 'ilcc_marketing_trackers', self::clean_domains( $option ) ) );
	}

	/**
	 * Get analytics trackers.
	 *
	 * @return array
	 */
	public static function get_analytics() {
		$option = get_option( 'ilcc_domains_analytics', self::$analytics );

		return self::clean_domains( apply_filters( 'ilcc_analytics_trackers', self::clean_domains( $option ) ) );
	}

	/**
	 * Get all trackers.
	 *
	 * @return array
	 */
	public static function get_all() {
		return array_merge( self::get_marketing(), self::get_analytics() );
	}

	/**
	 * Get the tracker lists, per category, for use in JavaScript.
	 *
	 * The lists are the same for every visitor. Whether a category is blocked
	 * or allowed is decided in the browser from the consent cookie, so pages
	 * stay safe to serve from a full page cache.
	 *
	 * @return array
	 */
	public static function get_lists_for_js() {
		return [
			'necessary' => self::get_necessary(),
			'analytics' => self::get_analytics(),
			'marketing' => self::get_marketing(),
		];
	}

	/**
	 * Get the inline script that configures the script blocker.
	 *
	 * It must run before the blocker (yett) loads. It reads the consent cookie
	 * in the browser and sets window.YETT_BLACKLIST / window.YETT_WHITELIST.
	 *
	 * @return string
	 */
	public static function get_blocker_script() {
		$config = [
			'trackers'                      => self::get_lists_for_js(),
			'preferencesCookieName'         => Ilmenite_Cookie_Consent::get_preferences_cookie_name(),
			'consentedCategoriesCookieName' => Ilmenite_Cookie_Consent::get_categories_cookie_name(),
		];

		$json = wp_json_encode( $config );

		return <<<JS
(function () {
	var config = {$json};

	function readCookie(name) {
		var parts = document.cookie ? document.cookie.split(';') : [];
		for (var i = 0; i < parts.length; i++) {
			var part = parts[i].replace(/^\s+/, '');
			if (part.indexOf(name + '=') === 0) {
				try {
					return decodeURIComponent(part.substring(name.length + 1));
				} catch (e) {
					return part.substring(name.length + 1);
				}
			}
		}
		return null;
	}

	function toPatterns(domains) {
		var patterns = [];
		for (var i = 0; i < domains.length; i++) {
			patterns.push(new RegExp(domains[i].replace(/[.*+?^\${}()|[\]\\\\\/]/g, '\\\\$&')));
		}
		return patterns;
	}

	var consented = [];
	if (readCookie(config.preferencesCookieName) === '1') {
		try {
			var parsed = JSON.parse(readCookie(config.consentedCategoriesCookieName));
			if (Array.isArray(parsed)) {
				consented = parsed;
			}
		} catch (e) {}
	}

	var patterns = {
		necessary: toPatterns(config.trackers.necessary),
		analytics: toPatterns(config.trackers.analytics),
		marketing: toPatterns(config.trackers.marketing)
	};

	var blacklist = [];
	var whitelist = patterns.necessary.slice();
	['analytics', 'marketing'].forEach(function (category) {
		if (consented.indexOf(category) !== -1) {
			whitelist = whitelist.concat(patterns[category]);
		} else {
			blacklist = blacklist.concat(patterns[category]);
		}
	});

	window.ilccBlocker = { patterns: patterns, consented: consented };
	window.YETT_BLACKLIST = blacklist;
	if (whitelist.length) {
		window.YETT_WHITELIST = whitelist;
	}
})();
JS;
	}

	/**
	 * Get Whitelist for use in JavaScript
	 *
	 * @deprecated 3.4.0 The lists are now built in the browser. See get_blocker_script().
	 *
	 * @return string
	 */
	public static function get_allowlist_for_js() {
		$domains = self::get_necessary();

		if ( ILCC_Consent::has_consented_to( 'marketing' ) ) {
			$domains = array_merge( $domains, self::get_marketing() );
		}

		if ( ILCC_Consent::has_consented_to( 'analytics' ) ) {
			$domains = array_merge( $domains, self::get_analytics() );
		}

		return self::to_js_regex_list( $domains );
	}

	/**
	 * Get Disallow List for use in JavaScript
	 *
	 * @deprecated 3.4.0 The lists are now built in the browser. See get_blocker_script().
	 *
	 * @return string
	 */
	public static function get_disallow_for_js() {
		$domains = [];

		if ( ! ILCC_Consent::has_consented_to( 'marketing' ) ) {
			$domains = array_merge( $domains, self::get_marketing() );
		}

		if ( ! ILCC_Consent::has_consented_to( 'analytics' ) ) {
			$domains = array_merge( $domains, self::get_analytics() );
		}

		return self::to_js_regex_list( $domains );
	}

	/**
	 * Turn a list of domains into a comma separated list of JS regex literals.
	 *
	 * @param  array  $domains
	 *
	 * @return string
	 */
	protected static function to_js_regex_list( $domains ) {
		$patterns = [];

		foreach ( $domains as $domain ) {
			$patterns[] = '/' . preg_quote( $domain, '/' ) . '/';
		}

		return implode( ',', $patterns );
	}
}
