import { hasConsentedTo } from "./consent";

export function isConsentModeEnabled() {
	return ilcc.consentModeEnabled === '1';
}

function hasGtagOnPage() {
	return typeof gtag === 'function';
}

/**
 * Send the visitor's current choices to Google Consent Mode.
 *
 * Analytics storage follows the analytics category, and the ad signals
 * follow the marketing category. Denied categories are sent explicitly
 * so that a narrowed choice is also passed on.
 */
export function updateConsentMode() {
	if ( ! isConsentModeEnabled() || ! hasGtagOnPage() ) {
		return;
	}

	const analytics = hasConsentedTo( 'analytics' ) ? 'granted' : 'denied';
	const marketing = hasConsentedTo( 'marketing' ) ? 'granted' : 'denied';

	gtag( 'consent', 'update', {
		ad_storage: marketing,
		ad_user_data: marketing,
		ad_personalization: marketing,
		analytics_storage: analytics,
	} );
}

/**
 * @deprecated Use updateConsentMode().
 */
export const grantConsent = updateConsentMode;
