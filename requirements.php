<?php

/**
 * Checks the platform before the plugin code is loaded.
 *
 * Keep this file parsable by PHP 7.4, it runs before the requirements are known to be met.
 * Returns true when the plugin can run, otherwise shows a notice in the admin.
 */

declare(strict_types=1);

use ItalyStrap\PlatformRequirementsCheck\RangeVersionRequirement;
use ItalyStrap\PlatformRequirementsCheck\Requirements;

return (static function (): bool {
	$php_minimum = '8.2';
	$wordpress_minimum = '6.1';
	$wordpress_version = (string) get_bloginfo( 'version' );

	$requirements = new Requirements(
		new RangeVersionRequirement( 'PHP', PHP_VERSION, $php_minimum ),
		new RangeVersionRequirement( 'WordPress', $wordpress_version, $wordpress_minimum )
	);

	if ( $requirements->check() ) {
		return true;
	}

	add_action(
		'admin_notices',
		static function () use ( $php_minimum, $wordpress_minimum, $wordpress_version ): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						'Italy Cookie Choices is not running: it needs PHP %1$s and WordPress %2$s or newer, this site has PHP %3$s and WordPress %4$s.',
						$php_minimum,
						$wordpress_minimum,
						PHP_VERSION,
						$wordpress_version
					)
				)
			);
		}
	);

	return false;
})();
