<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings;

use ItalyCookieChoices\Settings\LegacySnippets;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LegacySnippetsTest extends WPTestCase
{
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public function snippets(): iterable
    {
        yield 'empty' => ['', []];
        yield 'script file' => ['<script src="https://tracker.example.com/t.js?id=1#a">'
            . '</script>', ['tracker.example.com/t.js']];
        yield 'protocol relative file' => ["<script src='//tracker.example.com/t.js'>"
            . "</script>", ['tracker.example.com/t.js']];
        yield 'frame' => ['<iframe width="1" SRC = "http://widgets.example.com/w">'
            . '</iframe>', ['widgets.example.com/w']];
        yield 'inline script' => ['<script>window.tracker = true;</script>', ['window.tracker = true;']];
        yield 'wildcards keep the longest piece' => [
            "<script>var a = 1;<---------SOMETHING--------->trackerInit('id');</script>",
            ["trackerInit('id');"],
        ];
        yield 'wildcard inside a tag' => ['<script id="tracker"<---------SOMETHING---------></script>', []];
        yield 'too short' => ['<script>a=1</script>', []];
        yield 'separated snippets' => [
            "<script src=\"https://a.example.com/a.js\"></script>\n<---------SEP--------->\n"
            . "<script>window.b = true;</script>\r\n<---------SEP--------->\n\n"
            . '<script src="https://a.example.com/a.js"></script>',
            ['a.example.com/a.js', 'window.b = true;'],
        ];
    }

    /**
     * @dataProvider snippets
     * @param list<string> $expected
     */
    public function testItReadsTheTextsOfTheSnippets(string $snippets, array $expected): void
    {
        $this->assertSame($expected, (new LegacySnippets())->texts($snippets));
    }
}
