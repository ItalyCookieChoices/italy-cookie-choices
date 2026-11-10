<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\UI;

use ItalyCookieChoices\Blocking\UI\VendorAbilities;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class VendorAbilitiesTest extends WPTestCase
{
    use BuildsContainer;

    private const LIST = 'italy-cookie-choices/list-vendors';
    private const SWITCH = 'italy-cookie-choices/switch-vendor';
    private const ADD = 'italy-cookie-choices/add-custom-vendor';

    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function testItRegistersWithTheAbilities(): void
    {
        $this->assertSame(
            ['wp_abilities_api_init' => 'register'],
            \iterator_to_array($this->service(VendorAbilities::class)->getSubscribedEvents())
        );
    }

    public function testTheAdministratorsListTheVendors(): void
    {
        $this->logIn('administrator');
        $ability = $this->ability(self::LIST);

        $vendors = $ability->execute();

        $this->assertIsArray($vendors);
        $this->assertContains(
            ['id' => 'youtube', 'name' => 'YouTube', 'category' => 'marketing', 'preset' => true, 'enabled' => true],
            $vendors
        );
        $this->assertSame(Abilities::CATEGORY, $ability->get_category());
        $this->assertTrue($this->annotations($ability)['readonly'] ?? null);
    }

    public function testTheAdministratorsTurnAPresetOffAndOn(): void
    {
        $this->logIn('administrator');
        $ability = $this->ability(self::SWITCH);

        $this->assertSame(
            ['id' => 'youtube', 'enabled' => false],
            $ability->execute(['id' => 'youtube', 'enabled' => false])
        );
        $this->assertContains('youtube', $this->disabled());

        $ability->execute(['id' => 'youtube', 'enabled' => true]);
        $this->assertNotContains('youtube', $this->disabled());

        $annotations = $this->annotations($ability);
        $this->assertFalse($annotations['readonly'] ?? null);
        $this->assertFalse($annotations['destructive'] ?? null);
        $this->assertTrue($annotations['idempotent'] ?? null);
    }

    public function testAVendorThatIsNotAPresetIsAnError(): void
    {
        $this->logIn('administrator');

        $error = $this->ability(self::SWITCH)->execute(['id' => 'tracker', 'enabled' => false]);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('italy_cookie_choices_invalid_vendor', $error->get_error_code());
        $this->assertSame('There is no common vendor with the id "tracker".', $error->get_error_message());
    }

    public function testTheAdministratorsAddACustomVendor(): void
    {
        $this->logIn('administrator');
        $ability = $this->ability(self::ADD);

        $vendor = $ability->execute([
            'name' => 'Chat Widget',
            'category' => 'preferences',
            'patterns' => ['chat.example.com'],
        ]);

        $expected = [
            'id' => 'chatwidget',
            'name' => 'Chat Widget',
            'category' => 'preferences',
            'patterns' => ['chat.example.com'],
            'cookies' => [],
        ];
        $this->assertSame($expected, $vendor);
        $this->assertSame([$expected], $this->saved()['custom_vendors'] ?? null);
        $annotations = $this->annotations($ability);
        $this->assertFalse($annotations['destructive'] ?? null);
        $this->assertFalse($annotations['idempotent'] ?? null);
    }

    public function testAnInvalidCustomVendorIsAnError(): void
    {
        $this->logIn('administrator');

        $error = $this->ability(self::ADD)->execute([
            'name' => 'YouTube',
            'category' => 'marketing',
            'patterns' => ['a.example.com'],
        ]);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('italy_cookie_choices_invalid_vendor', $error->get_error_code());
    }

    public function testTheOtherUsersCannotUseTheVendorAbilities(): void
    {
        $this->logIn('editor');

        $this->assertInstanceOf(\WP_Error::class, $this->ability(self::LIST)->execute());
        $this->assertInstanceOf(
            \WP_Error::class,
            $this->ability(self::SWITCH)->execute(['id' => 'youtube', 'enabled' => false])
        );
        $this->assertInstanceOf(
            \WP_Error::class,
            $this->ability(self::ADD)->execute(['name' => 'Chat', 'category' => 'marketing', 'patterns' => ['a']])
        );
        $this->assertArrayNotHasKey('disabled_vendors', $this->saved());
        $this->assertArrayNotHasKey('custom_vendors', $this->saved());
    }

    private function ability(string $name): \WP_Ability
    {
        $ability = \wp_get_ability($name);
        $this->assertInstanceOf(\WP_Ability::class, $ability);

        return $ability;
    }

    /**
     * @phpstan-impure
     * @return array<array-key, mixed>
     */
    private function saved(): array
    {
        $saved = \get_option(Schema::OPTION);
        $this->assertIsArray($saved);

        return $saved;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function disabled(): array
    {
        $disabled = $this->saved()['disabled_vendors'] ?? [];
        $this->assertIsArray($disabled);

        return $disabled;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function annotations(\WP_Ability $ability): array
    {
        $annotations = $ability->get_meta_item('annotations');
        $this->assertIsArray($annotations);

        return $annotations;
    }

    private function logIn(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }
}
