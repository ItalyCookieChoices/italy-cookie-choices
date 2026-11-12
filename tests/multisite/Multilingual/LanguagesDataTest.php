<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Multilingual;

use ItalyCookieChoices\Multilingual\UI\LanguagesData;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeConnectedSites;
use ItalyCookieChoices\Tests\Support\Multilingual\FakeTranslator;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class LanguagesDataTest extends WPTestCase
{
    public function testItListsTheConnectedSitesTheUserAdministers(): void
    {
        $italian = $this->site('Italiano');
        $german = $this->site('Deutsch');
        $spanish = $this->site('Español');
        $userId = self::factory()->user->create(['role' => 'subscriber']);
        $this->assertIsInt($userId);
        \add_user_to_blog($italian, $userId, 'administrator');
        \add_user_to_blog($german, $userId, 'administrator');
        \add_user_to_blog($spanish, $userId, 'editor');
        \wp_set_current_user($userId);
        $sut = new LanguagesData(
            new FakeConnectedSites([$italian => 'it-IT', $german => 'de-DE', $spanish => 'es-ES']),
            new FakeTranslator()
        );

        $this->assertSame(
            [
                'siteId' => $italian,
                'language' => 'it-IT',
                'sites' => [
                    [
                        'id' => $german,
                        'name' => 'Deutsch',
                        'url' => \get_home_url($german, '/'),
                        'language' => 'de-DE',
                        'canTranslate' => true,
                    ],
                ],
            ],
            $sut->forSite($italian)
        );
    }

    public function testASiteWithoutConnectionsHasNoLanguages(): void
    {
        $sut = new LanguagesData(new FakeConnectedSites(), new FakeTranslator(false));

        $siteId = $this->site('Alone');

        $this->assertSame(['siteId' => $siteId, 'language' => '', 'sites' => []], $sut->forSite($siteId));
    }

    private function site(string $title): int
    {
        $siteId = self::factory()->blog->create(['title' => $title]);
        $this->assertIsInt($siteId);

        return $siteId;
    }
}
