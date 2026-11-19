<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Migration\Application;

use ItalyCookieChoices\Migration\Application\Conversions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConversionsTest extends WPTestCase
{
    public function testTheCustomCssIsKeptInAComment(): void
    {
        $css = (new Conversions())->commentedCss('Other', '.other { color: red; } /* mine */');

        $this->assertStringStartsWith('/* The custom CSS of Other', $css);
        $this->assertStringContainsString('.other { color: red; } /* mine * /', $css);
        $this->assertStringEndsWith('*/', $css);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cssWithoutRules(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces' => ["  \n\t "];
        yield 'comments' => ['/* Container */ /* Logo */'];
        yield 'empty rules' => ["/* Container */\n.other{}\n/* Logo */\n.other .logo{ }"];
        yield 'empty media query' => ['@media (max-width: 768px) { /* Mobile */ .other {} }'];
    }

    /**
     * @dataProvider cssWithoutRules
     */
    public function testTheCustomCssWithoutDeclarationsIsNotImported(string $css): void
    {
        $this->assertSame('', (new Conversions())->commentedCss('Other', $css));
    }
}
