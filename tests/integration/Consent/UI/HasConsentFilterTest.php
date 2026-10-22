<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository;
use ItalyCookieChoices\Consent\Infrastructure\FilteredConsentPolicy;
use ItalyCookieChoices\Consent\UI\HasConsentFilter;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class HasConsentFilterTest extends WPTestCase
{
    public function testItAnswersTheHasConsentFilter(): void
    {
        $this->assertSame(
            [
                HasConsentFilter::FILTER => [
                    SubscriberInterface::CALLBACK => 'hasConsent',
                    SubscriberInterface::ACCEPTED_ARGS => 2,
                ],
            ],
            \iterator_to_array($this->makeSut([])->getSubscribedEvents())
        );
    }

    public function testTheNecessaryCookiesNeedNoConsent(): void
    {
        $this->assertTrue($this->makeSut([])->hasConsent(false, 'necessary'));
    }

    public function testTheOptionalCookiesNeedAConsent(): void
    {
        $this->assertFalse($this->makeSut([])->hasConsent(false, 'statistics'));
    }

    public function testTheAcceptedCategoriesAreAllowed(): void
    {
        $sut = $this->makeSut(['statistics']);

        $this->assertTrue($sut->hasConsent(false, 'statistics'));
        $this->assertFalse($sut->hasConsent(false, 'marketing'));
    }

    public function testAConsentForAnotherVersionIsAskedAgain(): void
    {
        \add_filter(FilteredConsentPolicy::VERSION_FILTER, static fn(): string => '2');

        $this->assertFalse($this->makeSut(['statistics'])->hasConsent(false, 'statistics'));
    }

    public function testWithoutACategoryTheValueIsKept(): void
    {
        $sut = $this->makeSut(['statistics']);

        $this->assertTrue($sut->hasConsent(true));
        $this->assertFalse($sut->hasConsent(false, ''));
        $this->assertTrue($sut->hasConsent(1, ['statistics']));
    }

    public function testThemesAskWithTheFilter(): void
    {
        $this->assertTrue(\apply_filters(HasConsentFilter::FILTER, false, 'necessary'));
        $this->assertFalse(\apply_filters(HasConsentFilter::FILTER, false, 'marketing'));
    }

    /**
     * @param list<string> $categories
     */
    private function makeSut(array $categories): HasConsentFilter
    {
        $cookies = [];
        if ($categories !== []) {
            $cookies['icc_consent'] = \wp_slash((string) \wp_json_encode([
                'categories' => $categories,
                'version' => '1',
                'date' => (new \DateTimeImmutable('-1 day'))->format(\DateTimeInterface::RFC3339_EXTENDED),
            ]));
        }

        return new HasConsentFilter(new FilteredConsentPolicy(), new CookieConsentRepository($cookies));
    }
}
