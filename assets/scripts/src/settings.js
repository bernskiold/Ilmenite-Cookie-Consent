export const settings = {
	consentRememberDuration: ilcc.rememberDuration,
	setPreferencesCookieName: ilcc.preferencesCookieName,
	consentedCategories: ilcc.consentedCategoriesCookieName
};

/**
 * The consent version. Stored in the preferences cookie, so that raising
 * it asks every visitor for consent again.
 *
 * @return {string} Version.
 */
export function getConsentVersion() {
	return String( ilcc.consentVersion || "1" );
}

export function getBannerStyle() {
	return ilcc.style;
}

export function isConfigurable() {
	return isMarketingShown() || isAnalyticsShown();
}

export function isAnalyticsShown() {
	return 1 == ilcc.isAnalyticsShown;
}

export function isMarketingShown() {
	return 1 == ilcc.isMarketingShown;
}

export function isDebugging() {
	return 1 === ilcc.debug;
}

export function hasMatomo() {
	return typeof _paq !== "undefined";
}
