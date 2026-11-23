<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Cookies\UI;

use ItalyCookieChoices\Cookies\UI\SiteCookieAbility;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\RunsAbilities;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class SiteCookieAbilityTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;
    use RunsAbilities;

    private const NAME = 'italy-cookie-choices/add-site-cookie';
    private const COOKIE = [
        'name' => 'wp_lang',
        'category' => 'necessary',
        'duration' => 'Session',
        'purpose' => 'Language',
    ];

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function testItRegistersWithTheAbilities(): void
    {
        $this->assertSame(
            ['wp_abilities_api_init' => 'register'],
            \iterator_to_array($this->service(SiteCookieAbility::class)->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsAddASiteCookie(): void
    {
        $this->logInAs('administrator');
        $ability = $this->ability(self::NAME);

        $this->assertSame(self::COOKIE, $ability->execute(self::COOKIE));

        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);
        $this->assertSame([self::COOKIE], $saved['site_cookies'] ?? null);
        $annotations = $this->annotations($ability);
        $this->assertFalse($annotations['readonly'] ?? null);
        $this->assertFalse($annotations['destructive'] ?? null);
        $this->assertFalse($annotations['idempotent'] ?? null);
    }

    public function testACookieAlreadyThereIsAnError(): void
    {
        $this->logInAs('administrator');
        $ability = $this->ability(self::NAME);
        $ability->execute(self::COOKIE);

        $error = $ability->execute(self::COOKIE);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('italy_cookie_choices_invalid_cookie', $error->get_error_code());
        $this->assertSame('The site cookie "wp_lang" already exists.', $error->get_error_message());
    }

    public function testAnUnknownCategoryIsAnError(): void
    {
        $this->logInAs('administrator');

        $error = $this->ability(self::NAME)->execute([...self::COOKIE, 'category' => 'analytics']);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame(
            'There is no category "analytics": necessary, preferences, statistics, marketing.',
            $error->get_error_message()
        );
    }

    public function testACookieNeedsAName(): void
    {
        $this->logInAs('administrator');

        $error = $this->ability(self::NAME)->execute([...self::COOKIE, 'name' => '  ']);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('The cookie needs a name.', $error->get_error_message());
    }

    public function testTheOtherUsersCannotAddOne(): void
    {
        $this->logInAs('editor');

        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::NAME)->execute(self::COOKIE));
    }
}
