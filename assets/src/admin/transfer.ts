import { SCHEMA_VERSION, type Settings } from './settings';

/**
 * The file of the exported settings, the import reads it and the 2.x one, a list with the option.
 */
export interface ExportFile {
	plugin: 'italy-cookie-choices';
	version: number;
	settings: Settings;
}

export function exportFile( settings: Settings ): ExportFile {
	return {
		plugin: 'italy-cookie-choices',
		version: SCHEMA_VERSION,
		settings,
	};
}

export function exportFileName( date: Date ): string {
	return `italy-cookie-choices-settings-${ date
		.toISOString()
		.slice( 0, 10 ) }.json`;
}

/**
 * Downloads the settings as a JSON file.
 * @param settings The saved settings.
 * @param date     The date in the file name.
 */
export function downloadSettings( settings: Settings, date: Date ): void {
	const blob = new Blob(
		[ JSON.stringify( exportFile( settings ), null, 2 ) ],
		{ type: 'application/json' }
	);
	const url = URL.createObjectURL( blob );
	const link = document.createElement( 'a' );
	link.href = url;
	link.download = exportFileName( date );
	link.click();
	URL.revokeObjectURL( url );
}

/**
 * The JSON of a file, null when it is not JSON.
 * @param file The file chosen by the site owner.
 */
export async function readJsonFile(
	file: Pick< Blob, 'text' >
): Promise< unknown > {
	try {
		return JSON.parse( await file.text() ) as unknown;
	} catch {
		return null;
	}
}
