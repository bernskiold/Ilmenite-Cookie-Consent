/**
 * Internal dependencies
 */
import { isDebugging, settings } from "./settings";

/**
 * Get the value of a cookie by its name.
 *
 * @param {string} name Cookie Name.
 * @return {string|null} String or null.
 */
export function getCookieValue( name ) {
	const nameEQ = name + '=';
	const cookies = document.cookie.split( ';' );
	const cookiesCount = cookies.length;

	for ( let i = 0; i < cookiesCount; i++ ) {
		let cookie = cookies[ i ];

		while ( cookie.charAt( 0 ) === ' ' ) {
			cookie = cookie.substring( 1, cookie.length );
		}

		if ( cookie.indexOf( nameEQ ) === 0 ) {
			const value = cookie.substring( nameEQ.length, cookie.length );

			try {
				return decodeURIComponent( value );
			} catch ( e ) {
				return value;
			}
		}
	}

	return null;
}

/**
 * Get cookie value that is expected to be JSON.
 *
 * Returns null if the cookie is missing or not valid JSON, so that a
 * malformed cookie can never stop the banner from working.
 *
 * @param {string} name
 * @return {any} Parsed value or null.
 */
export function getJsonCookieValue( name ) {
	const value = getCookieValue( name );

	if ( null === value ) {
		return null;
	}

	try {
		return JSON.parse( value );
	} catch ( e ) {
		return null;
	}
}

/**
 * Set Cookie
 *
 * @param {string} name Cookie Name.
 * @param {string} value Cookie Value
 */
export function setCookie( name, value ) {
	if ( isDebugging() ) {
		/* eslint-disable no-console */
		console.log( 'Debug Mode Active. Not Setting Cookie.' );
		console.log( 'Name: ' + name );
		console.log( 'Value:' );
		console.log( value );
		/* eslint-enable no-console */

		return;
	}

	const date = new Date();
	date.setTime( date.getTime() + convertDaysToMilliseconds( settings.consentRememberDuration ) );

	let cookie = name + '=' + encodeURIComponent( value ) + '; expires=' + date.toUTCString() + '; path=/; SameSite=Lax';

	if ( 'https:' === window.location.protocol ) {
		cookie += '; Secure';
	}

	document.cookie = cookie;
}

/**
 * Convert Days to Milliseconds
 *
 * @param {number} days
 *
 * @return {number} Day in milliseconds.
 */
function convertDaysToMilliseconds( days ) {
	return days * 24 * 60 * 60 * 1000;
}
