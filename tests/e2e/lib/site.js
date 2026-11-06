/**
 * Boots a throwaway WordPress site with WordPress Playground (PHP compiled
 * to WebAssembly), so the tests need neither Docker nor MySQL.
 */
const { spawn } = require( 'child_process' );
const fs = require( 'fs' );
const net = require( 'net' );
const os = require( 'os' );
const path = require( 'path' );

const FIXTURES = path.resolve( __dirname, '../fixtures' );

function freePort() {
	return new Promise( ( resolve, reject ) => {
		const server = net.createServer();
		server.unref();
		server.on( 'error', reject );
		server.listen( 0, '127.0.0.1', () => {
			const { port } = server.address();
			server.close( () => resolve( port ) );
		} );
	} );
}

/**
 * Start a site.
 *
 * @param {Object} options
 * @param {Array}  options.steps   Blueprint steps.
 * @param {Array}  [options.mounts] [hostPath, vfsPath] pairs mounted before WordPress installs.
 * @return {Promise<{url: string, stop: Function, wpVersion: string, log: Function}>}
 */
async function startSite( { steps = [], mounts = [] } = {} ) {
	const port = await freePort();
	const dir = fs.mkdtempSync( path.join( os.tmpdir(), 'ilcc-site-' ) );
	const blueprintPath = path.join( dir, 'blueprint.json' );
	const wp = process.env.WP_VERSION || 'latest';
	const php = process.env.PHP_VERSION || '8.3';
	// The blueprint's versions win over the CLI flags, so set both.
	fs.writeFileSync( blueprintPath, JSON.stringify( { preferredVersions: { wp, php }, steps }, null, 2 ) );

	const cli = path.join( path.dirname( require.resolve( '@wp-playground/cli/package.json' ) ), 'wp-playground.js' );
	const args = [
		cli,
		'server',
		`--port=${ port }`,
		`--wp=${ wp }`,
		`--php=${ php }`,
		`--blueprint=${ blueprintPath }`,
		'--define-bool', 'WP_DEBUG', 'true',
		'--define-bool', 'WP_DEBUG_DISPLAY', 'false',
		`--mount-before-install=${ path.join( FIXTURES, 'mu-plugins' ) }:/wordpress/wp-content/mu-plugins`,
		...mounts.map( ( [ host, vfs ] ) => `--mount-before-install=${ host }:${ vfs }` ),
	];

	let output = '';
	const child = spawn( process.execPath, args, { env: process.env, stdio: [ 'ignore', 'pipe', 'pipe' ] } );
	child.stdout.on( 'data', ( d ) => ( output += d ) );
	child.stderr.on( 'data', ( d ) => ( output += d ) );

	await new Promise( ( resolve, reject ) => {
		const timer = setTimeout( () => reject( new Error( `WordPress did not start in time:\n${ output }` ) ), 180000 );
		const check = setInterval( () => {
			if ( /Ready!/.test( output ) ) {
				clearTimeout( timer );
				clearInterval( check );
				resolve();
			}
		}, 250 );
		child.on( 'exit', ( code ) => {
			clearTimeout( timer );
			clearInterval( check );
			reject( new Error( `WordPress exited with code ${ code }:\n${ output }` ) );
		} );
	} );

	const url = `http://127.0.0.1:${ port }`;
	const home = await fetch( url + '/' ).then( ( r ) => r.text() );
	const wpVersion = ( home.match( /name="generator" content="WordPress ([^"]+)"/ ) || [] )[ 1 ] || 'unknown';

	return {
		url,
		wpVersion,
		log: () => output,
		stop: () =>
			new Promise( ( resolve ) => {
				child.removeAllListeners( 'exit' );
				child.on( 'exit', () => resolve() );
				child.kill( 'SIGTERM' );
				setTimeout( () => {
					child.kill( 'SIGKILL' );
					resolve();
				}, 5000 );
			} ),
	};
}

module.exports = { startSite };
