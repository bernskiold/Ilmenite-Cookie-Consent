<?php

/**
 * Class ILCC_Blocker
 *
 * Makes tracker tags in the page inert before they reach the browser.
 *
 * Blocking in the browser alone is not enough: browsers start downloading
 * `<script src>`, `<iframe>`, `<img>` and preload URLs from the HTML before
 * any script can run, and those requests carry and set third-party cookies.
 * So tags pointing at a non-necessary tracker domain are rewritten here into
 * opt-in markup the browser ignores:
 *
 *     <script src="…">               → <script type="text/plain" data-ilcc-category="…" src="…">
 *     <iframe src="…">, <img src="…"> → data-src="…" data-ilcc-category="…"
 *     <link rel="preload" href="…">  → data-href="…" data-ilcc-category="…"
 *
 * The rewrite is the same for every visitor, so pages stay cache-safe; the
 * script blocker in the browser restores the tags the visitor consented to.
 *
 * Disable with the 'ilcc_rewrite_tracker_tags' filter. Add a
 * data-ilcc-ignore attribute to a tag to leave it alone.
 */
class ILCC_Blocker {

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function hooks() {
		// Late, so this buffer sits inside any page cache's buffer and the
		// cache stores the rewritten page.
		add_action( 'template_redirect', [ self::class, 'start_buffer' ], 9999 );

		// Scripts enqueued through WordPress, also when output buffering is off.
		add_filter( 'script_loader_tag', [ self::class, 'filter_script_tag' ], 9999 );
	}

	/**
	 * Whether tracker tags should be rewritten on this request.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() || is_robots() ) {
			return false;
		}

		if ( ! Ilmenite_Cookie_Consent::should_load_banner() ) {
			return false;
		}

		return (bool) apply_filters( 'ilcc_rewrite_tracker_tags', true );
	}

	/**
	 * Start buffering the page so it can be rewritten.
	 *
	 * @return void
	 */
	public static function start_buffer() {
		if ( self::is_enabled() ) {
			ob_start( [ self::class, 'rewrite_html' ] );
		}
	}

	/**
	 * Rewrite one script tag printed by WordPress.
	 *
	 * @param  string  $tag
	 *
	 * @return string
	 */
	public static function filter_script_tag( $tag ) {
		return self::is_enabled() ? self::rewrite_html( $tag ) : $tag;
	}

	/**
	 * Rewrite tracker tags in a piece of HTML.
	 *
	 * @param  string  $html
	 *
	 * @return string
	 */
	public static function rewrite_html( $html ) {
		if ( ! is_string( $html ) || '' === $html || false === stripos( $html, '<' ) ) {
			return $html;
		}

		// Only HTML responses.
		if ( function_exists( 'headers_list' ) ) {
			foreach ( headers_list() as $header ) {
				if ( 0 === stripos( $header, 'content-type:' ) && false === stripos( $header, 'html' ) ) {
					return $html;
				}
			}
		}

		// Whole script elements are matched so that their contents are skipped:
		// a "<script src=…>" inside JavaScript code must never be touched.
		$attributes = '((?:[^>"\']+|"[^"]*"|\'[^\']*\')*)';
		$pattern    = '/<(script)\b' . $attributes . '>(.*?)<\/script\s*>|<(iframe|img|link)\b' . $attributes . '>/is';

		$rewritten = preg_replace_callback( $pattern, [ self::class, 'rewrite_match' ], $html );

		return null === $rewritten ? $html : $rewritten;
	}

	/**
	 * Rewrite one match of the tag pattern.
	 *
	 * @param  array  $match
	 *
	 * @return string
	 */
	protected static function rewrite_match( $match ) {
		if ( isset( $match[4] ) && '' !== $match[4] ) {
			return self::rewrite_tag( [ $match[0], $match[4], $match[5] ] );
		}

		$opening   = '<' . $match[1] . $match[2] . '>';
		$rewritten = self::rewrite_tag( [ $opening, $match[1], $match[2] ] );

		return $rewritten . substr( $match[0], strlen( $opening ) );
	}

