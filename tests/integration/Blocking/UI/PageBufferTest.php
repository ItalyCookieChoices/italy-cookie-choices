<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\UI;

use ItalyCookieChoices\Blocking\Application\VendorPresets;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker;
use ItalyCookieChoices\Blocking\UI\BlockableRequest;
use ItalyCookieChoices\Blocking\UI\PageBuffer;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PageBufferTest extends WPTestCase
{
    private const PAGE = '<!DOCTYPE html><html><head><script async '
        . 'src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>'
        . '</head><body><p>Text</p></body></html>';

    public function testItStartsRightBeforeTheTemplate(): void
    {
        $this->assertSame(
            [
                'template_redirect' => [
                    SubscriberInterface::CALLBACK => 'start',
                    SubscriberInterface::PRIORITY => \PHP_INT_MAX,
                ],
            ],
            \iterator_to_array($this->makeSut()->getSubscribedEvents())
        );
    }

    public function testItBlocksTheWholePageInTheCompatibilityMode(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', PageBuffer::OPTION => '1']);
        $level = \ob_get_level();

        $this->makeSut()->start();
        echo self::PAGE; // phpcs:ignore
        $this->assertSame($level + 1, \ob_get_level());
        \ob_end_flush();

        $this->expectOutputString(
            '<!DOCTYPE html><html><head><script data-category="statistics" type="text/plain" async '
            . 'src="https://www.googletagmanager.com/gtag/js?id=G-1"></script></head><body><p>Text</p></body></html>'
        );
    }

    public function testItDoesNotBufferByDefault(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $level = \ob_get_level();

        $this->makeSut()->start();

        $this->assertSame($level, \ob_get_level());
    }

    public function testItDoesNotBufferTheFeeds(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', PageBuffer::OPTION => '1']);
        $this->go_to(\get_feed_link());
        $level = \ob_get_level();

        $this->makeSut()->start();

        $this->assertSame($level, \ob_get_level());
    }

    public function testItLeavesTheOutputThatIsNotAPageAlone(): void
    {
        $json = '{"script": "<script src=\"https://www.googletagmanager.com/gtag/js\"></script>"}';

        $this->assertSame($json, $this->makeSut()->block($json));
    }

    private function makeSut(): PageBuffer
    {
        $options = new Options();

        return new PageBuffer(
            new HtmlBlocker(),
            new VendorsFactory(
                new VendorPresets(),
                new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState()),
                $options
            ),
            new BlockableRequest($options),
            $options
        );
    }
}
