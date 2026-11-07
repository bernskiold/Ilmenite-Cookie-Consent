/**
 * Build configuration, based on @wordpress/scripts.
 *
 * Built files keep the paths the plugin enqueues:
 * - assets/scripts/dist/cookie-banner.js         (banner)
 * - assets/scripts/dist/cookie-banner-blocker.js (script blocker, loaded in the head)
 * - assets/styles/dist/cookie-banner.css         (styles)
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'scripts/dist/cookie-banner': './assets/scripts/src/index.js',
		'scripts/dist/cookie-banner-blocker': './assets/scripts/src/blocker.js',
		'styles/dist/cookie-banner': './assets/styles/src/cookie-banner.scss',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'assets' ),
		// The output folder also holds the sources, so never clean it.
		clean: false,
	},
	plugins: [
		// Not needed here: the default CopyPlugin copies block.json files, which
		// this plugin has none of, and the plugin does not enqueue an RTL stylesheet.
		...defaultConfig.plugins.filter(
			( plugin ) => ! [ 'CopyPlugin', 'RtlCssPlugin' ].includes( plugin.constructor.name )
		),
	],
};
