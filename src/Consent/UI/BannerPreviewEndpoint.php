<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\UI;

use ItalyCookieChoices\Settings\Sanitizer;
use ItalyCookieChoices\Settings\Schema;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/preview, the banner of the settings not saved yet.
 *
 * It renders the same template with the same styles as the front end, in a page without scripts
 * that the settings page shows in a sandboxed frame.
 */
final readonly class BannerPreviewEndpoint implements SubscriberInterface
{
    public const NAMESPACE = 'italy-cookie-choices/v1';
    public const ROUTE = '/preview';

    private const DIALOGS = ['banner' => 'icc-banner', 'preferences' => 'icc-preferences'];

    public function __construct(
        private Banner $banner,
        private Sanitizer $sanitizer,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        \register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->preview(...),
            'permission_callback' => static fn(): bool => \current_user_can(SettingsPage::CAPABILITY),
            'args' => [
                'settings' => ['type' => 'object', 'required' => true],
                'dialog' => ['type' => 'string', 'enum' => \array_keys(self::DIALOGS), 'default' => 'banner'],
            ],
        ]);
    }

    public function preview(\WP_REST_Request $request): \WP_REST_Response
    {
        $dialog = $request->get_param('dialog');

        return new \WP_REST_Response([
            'html' => $this->document(
                $request->get_param('settings'),
                self::DIALOGS[\is_string($dialog) ? $dialog : ''] ?? self::DIALOGS['banner']
            ),
        ]);
    }

    /**
     * The page of the preview, rendered with the settings instead of the saved ones.
     */
    public function document(mixed $settings, string $dialogId): string
    {
        $settings = ($this->sanitizer)($settings);
        $replace = static fn(): array => $settings;
        \add_filter('pre_option_' . Schema::OPTION, $replace);

        try {
            return $this->page($this->shown($this->banner->html(), $dialogId));
        } finally {
            \remove_filter('pre_option_' . Schema::OPTION, $replace);
        }
    }

    private function page(string $markup): string
    {
        $stylesheet = $this->banner->stylesheet();

        return \sprintf(
            '<!doctype html><html lang="%s"><head><meta charset="utf-8">%s<style>%s</style></head>'
            . '<body>%s</body></html>',
            \esc_attr(\get_bloginfo('language')),
            $stylesheet === null ? '' : \sprintf('<link rel="stylesheet" href="%s">', \esc_url($stylesheet)),
            $this->banner->inlineStyle(),
            $markup
        );
    }

    /**
     * The markup with the dialog shown, the script that shows it does not run in the preview.
     */
    private function shown(string $markup, string $dialogId): string
    {
        $processor = new \WP_HTML_Tag_Processor($markup);
        while ($processor->next_tag()) {
            if ($processor->get_attribute('id') !== $dialogId) {
                continue;
            }

            $processor->remove_attribute('hidden');
            break;
        }

        return $processor->get_updated_html();
    }
}
