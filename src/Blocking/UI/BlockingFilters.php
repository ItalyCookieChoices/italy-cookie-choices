<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\UI;

use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Blocks the vendors in the content, the widgets, the embeds and the enqueued scripts, without output buffering.
 *
 * An enqueued script is blocked by its vendor, or by hand with its category:
 *
 * wp_script_add_data( 'my-analytics', 'consent_category', 'statistics' );
 */
final readonly class BlockingFilters implements SubscriberInterface
{
    public const SCRIPT_CATEGORY = 'consent_category';

    // After the shortcodes and the other filters that add HTML.
    private const LATE_PRIORITY = 999;

    private const HTML_FILTERS = [
        'the_content',
        'widget_text',
        'widget_custom_html_content',
        'widget_block_content',
        'embed_oembed_html',
    ];

    public function __construct(
        private HtmlBlocker $blocker,
        private VendorsFactory $vendors,
        private BlockableRequest $request,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        foreach (self::HTML_FILTERS as $filter) {
            yield $filter => [
                self::CALLBACK => 'blockHtml',
                self::PRIORITY => self::LATE_PRIORITY,
            ];
        }

        yield 'script_loader_tag' => [
            self::CALLBACK => 'blockScript',
            self::PRIORITY => self::LATE_PRIORITY,
            self::ACCEPTED_ARGS => 2,
        ];
    }

    public function blockHtml(mixed $html): mixed
    {
        if (!\is_string($html) || !$this->request->isBlockable()) {
            return $html;
        }

        return $this->blocker->block($html, $this->vendors->create());
    }

    /**
     * The tag has the inline scripts added before and after the script, they wait for the same consent.
     */
    public function blockScript(mixed $tag, mixed $handle = ''): mixed
    {
        if (!\is_string($tag) || !\is_string($handle) || !$this->request->isBlockable()) {
            return $tag;
        }

        $category = \wp_scripts()->get_data($handle, self::SCRIPT_CATEGORY);

        return $this->blocker->block($tag, $this->vendors->create(), \is_string($category) ? $category : '');
    }
}
