<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Screenshots;

use Codeception\Example;
use E2eTester;

/**
 * Takes the screenshots of the plugin page on WordPress.org and of the documentation, in English and Italian.
 *
 * Run it with make screenshots, the images are saved in tests/_output/debug.
 */
final class ScreenshotsCest
{
    private const SETTINGS_PAGE = '/admin.php?page=italy-cookie-choices';
    private const PREVIEW = '.icc-admin__preview-frame';

    /**
     * @return list<array{locale: string, text: string}>
     */
    protected function locales(): array
    {
        return [
            [
                'locale' => 'en_US',
                'text' => 'We use cookies to measure the visits and to show videos and maps. '
                    . 'You can accept them, refuse them or choose the categories.',
            ],
            [
                'locale' => 'it_IT',
                'text' => 'Usiamo i cookie per misurare le visite e per mostrare video e mappe. '
                    . 'Puoi accettarli, rifiutarli o scegliere le categorie.',
            ],
        ];
    }

    /**
     * @dataProvider locales
     * @param Example<string, string> $example
     */
    public function takeTheScreenshots(E2eTester $I, Example $example): void
    {
        $locale = $this->text($I, $example, 'locale');
        $I->haveOptionInDatabase('WPLANG', $locale === 'en_US' ? '' : $locale);
        $I->haveOptionInDatabase('icc_e2e_screenshots', '1');
        $I->haveOptionInDatabase('blogname', $locale === 'en_US' ? 'My site' : 'Il mio sito');
        $I->haveCookieChoicesOptions([
            'banner' => '3',
            'text' => $this->text($I, $example, 'text'),
            'anchor_text' => 'Cookie policy',
            'button_text' => null,
            'content_message_text' => null,
            'content_message_button_text' => null,
            'consent_mode' => '1',
            'youtube_nocookie' => '1',
            'ga_measurement_id' => 'G-EXAMPLE123',
            'custom_vendors' => [[
                'id' => 'newsletter',
                'name' => 'Newsletter',
                'category' => 'marketing',
                'patterns' => ['newsletter.example.com/form.js'],
            ]],
        ]);
        $I->havePostInDatabase([
            'post_title' => $locale === 'en_US' ? 'Our latest video' : 'Il nostro ultimo video',
            'post_name' => 'video',
            'post_content' => '<!-- wp:embed {"url":"https://www.youtube.com/watch?v=icc-oembed","type":"video",'
                . "\"providerNameSlug\":\"youtube\"} -->\n<figure class=\"wp-block-embed is-type-video\">"
                . "<div class=\"wp-block-embed__wrapper\">\nhttps://www.youtube.com/watch?v=icc-oembed\n"
                . "</div></figure>\n<!-- /wp:embed -->",
        ]);

        $this->settings($I, $locale);
        $this->frontEnd($I, $locale);
    }

    private function settings(E2eTester $I, string $locale): void
    {
        $I->loginAsAdmin();
        $I->amOnAdminPage(self::SETTINGS_PAGE);
        $I->waitForElement('.icc-admin__tabs', 10);
        $this->waitForThePreview($I, 'icc-banner');
        $this->screenshot($I, $locale, 1, 'settings-banner');

        $I->click('.components-tab-panel__tabs button:nth-child(3)');
        $I->click('.icc-admin__preview .components-button-group button:nth-child(2)');
        $this->waitForThePreview($I, 'icc-preferences');
        $this->screenshot($I, $locale, 2, 'settings-categories');

        $I->click('.components-tab-panel__tabs button:nth-child(4)');
        $this->screenshot($I, $locale, 3, 'settings-blocking');

        $I->click('.components-tab-panel__tabs button:nth-child(5)');
        $this->screenshot($I, $locale, 4, 'settings-integrations');
        $I->logOut();
    }

    private function frontEnd(E2eTester $I, string $locale): void
    {
        $I->amOnPage('/video/');
        $I->waitForElementVisible('#icc-banner');
        $this->screenshot($I, $locale, 5, 'banner');

        $I->click('#icc-banner [data-icc-action="preferences"]');
        $I->waitForElementVisible('#icc-preferences');
        $this->screenshot($I, $locale, 6, 'preferences');

        $I->click('#icc-preferences [data-icc-action="reject-all"]');
        $I->waitForElementNotVisible('#icc-preferences');
        $I->scrollTo('.wp-block-embed', 0, -120);
        $this->screenshot($I, $locale, 7, 'placeholder');
    }

    /**
     * @param Example<string, string> $example
     */
    private function text(E2eTester $I, Example $example, string $key): string
    {
        $text = $example[$key];
        $I->assertIsString($text);

        return $text;
    }

    private function waitForThePreview(E2eTester $I, string $dialog): void
    {
        $I->waitForJS(
            'const frame = document.querySelector("' . self::PREVIEW . '");'
            . 'return frame !== null && frame.srcdoc.includes(\'id="' . $dialog . '"\');',
            10
        );
        // The frame paints after its styles load.
        $I->wait(1);
    }

    private function screenshot(E2eTester $I, string $locale, int $number, string $name): void
    {
        $I->wait(0.5);
        $I->makeScreenshot(\sprintf('%s-%d-%s', $locale, $number, $name));
    }
}
