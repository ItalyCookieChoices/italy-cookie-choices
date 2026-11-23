<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI;

use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\ConsentLog\Domain\ConsentId;
use ItalyCookieChoices\ConsentLog\Domain\ConsentLog;
use ItalyCookieChoices\ConsentLog\Domain\ConsentRecord;
use ItalyCookieChoices\ConsentLog\UI\ConsentStatisticsAbility;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\RunsAbilities;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConsentStatisticsAbilityTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;
    use RunsAbilities;

    private const NAME = 'italy-cookie-choices/get-consent-statistics';

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1', 'consent_log' => '1']);
    }

    public function testItRegistersWithTheAbilities(): void
    {
        $this->assertSame(
            ['wp_abilities_api_init' => 'register'],
            \iterator_to_array($this->service(ConsentStatisticsAbility::class)->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsReadTheStatisticsByMonth(): void
    {
        $this->logInAs('administrator');
        $log = $this->service(ConsentLog::class);
        $log->add($this->record(['necessary', 'preferences', 'statistics', 'marketing']));
        $log->add($this->record(['necessary', 'statistics']));
        $ability = $this->ability(self::NAME);

        $statistics = $ability->execute();

        $this->assertIsArray($statistics);
        $this->assertSame(
            [
                ['id' => 'preferences', 'label' => 'Preferences'],
                ['id' => 'statistics', 'label' => 'Statistics'],
                ['id' => 'marketing', 'label' => 'Marketing'],
            ],
            $statistics['categories'] ?? null
        );
        $this->assertIsArray($statistics['months'] ?? null);
        $month = $statistics['months'][0] ?? null;
        $this->assertIsArray($month);
        $this->assertSame(2, $month['choices'] ?? null);
        $this->assertSame(1, $month['acceptAll'] ?? null);
        $this->assertSame(1, $month['partial'] ?? null);
        $this->assertSame(['preferences' => 1, 'statistics' => 2, 'marketing' => 1], $month['categories'] ?? null);
        $this->assertTrue($this->annotations($ability)['readonly'] ?? null);
    }

    public function testAnEmptyLogHasNoMonths(): void
    {
        $this->logInAs('administrator');

        $statistics = $this->ability(self::NAME)->execute();

        $this->assertIsArray($statistics);
        $this->assertSame([], $statistics['months'] ?? null);
    }

    public function testTheOtherUsersCannotReadThem(): void
    {
        $this->logInAs('editor');

        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::NAME)->execute());
    }

    /**
     * @param list<string> $categories
     */
    private function record(array $categories): ConsentRecord
    {
        return new ConsentRecord(
            new ConsentId('3f2504e0-4f89-41d3-9a0c-0305e82c3301'),
            $categories,
            new PolicyVersion('1'),
            new \DateTimeImmutable()
        );
    }
}
