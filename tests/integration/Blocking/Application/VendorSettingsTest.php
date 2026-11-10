<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Application;

use ItalyCookieChoices\Blocking\Application\VendorSettings;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorSettingsTest extends WPTestCase
{
    use BuildsContainer;

    private const TRACKER = [
        'id' => 'tracker',
        'name' => 'Tracker',
        'category' => 'statistics',
        'patterns' => ['tracker.example.com'],
        'cookies' => [],
    ];

    public function testTheVendorsAreThePresetsAndTheCustomVendors(): void
    {
        \update_option(Schema::OPTION, ['disabled_vendors' => ['youtube'], 'custom_vendors' => [self::TRACKER]]);

        $vendors = $this->sut()->all();

        $this->assertContains(
            ['id' => 'youtube', 'name' => 'YouTube', 'category' => 'marketing', 'preset' => true, 'enabled' => false],
            $vendors
        );
        $this->assertContains(
            ['id' => 'vimeo', 'name' => 'Vimeo', 'category' => 'marketing', 'preset' => true, 'enabled' => true],
            $vendors
        );
        $this->assertSame(
            ['id' => 'tracker', 'name' => 'Tracker', 'category' => 'statistics', 'preset' => false, 'enabled' => true],
            \end($vendors)
        );
    }

    public function testAPresetIsTurnedOffAndOnAgain(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'disabled_vendors' => ['google-recaptcha']]);

        $this->sut()->disable('youtube');
        $this->assertSame(['google-recaptcha', 'youtube'], $this->saved()['disabled_vendors'] ?? null);

        $this->sut()->disable('youtube');
        $this->assertSame(['google-recaptcha', 'youtube'], $this->saved()['disabled_vendors'] ?? null);

        $this->sut()->enable('youtube');
        $this->assertSame(['google-recaptcha'], $this->saved()['disabled_vendors'] ?? null);
        $this->assertSame('1', $this->saved()['active'] ?? null);
    }

    public function testOnlyThePresetsAreTurnedOnOrOff(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no common vendor with the id "tracker".');

        $this->sut()->disable('tracker');
    }

    public function testACustomVendorIsAddedAfterTheOthers(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'custom_vendors' => [self::TRACKER]]);

        $vendor = $this->sut()->add('Chat Widget', 'preferences', [' chat.example.com ', '', 'loadChat(']);

        $expected = [
            'id' => 'chatwidget',
            'name' => 'Chat Widget',
            'category' => 'preferences',
            'patterns' => ['chat.example.com', 'loadChat('],
            'cookies' => [],
        ];
        $this->assertSame($expected, $vendor);
        $this->assertSame([self::TRACKER, $expected], $this->saved()['custom_vendors'] ?? null);
        $this->assertSame('1', $this->saved()['active'] ?? null);
    }

    /**
     * @return iterable<string, array{string, string, list<string>, string}>
     */
    public function invalidVendors(): iterable
    {
        yield 'no name' => [' ', 'statistics', ['a.example.com'], 'The vendor needs a name.'];
        yield 'taken by a custom vendor' => [
            'Tracker',
            'statistics',
            ['a.example.com'],
            'A vendor with the id "tracker" already exists.',
        ];
        yield 'taken by a preset' => [
            'YouTube',
            'marketing',
            ['a.example.com'],
            'A vendor with the id "youtube" already exists.',
        ];
        yield 'required category' => [
            'Chat',
            'necessary',
            ['a.example.com'],
            'The category "necessary" is not one the visitors can refuse: preferences, statistics, marketing.',
        ];
        yield 'unknown category' => [
            'Chat',
            'ads',
            ['a.example.com'],
            'The category "ads" is not one the visitors can refuse: preferences, statistics, marketing.',
        ];
        yield 'no pattern' => ['Chat', 'statistics', [' '], 'The vendor needs at least a pattern to block.'];
    }

    /**
     * @dataProvider invalidVendors
     * @param list<string> $patterns
     */
    public function testAnInvalidCustomVendorIsNotAdded(
        string $name,
        string $category,
        array $patterns,
        string $message
    ): void {
        \update_option(Schema::OPTION, ['custom_vendors' => [self::TRACKER]]);

        try {
            $this->sut()->add($name, $category, $patterns);
            $this->fail('The vendor was added.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }

        $this->assertSame([self::TRACKER], $this->saved()['custom_vendors'] ?? null);
    }

    private function sut(): VendorSettings
    {
        return $this->service(VendorSettings::class);
    }

    /**
     * @phpstan-impure
     * @return array<array-key, mixed>
     */
    private function saved(): array
    {
        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);

        return $saved;
    }
}
