const { expect } = require( '@playwright/test' );

const TRACKERS = {
	analytics: 'www.google-analytics.com',
	marketing: 'connect.facebook.net',
	necessary: 'cdn.necessary.example',
};

/**
 * Answer tracker requests with a script that records that it ran, and keep
 * every other external request off the network.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} siteUrl
 */
async function stubExternalRequests( page, siteUrl ) {
	await page.route( ( url ) => ! url.href.startsWith( siteUrl ), ( route ) => {
		const host = new URL( route.request().url() ).hostname;
		return route.fulfill( {
			contentType: 'application/javascript',
			body: `window.ilccTestLoaded = (window.ilccTestLoaded || []).concat(${ JSON.stringify( host ) });`,
		} );
	} );
}

async function loadedTrackers( page ) {
	const hosts = await page.evaluate( () => window.ilccTestLoaded || [] );
	return Object.keys( TRACKERS ).filter( ( category ) => hosts.includes( TRACKERS[ category ] ) );
}

/**
 * Fail if PHP raised an error in the plugin, or any fatal error, since the
 * last check. The log is kept by fixtures/mu-plugins/ilcc-test-error-log.php.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectNoPhpErrors( page ) {
	const origin = new URL( page.url() ).origin;
	const log = await ( await page.request.get( origin + '/?ilcc-test-errors=1&clear=1' ) ).text();
	expect( log.trim(), 'PHP errors while loading ' + page.url() ).toBe( '' );
	await expect( page.locator( 'body' ) ).not.toContainText( 'There has been a critical error' );
}

async function login( page, siteUrl ) {
	await page.goto( siteUrl + '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'password' );
	await Promise.all( [ page.waitForURL( /wp-admin/ ), page.click( '#wp-submit' ) ] );
}

/**
 * The visitor's consent cookies, decoded.
 *
 * @param {import('@playwright/test').BrowserContext} context
 */
async function consentCookies( context ) {
	const cookies = {};
	for ( const cookie of await context.cookies() ) {
		cookies[ cookie.name ] = decodeURIComponent( cookie.value );
	}
	return { preferences: cookies.ilcc_has_preferences, categories: cookies.ilcc_consent_categories ? JSON.parse( cookies.ilcc_consent_categories ) : undefined };
}

module.exports = { TRACKERS, stubExternalRequests, loadedTrackers, expectNoPhpErrors, login, consentCookies };
