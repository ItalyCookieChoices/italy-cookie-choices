<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog;

use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Infrastructure\DatabaseConsentLog;
use ItalyCookieChoices\ConsentLog\Module;
use ItalyCookieChoices\ConsentLog\UI\ConsentLogEndpoint;
use ItalyCookieChoices\ConsentLog\UI\ConsentLogExport;
use ItalyCookieChoices\ConsentLog\UI\LogScript;
use ItalyCookieChoices\ConsentLog\UI\LogTableInstaller;
use ItalyCookieChoices\ConsentLog\UI\PrivacyPolicyText;
use ItalyCookieChoices\ConsentLog\UI\RetentionCleanup;
use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Event\Module as EventModule;
use ItalyStrap\Event\SubscribersConfigExtension;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ModuleTest extends WPTestCase
{
    public function testTheSubscribersAreRegistered(): void
    {
        $config = (new Module('/plugin/', 'https://example.com/plugin/'))();

        $this->assertIsArray($config);
        $this->assertSame(
            [
                LogTableInstaller::class,
                LogScript::class,
                ConsentLogEndpoint::class,
                RetentionCleanup::class,
                ConsentLogExport::class,
                PrivacyPolicyText::class,
            ],
            $config[SubscribersConfigExtension::SUBSCRIBERS]
        );
    }

    public function testTheLogIsTheDatabaseTable(): void
    {
        $container = (new ContainerBuilder())
            ->addModule(new EventModule())
            ->addModule(new Module('/plugin/', 'https://example.com/plugin/'))
            ->build();

        $this->assertInstanceOf(DatabaseConsentLog::class, $container->get(ConsentLog::class));
    }
}
