<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Migration;
use ItalyCookieChoices\Settings\Module;
use ItalyCookieChoices\Settings\Registration;
use ItalyCookieChoices\Settings\SharedSettings;
use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyCookieChoices\Settings\UI\ImportEndpoint;
use ItalyCookieChoices\Settings\UI\NetworkSettingsPage;
use ItalyCookieChoices\Settings\UI\PluginActionLinks;
use ItalyCookieChoices\Settings\UI\SettingsAbility;
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
                SharedSettings::class,
                StringRegistration::class,
                SettingsPage::class,
                ImportEndpoint::class,
                PluginActionLinks::class,
                Abilities::class,
                SettingsAbility::class,
                NetworkSettingsPage::class,
            ],
            $config[SubscribersConfigExtension::SUBSCRIBERS]
        );
    }

    public function testTheLinksAreSubscribedToTheMainPluginFile(): void
    {
        $links = $this->service(PluginActionLinks::class);

        $this->assertSame(
            [self::ACTION_LINKS => 'links'],
            \iterator_to_array($links->getSubscribedEvents())
        );
    }

    public function testThePluginSubscribesTheSettingsPage(): void
    {
        $this->assertTrue($this->isSubscribed('admin_menu', SettingsPage::class));
        $this->assertTrue($this->isSubscribed('admin_init', StringRegistration::class));
        $this->assertTrue($this->isSubscribed(self::ACTION_LINKS, PluginActionLinks::class));
    }

    /**
     * @param class-string $class
     */
    private function isSubscribed(string $eventName, string $class): bool
    {
        /** @var array<string, \WP_Hook> $wp_filter */
        global $wp_filter;

        foreach ($wp_filter[$eventName]->callbacks ?? [] as $callbacks) {
            foreach ($callbacks as $callback) {
                if (\is_array($callback['function']) && $callback['function'][0] instanceof $class) {
                    return true;
                }
            }
        }

        return false;
    }
}
