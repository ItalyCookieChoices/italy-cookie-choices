<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Cookies\UI;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The block italy-cookie-choices/cookie-table, built from assets/src/blocks/cookie-table.
 *
 * Its metadata comes from the built block.json, the table is printed here: it changes with the vendors.
 */
final readonly class CookieTableBlock implements SubscriberInterface
{
    public const NAME = 'italy-cookie-choices/cookie-table';

    private const FOLDER = 'cookie-table';

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $languagesPath The folder of the translations bundled with the plugin.
     */
    public function __construct(
        private CookieTableRenderer $renderer,
        private string $buildPath,
        private string $languagesPath,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'init' => 'register';
    }

    public function register(): void
    {
        $folder = $this->buildPath . self::FOLDER;
        if (!\is_readable($folder . '/block.json')) {
            return;
        }

        $block = \register_block_type($folder, ['render_callback' => $this->render(...)]);
        if ($block === false) {
            return;
        }

        foreach ($block->editor_script_handles as $handle) {
            \wp_set_script_translations($handle, TextDomain::DOMAIN, $this->languagesPath);
        }
    }

    public function render(): string
    {
        return \sprintf('<div %s>%s</div>', \get_block_wrapper_attributes(), $this->renderer->html());
    }
}
