<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Cookies\UI;

use ItalyCookieChoices\Cookies\Application\BuildingCookieTable;
use ItalyCookieChoices\Cookies\Domain\Cookie;
use ItalyCookieChoices\Cookies\UI\CookieTableBlock;
use ItalyCookieChoices\Cookies\UI\CookieTableRenderer;
use ItalyCookieChoices\Cookies\UI\CookieTableShortcode;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CookieTableTest extends WPTestCase
{
    use BuildsContainer;

    public function setUp(): void
    {
        parent::setUp();
        \update_option('blogname', 'My site');
        \update_option(Schema::OPTION, ['active' => '1', 'disabled_vendors' => ['google-analytics']]);
        $this->unregisterTheBlock();
    }

    public function tearDown(): void
    {
        $this->unregisterTheBlock();
        \remove_shortcode(CookieTableShortcode::TAG);
        parent::tearDown();
    }

    public function testTheTableHasACaptionAndTheColumnsForEachCategory(): void
    {
        $html = $this->service(CookieTableRenderer::class)->html();

        $this->assertStringContainsString('<div class="icc-cookie-table">', $html);
        $this->assertStringContainsString('<caption>Necessary</caption>', $html);
        foreach (['Cookie', 'Provider', 'Purpose', 'Duration'] as $column) {
            $this->assertStringContainsString('<th scope="col">' . $column . '</th>', $html);
        }
        $this->assertStringContainsString('<td>icc_consent</td>', $html);
        $this->assertStringContainsString('<td>My site</td>', $html);
    }

    public function testACategoryWithoutCookiesIsLeftOut(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'disabled_vendors' => ['google-analytics', 'hotjar']]);
        \add_action(BuildingCookieTable::class, static function (BuildingCookieTable $event): void {
            foreach ($event->cookies() as $cookie) {
                if ($cookie->category() === 'statistics') {
                    $event->removeCookie($cookie->name());
                }
            }
        });

        $html = $this->service(CookieTableRenderer::class)->html();

        $this->assertStringNotContainsString('<caption>Statistics</caption>', $html);
    }

    public function testTheTextsAreEscaped(): void
    {
        \add_action(BuildingCookieTable::class, static function (BuildingCookieTable $event): void {
            $event->addCookie(new Cookie('<b>x</b>', 'A & B', 'preferences', '<script>alert(1)</script>', '1 year'));
        });

        $html = $this->service(CookieTableRenderer::class)->html();

        $this->assertStringContainsString('<td>&lt;b&gt;x&lt;/b&gt;</td>', $html);
        $this->assertStringContainsString('<td>A &amp; B</td>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testTheShortcodePrintsTheTable(): void
    {
        $this->service(CookieTableShortcode::class)->register();

        $this->assertSame(
            $this->service(CookieTableRenderer::class)->html(),
            \do_shortcode('[' . CookieTableShortcode::TAG . ']')
        );
    }

    public function testTheShortcodeIsNotRegisteredWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, []);

        $this->service(CookieTableShortcode::class)->register();

        $this->assertFalse(\shortcode_exists(CookieTableShortcode::TAG));
    }

    public function testTheBlockIsRegisteredFromItsMetadata(): void
    {
        $this->makeBlock()->register();

        $this->assertTrue(\WP_Block_Type_Registry::get_instance()->is_registered(CookieTableBlock::NAME));
    }

    public function testTheBlockPrintsTheTableInItsWrapper(): void
    {
        $this->makeBlock()->register();

        $html = \render_block(['blockName' => CookieTableBlock::NAME, 'attrs' => [], 'innerBlocks' => []]);

        $this->assertStringStartsWith('<div class="wp-block-italy-cookie-choices-cookie-table">', $html);
        $this->assertStringContainsString('<td>icc_consent</td>', $html);
    }

    public function testNoBlockIsRegisteredWhenTheAssetsAreNotBuilt(): void
    {
        $this->makeBlock('/not/built/')->register();

        $this->assertFalse(\WP_Block_Type_Registry::get_instance()->is_registered(CookieTableBlock::NAME));
    }

    private function makeBlock(string $buildPath = ''): CookieTableBlock
    {
        $fixtures = \codecept_data_dir('consent-build/');
        $this->assertIsString($fixtures);

        return new CookieTableBlock(
            $this->service(CookieTableRenderer::class),
            $buildPath === '' ? $fixtures : $buildPath,
            '/plugin/languages'
        );
    }

    private function unregisterTheBlock(): void
    {
        if (\WP_Block_Type_Registry::get_instance()->is_registered(CookieTableBlock::NAME)) {
            \unregister_block_type(CookieTableBlock::NAME);
        }
    }
}
