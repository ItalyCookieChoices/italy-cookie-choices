<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\UI\AcceptButtonShortcode;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class AcceptButtonShortcodeTest extends WPTestCase
{
    use BuildsOptions;

    public function setUp(): void
    {
        parent::setUp();
        \remove_shortcode(AcceptButtonShortcode::TAG);
        \update_option(Schema::OPTION, ['active' => '1', 'button_text' => 'I agree']);
    }

    public function testItRegistersOnInit(): void
    {
        $this->assertSame(['init' => 'register'], \iterator_to_array($this->makeSut()->getSubscribedEvents()));
    }

    public function testTheButtonAcceptsEveryCategory(): void
    {
        $this->makeSut()->register();

        $this->assertSame(
            '<button type="button" class="icc-accept-button" data-icc-accept-all>I agree</button>',
            \do_shortcode('[accept_button]')
        );
    }

    public function testTheTextAttributeReplacesTheSavedText(): void
    {
        $this->assertSame(
            '<button type="button" class="icc-accept-button" data-icc-accept-all>&lt;b&gt;Yes&lt;/b&gt;</button>',
            $this->makeSut()->render(['text' => '<b>Yes</b>'])
        );
    }

    public function testTheDefaultTextIsUsedWithoutASavedText(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertStringContainsString('>Accept all</button>', $this->makeSut()->render(''));
    }

    public function testTheShortcodeIsNotRegisteredWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, ['button_text' => 'I agree']);

        $this->makeSut()->register();

        $this->assertFalse(\shortcode_exists(AcceptButtonShortcode::TAG));
    }

    private function makeSut(): AcceptButtonShortcode
    {
        return new AcceptButtonShortcode($this->options());
    }
}
