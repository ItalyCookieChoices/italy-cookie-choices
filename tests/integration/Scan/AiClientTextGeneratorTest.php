<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\TextGenerator;
use ItalyCookieChoices\Scan\Infrastructure\AiClientTextGenerator;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class AiClientTextGeneratorTest extends WPTestCase
{
    use BuildsContainer;

    public function testItIsTheGeneratorOfThePlugin(): void
    {
        $this->assertInstanceOf(AiClientTextGenerator::class, $this->service(TextGenerator::class));
    }

    public function testItIsNotAvailableWhenTheSiteTurnsTheAiOff(): void
    {
        \add_filter('wp_supports_ai', '__return_false');

        $this->assertFalse((new AiClientTextGenerator())->isAvailable());
    }

    public function testItIsNotAvailableWithoutAProvider(): void
    {
        // The test site has no AI connector.
        $this->assertFalse((new AiClientTextGenerator())->isAvailable());
    }

    public function testItFailsWhenItIsNotAvailable(): void
    {
        \add_filter('wp_supports_ai', '__return_false');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No AI provider generates text on this site.');

        (new AiClientTextGenerator())->json('Instruction', 'Prompt', ['type' => 'object']);
    }
}
