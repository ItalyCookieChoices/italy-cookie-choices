<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\UI;

use ItalyCookieChoices\Blocking\Application\CustomScripts;
use ItalyCookieChoices\Blocking\Application\VendorsFactory;
use ItalyCookieChoices\Blocking\Domain\CustomScript;
use ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker;
use ItalyStrap\Config\Config;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\View\ViewInterface;

/**
 * Prints the custom scripts in the head and at the end of the body.
 *
 * The code of the necessary category is printed as it is. The other code goes in a template, with its scripts
 * blocked: nothing of it loads, the image of a pixel neither, until the blocking script adds it to the page
 * after the consent to its category.
 */
final readonly class CustomScriptsPrinter implements SubscriberInterface
{
    private const TEMPLATE = 'custom-scripts';

    public function __construct(
        private CustomScripts $scripts,
        private VendorsFactory $vendors,
        private HtmlBlocker $blocker,
        private BlockableRequest $request,
        private ViewInterface $view,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'wp_head' => 'printHead';
        yield 'wp_footer' => 'printBody';
    }

    public function printHead(): void
    {
        $this->print(CustomScript::HEAD);
    }

    public function printBody(): void
    {
        $this->print(CustomScript::BODY);
    }

    private function print(string $position): void
    {
        $scripts = $this->request->isBlockable() ? $this->scripts->in($position) : [];
        if ($scripts === []) {
            return;
        }

        $vendors = $this->vendors->create();
        $blocks = [];
        foreach ($scripts as $script) {
            $blocks[] = $script->runsWithoutConsent()
                ? ['category' => '', 'code' => $script->code()]
                : [
                    'category' => $script->category(),
                    'code' => $this->blocker->block($script->code(), $vendors, $script->category()),
                ];
        }

        echo $this->view->render( // phpcs:ignore -- The code of the site owner, saved with unfiltered_html.
            self::TEMPLATE,
            new Config(['scripts' => $blocks])
        );
    }
}
