/**
 * The notice of the settings page, after a save or an error.
 */
export interface Message {
	status: 'success' | 'error';
	text: string;
}
