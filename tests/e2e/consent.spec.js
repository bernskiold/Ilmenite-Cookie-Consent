/**
 * The packaged plugin on a fresh WordPress install: banner, blocking,
 * consent choices, Consent Mode and the admin screens.
 */
const { test, expect } = require( '@playwright/test' );
const { startSite } = require( './lib/site' );
const { EXTRACTED, SLUG } = require( './lib/package' );
const { stubExternalRequests, loadedTrackers, expectNoPhpErrors, login, consentCookies } = require( './lib/helpers' );
const path = require( 'path' );

test.describe.configure( { mode: 'serial' } );

let site;

test.beforeAll( async () => {
	test.setTimeout( 240000 );
	site = await startSite( {
		mounts: [ [ path.join( EXTRACTED, SLUG ), `/wordpress/wp-content/plugins/${ SLUG }` ] ],
		steps: [
			{ step: 'activatePlugin', pluginPath: `${ SLUG }/${ SLUG }.php` },
			{
				step: 'setSiteOptions',
				options: {
					ilcc_settings_analytics_is_shown: '1',
					ilcc_settings_marketing_is_shown: '1',
					ilcc_settings_google_consent_mode_enabled: '1',
					ilcc_domains_necessary: 'cdn.necessary.example',
				},
			},
		],
	} );
	console.log( `Testing on WordPress ${ site.wpVersion } (PHP ${ process.env.PHP_VERSION || '8.3' })` );
} );

test.afterAll( async () => {
	if ( site ) {
		await site.stop();
	}
} );

async function visit( page, hash = '' ) {
	await stubExternalRequests( page, site.url );
	await page.goto( site.url + '/' + hash );
	await page.waitForLoadState( 'networkidle' );
}

const consentCalls = ( page ) =>
	page.evaluate( () => ( window.dataLayer || [] ).filter( ( a ) => a[ 0 ] === 'consent' ).map( ( a ) => ( { type: a[ 1 ], ...a[ 2 ] } ) ) );

test( 'first visit shows the banner and blocks trackers', async ( { page } ) => {
	await visit( page );
	await expectNoPhpErrors( page );

	await expect( page.locator( '.js--ilcc-cookie-consent-notice' ) ).toBeVisible();
	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'necessary' ] );

	const [ first ] = await consentCalls( page );
	expect( first ).toMatchObject( { type: 'default', analytics_storage: 'denied', ad_storage: 'denied' } );
} );

test( 'page HTML does not depend on the consent cookie (cache-safe)', async ( { playwright } ) => {
	const fetchHome = async ( cookie ) => {
		const request = await playwright.request.newContext( { extraHTTPHeaders: cookie ? { cookie } : {} } );
		const html = await ( await request.get( site.url + '/' ) ).text();
		await request.dispose();
		return html.replace( / nonce="[^"]*"/g, '' );
	};

	const anonymous = await fetchHome();
	const consented = await fetchHome( 'ilcc_has_preferences=1; ilcc_consent_categories=%5B%22necessary%22%2C%22analytics%22%2C%22marketing%22%5D' );

	expect( consented ).toBe( anonymous );
} );

test( 'toggling a category without saving stores nothing', async ( { page, context } ) => {
	await visit( page );
	await page.click( '.js--ilcc-cookie-consent-settings-toggle' );
	await page.click( '[data-category="analytics"]' );

	expect( await consentCookies( context ) ).toEqual( { preferences: undefined, categories: undefined } );

	await page.reload();
	await page.waitForLoadState( 'networkidle' );
	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'necessary' ] );
} );

test( 'saving analytics only applies straight away and on return visits', async ( { page, context } ) => {
	await visit( page );
	await page.click( '.js--ilcc-cookie-consent-settings-toggle' );
	await page.click( '[data-category="analytics"]' );
	await page.click( '.js--ilcc-cookie-consent-settings-save-button' );
	await page.waitForLoadState( 'networkidle' );

	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'analytics', 'necessary' ] );
	expect( await consentCookies( context ) ).toEqual( { preferences: '1', categories: [ 'necessary', 'analytics' ] } );
	expect( ( await consentCalls( page ) ).pop() ).toMatchObject( { type: 'update', analytics_storage: 'granted', ad_storage: 'denied' } );

	await page.reload();
	await page.waitForLoadState( 'networkidle' );
	await expect( page.locator( '.js--ilcc-cookie-consent-notice' ) ).toHaveCount( 0 );
	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'analytics', 'necessary' ] );

	const calls = await consentCalls( page );
	expect( calls[ 0 ].type ).toBe( 'default' );
	expect( calls[ 1 ] ).toMatchObject( { type: 'update', analytics_storage: 'granted', ad_user_data: 'denied' } );
} );

test( 'visitors can reopen the settings and withdraw consent', async ( { page, context } ) => {
	await visit( page );
	await page.click( '.js--ilcc-cookie-consent-close' );
	await page.waitForLoadState( 'networkidle' );
	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'analytics', 'marketing', 'necessary' ] );

	await page.click( '.ilcc-test-footer [data-ilcc-open-settings]' );
	await expect( page.locator( '.js--ilcc-cookie-consent-settings.is-open' ) ).toBeVisible();
	await expect( page.locator( '[data-category="marketing"]' ) ).toHaveClass( /ilcc-toggle-active/ );

	await page.click( '[data-category="marketing"]' );
	await Promise.all( [ page.waitForEvent( 'load' ), page.click( '.js--ilcc-cookie-consent-settings-save-button' ) ] );
	await page.waitForLoadState( 'networkidle' );

	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'analytics', 'necessary' ] );
	expect( ( await consentCookies( context ) ).categories ).toEqual( [ 'necessary', 'analytics' ] );
} );

test( 'a link to #ilcc-settings from another page opens the settings', async ( { page } ) => {
	await visit( page );
	await page.click( '.js--ilcc-cookie-consent-necessary' );
	await page.goto( site.url + '/sample-page/' );
	await page.goto( site.url + '/#ilcc-settings' );
	await expect( page.locator( '.js--ilcc-cookie-consent-settings.is-open' ) ).toBeVisible();
} );

test( '"Only necessary" keeps everything blocked', async ( { page, context } ) => {
	await visit( page );
	await page.click( '.js--ilcc-cookie-consent-necessary' );
	await page.reload();
	await page.waitForLoadState( 'networkidle' );

	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'necessary' ] );
	expect( await consentCookies( context ) ).toEqual( { preferences: '1', categories: [ 'necessary' ] } );
} );

test.describe( 'admin', () => {
	test.beforeEach( async ( { page } ) => {
		await login( page, site.url );
	} );

	test( 'tracker settings are cleaned when saved', async ( { page } ) => {
		await page.goto( site.url + '/wp-admin/options-general.php?page=ilcc-trackers' );
		await expectNoPhpErrors( page );

		await page.fill( '#ilcc_domains_marketing', '  https://ads.example  \n\nads.example\n//pixel.example/p.js' );
		await page.click( '#submit' );
		await expectNoPhpErrors( page );

		await expect( page.locator( '#ilcc_domains_marketing' ) ).toHaveValue( 'ads.example\npixel.example/p.js' );
	} );

	test( 'the Customizer loads with the Cookie Banner panel', async ( { page } ) => {
		await page.goto( site.url + '/wp-admin/customize.php' );
		await expectNoPhpErrors( page );
		await page.click( '#accordion-panel-ilmenite_cookie_banner' );
		await expect( page.locator( '#accordion-section-ilmenite_cookie_banner_integrations' ) ).toBeVisible();
	} );
} );
