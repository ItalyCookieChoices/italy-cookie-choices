<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Dashboard\UI;

use ItalyCookieChoices\Dashboard\UI\SiteStatus;
use ItalyCookieChoices\Dashboard\UI\StatusAbility;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\RunsAbilities;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class StatusAbilityTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;
    use RunsAbilities;

    private const NAME = 'italy-cookie-choices/get-status';

    public function testItRegistersWithTheAbilities(): void
    {
        $this->assertSame(
            ['wp_abilities_api_init' => 'register'],
            \iterator_to_array($this->service(StatusAbility::class)->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsGetTheStatusOfTheDashboardWidget(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $this->logInAs('administrator');
        $ability = $this->ability(self::NAME);

        $status = $ability->execute();

        $expected = $this->service(SiteStatus::class)->items();
        $this->assertSame(['items' => $expected], $status);
        $items = \array_column($expected, 'state', 'id');
        $this->assertSame(SiteStatus::OK, $items['banner'] ?? null);
        $this->assertSame(SiteStatus::WARNING, $items['policy'] ?? null);
        $this->assertTrue($this->annotations($ability)['readonly'] ?? null);
    }

    public function testTheOtherUsersCannotGetIt(): void
    {
        $this->logInAs('editor');

        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::NAME)->execute());
    }
}
