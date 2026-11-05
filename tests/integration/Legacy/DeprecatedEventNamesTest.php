<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Legacy;

use ItalyCookieChoices\Legacy\DeprecatedEventNames;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class DeprecatedEventNamesTest extends WPTestCase
{
    public function testItWarnsOnInit(): void
    {
        $this->assertSame(['init' => 'warn'], \iterator_to_array((new DeprecatedEventNames())->getSubscribedEvents()));
    }

    public function testFilteringTheBotsIsDeprecated(): void
    {
        $this->setExpectedDeprecated('icc_bots');
        \add_filter('icc_bots', static fn(array $bots): array => [...$bots, 'mybot']);

        (new DeprecatedEventNames())->warn();
    }

    public function testNothingIsDeprecatedWithoutFilters(): void
    {
        \remove_all_filters('icc_bots');
        $warnings = 0;
        \add_action('deprecated_hook_run', static function () use (&$warnings): void {
            $warnings++;
        });

        (new DeprecatedEventNames())->warn();

        $this->assertSame(0, $warnings);
    }
}
