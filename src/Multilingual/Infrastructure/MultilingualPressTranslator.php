<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\Infrastructure;

use Inpsyde\MultilingualPress\Module\AutomaticTranslation\Translator\TranslatorManager\TranslatorManagerInterface;
use Inpsyde\MultilingualPress\Module\AutomaticTranslation\Translator\Translators\TranslatorInterface;
use ItalyCookieChoices\Multilingual\Application\Translator;

/**
 * The automatic translation of MultilingualPress, with the first translation service set up
 * that translates between the two languages.
 *
 * The services set up are read from the module of the automatic translation, the text is translated
 * with the public translate() function. Without MultilingualPress, or without a service, nothing is translated.
 */
final readonly class MultilingualPressTranslator implements Translator
{
    private const RESOLVE = 'Inpsyde\\MultilingualPress\\resolve';
    private const TRANSLATE = 'Inpsyde\\MultilingualPress\\translate';

    public function canTranslate(string $sourceLanguage, string $targetLanguage): bool
    {
        return $this->translatorFor($sourceLanguage, $targetLanguage) !== '';
    }

    public function translate(string $sourceLanguage, string $targetLanguage, string $text): string
    {
        $translator = $this->translatorFor($sourceLanguage, $targetLanguage);
        if ($translator === '' || !\function_exists(self::TRANSLATE)) {
            throw new \RuntimeException(
                \__('No translation service translates between the languages of the sites.', 'italy-cookie-choices')
            );
        }

        return \Inpsyde\MultilingualPress\translate($sourceLanguage, $targetLanguage, $translator, $text);
    }

    /**
     * @return string The id of the translation service, empty when there is none.
     */
    private function translatorFor(string $sourceLanguage, string $targetLanguage): string
    {
        if ($sourceLanguage === '' || $targetLanguage === '') {
            return '';
        }

        foreach ($this->activeTranslators() as $translator) {
            if ($translator->isLanguageCombinationSupported($sourceLanguage, $targetLanguage)) {
                return $translator->id();
            }
        }

        return '';
    }

    /**
     * @return list<TranslatorInterface>
     */
    private function activeTranslators(): array
    {
        if (!\function_exists(self::RESOLVE) || !\interface_exists(TranslatorManagerInterface::class)) {
            return [];
        }

        try {
            $manager = \Inpsyde\MultilingualPress\resolve(TranslatorManagerInterface::class);
        } catch (\Throwable) {
            return [];
        }

        if (!$manager instanceof TranslatorManagerInterface) {
            return [];
        }

        return \array_values(\array_filter(
            $manager->activeTranslators(),
            static fn(mixed $translator): bool => $translator instanceof TranslatorInterface
        ));
    }
}
