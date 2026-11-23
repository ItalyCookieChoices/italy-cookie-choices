<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings\UI;

use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyCookieChoices\Settings\UI\SettingsAbility;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use ItalyCookieChoices\Tests\Support\RunsAbilities;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class AbilitiesTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;
    use RunsAbilities;

    private const GET_SETTINGS = 'italy-cookie-choices/get-settings';
    private const UPDATE_SETTINGS = 'italy-cookie-choices/update-settings';

    public function tearDown(): void
    {
        unset($GLOBALS['wp_rest_server']);
        parent::tearDown();
    }

    public function testTheCategoryIsRegisteredWithTheAbilityCategories(): void
    {
        $this->assertSame(
            ['wp_abilities_api_categories_init' => 'registerCategory'],
            \iterator_to_array($this->service(Abilities::class)->getSubscribedEvents())
        );

        $category = \wp_get_ability_category(Abilities::CATEGORY);

        $this->assertInstanceOf(\WP_Ability_Category::class, $category);
        $this->assertSame('Cookie Choices', $category->get_label());
    }

    public function testTheSettingsAbilityIsRegisteredWithTheAbilities(): void
    {
        $this->assertSame(
            ['wp_abilities_api_init' => 'register'],
            \iterator_to_array($this->service(SettingsAbility::class)->getSubscribedEvents())
        );
    }

    public function testTheAbilitiesArePublicForTheRestApiAndTheMcpAdapter(): void
    {
        $ability = $this->ability(self::GET_SETTINGS);

        $this->assertSame(Abilities::CATEGORY, $ability->get_category());
        $this->assertTrue($ability->get_meta_item('public'));
        $this->assertTrue($ability->get_meta_item('show_in_rest'));
        $this->assertSame(['public' => true], $ability->get_meta_item('mcp'));
    }

    public function testTheAdministratorsReadTheSettings(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Hello']);
        $this->logInAs('administrator');
        $ability = $this->ability(self::GET_SETTINGS);

        $settings = $ability->execute();

        $this->assertIsArray($settings);
        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('Hello', $settings['text'] ?? null);
        $this->assertTrue($this->annotations($ability)['readonly'] ?? null);
    }

    public function testTheSettingsLeaveOutTheKeysOutsideTheSchema(): void
    {
        \remove_all_filters('sanitize_option_' . Schema::OPTION);
        \update_option(Schema::OPTION, ['active' => '1', 'unknown' => 'value']);
        $this->logInAs('administrator');

        $settings = $this->ability(self::GET_SETTINGS)->execute();

        $this->assertIsArray($settings);
        $this->assertArrayNotHasKey('unknown', $settings);
    }

    public function testTheOtherUsersCannotReadThem(): void
    {
        $this->logInAs('editor');

        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::GET_SETTINGS)->execute());
    }

    public function testTheAdministratorsUpdateSomeSettings(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'text' => 'Hello', 'consent_log' => '1']);
        $this->logInAs('administrator');
        $ability = $this->ability(self::UPDATE_SETTINGS);

        $settings = $ability->execute(['text' => 'Ciao', 'consent_log' => null, 'reload' => '1']);

        $this->assertIsArray($settings);
        $this->assertSame('1', $settings['active'] ?? null);
        $this->assertSame('Ciao', $settings['text'] ?? null);
        $this->assertSame('1', $settings['reload'] ?? null);
        $this->assertArrayNotHasKey('consent_log', $settings);
        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);
        $this->assertSame('Ciao', $saved['text'] ?? null);
        $this->assertArrayNotHasKey('consent_log', $saved);
        $annotations = $this->annotations($ability);
        $this->assertFalse($annotations['readonly'] ?? null);
        $this->assertTrue($annotations['destructive'] ?? null);
        $this->assertTrue($annotations['idempotent'] ?? null);
    }

    public function testTheUpdateIsCheckedLikeTheSettingsPage(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $this->logInAs('administrator');
        $ability = $this->ability(self::UPDATE_SETTINGS);

        $this->assertInstanceOf(\WP_Error::class, $ability->execute(['unknown' => 'value']));
        $this->assertInstanceOf(\WP_Error::class, $ability->execute(['active' => 'yes']));
        $this->assertInstanceOf(\WP_Error::class, $ability->execute([]));
        $settings = $ability->execute(['text' => '<script>alert(1)</script>Hello']);
        $this->assertIsArray($settings);
        $this->assertSame('alert(1)Hello', $settings['text'] ?? null);
    }

    public function testTheOtherUsersCannotUpdateThem(): void
    {
        $this->logInAs('editor');

        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::UPDATE_SETTINGS)->execute(['text' => 'Hi']));
    }

    public function testTheRestApiRunsTheAbility(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        $this->logInAs('administrator');

        $response = \rest_do_request(
            new \WP_REST_Request('GET', '/wp-abilities/v1/abilities/' . self::GET_SETTINGS . '/run')
        );

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('1', $data['active'] ?? null);
    }
}
