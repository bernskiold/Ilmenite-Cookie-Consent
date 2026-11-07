/**
 * The script blocker on real WordPress and Chromium.
 *
 * Every tracker response here behaves like a real one: it sets a
 * third-party cookie with Set-Cookie, and its script sets a first-party
 * cookie. The tests then check the browser's cookie jar and every request
 * it made, per consent category.
 */
const { test, expect } = require( '@playwright/test' );
const { startSite } = require( './lib/site' );
const { EXTRACTED, SLUG } = require( './lib/package' );
const { expectNoPhpErrors } = require( './lib/helpers' );
const path = require( 'path' );

test.describe.configure( { mode: 'serial' } );

let site;

test.beforeAll( async () => {
	test.setTimeout( 240000 );
	site = await startSite( {
		mounts: [ [ path.join( EXTRACTED, SLUG ), `/wordpress/wp-content/plugins/${ SLUG }` ] ],
		steps: [
			{ step: 'activatePlugin', pluginPath: `${ SLUG }/${ SLUG }.php` },
			{ step: 'setSiteOptions', options: { ilcc_settings_analytics_is_shown: '1', ilcc_settings_marketing_is_shown: '1' } },
		],
	} );
	console.log( `Testing on WordPress ${ site.wpVersion } (PHP ${ process.env.PHP_VERSION || '8.3' })` );
} );

test.afterAll( async () => {
	if ( site ) {
		await site.stop();
	}
} );

/**
 * Which category each tracker host on the fixture page belongs to.
 * The *.example hosts are only reachable through opt-in markup.
 */
const HOSTS = {
	analytics: [ 'www.google-analytics.com', 'www.googletagmanager.com', 'static.hotjar.com' ],
	marketing: [ 'connect.facebook.net', 'www.facebook.com', 'googleads.g.doubleclick.net', 'optin.example', 'video.example' ],
};

const URLS = {
	first: 'https://www.googletagmanager.com/first.js',
	second: 'https://www.googletagmanager.com/second.js',
	hotjar: 'https://static.hotjar.com/c/hotjar.js',
	doubleclick: 'https://googleads.g.doubleclick.net/pagead/ads.js',
	optin: 'https://optin.example/marketing.js',
};

const categoryOf = ( host ) => Object.keys( HOSTS ).find( ( category ) => HOSTS[ category ].includes( host ) );

/**
 * Open the fixture page. Tracker responses set cookies, like real ones.
 *
 * @return {Promise<string[]>} Every external URL the browser requested.
 */
async function visit( page, query = '' ) {
	const requested = [];
	await page.route( ( url ) => ! url.href.startsWith( site.url ), ( route ) => {
		const request = route.request();
		const url = new URL( request.url() );
		requested.push( request.url() );

		const cookieName = '_trk_' + url.hostname.replace( /\W/g, '_' );
		const headers = { 'set-cookie': `tp_${ cookieName }=1; Domain=${ url.hostname }; Path=/; SameSite=None; Secure` };

		if ( 'script' === request.resourceType() ) {
			return route.fulfill( {
				headers,
				contentType: 'application/javascript',
				body: `document.cookie = "${ cookieName }=1; path=/"; window.ilccTestRan = (window.ilccTestRan || []).concat(${ JSON.stringify( request.url() ) });`,
			} );
		}

		if ( 'image' === request.resourceType() ) {
			return route.fulfill( { headers, contentType: 'image/gif', body: Buffer.from( 'R0lGODlhAQABAAAAACw=', 'base64' ) } );
		}

		return route.fulfill( { headers, contentType: 'text/html', body: '<p>embed</p>' } );
	} );
	await page.goto( site.url + '/?ilcc-test-blocker=1' + query );
	await page.waitForLoadState( 'networkidle' );
	return requested;
}

/**
 * Tracker cookies in the jar, by category: third-party ones from
 * Set-Cookie and first-party ones written by tracker scripts.
 */
async function trackerCookies( context ) {
	const found = { analytics: [], marketing: [] };

	for ( const cookie of await context.cookies() ) {
		const match = cookie.name.match( /^(?:tp_)?_trk_(.+)$/ );
		if ( ! match ) {
			continue;
		}
		const host = Object.values( HOSTS ).flat().find( ( h ) => h.replace( /\W/g, '_' ) === match[ 1 ] );
		// Cookies from necessary (allowed) hosts are not tracker cookies.
		if ( host ) {
			found[ categoryOf( host ) ].push( cookie.name );
		}
	}

	return found;
}

const requestedCategories = ( requested ) => [ ...new Set( requested.map( ( url ) => categoryOf( new URL( url ).hostname ) ).filter( Boolean ) ) ].sort();
const ran = async ( page ) => ( await page.evaluate( () => window.ilccTestRan || [] ) ).filter( ( url ) => Object.values( URLS ).includes( url ) );

