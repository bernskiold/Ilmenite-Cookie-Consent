/**
 * Builds the plugin the way a release does and packages it as a zip, so the
 * tests run against what users actually install.
 */
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

const ROOT = path.resolve( __dirname, '../../..' );
const CACHE = path.join( ROOT, 'tests/e2e/.cache' );
const SLUG = 'ilmenite-cookie-consent';
const ZIP = path.join( CACHE, `${ SLUG }.zip` );
const EXTRACTED = path.join( CACHE, 'package' );

/**
 * Paths from .distignore, which the WordPress.org deploy also uses.
 */
function distIgnore() {
	return fs
		.readFileSync( path.join( ROOT, '.distignore' ), 'utf8' )
		.split( /\r?\n/ )
		.map( ( line ) => line.trim() )
		.filter( ( line ) => line && ! line.startsWith( '#' ) )
		.map( ( line ) => line.replace( /^\//, '' ) );
}

function packagePlugin() {
	if ( ! process.env.ILCC_SKIP_BUILD ) {
		execFileSync( 'npm', [ 'run', 'build' ], { cwd: ROOT, stdio: 'inherit' } );
	}

	for ( const file of [ 'assets/scripts/dist/cookie-banner.js', 'assets/scripts/dist/cookie-banner-blocker.js', 'assets/styles/dist/cookie-banner.css' ] ) {
		if ( ! fs.existsSync( path.join( ROOT, file ) ) ) {
			throw new Error( `Missing build output ${ file }. Run "npm run build".` );
		}
	}

	fs.rmSync( CACHE, { recursive: true, force: true } );
	const target = path.join( EXTRACTED, SLUG );
	fs.mkdirSync( target, { recursive: true } );

	const ignored = new Set( [ ...distIgnore(), 'tests', 'phpunit.xml.dist', 'playwright-report', 'test-results' ] );
	const ignoredExactly = ( rel ) => ignored.has( rel ) || ignored.has( rel.split( '/' )[ 0 ] );

	( function copy( relDir ) {
		for ( const entry of fs.readdirSync( path.join( ROOT, relDir ), { withFileTypes: true } ) ) {
			const rel = path.posix.join( relDir, entry.name );
			if ( ignoredExactly( rel ) || entry.name === '.git' ) {
				continue;
			}
			const from = path.join( ROOT, rel );
			const to = path.join( target, rel );
			if ( entry.isDirectory() ) {
				fs.mkdirSync( to, { recursive: true } );
				copy( rel );
			} else {
				fs.copyFileSync( from, to );
			}
		}
	} )( '' );

	execFileSync( 'zip', [ '-qr', ZIP, SLUG ], { cwd: EXTRACTED } );

	return { zip: ZIP, dir: target };
}

module.exports = { packagePlugin, ZIP, EXTRACTED, SLUG };
