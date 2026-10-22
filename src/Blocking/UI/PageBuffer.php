<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\UI;

use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker;
use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The opt-in compatibility mode: blocks the vendors in the whole page, also the scripts printed by the theme.
 *
 * It opens its own buffer when the template is chosen and leaves the other buffers alone,
 * requests that do not load a template, like a script that includes wp-load.php, are never buffered.
 */
final readonly class PageBuffer implements SubscriberInterface
{
    public const OPTION = 'all_block';

    public function __construct(
        private HtmlBlocker $blocker,
        private VendorsFactory $vendors,
        private BlockableRequest $request,
        private Options $options,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        // After the redirects, right before the template is loaded.
        yield 'template_redirect' => [
            self::CALLBACK => 'start',
            self::PRIORITY => \PHP_INT_MAX,
        ];
    }

    public function start(): void
    {
        if (!$this->options->isChecked(self::OPTION) || !$this->request->isBlockable()) {
            return;
        }

        \ob_start($this->block(...));
    }

    public function block(string $buffer): string
    {
        if (\stripos($buffer, '<html') === false) {
            return $buffer;
        }

        return $this->blocker->block($buffer, $this->vendors->create());
    }
}
