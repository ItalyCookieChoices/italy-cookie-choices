<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Multilingual;

use ItalyCookieChoices\Multilingual\Application\SiteTexts;
use ItalyCookieChoices\Multilingual\UI\ConnectionNotice;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeConnectedSites;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeTranslator;
use ItalyStrap\View\ViewInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ConnectionNoticeTest extends WPTestCase
{
    use BuildsContainer;

    private int $italian = 0;
    private int $german = 0;
    private int $alone = 0;
    private ConnectionNotice $sut;

    public function setUp(): void
    {
        parent::setUp();
        $this->italian = $this->site(['text' => 'Usiamo i cookie']);
        $this->german = $this->site(['banner' => '3']);
        $this->alone = $this->site(['banner' => '3']);
        $sites = new FakeConnectedSites([$this->italian => 'it-IT', $this->german => 'de-DE']);
        $this->sut = new ConnectionNotice(
            new SiteTexts($sites, new FakeTranslator()),
            $sites,
            $this->service(ViewInterface::class)
        );
    }

    public function tearDown(): void
    {
        unset($_REQUEST['_wpnonce']);
        parent::tearDown();
    }

    public function testItListensToTheConnectionsOfMultilingualPress(): void
    {
        $remember = [ConnectionNotice::CALLBACK => 'remember', ConnectionNotice::ACCEPTED_ARGS => 1];

        $this->assertSame(
            [
                'multilingualpress.define_initial_site_settings' => $remember,
                'multilingualpress.update_site_settings' => $remember,
                'admin_notices' => 'show',
                'admin_post_' . ConnectionNotice::ACTION => 'ignore',
            ],
            \iterator_to_array($this->sut->getSubscribedEvents())
        );
    }

    public function testItRemembersTheConnectedSitesWithoutTexts(): void
    {
        $this->sut->remember($this->italian);
        $this->sut->remember($this->alone);

        $this->assertSame('1', \get_blog_option($this->german, ConnectionNotice::OPTION));
        $this->assertFalse(\get_blog_option($this->italian, ConnectionNotice::OPTION));
        $this->assertFalse(\get_blog_option($this->alone, ConnectionNotice::OPTION));
    }

    public function testItAsksTheAdminToFillTheTexts(): void
    {
        $this->sut->remember($this->german);
        \switch_to_blog($this->german);
        $this->logInAs('administrator');

        $html = $this->notice();

        $this->assertStringContainsString('Fill the texts', $html);
        $settingsTab = \admin_url('admin.php?page=italy-cookie-choices&tab=languages');
        $this->assertStringContainsString(\esc_url($settingsTab), $html);
        $this->assertStringContainsString('action=' . ConnectionNotice::ACTION, $html);
        $this->logInAs('editor');
        $this->assertSame('', $this->notice());
        \restore_current_blog();
    }

    public function testTheNoticeEndsWhenTheSiteHasItsTexts(): void
    {
        $this->sut->remember($this->german);
        \switch_to_blog($this->german);
        $this->logInAs('administrator');
        \update_option(Schema::OPTION, ['text' => 'Wir verwenden Cookies']);

        $this->assertSame('', $this->notice());
        $this->assertFalse(\get_option(ConnectionNotice::OPTION));
        \restore_current_blog();
    }

    public function testTheAdminIgnoresTheNotice(): void
    {
        $this->sut->remember($this->german);
        \switch_to_blog($this->german);
        $this->logInAs('administrator');
        $_REQUEST['_wpnonce'] = \wp_create_nonce(ConnectionNotice::ACTION);
        // Stops the ignore before its exit.
        $filter = static function (string $location): string {
            if ($location !== '') {
                throw new \RuntimeException($location);
            }

            return $location;
        };
        \add_filter('wp_redirect', $filter);

        try {
            $this->sut->ignore();
            $this->fail('The ignore did not redirect.');
        } catch (\RuntimeException $redirect) {
            $this->assertSame(\admin_url(), $redirect->getMessage());
        } finally {
            \remove_filter('wp_redirect', $filter);
        }

        $this->assertFalse(\get_option(ConnectionNotice::OPTION));
        \restore_current_blog();
    }

    private function notice(): string
    {
        \ob_start();
        $this->sut->show();

        return (string) \ob_get_clean();
    }

    private function logInAs(string $role): void
    {
        $userId = self::factory()->user->create(['role' => $role]);
        $this->assertIsInt($userId);
        \wp_set_current_user($userId);
    }

    /**
     * @param array<string, mixed> $option
     */
    private function site(array $option): int
    {
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);
        \switch_to_blog($siteId);
        \update_option(Schema::OPTION, $option);
        \restore_current_blog();

        return $siteId;
    }
}
