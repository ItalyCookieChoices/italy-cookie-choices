<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Settings\Options;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The block italy-cookie-choices/preferences-button, built from assets/src/blocks/preferences-button.
 *
 * Its metadata comes from the built block.json, the button is printed here so the default label is translated.
 */
final readonly class PreferencesButtonBlock implements SubscriberInterface
{
    public const NAME = 'italy-cookie-choices/preferences-button';

    private const FOLDER = 'preferences-button';

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     */
    public function __construct(
        private PreferencesButton $button,
        private Options $options,
        private string $buildPath,
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

        \register_block_type($folder, ['render_callback' => $this->render(...)]);
    }

    /**
     * @param array<array-key, mixed> $attributes
     */
    public function render(array $attributes): string
    {
        if (!$this->options->isActive()) {
            return '';
        }

        $wrapperAttributes = \get_block_wrapper_attributes([
            'class' => PreferencesButton::CLASS_NAME . ' wp-element-button',
        ]);

        return $this->button->html($this->label($attributes), $wrapperAttributes);
    }

    /**
     * @param array<array-key, mixed> $attributes
     */
    private function label(array $attributes): string
    {
        $label = $attributes['label'] ?? '';

        return \is_string($label) ? \wp_strip_all_tags($label) : '';
    }
}
