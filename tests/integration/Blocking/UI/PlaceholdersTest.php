<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\UI;

use ItalyCookieChoices\Blocking\UI\BlockableRequest;
use ItalyCookieChoices\Blocking\UI\Placeholders;
use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\Consent\UI\BuildingBanner;
use ItalyCookieChoices\Consent\UI\ConsentScript;
use ItalyCookieChoices\Consent\UI\ConsentTexts;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use ItalyStrap\Finder\FinderFactory;
use ItalyStrap\View\View;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PlaceholdersTest extends WPTestCase
{
    private const BUILD_URL = 'https://example.com/wp-content/plugins/italy-cookie-choices/assets/build/';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        $GLOBALS['wp_styles'] = new \WP_Styles();
        // Registered by ConsentScript on the same hook.
        \wp_register_script(ConsentScript::HANDLE, false, [], null);
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts'], $GLOBALS['wp_styles']);
        parent::tearDown();
    }

    public function testItEnqueuesAndRendersOnTheFrontEnd(): void
    {
        $this->assertSame(
            ['wp_enqueue_scripts' => 'enqueue', 'wp_footer' => 'render'],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItEnqueuesTheScriptAfterTheConsentApiAndTheStyle(): void
    {
        $this->makeSut()->enqueue();

        $script = \wp_scripts()->registered[Placeholders::HANDLE];
        $this->assertTrue(\wp_script_is(Placeholders::HANDLE, 'enqueued'));
        $this->assertSame(self::BUILD_URL . 'blocking.js', $script->src);
        $this->assertSame([ConsentScript::HANDLE], $script->deps);
        $this->assertSame('blocking-version', $script->ver);
        $this->assertSame(1, \wp_scripts()->get_data(Placeholders::HANDLE, 'group'));
        $this->assertTrue(\wp_style_is(Placeholders::HANDLE, 'enqueued'));
        $this->assertSame(self::BUILD_URL . 'blocking.css', \wp_styles()->registered[Placeholders::HANDLE]->src);
    }

    public function testNothingIsEnqueuedWhenTheRequestIsNotBlocked(): void
    {
        $this->go_to(\get_feed_link());

        $this->makeSut()->enqueue();

        $this->assertFalse(\wp_script_is(Placeholders::HANDLE, 'registered'));
    }

    public function testNothingIsEnqueuedWhenTheAssetsAreNotBuilt(): void
    {
        $this->makeSut('/not/built/')->enqueue();

        $this->assertFalse(\wp_script_is(Placeholders::HANDLE, 'registered'));
    }

    public function testThePlaceholdersAreForTheOptionalCategories(): void
    {
        $this->assertSame(
            [
                'preferences' => 'Preferences',
                'categories' => [
                    [
                        'id' => 'preferences',
                        'message' => 'This content is blocked until you accept the Preferences cookies.',
                        'button' => 'Accept the Preferences cookies',
                    ],
                    [
                        'id' => 'statistics',
                        'message' => 'This content is blocked until you accept the Statistics cookies.',
                        'button' => 'Accept the Statistics cookies',
                    ],
                    [
                        'id' => 'marketing',
                        'message' => 'This content is blocked until you accept the Marketing cookies.',
                        'button' => 'Accept the Marketing cookies',
                    ],
                ],
            ],
            $this->makeSut()->data()
        );
    }

    public function testTheSavedTextsReplaceTheDefaults(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'content_message_text' => '<strong>Content blocked</strong><script>alert(1)</script>',
            'content_message_button_text' => 'Allow',
        ]);

        $category = $this->makeSut()->data()['categories'][0];

        $this->assertSame('<strong>Content blocked</strong>alert(1)', $category['message']);
        $this->assertSame('Allow', $category['button']);
    }

    public function testTheLabelsFollowTheBannerListeners(): void
    {
        \add_action(BuildingBanner::class, static function (BuildingBanner $event): void {
            $event->changeCategoryLabel('marketing', 'Ads <b>', 'Ads description');
            $event->changeText(BuildingBanner::PREFERENCES, 'Choose');
        });

        $data = $this->makeSut()->data();

        $this->assertSame('Choose', $data['preferences']);
        $this->assertSame('This content is blocked until you accept the Ads &lt;b&gt; '
            . 'cookies.', $data['categories'][2]['message']);
        $this->assertSame('Accept the Ads <b> cookies', $data['categories'][2]['button']);
    }

    public function testItRendersATemplateForEachCategory(): void
    {
        $sut = $this->makeSut();
        $sut->enqueue();

        $output = $this->render($sut);

        $this->assertSame(3, \substr_count($output, '<template data-icc-placeholder='));
        $this->assertStringContainsString('<template data-icc-placeholder="marketing">', $output);
        $this->assertMatchesRegularExpression(
            '#<button type="button" class="icc-placeholder__button" data-icc-accept="marketing">\s*Accept the '
                . 'Marketing cookies\s*</button>#',
            $output
        );
        $this->assertMatchesRegularExpression(
            '#<a href="\#icc-preferences" class="icc-placeholder__preferences">\s*Preferences\s*</a>#',
            $output
        );
    }

    public function testTheTemplateEscapesTheTexts(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'content_message_button_text' => 'Allow & show']);
        $sut = $this->makeSut();
        $sut->enqueue();

        $this->assertStringContainsString('Allow &amp; show', $this->render($sut));
    }

    public function testNothingIsRenderedWithoutTheScript(): void
    {
        $this->assertSame('', $this->render($this->makeSut()));
    }

    private function render(Placeholders $sut): string
    {
        \ob_start();
        $sut->render();

        return (string) \ob_get_clean();
    }

    private function makeSut(string $buildPath = ''): Placeholders
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $templates = \codecept_root_dir('templates/');
        $this->assertIsString($fixtures);
        $this->assertIsString($templates);
        $finder = (new FinderFactory())->make();
        $finder->in($templates);
        $dispatcher = new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState());
        $options = new Options();

        return new Placeholders(
            new ConsentTexts(new ConsentPolicyFactory($dispatcher), $dispatcher, $options),
            $options,
            new BlockableRequest($options),
            new View($finder),
            $buildPath === '' ? $fixtures : $buildPath,
            self::BUILD_URL
        );
    }
}
