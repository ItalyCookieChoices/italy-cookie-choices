<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Settings\UI;

use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\UI\StringRegistration;
use ItalyCookieChoices\Tests\Support\BuildsContainer;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class StringRegistrationTest extends WPTestCase
{
    use BuildsContainer;

    /**
     * @var list<array{string, string, string}>
     */
    private array $registered = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->registered = [];
        \add_action(
            'wpml_register_single_string',
            function (string $context, string $name, string $value): void {
                $this->registered[] = [$context, $name, $value];
            },
            10,
            3
        );
    }

    public function testItRegistersOnEveryAdminRequest(): void
    {
        $this->assertSame(
            ['admin_init' => 'register'],
            \iterator_to_array($this->service(StringRegistration::class)->getSubscribedEvents())
        );
    }

    public function testTheSavedTextsAreRegistered(): void
    {
        \update_option(Schema::OPTION, [
            'text' => 'Text',
            'url' => 'https://example.com/policy/',
            'slug' => 'policy',
            'anchor_text' => 'Anchor',
            'button_text' => '',
            'content_message_text' => 'Message',
            'content_message_button_text' => 'Message button',
            'categories' => [
                'statistics' => ['label' => 'Analytics', 'description' => 'Anonymous visits.'],
                'marketing' => ['description' => 'Ads.'],
            ],
        ]);

        $this->service(StringRegistration::class)->register();

        $this->assertSame(
            [
                ['Italy Cookie Choices', 'Banner text', 'Text'],
                ['Italy Cookie Choices', 'Banner url', 'https://example.com/policy/'],
                ['Italy Cookie Choices', 'Banner anchor text', 'Anchor'],
                ['Italy Cookie Choices', 'Content message text', 'Message'],
                ['Italy Cookie Choices', 'Content message button text', 'Message button'],
                ['Italy Cookie Choices', 'Category statistics label', 'Analytics'],
                ['Italy Cookie Choices', 'Category statistics description', 'Anonymous visits.'],
                ['Italy Cookie Choices', 'Category marketing description', 'Ads.'],
            ],
            $this->registered
        );
    }
}
