<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\Application;

use ItalyCookieChoices\Consent\Application\ConsentPolicyFactory;
use ItalyCookieChoices\ConsentLog\Application\RecordConsent;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;
use ItalyStrap\Event\Dispatcher;
use ItalyStrap\Event\GlobalOrderedListenerProvider;
use ItalyStrap\Event\GlobalState;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class RecordConsentTest extends WPTestCase
{
    private const ID = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';

    private ConsentLog $log;

    /**
     * @var list<ConsentRecord>
     */
    private array $records = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->records = [];
        $this->log = new class ($this->records) implements ConsentLog {
            /**
             * @param list<ConsentRecord> $records
             */
            public function __construct(private array &$records)
            {
            }

            public function add(ConsentRecord $record): void
            {
                $this->records[] = $record;
            }

            public function deleteBefore(\DateTimeImmutable $date): int
            {
                return 0;
            }

            public function all(): iterable
            {
                return $this->records;
            }
        };
    }

    public function testItLogsTheChoiceInThePolicyOrder(): void
    {
        $now = new \DateTimeImmutable('2026-10-22T10:00:00+00:00');

        $record = ($this->makeSut())(self::ID, ['marketing', 'necessary'], '1', $now);

        $this->assertSame([$record], $this->records);
        $this->assertSame(['necessary', 'marketing'], $record->categories());
        $this->assertSame($now, $record->recordedAt());
    }

    public function testItLogsAWithdrawal(): void
    {
        $record = ($this->makeSut())(self::ID, [], '1', new \DateTimeImmutable());

        $this->assertTrue($record->isWithdrawal());
    }

    /**
     * @return iterable<string, array{string, list<string>, string}>
     */
    public function invalidChoices(): iterable
    {
        yield 'another policy version' => [self::ID, ['necessary'], '2'];
        yield 'unknown category' => [self::ID, ['necessary', 'social'], '1'];
        yield 'invalid id' => ['visitor-1', ['necessary'], '1'];
        yield 'empty version' => [self::ID, ['necessary'], ''];
    }

    /**
     * @dataProvider invalidChoices
     * @param list<string> $categories
     */
    public function testAChoiceThatDoesNotFitThePolicyIsNotLogged(string $id, array $categories, string $version): void
    {
        try {
            ($this->makeSut())($id, $categories, $version, new \DateTimeImmutable());
            $this->fail('The choice was logged');
        } catch (\InvalidArgumentException) {
            $this->assertSame([], $this->records);
        }
    }

    private function makeSut(): RecordConsent
    {
        return new RecordConsent(
            new ConsentPolicyFactory(new Dispatcher(new GlobalOrderedListenerProvider(), new GlobalState())),
            $this->log
        );
    }
}
