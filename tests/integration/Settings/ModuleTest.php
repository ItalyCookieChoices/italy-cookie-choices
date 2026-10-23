<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Migration;
use ItalyCookieChoices\Settings\Module;
use ItalyCookieChoices\Settings\Registration;
use ItalyCookieChoices\Settings\UI\ImportEndpoint;
use ItalyCookieChoices\Settings\UI\PluginActionLinks;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyCookieChoices\Settings\UI\StringRegistration;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyStrap\Event\SubscribersConfigExtension;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ModuleTest extends WPTestCase
{
    use BuildsContainer;

    private const ACTION_LINKS = 'plugin_action_links_italy-cookie-choices/italy-cookie-choices.php';

    public function testTheSubscribersAreRegistered(): void
    {
        $config = (new Module('/plugin/', 'https://example.com/plugin/', 'plugin/plugin.php'))();

        $this->assertIsArray($config);
        $this->assertSame(
            [
                Migration::class,
                Registration::class,
                StringRegistration::class,
                SettingsPage::class,
                ImportEndpoint::class,
                PluginActionLinks::class,
            ],
            $config[SubscribersConfigExtension::SUBSCRIBERS]
        );
    }

    public function testTheLinksAreHookedToTheMainPluginFile(): void
    {
        $links = $this->service(PluginActionLinks::class);

        $this->assertSame(
            [self::ACTION_LINKS => 'links'],
            \iterator_to_array($links->getSubscribedEvents())
        );
    }

    public function testThePluginHooksTheSettingsPage(): void
    {
        $this->assertTrue($this->isHooked('admin_menu', SettingsPage::class));
        $this->assertTrue($this->isHooked('admin_init', StringRegistration::class));
        $this->assertTrue($this->isHooked(self::ACTION_LINKS, PluginActionLinks::class));
    }

    /**
     * @param class-string $class
     */
    private function isHooked(string $hook, string $class): bool
    {
        /** @var array<string, \WP_Hook> $wp_filter */
        global $wp_filter;

        foreach ($wp_filter[$hook]->callbacks ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                if (\is_array($callback['function']) && $callback['function'][0] instanceof $class) {
                    return true;
                }
            }
        }

        return false;
    }
}
