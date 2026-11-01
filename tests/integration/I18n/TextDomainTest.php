<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\I18n;

use ItalyCookieChoices\I18n\TextDomain;
use ItalyStrap\Event\SubscriberInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class TextDomainTest extends WPTestCase
{
    public function testItLoadsOnInitAfterTheDefaultPriority(): void
    {
        $sut = new TextDomain('italy-cookie-choices/languages');

        $this->assertSame(
            [
                'init' => [
                    SubscriberInterface::CALLBACK => 'load',
                    SubscriberInterface::PRIORITY => 100,
                ],
            ],
            \iterator_to_array($sut->getSubscribedEvents())
        );
    }

    public function testLoadUsesTheLanguagesFolder(): void
    {
        \switch_to_locale('it_IT');
        \unload_textdomain(TextDomain::DOMAIN);

        (new TextDomain('italy-cookie-choices/languages'))->load();

        /** @var \WP_Textdomain_Registry $registry */
        $registry = $GLOBALS['wp_textdomain_registry'];
        $this->assertSame(
            WP_PLUGIN_DIR . '/italy-cookie-choices/languages/',
            $registry->get(TextDomain::DOMAIN, 'it_IT')
        );
        \restore_previous_locale();
    }

    /**
     * The 2.x language pack of WordPress.org would hide the strings added later.
     */
    public function testTheBundledTranslationComesBeforeTheLanguagePack(): void
    {
        $pack = \get_temp_dir() . 'italy-cookie-choices-it_IT.l10n.php';
        \file_put_contents($pack, '<?php return ' . \var_export([
            'messages' => ['Accept all' => 'Testo vecchio', 'Only in the pack' => 'Solo nel pacchetto'],
        ], true) . ';');
        // The site language, determine_locale() ignores switch_to_locale().
        $italian = static fn(): string => 'it_IT';
        \add_filter('locale', $italian);
        \unload_textdomain(TextDomain::DOMAIN);

        try {
            (new TextDomain('italy-cookie-choices/languages'))->load();
            // Loaded after, like the language pack in the languages folder of WordPress.
            \load_textdomain(TextDomain::DOMAIN, \str_replace('.l10n.php', '.mo', $pack));

            $this->assertSame('Accetta tutti', \__('Accept all', 'italy-cookie-choices'));
            $this->assertSame('Solo nel pacchetto', \__('Only in the pack', 'italy-cookie-choices'));
        } finally {
            \unlink($pack);
            \unload_textdomain(TextDomain::DOMAIN);
            \remove_filter('locale', $italian);
        }
    }
}
