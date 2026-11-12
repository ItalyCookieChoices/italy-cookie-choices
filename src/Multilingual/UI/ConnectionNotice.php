<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Multilingual\UI;

use ItalyCookieChoices\Multilingual\Application\ConnectedSites;
use ItalyCookieChoices\Multilingual\Application\SiteTexts;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyStrap\Event\SubscriberInterface;

/**
 * When MultilingualPress creates a site or changes its connections, the connected sites without their own
 * texts ask the admin to fill them from a connected site. Nothing is filled without the admin.
 */
final readonly class ConnectionNotice implements SubscriberInterface
{
    public const OPTION = 'italy_cookie_choices_connection_notice';
    public const ACTION = 'italy_cookie_choices_ignore_connection';

    /**
     * The actions of MultilingualPress, with the id of the site.
     */
    private const NEW_SITE = 'multilingualpress.define_initial_site_settings';
    private const UPDATED_SITE = 'multilingualpress.update_site_settings';
    private const SETTINGS_TAB = 'languages';

    public function __construct(
        private SiteTexts $texts,
        private ConnectedSites $sites,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        $remember = [self::CALLBACK => 'remember', self::ACCEPTED_ARGS => 1];

        yield self::NEW_SITE => $remember;
        yield self::UPDATED_SITE => $remember;
        yield 'admin_notices' => 'show';
        yield 'admin_post_' . self::ACTION => 'ignore';
    }

    public function remember(mixed $siteId): void
    {
        if (!\is_numeric($siteId)) {
            return;
        }

        foreach ([(int) $siteId, ...$this->sites->of((int) $siteId)] as $site) {
            if ($this->sites->of($site) === [] || $this->texts->hasOwnTexts($site)) {
                continue;
            }

            \update_blog_option($site, self::OPTION, '1');
        }
    }

    public function show(): void
    {
        if (\get_option(self::OPTION) !== '1' || !\current_user_can(SettingsPage::CAPABILITY)) {
            return;
        }

        if ($this->texts->hasOwnTexts(\get_current_blog_id())) {
            \delete_option(self::OPTION);
            return;
        }

        \printf(
            '<div class="notice notice-info"><p>%s</p><p><a class="button button-primary" href="%s">%s</a> '
            . '<a class="button" href="%s">%s</a></p></div>',
            \esc_html__(
                'This site is connected to other languages: fill its banner texts from a connected site.',
                'italy-cookie-choices'
            ),
            \esc_url(\add_query_arg('tab', self::SETTINGS_TAB, \admin_url('admin.php?page=' . SettingsPage::SLUG))),
            \esc_html__('Fill the texts', 'italy-cookie-choices'),
            \esc_url(\wp_nonce_url(\admin_url('admin-post.php?action=' . self::ACTION), self::ACTION)),
            \esc_html__('Ignore', 'italy-cookie-choices')
        );
    }

    public function ignore(): void
    {
        if (!\current_user_can(SettingsPage::CAPABILITY)) {
            \wp_die(\esc_html__('You cannot change the settings.', 'italy-cookie-choices'), '', ['response' => 403]);
        }

        \check_admin_referer(self::ACTION);
        \delete_option(self::OPTION);

        $referer = \wp_get_referer();
        \wp_safe_redirect(\is_string($referer) ? $referer : \admin_url());
        exit;
    }
}
