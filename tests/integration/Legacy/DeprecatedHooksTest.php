<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Legacy;

use ItalyCookieChoices\Legacy\DeprecatedHooks;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class DeprecatedHooksTest extends WPTestCase
{
    public function testItWarnsOnInit(): void
    {
        $this->assertSame(['init' => 'warn'], \iterator_to_array((new DeprecatedHooks())->getSubscribedEvents()));
    }

    public function testFilteringTheBotsIsDeprecated(): void
    {
        $this->setExpectedDeprecated('icc_bots');
        \add_filter('icc_bots', static fn(array $bots): array => [...$bots, 'mybot']);

        (new DeprecatedHooks())->warn();
    }

    public function testNothingIsDeprecatedWithoutFilters(): void
    {
        \remove_all_filters('icc_bots');
        $warnings = 0;
        \add_action('deprecated_hook_run', static function () use (&$warnings): void {
            $warnings++;
        });

        (new DeprecatedHooks())->warn();

        $this->assertSame(0, $warnings);
    }
}
