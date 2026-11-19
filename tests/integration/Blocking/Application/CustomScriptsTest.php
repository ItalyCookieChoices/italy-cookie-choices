<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Blocking\Application;

use ItalyCookieChoices\Blocking\Application\CustomScripts;
use ItalyCookieChoices\Blocking\Domain\CustomScript;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use ItalyCookieChoices\Tests\Support\LogsIn;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class CustomScriptsTest extends WPTestCase
{
    use BuildsContainer;
    use LogsIn;

    public function setUp(): void
    {
        parent::setUp();
        // Only the users with unfiltered_html save the custom scripts.
        $this->logInAs('administrator');
    }

    public function testItListsTheSavedScriptsOfAPositionInTheirOrder(): void
    {
        \update_option(Schema::OPTION, [
            'active' => '1',
            'custom_scripts' => [
                ['name' => 'Chat', 'category' => 'preferences', 'position' => 'body', 'code' => '<script>a</script>'],
                ['name' => 'Tag', 'category' => 'statistics', 'position' => 'head', 'code' => '<script>b</script>'],
                ['name' => 'Ads', 'category' => 'marketing', 'position' => 'body', 'code' => '<script>c</script>'],
                ['name' => 'Broken', 'category' => 'marketing', 'position' => 'body', 'code' => ''],
            ],
        ]);

        $scripts = $this->service(CustomScripts::class);

        $this->assertSame(
            ['Chat', 'Ads'],
            \array_map(static fn(CustomScript $script): string => $script->name(), $scripts->in(CustomScript::BODY))
        );
        $this->assertSame(
            ['Tag'],
            \array_map(static fn(CustomScript $script): string => $script->name(), $scripts->in(CustomScript::HEAD))
        );
    }

    public function testThereAreNoScriptsByDefault(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertSame([], $this->service(CustomScripts::class)->in(CustomScript::BODY));
    }
}
