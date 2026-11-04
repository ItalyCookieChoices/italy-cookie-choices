<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use ItalyCookieChoices\Integrations\Application\WpConsentApiMapping;
use ItalyCookieChoices\Integrations\UI\WpConsentApiFilters;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class WpConsentApiFiltersTest extends WPTestCase
{
    use BuildsOptions;

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function testItAnswersTheWpConsentApiFilters(): void
    {
        $this->assertSame(
            [
                'wp_get_consent_type' => 'consentType',
                'wp_has_consent' => [
                    SubscriberInterface::CALLBACK => 'hasConsent',
                    SubscriberInterface::ACCEPTED_ARGS => 2,
                ],
                'wp_consent_api_cookie_expiration' => 'cookieExpiration',
            ],
            \iterator_to_array($this->makeSut([])->getSubscribedEvents())
        );
    }

    public function testTheConsentIsOptIn(): void
    {
        $this->assertSame('optin', $this->makeSut([])->consentType(''));
    }

    public function testTheFunctionalCookiesNeedNoConsent(): void
    {
        $this->assertTrue($this->makeSut([])->hasConsent(false, 'functional'));
    }

    public function testTheOtherCategoriesNeedAConsent(): void
    {
        $sut = $this->makeSut([]);

        $this->assertFalse($sut->hasConsent(true, 'statistics'));
        $this->assertFalse($sut->hasConsent(true, 'marketing'));
    }

    public function testTheCategoriesOfTheConsentAreAllowed(): void
    {
        $sut = $this->makeSut(['statistics']);

        $this->assertTrue($sut->hasConsent(false, 'statistics'));
        $this->assertTrue($sut->hasConsent(false, 'statistics-anonymous'));
        $this->assertFalse($sut->hasConsent(true, 'marketing'));
    }

    public function testAnUnknownCategoryKeepsTheValue(): void
    {
        $sut = $this->makeSut([]);

        $this->assertTrue($sut->hasConsent(true, 'unknown'));
        $this->assertFalse($sut->hasConsent(false, 'unknown'));
        $this->assertTrue($sut->hasConsent(1, ['statistics']));
    }

    public function testTheCookiesOfTheWpConsentApiLastAsTheConsent(): void
    {
        $this->assertSame(182, $this->makeSut([])->cookieExpiration(30));
    }

    public function testWithTheBannerOffTheValuesAreKept(): void
    {
        \update_option(Schema::OPTION, []);
        $sut = $this->makeSut(['statistics']);

        $this->assertSame('', $sut->consentType(''));
        $this->assertTrue($sut->hasConsent(true, 'marketing'));
        $this->assertSame(30, $sut->cookieExpiration(30));
    }

    /**
     * @param list<string> $categories
     */
    private function makeSut(array $categories): WpConsentApiFilters
    {
        $cookies = [];
        if ($categories !== []) {
            $cookies['icc_consent'] = \wp_slash((string) \wp_json_encode([
                'categories' => $categories,
                'version' => '1',
                'date' => (new \DateTimeImmutable('-1 day'))->format(\DateTimeInterface::RFC3339_EXTENDED),
            ]));
        }

        $dispatcher = new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState());

        return new WpConsentApiFilters(
            $this->options(),
            new WpConsentApiMapping($dispatcher),
            new ConsentPolicyFactory($dispatcher),
            new CookieConsentRepository($cookies)
        );
    }
}
