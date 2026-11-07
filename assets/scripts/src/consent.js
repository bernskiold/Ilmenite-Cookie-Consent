/**
 * Internal dependencies
 */
import { getCookieValue, getJsonCookieValue, setCookie } from "./cookies";
import { getConsentVersion, hasMatomo, settings } from "./settings";
import { updateConsentMode } from "./consent-mode";

/**
 * Set the "has set preferences" cookie.
 *
 * @param {boolean} hasSet If preference has been stored.
 */
export function setHasSetPreferences( hasSet = true ) {
	let value = getConsentVersion();

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
	if ( ! hasUserSetPreferences() ) {
		return [];
	}

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
	return getConsentVersion() === getCookieValue( settings.setPreferencesCookieName );
}

/**
 * Store the visitor's choice and apply it to the current page.
 *
 * Saves the cookies, then unblocks the scripts of every consented
 * category and passes the choice on to Google Consent Mode, so that
 * the choice takes effect without a page reload.
 *
 * @param {Array} categories The consented categories.
 */
export function applyConsent( categories ) {
	if ( ! categories.includes( "necessary" ) ) {
		categories = [ "necessary", ...categories ];
	}

	setHasSetPreferences();
	setConsentedCategories( categories );
	unblockCategories( categories );
	updateConsentMode();
}

/**
 * Unblock the scripts of the consented categories.
 *
 * @param {Array} categories
 */
function unblockCategories( categories ) {
	if ( window.ilccBlocker && typeof window.ilccBlocker.unblock === "function" ) {
		window.ilccBlocker.unblock( categories.filter( ( category ) => "necessary" !== category ) );
	}
}
