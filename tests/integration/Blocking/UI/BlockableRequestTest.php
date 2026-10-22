<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\UI;

use ItalyCookieChoices\Blocking\UI\BlockableRequest;
use ItalyCookieChoices\Settings\Options;
use ItalyCookieChoices\Settings\Schema;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BlockableRequestTest extends WPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        \update_option(Schema::OPTION, ['active' => '1']);
    }

    public function tearDown(): void
    {
        unset($_SERVER['HTTP_ACCEPT']);
        parent::tearDown();
    }

    public function testThePagesAreBlockable(): void
    {
        $this->go_to(\home_url('/'));

        $this->assertTrue($this->makeSut()->isBlockable());
    }

    public function testNothingIsBlockedWhenThePluginIsNotActive(): void
    {
        \update_option(Schema::OPTION, []);

        $this->assertFalse($this->makeSut()->isBlockable());
    }

    public function testTheFeedsAreNeverBlocked(): void
    {
        $this->go_to(\get_feed_link());

        $this->assertFalse($this->makeSut()->isBlockable());
    }

    public function testTheAdminIsNeverBlocked(): void
    {
        \set_current_screen('dashboard');

        $this->assertFalse($this->makeSut()->isBlockable());

        \set_current_screen('front');
    }

    public function testTheJsonRequestsAreNeverBlocked(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $this->assertFalse($this->makeSut()->isBlockable());
    }

    private function makeSut(): BlockableRequest
    {
        return new BlockableRequest(new Options());
    }
}
