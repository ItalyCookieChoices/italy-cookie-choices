<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Settings\UI;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyCookieChoices\Settings\UI\NetworkSettingsPage;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class NetworkSettingsPageTest extends WPTestCase
{
    use BuildsContainer;

    public function tearDown(): void
    {
        \delete_site_option(NetworkSettings::OPTION);
        unset($_POST['shared'], $_POST['_wpnonce'], $_REQUEST['_wpnonce']);
        parent::tearDown();
    }

    public function testItAddsThePageToTheNetworkSettings(): void
    {
        $this->assertSame(
            [
                'network_admin_menu' => 'addPage',
                'network_admin_edit_' . NetworkSettingsPage::ACTION => 'save',
            ],
            \iterator_to_array($this->sut()->getSubscribedEvents())
        );

        $this->logInAsSuperAdmin();
        $this->sut()->addPage();

        global $submenu;
        $this->assertIsArray($submenu);
        $settings = $submenu['settings.php'] ?? [];
        $this->assertIsArray($settings);
        $this->assertContains(NetworkSettingsPage::SLUG, \array_column($settings, 2));
    }

    public function testThePageShowsTheSwitch(): void
    {
        (new NetworkSettings())->share(true);

        \ob_start();
        $this->sut()->render();
        $html = (string) \ob_get_clean();

        $processor = new \WP_HTML_Tag_Processor($html);
        $this->assertTrue($processor->next_tag(['tag_name' => 'form']));
        $this->assertSame(
            \network_admin_url('edit.php?action=' . NetworkSettingsPage::ACTION),
            $processor->get_attribute('action')
        );
        $this->assertTrue($processor->next_tag(['tag_name' => 'input', 'class_name' => 'icc-network-shared']));
        $this->assertSame('shared', $processor->get_attribute('name'));
        $this->assertNotNull($processor->get_attribute('checked'));
        $this->assertStringContainsString('name="_wpnonce"', $html);
        $this->assertStringContainsString('Use the settings of the main site on every site', $html);
    }

    public function testTheSuperAdminSharesTheSettings(): void
    {
        $this->logInAsSuperAdmin();
        $_POST['shared'] = '1';
        $_REQUEST['_wpnonce'] = \wp_create_nonce(NetworkSettingsPage::ACTION);

        $this->assertSame(
            \add_query_arg(
                ['page' => NetworkSettingsPage::SLUG, 'updated' => 'true'],
                \network_admin_url('settings.php')
            ),
            $this->redirectOfTheSave()
        );
        $this->assertTrue((new NetworkSettings())->sharesTheMainSite());

        unset($_POST['shared']);
        $this->redirectOfTheSave();
        $this->assertFalse((new NetworkSettings())->sharesTheMainSite());
    }

    public function testTheSaveNeedsTheNonce(): void
    {
        $this->logInAsSuperAdmin();
        $_POST['shared'] = '1';
        $_REQUEST['_wpnonce'] = 'invalid';

        $this->expectException(\WPDieException::class);

        $this->sut()->save();
    }

    public function testOnlyTheSuperAdminsSave(): void
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
        $_POST['shared'] = '1';
        $_REQUEST['_wpnonce'] = \wp_create_nonce(NetworkSettingsPage::ACTION);

        try {
            $this->sut()->save();
            $this->fail('The settings were saved.');
        } catch (\WPDieException) {
            $this->assertFalse((new NetworkSettings())->sharesTheMainSite());
        }
    }

    private function redirectOfTheSave(): string
    {
        // Stops the save before its exit.
        $filter = static function (string $location): string {
            if ($location !== '') {
                throw new \RuntimeException($location);
            }

            return $location;
        };
        \add_filter('wp_redirect', $filter);

        try {
            $this->sut()->save();
        } catch (\RuntimeException $redirect) {
            return $redirect->getMessage();
        } finally {
            \remove_filter('wp_redirect', $filter);
        }

        $this->fail('The save did not redirect.');
    }

    private function logInAsSuperAdmin(): void
    {
        $userId = self::factory()->user->create(['role' => 'administrator']);
        $this->assertIsInt($userId);
        \grant_super_admin($userId);
        \wp_set_current_user($userId);
    }

    private function sut(): NetworkSettingsPage
    {
        return $this->service(NetworkSettingsPage::class);
    }
}
