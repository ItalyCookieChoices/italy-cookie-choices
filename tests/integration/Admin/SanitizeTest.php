<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Admin;

use Italy_Cookie_Choices\Admin\Sanitize;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * The Sanitize class is not used by the plugin, Settings::sanitize_callback() is.
 */
final class SanitizeTest extends WPTestCase
{
    public function testItSanitizesTheInput(): void
    {
        $sut = new Sanitize(['cookie_name' => 'name', 'cookie_value' => 'value']);

        $this->assertSame(
            [
                'active' => '1',
                'text' => 'bold',
                'banner_bg' => '#fff',
                'banner_text_color' => '#000',
                'cookie_name' => 'name',
                'cookie_value' => 'value',
                'custom_script_block' => '<script></script>',
            ],
            $sut->sanitize_callback([
                'active' => '1',
                'text' => '<b>bold</b>',
                'cookie_name' => '<i>name</i>',
                'cookie_value' => 'value',
                'custom_script_block' => '<script></script>',
            ])
        );
    }

    /**
     * Current behaviour: the default cookie name is read from an undefined property.
     */
    public function testEmptyCookieNameReadsAnUndefinedProperty(): void
    {
        $notices = [];
        \set_error_handler(static function (int $level, string $message) use (&$notices): bool {
            $notices[] = $message;
            return true;
        }, E_NOTICE | E_WARNING);

        try {
            new Sanitize([]);
        } finally {
            \restore_error_handler();
        }

        $this->assertSame(
            [
                'Undefined property: Italy_Cookie_Choices\Admin\Sanitize::$cookieName',
                'Undefined property: Italy_Cookie_Choices\Admin\Sanitize::$cookieVal',
            ],
            $notices
        );
    }
}
