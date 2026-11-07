/**
 * Internal dependencies
 */
import { addConsentedCategory, hasConsentedTo, removeConsentedCategory } from "./consent";
import { getBannerStyle, isAnalyticsShown, isConfigurable, isMarketingShown } from "./settings";

const TITLE_ID       = "ilcc-cookie-consent-title";
const DESCRIPTION_ID = "ilcc-cookie-consent-description";
const SETTINGS_ID    = "ilcc-cookie-consent-settings";

/**
 * Set when the banner is closed by one of our own buttons,
 * so we can tell it apart from the browser closing the dialog.
 */
let isClosingIntentionally = false;

/**
 * The takeover style covers the page, so it is a modal dialog.
 * The other styles leave the page usable and are a labelled region.
 *
 * @return {boolean} True if the banner should be a modal dialog.
 */
function isModal() {
	return "takeover" === getBannerStyle() && typeof HTMLDialogElement === "function";
}

export function showBanner() {

	const consentBlock = document.createElement( isModal() ? "dialog" : "div" );
	consentBlock.id    = "cookie-consent-block";
	consentBlock.classList.add( "ilcc-cookie-consent-notice" );
	consentBlock.classList.add( "js--ilcc-cookie-consent-notice" );
	consentBlock.setAttribute( "aria-labelledby", TITLE_ID );
	consentBlock.setAttribute( "aria-describedby", DESCRIPTION_ID );
	consentBlock.setAttribute( "tabindex", "-1" );

	if ( ! isModal() ) {
		consentBlock.setAttribute( "role", "region" );
	}

	consentBlock.innerHTML = `
		<div class="ilcc-cookie-consent-notice-content">
			<div class="ilcc-cookie-consent-notice-text">
				<h2 id="${TITLE_ID}" class="ilcc-cookie-consent-title">${ilcc.cookieConsentTitle}</h2>
				<p id="${DESCRIPTION_ID}" class="ilcc-cookie-consent-description">${ilcc.cookieConsentText}</p>
			</div>
			<div class="ilcc-cookie-consent-actions">
				${isConfigurable() ? `<button type="button" class="ilcc-cookie-consent-necessary js--ilcc-cookie-consent-necessary ilcc-cookie-consent-button">${ilcc.necessaryText}</button>` : ""}
				<button type="button" class="ilcc-cookie-consent-close js--ilcc-cookie-consent-close close-cookie-block ilcc-cookie-consent-button">${ilcc.acceptText}</button>
				${isConfigurable() ? `<button type="button" class="ilcc-cookie-consent-settings-toggle js--ilcc-cookie-consent-settings-toggle" aria-expanded="false" aria-controls="${SETTINGS_ID}">${ilcc.configureSettingsText}</button>` : ""}
			</div>
		</div>
		${renderSettings()}
	`;

	// Insert first in the body so the banner comes first in reading and tab order.
	document.body.prepend( consentBlock );

	consentBlock.addEventListener( "keydown", handleKeydown );

	if ( isModal() ) {
		// Escape should not dismiss the dialog without the visitor making a choice.
		consentBlock.addEventListener( "cancel", ( e ) => e.preventDefault() );

		// Some browsers close a modal on repeated Escape presses regardless. Re-open it.
		consentBlock.addEventListener( "close", () => {
			if ( ! isClosingIntentionally && consentBlock.isConnected ) {
				consentBlock.showModal();
				consentBlock.focus();
			}
		} );

		consentBlock.showModal();
		consentBlock.focus();
	}

	// Add class to body if top style.
	if ( document.body.classList.contains( "ilcc-style-top" ) ) {
		const consentBlockHeight       = document.querySelector( ".js--ilcc-cookie-consent-notice" ).offsetHeight;
		document.body.style.paddingTop = consentBlockHeight + "px";
	}
}

export function removeBanner() {
	const consentNotice = document.querySelector( ".js--ilcc-cookie-consent-notice" );

	if ( ! consentNotice ) {
		return;
	}

	const hadFocus = consentNotice.contains( document.activeElement );

	// Keep the banner out of the tab order and accessibility tree while it animates away.
	consentNotice.setAttribute( "inert", "" );

	consentNotice.classList.add( "is-closed" );
	document.body.classList.add( "ilcc-banner-closed" );

	if ( isModal() && consentNotice.open ) {
		// Close the dialog now so the page is usable again. The "is-closed" class keeps it rendered while it fades out.
		isClosingIntentionally = true;
		consentNotice.close();
	}

	if ( hadFocus ) {
		moveFocusToDocumentStart();
	}

	if ( document.body.classList.contains( "ilcc-style-top" ) ) {
		document.body.style.paddingTop = 0;
		consentNotice.style.top        = ( -consentNotice.offsetHeight - 50 ) + "px";
	}

	if ( document.body.classList.contains( "ilcc-style-overlay" ) ) {
		consentNotice.style.bottom = ( -consentNotice.offsetHeight - 50 ) + "px";
	}

	setTimeout( () => {
		maybeRemoveClasses( document.body, [
			"has-ilcc-banner"
		] );
		consentNotice.remove();
	}, 750 );
}

/**
 * Move focus to the start of the document, where the banner was,
 * so keyboard and screen reader users are not dropped somewhere unexpected.
 */
