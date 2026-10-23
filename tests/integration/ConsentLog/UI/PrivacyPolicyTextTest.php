<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\ConsentLog\UI;

use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\ConsentLog\UI\PrivacyPolicyText;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Tests\Support\BuildsOptions;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PrivacyPolicyTextTest extends WPTestCase
{
    use BuildsOptions;

    public function testItAddsTheTextInTheAdmin(): void
    {
        $this->assertSame(['admin_init' => 'add'], \iterator_to_array($this->makeSut()->getSubscribedEvents()));
    }

    public function testTheTextDescribesTheLogAndItsRetention(): void
    {
        \update_option(Schema::OPTION, ['active' => '1', 'consent_log' => '1', 'consent_log_retention' => '24']);

        $text = $this->makeSut()->text();

        $this->assertStringContainsString('icc_consent', $text);
        $this->assertStringContainsString('keeps a log of your choices for 24 months', $text);
        $this->assertStringContainsString('does not contain your IP address', $text);
    }

    public function testTheLogIsNotDescribedWhenItIsOff(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);

        $this->assertStringNotContainsString('log', $this->makeSut()->text());
    }

    public function testTheTextIsInThePolicyGuide(): void
    {
        \update_option(Schema::OPTION, ['active' => '1']);
        require_once \ABSPATH . 'wp-admin/includes/misc.php';
        require_once \ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';
        \set_current_screen('dashboard');
        \remove_all_actions('admin_init');
        \add_action('admin_init', [$this->makeSut(), 'add']);

        \do_action('admin_init');
        \set_current_screen('front');

        $contents = \array_column(\WP_Privacy_Policy_Content::get_suggested_policy_text(), 'plugin_name');
        $this->assertContains('Italy Cookie Choices', $contents);
    }

    private function makeSut(): PrivacyPolicyText
    {
        return new PrivacyPolicyText(new LogSettings($this->options()));
    }
}
