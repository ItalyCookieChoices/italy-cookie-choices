<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Integrations\UI;

use ItalyCookieChoices\Integrations\UI\AmazonConsentScript;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class AmazonConsentScriptTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'amazon_consent_mode' => '1', 'amazon_country' => 'de']);
    }

    public function testItPrintsAtTheStartOfTheHead(): void
    {
        $this->assertSame(
            ['wp_head' => [SubscriberInterface::CALLBACK => 'print', SubscriberInterface::PRIORITY => 2]],
            \iterator_to_array($this->service(AmazonConsentScript::class)->getSubscribedEvents())
        );
    }

    public function testTheSignalWaitsForTheMarketingConsent(): void
    {
        $html = $this->printed();

        $processor = new \WP_HTML_Tag_Processor($html);
        $this->assertTrue($processor->next_tag(['tag_name' => 'script']));
        $this->assertSame('text/plain', $processor->get_attribute('type'));
        $this->assertSame('marketing', $processor->get_attribute('data-category'));
        $this->assertSame('amazon-ads', $processor->get_attribute('data-icc-vendor'));
        $this->assertSame(AmazonConsentScript::HELPER, $processor->get_attribute('src'));
        $this->assertTrue($processor->next_tag(['tag_name' => 'script']));
        $this->assertSame('text/plain', $processor->get_attribute('type'));
        $this->assertSame('marketing', $processor->get_attribute('data-category'));
        $this->assertSame('amazon-ads', $processor->get_attribute('data-icc-vendor'));
        $this->assertStringContainsString(
            'window.amznConsent({"countryCode":"DE","enableAdStorage":true,"enableUserData":true});',
            $html
        );
    }

    public function testTheCountryComesFromTheLanguageOfTheSiteWhenItIsNotSet(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'amazon_consent_mode' => '1']);
        \add_filter('locale', static fn(): string => 'it_IT');

        $this->assertStringContainsString('"countryCode":"IT"', $this->printed());
    }

    public function testNothingIsPrintedWhenTheSignalIsOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'amazon_country' => 'DE']);

        $this->assertSame('', $this->printed());
    }

    private function printed(): string
    {
        \ob_start();
        $this->service(AmazonConsentScript::class)->print();

        return (string) \ob_get_clean();
    }
}
