/**
 * Internal dependencies
 */
import { getSelectedCategories, removeBanner, showBanner, toggleCategory, toggleSettings } from "./banner";
import { applyConsent, getConsentedCategories, hasConsentedTo, hasUserSetPreferences } from "./consent";
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
 * Show the banner and wire up its buttons.
 */
function openBanner() {
	document.body.classList.remove( "ilcc-banner-closed" );
	document.body.classList.add( "has-ilcc-banner" );
	document.body.classList.add( "ilcc-style-" + getBannerStyle() );
	showBanner();
	bindBannerEvents();
}

/**
 * Save the choice, apply it and close the banner.
 *
 * Scripts that already ran cannot be stopped, so if the visitor withdrew
 * consent for a category we reload the page to start without them.
 *
 * @param {Array} categories
 */
function saveChoice( categories ) {
	const previous  = getConsentedCategories();
	const withdrawn = previous.some( ( category ) => ! categories.includes( category ) );

	applyConsent( categories );
	removeBanner();
	document.body.classList.add( "has-ilcc-consented" );

	if ( withdrawn ) {
		logDebug( "Consent was withdrawn for a category. Reloading." );
		window.location.reload();
	}
}

function bindBannerEvents() {
	document.querySelector( ".js--ilcc-cookie-consent-close" ).addEventListener( "click", function( e ) {
		e.preventDefault();
		saveChoice( [ "necessary", "analytics", "marketing" ] );
	} );

	if ( ! isConfigurable() ) {
		return;
	}

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

/**
 * Open the cookie settings, so the visitor can review or change their choice.
 */
function openSettings() {
	const notice = document.querySelector( ".js--ilcc-cookie-consent-notice" );

	// The banner may be on its way out. Remove it so we start fresh.
	if ( notice && notice.classList.contains( "is-closed" ) ) {
		notice.remove();
	}

	if ( ! document.querySelector( ".js--ilcc-cookie-consent-notice" ) ) {
		openBanner();
	}

	const panel = document.querySelector( ".js--ilcc-cookie-consent-settings" );

	if ( panel && ! panel.classList.contains( "is-open" ) ) {
		toggleSettings();
	}
}

/**
 * Initial state.
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
	openBanner();
}

if ( isDebugging() ) {
	document.body.classList.add( "ilcc-is-debugging" );
}

/**
 * Links that open the settings: the [ilcc_settings_link] shortcode,
 * any element with data-ilcc-open-settings, or a link to #ilcc-settings
 * (handy for menu items).
 */
document.addEventListener( "click", function( e ) {
	const trigger = e.target.closest && e.target.closest( "[data-ilcc-open-settings], a[href$='#ilcc-settings']" );

	if ( ! trigger ) {
		return;
	}

	e.preventDefault();
	openSettings();
} );

if ( "#ilcc-settings" === window.location.hash ) {
	openSettings();
}

/**
 * Public API.
 */
window.ilccConsent = {
	openSettings,
	hasConsent: hasConsentedTo,
	getCategories: getConsentedCategories,
};
