<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking;

use ItalyCookieChoices\Blocking\Module;
use ItalyCookieChoices\Blocking\UI\BlockingFilters;
use ItalyCookieChoices\Blocking\UI\PageBuffer;
use ItalyCookieChoices\Blocking\UI\Placeholders;
use ItalyCookieChoices\Consent\Module as ConsentModule;
use ItalyCookieChoices\Module as PluginModule;
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
            [BlockingFilters::class, PageBuffer::class, Placeholders::class],
            $config[SubscribersConfigExtension::SUBSCRIBERS]
        );
    }

    public function testTheContainerBuildsTheSubscribers(): void
    {
        $templates = \codecept_root_dir('templates/');
        $this->assertIsString($templates);
        $container = (new ContainerBuilder())
            ->addModule(new EventModule())
            ->addModule(new PluginModule('/plugin/', 'italy-cookie-choices/lang', $templates))
            ->addModule(new ConsentModule('/plugin/', 'https://example.com/plugin/', []))
            ->addModule(new Module('/plugin/', 'https://example.com/plugin/'))
            ->build();

        $this->assertInstanceOf(BlockingFilters::class, $container->get(BlockingFilters::class));
        $this->assertInstanceOf(PageBuffer::class, $container->get(PageBuffer::class));
        $this->assertInstanceOf(Placeholders::class, $container->get(Placeholders::class));
    }
}
