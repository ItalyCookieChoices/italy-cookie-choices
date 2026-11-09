/**
 * The cookie table of the cookie policy, rendered by ItalyCookieChoices\Cookies\UI\CookieTableBlock.
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import { Edit } from './edit';

// The metadata registered on the server, read for the types and the settings that are not in the PHP registry.
registerBlockType( metadata as BlockConfiguration< Record< string, never > >, {
	edit: Edit,
	save: () => null,
} );
