/**
 * Internal dependencies
 */
import { getCookieValue, getJsonCookieValue, setCookie } from "./cookies";
import { hasMatomo, settings } from "./settings";

/**
 * Set the "has set preferences" cookie.
 *
 * @param {boolean} hasSet If preference has been stored.
 */
export function setHasSetPreferences( hasSet = true ) {
	let value = "1";

	if ( ! hasSet ) {
		value = "0";
	}

	setCookie( settings.setPreferencesCookieName, value );
}

/**
 * Set the consented categories.
 *
 * @param {Array} categories
 */
export function setConsentedCategories( categories = [] ) {
	setCookie( settings.consentedCategories, JSON.stringify( categories ) );

	if ( hasMatomo() && hasConsentedTo( "analytics" ) ) {
		_paq.push( [ "setCookieConsentGiven" ] );
	}
}

/**
 * Get the consented categories as object.
 *
 * @return {Array} Object of categories.
 */
export function getConsentedCategories() {
	const categories = getJsonCookieValue( settings.consentedCategories );

	return Array.isArray( categories ) ? categories : [];
}

/**
 * Check if a user has consented to a category.
 *
 * @param {string} category
 * @return {boolean} True if consented.
 */
export function hasConsentedTo( category ) {
	const categories = getConsentedCategories();

	if ( ! categories || categories.length <= 0 ) {
		return false;
	}

	return categories.includes( category );
}

/**
 * Check if user has set their preferences.
 *
 * @return {boolean} Prefs set or not.
 */
export function hasUserSetPreferences() {
	return "1" === getCookieValue( settings.setPreferencesCookieName );
}

export function addConsentedCategory( category ) {
	let categories = getConsentedCategories();

	if ( categories && categories.length > 0 ) {
		if ( ! categories.includes( category ) ) {
			categories.push( category );
		}
	} else {
		categories = [ category ];
	}

	setConsentedCategories( categories );
}

export function removeConsentedCategory( category ) {
	let categories = getConsentedCategories();

	if ( ! categories || categories.length <= 0 ) {
		return;
	}

	if ( ! categories.includes( category ) ) {
		return;
	}

	categories = categories.filter( function( e ) {
		return e !== category;
	} );

	setConsentedCategories( categories );
}
