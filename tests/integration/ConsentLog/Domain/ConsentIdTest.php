<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\Domain;

use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentIdTest extends WPTestCase
{
    public function testItIsAVersion4Uuid(): void
    {
        $id = new ConsentId('3F2504E0-4F89-41D3-9A0C-0305E82C3301');

        $this->assertSame('3f2504e0-4f89-41d3-9a0c-0305e82c3301', $id->toString());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'not a uuid' => ['visitor-1'];
        yield 'version 1' => ['3f2504e0-4f89-11d3-9a0c-0305e82c3301'];
        yield 'wrong variant' => ['3f2504e0-4f89-41d3-7a0c-0305e82c3301'];
        yield 'with braces' => ['{3f2504e0-4f89-41d3-9a0c-0305e82c3301}'];
        yield 'an email' => ['someone@example.com'];
    }

    /**
     * @dataProvider invalidIds
     */
    public function testOtherValuesAreRejected(string $id): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConsentId($id);
    }
}
