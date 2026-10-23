/**
 * A button that opens the cookie preferences, rendered by ItalyCookieChoices\Consent\UI\PreferencesButtonBlock.
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import { Edit } from './edit';
import type { PreferencesButtonAttributes } from './types';

// The metadata registered on the server, read for the types and the settings that are not in the PHP registry.
registerBlockType(
	metadata as BlockConfiguration< PreferencesButtonAttributes >,
	{
		edit: Edit,
		save: () => null,
	}
);
