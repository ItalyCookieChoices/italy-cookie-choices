<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings\UI;

use ItalyCookieChoices\Settings\UI\PluginActionLinks;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PluginActionLinksTest extends WPTestCase
{
    use BuildsContainer;

    public function testTheSettingsAndTheDocumentationComeFirst(): void
    {
        $links = $this->service(PluginActionLinks::class)->links(['deactivate' => '<a>Deactivate</a>']);

        $this->assertSame(['settings', 'documentation', 'deactivate'], \array_keys($links));
        $this->assertSame(
            '<a href="' . \admin_url('admin.php?page=italy-cookie-choices') . '">Settings</a>',
            $links['settings']
        );
        $this->assertSame(
            '<a href="https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki" target="_blank" '
            . 'rel="noopener">Documentation</a>',
            $links['documentation']
        );
    }

    public function testInvalidLinksAreIgnored(): void
    {
        $this->assertSame(
            ['settings', 'documentation'],
            \array_keys($this->service(PluginActionLinks::class)->links(null))
        );
    }
}