test( 'without consent: no tracker request, no tracker cookie, nothing runs', async ( { page, context } ) => {
	let requested = await visit( page );
	await expectNoPhpErrors( page );

	expect( requested.filter( ( url ) => categoryOf( new URL( url ).hostname ) ) ).toEqual( [] );
	expect( await trackerCookies( context ) ).toEqual( { analytics: [], marketing: [] } );
	expect( await ran( page ) ).toEqual( [] );
	expect( await page.evaluate( () => window.ilccTestInline || 0 ) ).toBe( 0 );

	// A second page view, still without a choice.
	requested = await visit( page, '&page=2' );
	expect( requested.filter( ( url ) => categoryOf( new URL( url ).hostname ) ) ).toEqual( [] );
	expect( await trackerCookies( context ) ).toEqual( { analytics: [], marketing: [] } );
} );

test( '"Only necessary": still no tracker request or cookie, on this and later pages', async ( { page, context } ) => {
	await visit( page );
	await page.click( '.js--ilcc-cookie-consent-necessary' );
	await page.waitForTimeout( 500 );
	const requested = await visit( page, '&page=2' );

	expect( requestedCategories( requested ) ).toEqual( [] );
	expect( await trackerCookies( context ) ).toEqual( { analytics: [], marketing: [] } );
} );

test( 'analytics only: analytics cookies are set, marketing ones never', async ( { page, context } ) => {
	let requested = await visit( page );
	await page.click( '.js--ilcc-cookie-consent-settings-toggle' );
	await page.click( '[data-category="analytics"]' );
	await page.click( '.js--ilcc-cookie-consent-settings-save-button' );

	await expect.poll( async () => ( await trackerCookies( context ) ).analytics.length ).toBeGreaterThan( 0 );
	await page.waitForTimeout( 500 );
	expect( requestedCategories( requested ) ).toEqual( [ 'analytics' ] );
	expect( ( await trackerCookies( context ) ).marketing ).toEqual( [] );
	expect( await page.evaluate( () => window.ilccTestInline || 0 ) ).toBe( 1 );

	// The next page view: analytics loads straight away, marketing stays out.
	requested = await visit( page, '&page=2' );
	expect( requestedCategories( requested ) ).toEqual( [ 'analytics' ] );
	expect( ( await trackerCookies( context ) ).marketing ).toEqual( [] );
} );

test( '"Allow all": every tracker loads once, scripts in their original order', async ( { page, context } ) => {
	const requested = await visit( page );
	await page.click( '.js--ilcc-cookie-consent-close' );

	await expect.poll( async () => ( await trackerCookies( context ) ).marketing.length ).toBeGreaterThan( 0 );
	await expect.poll( () => ran( page ) ).toEqual( expect.arrayContaining( Object.values( URLS ) ) );

	const order = await ran( page );
	expect( order.indexOf( URLS.first ) ).toBeLessThan( order.indexOf( URLS.second ) );
	expect( new Set( order ).size ).toBe( order.length );
	expect( requestedCategories( requested ) ).toEqual( [ 'analytics', 'marketing' ] );
	expect( await page.getAttribute( '#ilcc-test-iframe', 'src' ) ).toBe( 'https://video.example/embed' );
	expect( await page.getAttribute( '#ilcc-test-pixel', 'src' ) ).toContain( 'https://www.facebook.com/tr' );
	expect( await page.evaluate( () => window.ilccTestInline || 0 ) ).toBe( 1 );
} );

test( 'a returning visitor with marketing consent never loads analytics', async ( { browser } ) => {
	const context = await browser.newContext();
	await context.addCookies( [
		{ url: site.url, name: 'ilcc_has_preferences', value: '1' },
		{ url: site.url, name: 'ilcc_consent_categories', value: encodeURIComponent( '["necessary","marketing"]' ) },
	] );
	const page = await context.newPage();
	const requested = await visit( page );

	await expect.poll( () => ran( page ) ).toEqual( expect.arrayContaining( [ URLS.doubleclick, URLS.optin ] ) );
	expect( requestedCategories( requested ) ).toEqual( [ 'marketing' ] );
	expect( ( await trackerCookies( context ) ).analytics ).toEqual( [] );
	expect( await page.evaluate( () => window.ilccTestInline || 0 ) ).toBe( 0 );
	await context.close();
} );

test( 'after withdrawing marketing, later pages make no marketing request', async ( { page, context } ) => {
	await visit( page );
	await page.click( '.js--ilcc-cookie-consent-close' );
	await expect.poll( async () => ( await trackerCookies( context ) ).marketing.length ).toBeGreaterThan( 0 );

	await page.click( '.ilcc-test-footer [data-ilcc-open-settings]' );
	await page.click( '[data-category="marketing"]' );
	await Promise.all( [ page.waitForEvent( 'load' ), page.click( '.js--ilcc-cookie-consent-settings-save-button' ) ] );

	const requested = await visit( page, '&page=2' );
	expect( requestedCategories( requested ) ).toEqual( [ 'analytics' ] );
} );

test( 'window.yett.unblock() from custom code still unblocks everything', async ( { page } ) => {
	await visit( page );
	await page.evaluate( () => window.yett.unblock() );

	await expect.poll( () => ran( page ) ).toEqual( expect.arrayContaining( Object.values( URLS ) ) );
} );
