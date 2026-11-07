/**
 * Internal dependencies
 */
import { getSelectedCategories, removeBanner, showBanner, toggleCategory, toggleSettings } from "./banner";
import { applyConsent, hasConsentedTo, hasUserSetPreferences } from "./consent";
import { getJsonCookieValue } from "./cookies";
import { log, logDebug, logInfo } from "./log";
import { getBannerStyle, hasMatomo, isConfigurable, isDebugging, settings } from "./settings";
import { updateConsentMode } from "./consent-mode";

log( "=========== COOKIE CONSENT DEBUGGING ===========" );

logInfo( "Blacklisted Domains" );
logInfo( window.YETT_BLACKLIST );
logInfo( "Whitelisted Domains" );
logInfo( window.YETT_WHITELIST );

/**
 * If the user has not already consented, show the banner.
 */
if ( hasUserSetPreferences() ) {
	logDebug( "✅ User has expressed consent." );
	logDebug( "The following categories were granted:" );
	logDebug( getJsonCookieValue( settings.consentedCategories ) );
	document.body.classList.add( "has-ilcc-consented" );

	if ( hasMatomo() && hasConsentedTo( "analytics" ) ) {
		_paq.push( [ "setCookieConsentGiven" ] );
	}

	updateConsentMode();
} else {
	logDebug( "❌ User has not expressed consent." );
	document.body.classList.add( "has-ilcc-banner" );
	document.body.classList.add( "ilcc-style-" + getBannerStyle() );
	showBanner();
}

if ( isDebugging() ) {
	document.body.classList.add( "ilcc-is-debugging" );
}

/**
 * Save the choice, apply it and close the banner.
 *
 * @param {Array} categories
 */
function saveChoice( categories ) {
	applyConsent( categories );
	removeBanner();
	document.body.classList.add( "has-ilcc-consented" );
}

if ( document.querySelector( ".js--ilcc-cookie-consent-notice" ) ) {
	document.querySelector( ".js--ilcc-cookie-consent-close" ).addEventListener( "click", function( e ) {
		e.preventDefault();
		saveChoice( [ "necessary", "analytics", "marketing" ] );
	} );

	if ( isConfigurable() ) {
		document.querySelector( ".js--ilcc-cookie-consent-necessary" ).addEventListener( "click", function( e ) {
			e.preventDefault();
			saveChoice( [ "necessary" ] );
		} );

		document.querySelector( ".js--ilcc-cookie-consent-settings-toggle" ).addEventListener( "click", function( e ) {
			e.preventDefault();
			toggleSettings();
		} );

		document.querySelector( ".js--ilcc-cookie-consent-settings-save-button" ).addEventListener( "click", function( e ) {
			e.preventDefault();
			saveChoice( getSelectedCategories() );
		} );

		document.querySelectorAll( ".js--ilcc-cookie-consent-toggle" ).forEach( ( toggle ) => {
			toggle.addEventListener( "click", function( e ) {
				e.preventDefault();
				toggleCategory( this );
			} );
		} );
	}
}
