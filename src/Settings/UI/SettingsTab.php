<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

/**
 * The tabs of the settings page, with the names the React app reads from the `tab` query argument.
 */
enum SettingsTab: string
{
    private const QUERY_ARG = 'tab';

    case Texts = 'texts';
    case Banner = 'banner';
    case Blocking = 'blocking';
    case Integrations = 'integrations';
    case Languages = 'languages';
    case ConsentLog = 'consent-log';
    case Tools = 'tools';

    /**
     * The address of the tab in the settings page.
     */
    public function url(): string
    {
        return \add_query_arg(
            self::QUERY_ARG,
            $this->value, // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
            \admin_url('admin.php?page=' . SettingsPage::SLUG)
        );
    }
}