	/**
	 * Rewrite a single opening tag if it points at a blocked tracker.
	 *
	 * @param  array  $match  [ full tag, tag name, attributes ].
	 *
	 * @return string
	 */
	protected static function rewrite_tag( $match ) {
		$tag        = $match[0];
		$name       = strtolower( $match[1] );
		$attributes = $match[2];

		if ( self::has_attribute( $attributes, 'data-ilcc-ignore' ) || self::has_attribute( $attributes, 'data-ilcc-category' ) ) {
			return $tag;
		}

		$url_attribute = 'link' === $name ? 'href' : 'src';
		$url           = self::get_attribute( $attributes, $url_attribute );

		if ( null === $url ) {
			return $tag;
		}

		$category = self::blocked_category( html_entity_decode( $url, ENT_QUOTES ) );

		if ( null === $category ) {
			return $tag;
		}

		$marker = ' data-ilcc-category="' . $category . '"';

		if ( 'script' === $name ) {
			$type = self::get_attribute( $attributes, 'type' );

			// Leave data blocks (JSON, templates) and already inert scripts alone.
			if ( null !== $type && ! preg_match( '#^\s*(text/javascript|application/javascript|module|)\s*$#i', $type ) ) {
				return $tag;
			}

			$attributes = self::remove_attribute( $attributes, 'type' );

			// Remember a non-default type (module) so it can be restored.
			if ( null !== $type && 'module' === strtolower( trim( $type ) ) ) {
				$marker .= ' data-ilcc-type="module"';
			}

			return '<script type="text/plain"' . $marker . $attributes . '>';
		}

		if ( 'link' === $name ) {
			$rel = preg_split( '/\s+/', strtolower( trim( (string) self::get_attribute( $attributes, 'rel' ) ) ) );

			// Only links that make the browser fetch something. dns-prefetch and
			// preconnect open a connection but send no request, so no cookies.
			if ( ! array_intersect( $rel, [ 'preload', 'modulepreload', 'prefetch', 'stylesheet' ] ) ) {
				return $tag;
			}
		}

		$attributes = self::rename_attribute( $attributes, $url_attribute, 'data-' . $url_attribute );

		if ( 'img' === $name && self::has_attribute( $attributes, 'srcset' ) ) {
			$attributes = self::rename_attribute( $attributes, 'srcset', 'data-srcset' );
		}

		return '<' . $match[1] . $marker . $attributes . '>';
	}

	/**
	 * The non-necessary category a URL belongs to.
	 *
	 * Mirrors the matching in assets/scripts/src/blocker.js: a domain matches
	 * when it appears anywhere in the URL, and necessary domains always win.
	 *
	 * @param  string  $url
	 *
	 * @return string|null Category, or null if the URL is not a blocked tracker.
	 */
	public static function blocked_category( $url ) {
		$url = (string) $url;

		if ( '' === $url ) {
			return null;
		}

		foreach ( ILCC_Trackers::get_necessary() as $domain ) {
			if ( false !== strpos( $url, $domain ) ) {
				return null;
			}
		}

		$lists = [
			'analytics' => ILCC_Trackers::get_analytics(),
			'marketing' => ILCC_Trackers::get_marketing(),
		];

		foreach ( $lists as $category => $domains ) {
			foreach ( $domains as $domain ) {
				if ( false !== strpos( $url, $domain ) ) {
					return $category;
				}
			}
		}

		return null;
	}

	/**
	 * Attribute pattern: name, then an optional double-, single- or unquoted value.
	 *
	 * @param  string  $name
	 *
	 * @return string
	 */
	protected static function attribute_pattern( $name ) {
		return '/(\s)(' . preg_quote( $name, '/' ) . ')(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?(?=[\s\/]|$)/i';
	}

	protected static function has_attribute( $attributes, $name ) {
		return 1 === preg_match( self::attribute_pattern( $name ), $attributes );
	}

	protected static function get_attribute( $attributes, $name ) {
		if ( ! preg_match( self::attribute_pattern( $name ), $attributes, $match ) ) {
			return null;
		}

		return isset( $match[3] ) ? trim( $match[3], '"\'' ) : '';
	}

	protected static function remove_attribute( $attributes, $name ) {
		return preg_replace( self::attribute_pattern( $name ), '', $attributes, 1 );
	}

	protected static function rename_attribute( $attributes, $from, $to ) {
		return preg_replace_callback( self::attribute_pattern( $from ), function ( $match ) use ( $to ) {
			return $match[1] . $to . ( isset( $match[3] ) ? '=' . $match[3] : '' );
		}, $attributes, 1 );
	}
}
