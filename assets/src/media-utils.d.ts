/**
 * The part of @wordpress/media-utils the settings page uses, the version 4 of WordPress 6.5 has no types.
 */
declare module '@wordpress/media-utils' {
	import type { ComponentType, ReactElement } from 'react';

	export interface MediaUploadProps {
		allowedTypes?: string[];
		value?: number | undefined;
		onSelect: ( media: { id: number; url: string; alt?: string } ) => void;
		render: ( props: { open: () => void } ) => ReactElement;
	}

	export const MediaUpload: ComponentType< MediaUploadProps >;
}
