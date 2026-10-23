import type { AdminData } from '../../settings';

export const data: AdminData = {
	categories: [
		{
			id: 'necessary',
			required: true,
			label: 'Necessary',
			description: 'Always active.',
		},
		{
			id: 'statistics',
			required: false,
			label: 'Statistics',
			description: 'Visits.',
		},
		{
			id: 'marketing',
			required: false,
			label: 'Marketing',
			description: 'Ads.',
		},
	],
	vendors: [
		{ id: 'youtube', name: 'YouTube', category: 'marketing' },
		{ id: 'hotjar', name: 'Hotjar', category: 'statistics' },
	],
	consentLogExportUrl:
		'https://example.com/wp-admin/admin-post.php?action=export',
	documentationUrl: 'https://example.com/docs',
};
