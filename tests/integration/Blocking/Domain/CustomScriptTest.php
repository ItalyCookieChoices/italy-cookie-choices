<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Domain;

use ItalyCookieChoices\Blocking\Domain\CustomScript;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CustomScriptTest extends WPTestCase
{
    public function testACustomScriptDescribesItself(): void
    {
        $script = new CustomScript('Chat', 'preferences', CustomScript::HEAD, ' <script>chat();</script> ');

        $this->assertSame('Chat', $script->name());
        $this->assertSame('preferences', $script->category());
        $this->assertSame(CustomScript::HEAD, $script->position());
        $this->assertSame('<script>chat();</script>', $script->code());
        $this->assertFalse($script->runsWithoutConsent());
    }

    public function testTheScriptsOfTheNecessaryCategoryRunWithoutConsent(): void
    {
        $script = new CustomScript('Site', 'necessary', CustomScript::BODY, '<script>site();</script>');

        $this->assertTrue($script->runsWithoutConsent());
    }

    public function testACustomScriptNeedsCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CustomScript('Empty', 'statistics', CustomScript::BODY, ' ');
    }

    public function testACustomScriptNeedsAValidCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CustomScript('Chat', 'Not Valid', CustomScript::BODY, '<script></script>');
    }

    public function testACustomScriptNeedsAPosition(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CustomScript('Chat', 'statistics', 'footer', '<script></script>');
    }
}
