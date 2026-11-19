<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\UI;

use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyStrap\Config\Config;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\View\ViewInterface;

/**
 * Tells the administrators that the settings of another consent plugin can be imported, until they import
 * or dismiss them.
 */
final readonly class MigrationNotice implements SubscriberInterface
{
    public const ACTION = 'italy_cookie_choices_dismiss_migration';

    private const SETTINGS_TAB = 'tools';
    private const TEMPLATE = 'migration-notice';

    public function __construct(
        private MigrationSources $sources,
        private DismissedSources $dismissed,
        private ViewInterface $view,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'admin_notices' => 'show';
        yield 'admin_post_' . self::ACTION => 'dismiss';
    }

    public function show(): void
    {
        if (!\current_user_can(SettingsPage::CAPABILITY)) {
            return;
        }

        $dismissed = $this->dismissed->all();
        $names = \array_map(
            static fn(MigrationSource $source): string => $source->name(),
            \array_values(\array_filter(
                $this->sources->found(),
                static fn(MigrationSource $source): bool => !\in_array($source->id(), $dismissed, true)
            ))
        );
        if ($names === []) {
            return;
        }

        echo $this->view->render( // phpcs:ignore -- Escaped in the template.
            self::TEMPLATE,
            new Config([
                'plugins' => \implode(', ', $names),
                'importUrl' => \add_query_arg(
                    'tab',
                    self::SETTINGS_TAB,
                    \admin_url('admin.php?page=' . SettingsPage::SLUG)
                ),
                'dismissUrl' => \wp_nonce_url(\admin_url('admin-post.php?action=' . self::ACTION), self::ACTION),
            ])
        );
    }

    public function dismiss(): void
    {
        if (!\current_user_can(SettingsPage::CAPABILITY)) {
            \wp_die(\esc_html__('You cannot change the settings.', 'italy-cookie-choices'), '', ['response' => 403]);
        }

        \check_admin_referer(self::ACTION);
        foreach ($this->sources->found() as $source) {
            $this->dismissed->add($source->id());
        }

        $referer = \wp_get_referer();
        \wp_safe_redirect(\is_string($referer) ? $referer : \admin_url());
        exit;
    }
}
