<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Settings\UI;

use ItalyCookieChoices\Settings\NetworkSettings;
use ItalyStrap\Config\Config;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\View\ViewInterface;

/**
 * Network Admin > Settings > Cookie Choices, on a multisite with the plugin active on the network:
 * the switch that gives the settings of the main site to every site.
 */
final readonly class NetworkSettingsPage implements SubscriberInterface
{
    public const SLUG = 'italy-cookie-choices';
    public const ACTION = 'italy_cookie_choices_network';

    private const CAPABILITY = 'manage_network_options';
    private const PARENT = 'settings.php';
    private const TEMPLATE = 'network-settings';
    private const SHARED = 'shared';

    public function __construct(
        private NetworkSettings $network,
        private ViewInterface $view,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'network_admin_menu' => 'addPage';
        yield 'network_admin_edit_' . self::ACTION => 'save';
    }

    public function addPage(): void
    {
        \add_submenu_page(
            self::PARENT,
            \__('Cookie Choices', 'italy-cookie-choices'),
            \__('Cookie Choices', 'italy-cookie-choices'),
            self::CAPABILITY,
            self::SLUG,
            $this->render(...)
        );
    }

    public function render(): void
    {
        echo $this->view->render( // phpcs:ignore -- Escaped in the template.
            self::TEMPLATE,
            new Config([
                'title' => \__('Cookie Choices', 'italy-cookie-choices'),
                'action' => \network_admin_url('edit.php?action=' . self::ACTION),
                'nonceAction' => self::ACTION,
                'shared' => $this->network->sharesTheMainSite(),
                'updated' => ($_GET['updated'] ?? '') === 'true', // phpcs:ignore -- Only shows the notice.
            ])
        );
    }

    public function save(): void
    {
        if (!\current_user_can(self::CAPABILITY)) {
            \wp_die(
                \esc_html__('You cannot change the network settings.', 'italy-cookie-choices'),
                '',
                ['response' => 403]
            );
        }

        \check_admin_referer(self::ACTION);
        $this->network->share(\array_key_exists(self::SHARED, $_POST));

        \wp_safe_redirect(\add_query_arg(
            ['page' => self::SLUG, 'updated' => 'true'],
            \network_admin_url(self::PARENT)
        ));
        exit;
    }
}
