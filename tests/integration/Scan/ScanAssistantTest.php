<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Scan\Application\AssistantUsage;
use ItalyCookieChoices\Scan\Application\ScanAssistant;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Scan\FakeTextGenerator;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanAssistantTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function testItSuggestsTheHostsAndTheCookiesAsked(): void
    {
        $generator = new FakeTextGenerator([
            'hosts' => [
                [
                    'host' => 'widget.chat.example',
                    'name' => 'Chat <b>Widget</b>',
                    'category' => 'preferences',
                    'purpose' => 'Shows the chat.',
                    'vendor' => '',
                ],
                ['host' => 'not.asked', 'name' => 'X', 'category' => 'marketing', 'purpose' => '', 'vendor' => ''],
                ['host' => 'cdn.example', 'name' => 'CDN', 'category' => 'necessary', 'purpose' => '', 'vendor' => ''],
                [
                    'host' => 'stats.example',
                    'name' => 'Stats',
                    'category' => 'statistics',
                    'purpose' => 'Counts the visits.',
                    'vendor' => 'invented-vendor',
                ],
            ],
            'cookies' => [
                [
                    'name' => 'pll_language',
                    'provider' => 'Polylang',
                    'category' => 'necessary',
                    'purpose' => 'Keeps the language.',
                ],
                ['name' => 'other', 'provider' => '', 'category' => 'necessary', 'purpose' => ''],
                ['name' => 'woo', 'provider' => '', 'category' => 'invented', 'purpose' => ''],
            ],
        ]);

        $suggestions = $this->makeSut($generator)->suggest(
            ['widget.chat.example', 'cdn.example', 'stats.example'],
            ['pll_language', 'woo']
        );

        $this->assertSame(
            [
                'hosts' => [
                    [
                        'host' => 'widget.chat.example',
                        'name' => 'Chat Widget',
                        'category' => 'preferences',
                        'purpose' => 'Shows the chat.',
                        'vendor' => '',
                    ],
                    [
                        'host' => 'stats.example',
                        'name' => 'Stats',
                        'category' => 'statistics',
                        'purpose' => 'Counts the visits.',
                        'vendor' => '',
                    ],
                ],
                'cookies' => [
                    [
                        'name' => 'pll_language',
                        'provider' => 'Polylang',
                        'category' => 'necessary',
                        'purpose' => 'Keeps the language.',
                    ],
                ],
            ],
            $suggestions
        );
        $this->assertTrue($this->service(AssistantUsage::class)->wasUsed());
    }

    public function testItSendsOnlyTheNamesTheCategoriesAndTheCommonVendors(): void
    {
        $generator = new FakeTextGenerator();

        $this->makeSut($generator)->suggest(['cdn.example', 'cdn.example', ' '], ['pll_language']);

        $this->assertCount(1, $generator->prompts);
        $prompt = \json_decode($generator->prompts[0], true);
        $this->assertIsArray($prompt);
        $this->assertSame(['locale', 'categories', 'vendors', 'hosts', 'cookies'], \array_keys($prompt));
        $this->assertSame(['cdn.example'], $prompt['hosts']);
        $this->assertSame(['pll_language'], $prompt['cookies']);
        $this->assertSame(['necessary', 'preferences', 'statistics', 'marketing'], $prompt['categories']);
        $this->assertContains(
            ['id' => 'youtube', 'name' => 'YouTube', 'category' => 'marketing'],
            \is_array($prompt['vendors']) ? $prompt['vendors'] : []
        );
    }

    public function testItAsksAtMostFiftyNames(): void
    {
        $generator = new FakeTextGenerator();
        $hosts = \array_map(static fn(int $index): string => "host{$index}.example", \range(1, 60));

        $this->makeSut($generator)->suggest($hosts, []);

        $prompt = \json_decode($generator->prompts[0] ?? '', true);
        $this->assertIsArray($prompt);
        $this->assertCount(ScanAssistant::LIMIT, \is_array($prompt['hosts']) ? $prompt['hosts'] : []);
    }

    public function testNothingIsAskedWithoutNames(): void
    {
        $generator = new FakeTextGenerator();

        $this->assertSame(['hosts' => [], 'cookies' => []], $this->makeSut($generator)->suggest([], []));
        $this->assertSame([], $generator->prompts);
        $this->assertFalse($this->service(AssistantUsage::class)->wasUsed());
    }

    public function testAFailureOfTheProviderIsNotRecordedAsAUse(): void
    {
        try {
            $this->makeSut(new FakeTextGenerator(null))->suggest(['cdn.example'], []);
            $this->fail('The provider did not fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('The provider is down.', $exception->getMessage());
        }

        $this->assertFalse($this->service(AssistantUsage::class)->wasUsed());
    }

    private function makeSut(FakeTextGenerator $generator): ScanAssistant
    {
        return new ScanAssistant(
            $generator,
            $this->service(VendorPresets::class),
            $this->service(ConsentPolicyFactory::class),
            $this->service(AssistantUsage::class)
        );
    }
}
