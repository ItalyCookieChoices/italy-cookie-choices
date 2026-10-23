<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyStrap\Event\SubscriberInterface;

/**
 * The links to the settings and the documentation in the plugins list.
 *
 * The 2.x versions hooked them to the basename of define-constants.php, so they never showed up.
 */
final readonly class PluginActionLinks implements SubscriberInterface
{
    /**
     * @param string $basename The basename of the main plugin file, like italy-cookie-choices/italy-cookie-choices.php.
     */
    public function __construct(
        private SettingsPage $page,
        private string $basename,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'plugin_action_links_' . $this->basename => 'links';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function links(mixed $links = []): array
    {
        return [
            'settings' => \sprintf(
                '<a href="%s">%s</a>',
                \esc_url($this->page->url()),
                \esc_html__('Settings', 'italy-cookie-choices')
            ),
            'documentation' => \sprintf(
                '<a href="%s" target="_blank" rel="noopener">%s</a>',
                \esc_url(SettingsPage::DOCUMENTATION),
                \esc_html__('Documentation', 'italy-cookie-choices')
            ),
            ...(\is_array($links) ? $links : []),
        ];
    }
}
