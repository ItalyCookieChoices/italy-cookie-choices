<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent;

use ItalyCookieChoices\Consent\Domain\ConsentRepository;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use ItalyCookieChoices\Consent\Module;
use ItalyCookieChoices\Consent\UI\Banner;
use ItalyCookieChoices\Consent\UI\BannerPreviewEndpoint;
use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Consent\UI\HasConsentFilter;
use ItalyCookieChoices\Consent\UI\PreferencesButtonBlock;
use ItalyCookieChoices\Consent\UI\PreferencesShortcode;
use ItalyStrap\Empress\ContainerBuilder;
use ItalyStrap\Event\Module as EventModule;
use ItalyStrap\Event\SubscribersConfigExtension;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Psr\Container\ContainerInterface;

final class ModuleTest extends WPTestCase
{
    public function testTheSubscribersAreRegistered(): void
    {
        $config = (new Module('/plugin/', 'https://example.com/plugin/', []))();

        $this->assertIsArray($config);
        $this->assertSame(
            [
                ConsentScript::class,
                Banner::class,
                HasConsentFilter::class,
                PreferencesShortcode::class,
                PreferencesButtonBlock::class,
                BannerPreviewEndpoint::class,
            ],
            $config[SubscribersConfigExtension::SUBSCRIBERS]
        );
    }

    public function testTheContainerReadsTheConsentFromTheRequestCookies(): void
    {
        $cookies = [
            'icc_consent' => '{"categories":["necessary"],"version":"1","date":"2026-10-22T10:00:00.000Z"}',
        ];

        $repository = $this->container($cookies)->get(ConsentRepository::class);

        $this->assertInstanceOf(CookieConsentRepository::class, $repository);
        $this->assertSame(['necessary'], $repository->current()?->categories());
    }

    public function testTheContainerBuildsTheScriptWithThePolicy(): void
    {
        $script = $this->container([])->get(ConsentScript::class);

        $this->assertInstanceOf(ConsentScript::class, $script);
        $this->assertSame('1', $script->config()['version']);
    }

    /**
     * @param array<string, string> $cookies
     */
    private function container(array $cookies): ContainerInterface
    {
        return (new ContainerBuilder())
            ->addModule(new EventModule())
            ->addModule(new Module('/plugin/', 'https://example.com/plugin/', $cookies))
            ->build();
    }
}