function moveFocusToDocumentStart() {
	const body = document.body;

	if ( ! body.hasAttribute( "tabindex" ) ) {
		body.setAttribute( "tabindex", "-1" );
		body.style.outline = "none";

		body.addEventListener( "blur", () => {
			body.removeAttribute( "tabindex" );
			body.style.outline = "";
		}, { once: true } );
	}

	body.focus( { preventScroll: true } );
}

/**
 * Open or close the settings panel.
 *
 * @param {boolean|undefined} open Force a state. Toggles when omitted.
 */
export function toggleSettings( open ) {
	const settings = document.querySelector( ".js--ilcc-cookie-consent-settings" );
	const toggle   = document.querySelector( ".js--ilcc-cookie-consent-settings-toggle" );

	if ( ! settings ) {
		return;
	}

	const isOpen = settings.classList.toggle( "is-open", open );

	if ( toggle ) {
		toggle.setAttribute( "aria-expanded", isOpen ? "true" : "false" );
	}
}

function isSettingsOpen() {
	const settings = document.querySelector( ".js--ilcc-cookie-consent-settings" );

	return settings ? settings.classList.contains( "is-open" ) : false;
}

/**
 * Escape closes the settings panel and returns focus to its toggle.
 *
 * @param {KeyboardEvent} e
 */
function handleKeydown( e ) {
	if ( "Escape" !== e.key || ! isSettingsOpen() ) {
		return;
	}

	e.preventDefault();
	toggleSettings( false );

	const toggle = document.querySelector( ".js--ilcc-cookie-consent-settings-toggle" );

	if ( toggle ) {
		toggle.focus();
	}
}

function renderSettings() {

	if ( ! isConfigurable() ) {
		return "";
	}

	let html = `<div id="${SETTINGS_ID}" class="ilcc-cookie-consent-settings js--ilcc-cookie-consent-settings">
		<h3 class="ilcc-cookie-consent-settings-title">${ilcc.settingsTitle}</h3>
		<p class="ilcc-cookie-consent-settings-intro">${ilcc.settingsDescription}</p>
		<div class="ilcc-cookie-consent-categories">
			${renderCategory( "necessary", ilcc.necessaryHeading, ilcc.necessaryDescription, true )}`;

	if ( isAnalyticsShown() ) {
		html += renderCategory( "analytics", ilcc.analyticsHeading, ilcc.analyticsDescription );
	}

	if ( isMarketingShown() ) {
		html += renderCategory( "marketing", ilcc.marketingHeading, ilcc.marketingDescription );
	}

	html += `</div>
		<div class="ilcc-cookie-consent-settings-save">
			<button type="button" class="ilcc-cookie-consent-button js--ilcc-cookie-consent-settings-save-button">${ilcc.saveSettingsText}</button>
		</div>
</div>`;

	return html;
}

/**
 * Render a category as an accessible switch.
 *
 * @param {string}  category    Category key.
 * @param {string}  heading     Category heading, used as the switch label.
 * @param {string}  description Category description.
 * @param {boolean} alwaysOn    If the category cannot be turned off.
 *
 * @return {string} HTML.
 */
function renderCategory( category, heading, description, alwaysOn = false ) {
	const labelId       = `ilcc-cookie-consent-category-${category}-label`;
	const descriptionId = `ilcc-cookie-consent-category-${category}-description`;
	const isChecked     = alwaysOn || hasConsentedTo( category );

	const classes = [ "ilcc-cookie-consent-category" ];

	if ( alwaysOn ) {
		classes.push( "ilcc-toggle-disabled" );
	} else {
		classes.push( "js--ilcc-cookie-consent-toggle" );
	}

	if ( isChecked && ! alwaysOn ) {
		classes.push( "ilcc-toggle-active" );
	}

	return `
			<button type="button" role="switch" class="${classes.join( " " )}" data-category="${category}" aria-checked="${isChecked ? "true" : "false"}" aria-labelledby="${labelId}" aria-describedby="${descriptionId}"${alwaysOn ? ` aria-disabled="true"` : ""}>
				<span class="ilcc-cookie-consent-category-info">
					<strong id="${labelId}" class="ilcc-cookie-consent-category-heading">${heading}</strong>
					<span id="${descriptionId}" class="ilcc-cookie-consent-category-description">${description}</span>
				</span>
				<span class="ilcc-cookie-consent-category-toggle" aria-hidden="true">
					<span class="ilcc-cookie-consent-toggle"><span class="ilcc-cookie-consent-toggle-handle"></span></span>
				</span>
			</button>`;
}

export function toggleCategory( element ) {
	const category = element.dataset.category;

	if ( element.classList.contains( "ilcc-toggle-active" ) ) {
		removeConsentedCategory( category );
		element.classList.remove( "ilcc-toggle-active" );
		element.setAttribute( "aria-checked", "false" );
	} else {
		addConsentedCategory( category );
		element.classList.add( "ilcc-toggle-active" );
		element.setAttribute( "aria-checked", "true" );
	}
}

function maybeRemoveClass( element, className ) {
	if ( element.classList.contains( className ) ) {
		element.classList.remove( className );
	}
}

function maybeRemoveClasses( element, classes ) {
	classes.forEach( ( className ) => {
		maybeRemoveClass( element, className );
	} );
}
