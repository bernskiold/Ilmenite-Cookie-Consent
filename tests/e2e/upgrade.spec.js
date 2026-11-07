/**
 * Updating from the last release on WordPress.org to this build.
 *
 * Installs the published plugin, configures it and records a visitor's
 * consent with it, then lets WordPress's automatic updater (the code behind
 * background auto-updates) update it to the packaged build.
 * Afterwards settings, stored consent and the admin screens must all work.
 */
const { test, expect } = require( '@playwright/test' );
const path = require( 'path' );
const { startSite } = require( './lib/site' );
const { ZIP, SLUG } = require( './lib/package' );
const { stubExternalRequests, loadedTrackers, expectNoPhpErrors, login } = require( './lib/helpers' );

test.describe.configure( { mode: 'serial' } );

let site;
let visitorState;

test.beforeAll( async () => {
	test.setTimeout( 240000 );
	site = await startSite( {
		mounts: [ [ path.dirname( ZIP ), '/wordpress/wp-content/ilcc-test-package' ] ],
		steps: [
			{
				step: 'installPlugin',
				pluginData: { resource: 'wordpress.org/plugins', slug: SLUG },
				options: { activate: true },
			},
			{
				// Values as the previous release saved them: the domain lists
				// went to the database unsanitized, with Windows line endings
				// and stray spaces. (A blank line would break 3.3.0 itself, so
				// that case is covered by the PHP unit tests instead.)
				step: 'setSiteOptions',
				options: {
					ilcc_title: 'Cookies on this site',
					ilcc_text: 'We use cookies. Read our %linkstart%cookie policy%linkend%.',
					ilcc_policy_url: 'https://example.test/cookies/',
					ilcc_style: 'top',
					ilcc_button: 'Accept everything',
					ilcc_settings_analytics_is_shown: '1',
					ilcc_settings_marketing_is_shown: '1',
					ilcc_domains_necessary: 'cdn.necessary.example\r\n',
					ilcc_domains_analytics: 'google-analytics.com\r\n googletagmanager.com \r\n',
					ilcc_domains_marketing: 'facebook.com\r\nconnect.facebook.net\r\n',
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

async function visit( page ) {
	await stubExternalRequests( page, site.url );
	await page.goto( site.url + '/' );
	await page.waitForLoadState( 'networkidle' );
}

test( 'the released version is running', async ( { page } ) => {
	await visit( page );
	await expect( page.locator( '.js--ilcc-cookie-consent-notice' ) ).toBeVisible();
	expect( await page.evaluate( () => typeof window.ilccBlocker ) ).toBe( 'undefined' );
} );

test( 'a visitor gives analytics-only consent with the released version', async ( { page, context } ) => {
	await visit( page );
	await page.click( '.js--ilcc-cookie-consent-settings-toggle' );
	await page.click( '[data-category="analytics"]' );
	await page.click( '.js--ilcc-cookie-consent-settings-save-button' );
	visitorState = await context.storageState();

	expect( visitorState.cookies.map( ( c ) => c.name ).sort() ).toEqual( [ 'ilcc_consent_categories', 'ilcc_has_preferences' ] );
} );

test( 'WordPress auto-updates the plugin to this build', async ( { page, request } ) => {
	test.setTimeout( 120000 );

	// Only count errors from the new code.
	await request.get( site.url + '/?ilcc-test-errors=1&clear=1' );

	const response = await request.get( site.url + '/?ilcc-test-auto-update=1', { timeout: 110000 } );
	const result = await response.json();
	expect( result ).toEqual( { updated: true, error: null, active: true } );

	// Playground quirk, not a plugin issue: right after an update, another of
	// its PHP workers can still see the .maintenance file WordPress created
	// and removed during the update, and fail that one request. Make requests
	// until all workers agree; anything else still fails the test.
	let healthy = 0;
	for ( let attempt = 0; attempt < 20 && healthy < 5; attempt++ ) {
		const body = await ( await request.get( site.url + '/?ilcc-settle=' + attempt ) ).text();
		if ( body.includes( "Failed opening required '/wordpress/.maintenance'" ) ) {
			healthy = 0;
		} else {
			healthy++;
		}
	}
	expect( healthy ).toBe( 5 );

	await login( page, site.url );
	await page.goto( site.url + '/wp-admin/plugins.php' );
	await expectNoPhpErrors( page );
	await expect( page.locator( `#the-list tr[data-slug="${ SLUG }"]:not(.plugin-update-tr)` ) ).toHaveClass( /\bactive\b/ );
} );

test( 'the new code is what runs now', async ( { page } ) => {
	await visit( page );
	await expectNoPhpErrors( page );
	expect( await page.evaluate( () => typeof window.ilccBlocker ) ).toBe( 'object' );
} );

test( 'consent given before the update is still honoured', async ( { browser } ) => {
	const context = await browser.newContext( { storageState: visitorState } );
	const page = await context.newPage();
	await visit( page );

	await expect( page.locator( '.js--ilcc-cookie-consent-notice' ) ).toHaveCount( 0 );
	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'analytics', 'necessary' ] );
	await context.close();
} );

test( 'settings saved before the update are still used', async ( { page } ) => {
	await visit( page );

	const notice = page.locator( '.js--ilcc-cookie-consent-notice' );
	await expect( notice ).toContainText( 'Cookies on this site' );
	await expect( notice.locator( 'a[href="https://example.test/cookies/"]' ) ).toHaveText( 'cookie policy' );
	await expect( notice.locator( '.js--ilcc-cookie-consent-close' ) ).toHaveText( 'Accept everything' );
	await expect( page.locator( 'body' ) ).toHaveClass( /ilcc-style-top/ );
	await expect.poll( () => loadedTrackers( page ) ).toEqual( [ 'necessary' ] );
} );

test( 'admin screens work after the update', async ( { page } ) => {
	await login( page, site.url );

	await page.goto( site.url + '/wp-admin/options-general.php?page=ilcc-trackers' );
	await expectNoPhpErrors( page );
	await expect( page.locator( '#ilcc_domains_analytics' ) ).toHaveValue( /google-analytics\.com/ );

	await page.goto( site.url + '/wp-admin/customize.php' );
	await expectNoPhpErrors( page );
	await expect( page.locator( '#accordion-panel-ilmenite_cookie_banner' ) ).toHaveCount( 1 );
} );
