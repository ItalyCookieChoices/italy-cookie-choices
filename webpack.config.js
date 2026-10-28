/**
 * The default @wordpress/scripts config finds the blocks in assets/src/blocks from their block.json,
 * the other scripts are added as named entries.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const entries = {
	consent: './assets/src/consent/index.ts',
	banner: './assets/src/banner/index.ts',
	blocking: './assets/src/blocking/index.ts',
	'consent-log': './assets/src/consent-log/index.ts',
	admin: './assets/src/admin/index.tsx',
	'consent-mode': './assets/src/consent-mode/index.ts',
};

module.exports = {
	...defaultConfig,
	entry: () => ( {
		...( typeof defaultConfig.entry === 'function'
			? defaultConfig.entry()
			: defaultConfig.entry ),
		...entries,
	} ),
};
