<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Without a multilingual plugin the helpers fall back to the plain WordPress behaviour.
 */
final class LanguageFunctionsTest extends WPTestCase
{
    public function testGetLanguageReturnsTheLocale(): void
    {
        \add_filter('locale', static fn(): string => 'it_IT');

        $this->assertSame('it_IT', \get_language());
    }

    public function testGetStringReturnsTheGivenValue(): void
    {
        $this->assertSame(
            'Banner value',
            \get_string('Italy Cookie Choices', 'Banner text', 'Banner value')
        );
    }

    public function testRegisterStringDoesNothing(): void
    {
        $this->assertNull(\register_string('Italy Cookie Choices', 'Banner text', 'Banner value'));
    }

    public function testDeregisterStringDoesNothing(): void
    {
        $this->assertNull(\deregister_string('Italy Cookie Choices', 'Banner text'));
    }
}
