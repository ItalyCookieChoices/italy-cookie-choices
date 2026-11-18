<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Multisite\Multilingual;

use ItalyCookieChoices\Multilingual\Application\ConnectedSites;
use ItalyCookieChoices\Multilingual\Infrastructure\ActiveConnectedSites;
use ItalyCookieChoices\Multilingual\Infrastructure\MultilingualPressSites;
use ItalyCookieChoices\Multilingual\Infrastructure\MultisiteLanguageSwitcherSites;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class MultisiteLanguageSwitcherSitesTest extends WPTestCase
{
    use BuildsContainer;

    private int $italian = 0;
    private int $german = 0;
    private int $alone = 0;

    public function setUp(): void
    {
        parent::setUp();
        require_once \codecept_root_dir('tests/_support/stubs/msls.php');
        $this->italian = $this->site();
        $this->german = $this->site();
        $this->alone = $this->site();
        $GLOBALS['icc_msls_blogs'] = [$this->italian => 'it_IT', $this->german => 'de_DE'];
    }

    public function tearDown(): void
    {
        unset($GLOBALS['icc_msls_blogs']);
        parent::tearDown();
    }

    public function testTheSitesOfItsCollectionAreConnected(): void
    {
        $sut = new MultisiteLanguageSwitcherSites();

        $this->assertTrue($sut->isActive());
        $this->assertSame([$this->german], $sut->of($this->italian));
        $this->assertSame([$this->italian], $sut->of($this->german));
        $this->assertSame([], $sut->of($this->alone));
    }

    public function testTheLanguageIsTheLocaleAsATag(): void
    {
        $sut = new MultisiteLanguageSwitcherSites();

        $this->assertSame('de-DE', $sut->language($this->german));
        $this->assertSame('', $sut->language($this->alone));
    }

    public function testItFindsTheTranslationOfAPage(): void
    {
        \switch_to_blog($this->italian);
        \update_option('msls_12', ['de_DE' => 34]);
        \restore_current_blog();
        $sut = new MultisiteLanguageSwitcherSites();
        $currentSite = \get_current_blog_id();

        $this->assertSame(34, $sut->translatedPage($this->italian, 12, $this->german));
        $this->assertSame(0, $sut->translatedPage($this->italian, 13, $this->german));
        $this->assertSame(0, $sut->translatedPage($this->italian, 12, $this->alone));
        // The site is restored after the translation is read.
        $this->assertSame($currentSite, \get_current_blog_id());
    }

    public function testMultisiteLanguageSwitcherIsUsedWithoutMultilingualPress(): void
    {
        $this->assertFalse((new MultilingualPressSites())->isActive());
        $sut = $this->service(ConnectedSites::class);

        $this->assertInstanceOf(ActiveConnectedSites::class, $sut);
        $this->assertSame([$this->german], $sut->of($this->italian));
        $this->assertSame('it-IT', $sut->language($this->italian));
    }

    private function site(): int
    {
        $siteId = self::factory()->blog->create();
        $this->assertIsInt($siteId);

        return $siteId;
    }
}
