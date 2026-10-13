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
	 * Get Necessary trackers.
	 *
	 * @return array
	 */
	public static function get_necessary() {
		$option = get_option( 'ilcc_domains_necessary', self::$necessary );

		if ( is_string( $option ) ) {
			$option = explode( "\n", $option );
		}

		return apply_filters( 'ilcc_necessary_trackers', $option );
	}

	/**
	 * Get Marketing Trackers
	 *
	 * @return array
	 */
	public static function get_marketing() {
		$option = get_option( 'ilcc_domains_marketing', self::$marketing );

		if ( is_string( $option ) ) {
			$option = explode( "\n", $option );
		}

		return apply_filters( 'ilcc_marketing_trackers', $option );
	}

	/**
	 * Get analytics trackers.
	 *
	 * @return array
	 */
	public static function get_analytics() {
		$option = get_option( 'ilcc_domains_analytics', self::$analytics );

		if ( is_string( $option ) ) {
			$option = explode( "\n", $option );
		}

		return apply_filters( 'ilcc_analytics_trackers', $option );
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
	 * Format a list of domains as JavaScript regular expression literals.
	 *
	 * @param  array  $domains
	 *
	 * @return string
	 */
	public static function get_domains_for_js( $domains ) {
		$patterns = [];

		foreach ( (array) $domains as $domain ) {
			if ( ! empty( $domain ) ) {
				$patterns[] = '/' . addslashes( $domain ) . '/';
			}
		}

		return implode( ',', $patterns );
	}

	/**
	 * Get Whitelist for use in JavaScript
	 *
	 * @return string
	 */
	public static function get_allowlist_for_js() {
		$allowlist = [];

		foreach ( self::get_necessary() as $domain ) {
			if ( ! empty( $domain ) ) {
				$allowlist[] = '/' . addslashes( $domain ) . '/';
			}
		}

		if( ILCC_Consent::has_consented_to( 'marketing') ){
			foreach ( self::get_marketing() as $domain ) {
				if ( ! empty( $domain ) ) {
					$allowlist[] = '/' . addslashes( $domain ) . '/';
				}
			}
		}

		if( ILCC_Consent::has_consented_to( 'analytics') ){
			foreach ( self::get_analytics() as $domain ) {
				if ( ! empty( $domain ) ) {
					$allowlist[] = '/' . addslashes( $domain ) . '/';
				}
			}
		}

		return implode( ',', $allowlist );
	}

	/**
	 * Get Disallow List for use in JavaScript
	 *
	 * @return string
	 */
	public static function get_disallow_for_js() {

		$disallow = [];

		if( ! ILCC_Consent::has_consented_to( 'marketing') ){
			foreach ( self::get_marketing() as $domain ) {
				if ( ! empty( $domain ) ) {
					$disallow[] = '/' . addslashes( $domain ) . '/';
				}
			}
		}

		if( ! ILCC_Consent::has_consented_to( 'analytics') ){
			foreach ( self::get_analytics() as $domain ) {
				if ( ! empty( $domain ) ) {
					$disallow[] = '/' . addslashes( $domain ) . '/';
				}
			}
		}

		return implode( ',', $disallow );
	}
}
