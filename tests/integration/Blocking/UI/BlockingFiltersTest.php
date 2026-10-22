<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\UI;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker;
use ItalyCookieChoices\Blocking\UI\BlockableRequest;
use ItalyCookieChoices\Blocking\UI\BlockingFilters;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BlockingFiltersTest extends WPTestCase
{
    private const VIDEO = '<iframe src="https://www.youtube.com/embed/a"></iframe>';
    private const BLOCKED_VIDEO = '<iframe data-category="marketing" data-icc-src="https://www.youtube.com/embed/a" >'
        . '</iframe>';

    public function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_scripts'] = new \WP_Scripts();
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['wp_scripts']);
        parent::tearDown();
    }

    public function testItFiltersTheContentTheWidgetsTheEmbedsAndTheScripts(): void
    {
        $late = [SubscriberInterface::CALLBACK => 'blockHtml', SubscriberInterface::PRIORITY => 999];

        $this->assertSame(
            [
                'the_content' => $late,
                'widget_text' => $late,
                'widget_custom_html_content' => $late,
                'widget_block_content' => $late,
                'embed_oembed_html' => $late,
                'script_loader_tag' => [
                    SubscriberInterface::CALLBACK => 'blockScript',
                    SubscriberInterface::PRIORITY => 999,
                    SubscriberInterface::ACCEPTED_ARGS => 2,
                ],
            ],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItBlocksTheVendorsInTheHtml(): void
    {
        $blocked = $this->makeSut()->blockHtml('<p>Video</p>' . self::VIDEO);

        $this->assertSame('<p>Video</p>' . self::BLOCKED_VIDEO, $blocked);
    }

    public function testItLeavesTheValuesThatAreNotHtmlAlone(): void
    {
        $this->assertNull($this->makeSut()->blockHtml(null));
    }

    public function testItDoesNotBlockWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, []);

        $this->assertSame(self::VIDEO, $this->makeSut()->blockHtml(self::VIDEO));
    }

    public function testItDoesNotBlockTheFeeds(): void
    {
        $this->go_to(\get_feed_link());

        $this->assertSame(self::VIDEO, $this->makeSut()->blockHtml(self::VIDEO));
    }

    public function testItBlocksTheEnqueuedScriptsOfTheVendors(): void
    {
        \wp_enqueue_script('analytics', 'https://www.googletagmanager.com/gtag/js?id=G-1', [], null);
        \wp_enqueue_script('site', 'https://example.com/site.js', [], null);

        $output = $this->printScripts();

        $this->assertStringContainsString(
            '<script data-category="statistics" type="text/plain" id="analytics-js" '
                . 'src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>',
            $output
        );
        $this->assertStringContainsString('<script id="site-js" src="https://example.com/site.js"></script>', $output);
    }

    public function testItBlocksAScriptAndItsInlineScriptsByItsCategory(): void
    {
        \wp_enqueue_script('tracker', 'https://example.com/tracker.js', [], null);
        \wp_add_inline_script('tracker', 'window.trackerId = 1;', 'before');
        \wp_script_add_data('tracker', BlockingFilters::SCRIPT_CATEGORY, 'statistics');

        $output = $this->printScripts();

        $this->assertStringContainsString(
            '<script data-category="statistics" type="text/plain" id="tracker-js" '
                . 'src="https://example.com/tracker.js"></script>',
            $output
        );
        $this->assertMatchesRegularExpression(
            '#<script data-category="statistics" type="text/plain" id="tracker-js-before">\s*window.trackerId = 1;#',
            $output
        );
    }

    public function testTheScriptTagIsNotChangedWhenTheValuesAreNotStrings(): void
    {
        $this->assertNull($this->makeSut()->blockScript(null, 'handle'));
        $this->assertSame('<script></script>', $this->makeSut()->blockScript('<script></script>', null));
    }

    private function printScripts(): string
    {
        $sut = $this->makeSut();
        \add_filter('script_loader_tag', [$sut, 'blockScript'], 999, 2);

        \ob_start();
        \wp_print_scripts();

        return (string) \ob_get_clean();
    }

    private function makeSut(): BlockingFilters
    {
        $options = new Options();

        return new BlockingFilters(
            new HtmlBlocker(),
            new VendorsFactory(
                new VendorPresets(),
                new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()),
                $options
            ),
            new BlockableRequest($options)
        );
    }
}
