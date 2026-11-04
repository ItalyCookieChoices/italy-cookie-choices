<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations;

use ItalyCookieChoices\Integrations\Module;
use ItalyCookieChoices\Integrations\UI\AdvancedConsentMode;
use ItalyCookieChoices\Integrations\UI\ConsentModeScript;
use ItalyCookieChoices\Integrations\UI\TrackingTags;
use ItalyCookieChoices\Integrations\UI\WpConsentApiFilters;
use ItalyCookieChoices\Integrations\UI\WpConsentApiScript;
use ItalyCookieChoices\Integrations\UI\YouTubeNoCookie;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\Event\SubscribersConfigExtension;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ModuleTest extends WPTestCase
{
    use BuildsContainer;

    public function testTheSubscribersAreRegistered(): void
    {
        $config = (new Module('/plugin/', 'https://example.com/plugin/'))();

        $this->assertIsArray($config);
        $this->assertSame(
            [
                ConsentModeScript::class,
                TrackingTags::class,
                AdvancedConsentMode::class,
                YouTubeNoCookie::class,
                WpConsentApiFilters::class,
                WpConsentApiScript::class,
            ],
            $config[SubscribersConfigExtension::SUBSCRIBERS]
        );
    }

    public function testTheContainerBuildsTheConsentModeScript(): void
    {
        $this->assertSame(
            ['wp_head' => [SubscriberInterface::CALLBACK => 'print', SubscriberInterface::PRIORITY => 1]],
            \iterator_to_array($this->service(ConsentModeScript::class)->getSubscribedEvents())
        );
    }

    public function testTheContainerBuildsTheWpConsentApiScript(): void
    {
        $this->assertInstanceOf(WpConsentApiScript::class, $this->service(WpConsentApiScript::class));
    }
}
