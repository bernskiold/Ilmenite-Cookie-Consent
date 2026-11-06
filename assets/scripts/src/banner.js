/**
 * Internal dependencies
 */
import { hasConsentedTo } from "./consent";
import { isAnalyticsShown, isConfigurable, isMarketingShown } from "./settings";

export function showBanner() {

	const consentBlock = document.createElement( "div" );
	consentBlock.id    = "cookie-consent-block";
	consentBlock.classList.add( "ilcc-cookie-consent-notice" );
	consentBlock.classList.add( "js--ilcc-cookie-consent-notice" );

	consentBlock.innerHTML = `
		<div class="ilcc-cookie-consent-notice-content">
			<p><span>${ilcc.cookieConsentTitle}</span>${ilcc.cookieConsentText}</p>
			<div class="ilcc-cookie-consent-actions">
				${isConfigurable() ? `<button class="ilcc-cookie-consent-necessary js--ilcc-cookie-consent-necessary ilcc-cookie-consent-button">${ilcc.necessaryText}</button>` : ""}
				<button class="ilcc-cookie-consent-close js--ilcc-cookie-consent-close close-cookie-block ilcc-cookie-consent-button">${ilcc.acceptText}</button>
				${isConfigurable() ? `<button class="ilcc-cookie-consent-settings-toggle js--ilcc-cookie-consent-settings-toggle">${ilcc.configureSettingsText}</button>` : ""}
			</div>
		</div>
		${renderSettings()}
	`;

	document.body.appendChild( consentBlock );

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

	if ( document.body.classList.contains( "ilcc-style-top" ) ) {
		document.body.style.paddingTop = 0;
		consentNotice.style.top        = ( -consentNotice.offsetHeight - 50 ) + "px";
	}

	if ( document.body.classList.contains( "ilcc-style-overlay" ) ) {
		consentNotice.style.bottom = ( -consentNotice.offsetHeight - 50 ) + "px";
	}

	consentNotice.classList.add( "is-closed" );
	document.body.classList.add( "ilcc-banner-closed" );

	setTimeout( () => {
		consentNotice.remove();

		// The banner may have been opened again in the meantime.
		if ( ! document.querySelector( ".js--ilcc-cookie-consent-notice" ) ) {
			maybeRemoveClasses( document.body, [
				"has-ilcc-banner"
			] );
		}
	}, 750 );
}

export function toggleSettings() {
	const settings = document.querySelector( ".js--ilcc-cookie-consent-settings" );

	settings.classList.toggle( "is-open" );
}

function renderSettings() {

	if ( ! isConfigurable() ) {
		return "";
	}

	let html = `<div class="ilcc-cookie-consent-settings js--ilcc-cookie-consent-settings">
		<p class="ilcc-cookie-consent-settings-title">${ilcc.settingsTitle}</p>
		<p class="ilcc-cookie-consent-settings-intro">${ilcc.settingsDescription}</p>
		<div class="ilcc-cookie-consent-categories">
<a href="#" class="ilcc-cookie-consent-category ilcc-toggle-disabled" data-category="necessary">
				<span class="ilcc-cookie-consent-category-info">
					<strong>${ilcc.necessaryHeading}</strong>
					<p>${ilcc.necessaryDescription}</p>
				</span>
				<span class="ilcc-cookie-consent-category-toggle">
				${renderToggle()}
				</span>
			</a>`;

	if ( isAnalyticsShown() ) {
		html += `
			<a href="#" class="ilcc-cookie-consent-category js--ilcc-cookie-consent-toggle ${renderActiveSelector( "analytics" )}" data-category="analytics">
				<span class="ilcc-cookie-consent-category-info">
					<strong>${ilcc.analyticsHeading}</strong>
					<p>${ilcc.analyticsDescription}</p>
				</span>
				<span class="ilcc-cookie-consent-category-toggle">
				${renderToggle()}
				</span>
			</a>
		`;
	}

	if ( isMarketingShown() ) {
		html += `
			<a href="#" class="ilcc-cookie-consent-category js--ilcc-cookie-consent-toggle ${renderActiveSelector( "marketing" )}" data-category="marketing">
				<span class="ilcc-cookie-consent-category-info">
					<strong>${ilcc.marketingHeading}</strong>
					<p>${ilcc.marketingDescription}</p>
				</span>
				<span class="ilcc-cookie-consent-category-toggle">
				${renderToggle()}
				</span>
			</a>
		`;
	}

	html += `</div>
		<div class="ilcc-cookie-consent-settings-save">
			<button class="ilcc-cookie-consent-button js--ilcc-cookie-consent-settings-save-button">${ilcc.saveSettingsText}</button>
		</div>
</div>`;

	return html;
}

function renderActiveSelector( category ) {
	return hasConsentedTo( category ) ? "ilcc-toggle-active" : "";
}

function renderToggle() {
	return `<span class="ilcc-cookie-consent-toggle"><span class="ilcc-cookie-consent-toggle-handle"></span></span>`;
}

/**
 * Toggle a category in the settings panel.
 *
 * This only changes the toggle. Nothing is stored until the visitor saves.
 *
 * @param {HTMLElement} element
 */
export function toggleCategory( element ) {
	element.classList.toggle( "ilcc-toggle-active" );
}

/**
 * Get the categories that are switched on in the settings panel.
 *
 * @return {Array} Category names.
 */
export function getSelectedCategories() {
	const selected = [ "necessary" ];

	document.querySelectorAll( ".js--ilcc-cookie-consent-toggle.ilcc-toggle-active" ).forEach( ( toggle ) => {
		selected.push( toggle.dataset.category );
	} );

	return selected;
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
