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
}
