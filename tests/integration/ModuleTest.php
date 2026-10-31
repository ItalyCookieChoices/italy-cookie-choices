<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyCookieChoices\Legacy\DeprecatedHooks;
use ItalyCookieChoices\Module;
use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Event\Module as EventModule;
use ItalyStrap\Config\Config;
use ItalyStrap\Event\SubscribersConfigExtension;
use ItalyStrap\View\View;
use ItalyStrap\View\ViewInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ModuleTest extends WPTestCase
{
    private const LANGUAGES_PATH = 'italy-cookie-choices/languages';
    private const TEMPLATES_PATH = WP_PLUGIN_DIR . '/italy-cookie-choices/templates/';

    public function testTheSubscribersAreRegisteredInOrder(): void
    {
        $config = (new Module(self::LANGUAGES_PATH, self::TEMPLATES_PATH))();

        $this->assertIsArray($config);
        $this->assertSame(
            [TextDomain::class, DeprecatedHooks::class],
            $config[SubscribersConfigExtension::SUBSCRIBERS]
        );
    }

    public function testTheContainerBuildsTheSubscribersWithTheirPaths(): void
    {
        $container = (new ContainerBuilder())
            ->addModule(new EventModule())
            ->addModule(new Module(self::LANGUAGES_PATH, self::TEMPLATES_PATH))
            ->build();

        $this->assertEquals(new TextDomain(self::LANGUAGES_PATH), $container->get(TextDomain::class));
    }

    public function testTheSubscribersAreHookedOnce(): void
    {
        $this->assertSame(1, $this->countListeners('init', TextDomain::class));
        $this->assertSame(1, $this->countListeners('init', DeprecatedHooks::class));
    }

    public function testTheContainerRendersThePluginTemplates(): void
    {
        $view = (new ContainerBuilder())
            ->addModule(new EventModule())
            ->addModule(new Module(self::LANGUAGES_PATH, self::TEMPLATES_PATH))
            ->build()
            ->get(ViewInterface::class);

        $this->assertInstanceOf(View::class, $view);
        $this->assertStringContainsString('id="icc-banner"', $view->render('banner', new Config([
            'classes' => ['banner' => '', 'content' => '', 'text' => '', 'link' => '', 'button' => ''],
            'modal' => false,
            'reload' => false,
            'htmlMargin' => false,
            'text' => '',
            'link' => null,
            'texts' => [],
            'categories' => [],
        ])));
    }

    /**
     * @param class-string $listenerClass
     */
    private function countListeners(string $hook, string $listenerClass): int
    {
        /** @var array<string, \WP_Hook> $wp_filter */
        global $wp_filter;

        $count = 0;
        foreach ($wp_filter[$hook]->callbacks as $priorityCallbacks) {
            foreach ($priorityCallbacks as $callback) {
                if (\is_array($callback['function']) && $callback['function'][0] instanceof $listenerClass) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
