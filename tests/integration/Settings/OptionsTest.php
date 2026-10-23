<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class OptionsTest extends WPTestCase
{
    use BuildsOptions;

    public function testThePluginIsActiveWhenTheCheckboxIsSaved(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertTrue($this->options()->isActive());
    }

    public function testThePluginIsNotActiveByDefault(): void
    {
        \delete_option(Schema::OPTION);

        $this->assertFalse($this->options()->isActive());
    }

    public function testTheLegacyCookieIsTheSavedOne(): void
    {
        \update_option(Schema::OPTION, ['cookie_name' => 'accepted', 'cookie_value' => 'yes']);

        $this->assertSame(['name' => 'accepted', 'value' => 'yes'], $this->options()->legacyCookie());
    }

    public function testTheLegacyCookieFallsBackToTheDefaults(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, ['cookie_name' => '', 'cookie_value' => ['y']]);

        $this->assertSame(['name' => 'displayCookieConsent', 'value' => 'y'], $this->options()->legacyCookie());
    }

    public function testAnOptionThatIsNotAnArrayIsEmpty(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, 'invalid');

        $this->assertFalse($this->options()->isActive());
        $this->assertSame(['name' => 'displayCookieConsent', 'value' => 'y'], $this->options()->legacyCookie());
    }

    public function testACheckboxIsOnWhenItIsSaved(): void
    {
        \update_option(Schema::OPTION, ['target' => '1']);

        $this->assertTrue($this->options()->isChecked('target'));
        $this->assertFalse($this->options()->isChecked('nofollow'));
    }

    public function testATextFallsBackToTheDefault(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, ['url' => 'https://example.com/', 'slug' => '', 'banner' => 2]);

        $sut = $this->options();

        $this->assertSame('https://example.com/', $sut->text('url'));
        $this->assertSame('policy', $sut->text('slug', 'policy'));
        $this->assertSame('1', $sut->text('banner', '1'));
        $this->assertSame('', $sut->text('missing'));
    }

    public function testATranslatableTextIsTranslatedByItsName(): void
    {
        \update_option(Schema::OPTION, ['text' => 'Policy text', 'anchor_text' => '']);
        \add_filter(
            'wpml_translate_single_string',
            static fn(string $text, string $context, string $name): string => $name . ': ' . $text,
            10,
            3
        );

        $sut = $this->options();

        $this->assertSame('Banner text: Policy text', $sut->translated('text'));
        $this->assertSame('Default', $sut->translated('anchor_text', 'Default'));
    }

    public function testACategoryTextIsTranslatedByTheCategoryAndTheField(): void
    {
        \update_option(Schema::OPTION, ['categories' => ['statistics' => ['label' => 'Analytics']]]);
        \add_filter(
            'wpml_translate_single_string',
            static fn(string $text, string $context, string $name): string => $name . ': ' . $text,
            10,
            3
        );

        $sut = $this->options();

        $this->assertSame(
            'Category statistics label: Analytics',
            $sut->translatedCategoryText('statistics', 'label', 'Statistics')
        );
        $this->assertSame('Default', $sut->translatedCategoryText('statistics', 'description', 'Default'));
        $this->assertSame('Default', $sut->translatedCategoryText('marketing', 'label', 'Default'));
    }

    public function testTheCategoryTextsAreOnlyTheSavedStrings(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, [
            'categories' => [
                'statistics' => ['label' => 'Analytics', 'description' => '', 'other' => 42],
                'marketing' => 'invalid',
            ],
        ]);

        $this->assertSame(
            ['statistics' => ['label' => 'Analytics'], 'marketing' => []],
            $this->options()->categoryTexts()
        );
    }

    public function testTheRecordsAreTheArraysOfAList(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, ['custom_vendors' => [['id' => 'a'], 'invalid', ['id' => 'b']], 'text' => 'a']);

        $sut = $this->options();

        $this->assertSame([['id' => 'a'], ['id' => 'b']], $sut->records('custom_vendors'));
        $this->assertSame([], $sut->records('text'));
        $this->assertSame([], $sut->records('missing'));
    }
}
