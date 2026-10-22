<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog;

use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Infrastructure\DatabaseConsentLog;
use ItalyCookieChoices\ConsentLog\UI\ConsentLogEndpoint;
use ItalyCookieChoices\ConsentLog\UI\ConsentLogExport;
use ItalyCookieChoices\ConsentLog\UI\LogScript;
use ItalyCookieChoices\ConsentLog\UI\LogTableInstaller;
use ItalyCookieChoices\ConsentLog\UI\PrivacyPolicyText;
use ItalyCookieChoices\ConsentLog\UI\RetentionCleanup;
use ItalyStrap\Empress\AurynConfig;
use ItalyStrap\Empress\ModuleInterface;
use ItalyStrap\Event\SubscribersConfigExtension;

/**
 * The consent log context: the proof of each consent, its retention and its export.
 */
final readonly class Module implements ModuleInterface
{
    private const BUILD = 'assets/build/';

    /**
     * @param string $pluginPath The plugin folder, with the trailing slash.
     * @param string $pluginUrl The plugin URL, with the trailing slash.
     */
    public function __construct(
        private string $pluginPath,
        private string $pluginUrl,
    ) {
    }

    public function __invoke(): iterable
    {
        return [
            AurynConfig::ALIASES => [
                ConsentLog::class => DatabaseConsentLog::class,
            ],
            AurynConfig::DELEGATIONS => [
                \wpdb::class => static function (): \wpdb {
                    global $wpdb;
                    /** @var \wpdb $wpdb */

                    return $wpdb;
                },
            ],
            AurynConfig::DEFINITIONS => [
                LogScript::class => [
                    ':buildPath' => $this->pluginPath . self::BUILD,
                    ':buildUrl' => $this->pluginUrl . self::BUILD,
                ],
            ],
            SubscribersConfigExtension::SUBSCRIBERS => [
                LogTableInstaller::class,
                LogScript::class,
                ConsentLogEndpoint::class,
                RetentionCleanup::class,
                ConsentLogExport::class,
                PrivacyPolicyText::class,
            ],
        ];
    }
}
