<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Legacy;

use ItalyCookieChoices\Legacy\LegacyLoader;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LegacyLoaderTest extends WPTestCase
{
    private const FIXTURES = __DIR__ . '/../../_data/legacy-loader/';

    public function testItLoadsOnPluginsLoadedAfterTheDefaultPriority(): void
    {
        $sut = new LegacyLoader(self::FIXTURES);

        $this->assertSame(
            [
                'plugins_loaded' => [
                    SubscriberInterface::CALLBACK => 'load',
                    SubscriberInterface::PRIORITY => 11,
                ],
            ],
            \iterator_to_array($sut->getSubscribedEvents())
        );
    }

    public function testLoadRequiresTheFrontEndThenTheAdminFiles(): void
    {
        $GLOBALS['icc_legacy_loader_files'] = [];

        (new LegacyLoader(self::FIXTURES))->load();

        $this->assertSame(['init.php', 'init-admin.php'], $GLOBALS['icc_legacy_loader_files']);
        unset($GLOBALS['icc_legacy_loader_files']);
    }
}
