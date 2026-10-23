<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\StringTranslations;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Registers the saved texts in WPML and Polylang on every admin request (#137, #181).
 *
 * The 2.x versions registered them only when the settings were saved, and Polylang lists only
 * the strings registered on the current request.
 */
final readonly class StringRegistration implements SubscriberInterface
{
    private const CATEGORY_FIELDS = ['label' => false, 'description' => true];

    public function __construct(
        private Options $options,
        private StringTranslations $translations,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'admin_init' => 'register';
    }

    public function register(): void
    {
        foreach (Options::TRANSLATABLE as $option => $name) {
            $this->registerText($name, $this->options->text($option), \in_array($option, Options::MULTILINE, true));
        }

        foreach ($this->options->categoryTexts() as $category => $texts) {
            foreach (self::CATEGORY_FIELDS as $field => $multiline) {
                $this->registerText(
                    $this->options->categoryStringName($category, $field),
                    $texts[$field] ?? '',
                    $multiline
                );
            }
        }
    }

    private function registerText(string $name, string $text, bool $multiline): void
    {
        if ($text === '') {
            return;
        }

        $this->translations->register($name, $text, $multiline);
    }
}
