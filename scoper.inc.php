<?php

/**
 * PHP-Scoper configuration, used by bin/build-release.
 *
 * Only the production dependencies are prefixed, the same packages can be loaded by other plugins or themes.
 * The WordPress symbols stay global.
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$excludesDir = (string) \getenv('WP_EXCLUDES_DIR');

$wordpressSymbols = static function (string $kind) use ($excludesDir): array {
    $file = $excludesDir . '/exclude-wordpress-' . $kind . '.json';

    return (array) \json_decode((string) \file_get_contents($file), true);
};

return [
    'prefix' => 'ItalyCookieChoices\\Vendor',
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.lock/')
            ->exclude(['doc', 'docs', 'test', 'tests', 'Tests', 'vendor-bin'])
            ->in('vendor'),
    ],
    'patchers' => [
        // PHP 8.4 does not allow #[\Deprecated] on a class, WordPress.org lints with a recent PHP and refuses it
        static function (string $filePath, string $prefix, string $contents): string {
            if (!\str_ends_with($filePath, 'italystrap/config/legacy/ConfigThemeMods.php')) {
                return $contents;
            }

            return (string) \preg_replace('/^#\[\\\\Deprecated\]\R(?=(final |abstract )?class )/m', '', $contents);
        },
    ],
    'exclude-classes' => \array_merge(
        $wordpressSymbols('classes'),
        $wordpressSymbols('interfaces'),
        $wordpressSymbols('traits')
    ),
    'exclude-functions' => $wordpressSymbols('functions'),
    'exclude-constants' => $wordpressSymbols('constants'),
];
