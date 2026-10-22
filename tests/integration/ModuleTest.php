<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyCookieChoices\Legacy\LegacyLoader;
use ItalyCookieChoices\Module;
use ItalyCookieChoices\Settings\Migration;
use ItalyCookieChoices\Settings\Registration;
use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Event\Module as EventModule;
use ItalyStrap\Event\SubscribersConfigExtension;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ModuleTest extends WPTestCase
{
    private const LEGACY_PATH = '/legacy/';
    private const LANGUAGES_PATH = 'italy-cookie-choices/legacy/lang';

    public function testTheSubscribersAreRegisteredInOrder(): void
    {
        $config = (new Module(self::LEGACY_PATH, self::LANGUAGES_PATH))();

        $this->assertIsArray($config);
        $this->assertSame(
            [TextDomain::class, Migration::class, Registration::class, LegacyLoader::class],
            $config[SubscribersConfigExtension::SUBSCRIBERS]
        );
    }

    public function testTheContainerBuildsTheSubscribersWithTheirPaths(): void
    {
        $container = (new ContainerBuilder())
            ->addModule(new EventModule())
            ->addModule(new Module(self::LEGACY_PATH, self::LANGUAGES_PATH))
            ->build();

        $this->assertEquals(new TextDomain(self::LANGUAGES_PATH), $container->get(TextDomain::class));
        $this->assertEquals(new LegacyLoader(self::LEGACY_PATH), $container->get(LegacyLoader::class));
    }

    public function testTheSubscribersAreHookedOnce(): void
    {
        $this->assertSame(1, $this->countListeners('init', TextDomain::class));
        $this->assertSame(1, $this->countListeners('plugins_loaded', LegacyLoader::class));
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
