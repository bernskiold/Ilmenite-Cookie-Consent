const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: '.',
	testMatch: '*.spec.js',
	// Each spec file boots its own WordPress; keep them sequential so a
	// small CI runner is not overloaded.
	workers: 1,
	fullyParallel: false,
	timeout: 60000,
	globalSetup: require.resolve( './global-setup.js' ),
	reporter: process.env.CI ? [ [ 'list' ], [ 'github' ] ] : 'list',
	use: {
		trace: 'off',
	},
} );
