<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Integrations\UI;

use ItalyCookieChoices\Integrations\Application\IntegrationSettings;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The YouTube embeds use the privacy-enhanced youtube-nocookie.com, they are still blocked until the consent.
 */
final readonly class YouTubeNoCookie implements SubscriberInterface
{
    private const HOST = 'www.youtube-nocookie.com';

    public function __construct(
        private IntegrationSettings $settings,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        // Before the blocking, that moves the src.
        yield 'embed_oembed_html' => 'replaceHost';
    }

    public function replaceHost(mixed $html): mixed
    {
        if (!\is_string($html) || !$this->settings->usesYouTubeNoCookie()) {
            return $html;
        }

        $processor = new \WP_HTML_Tag_Processor($html);
        while ($processor->next_tag(['tag_name' => 'IFRAME'])) {
            $source = $processor->get_attribute('src');
            if (!\is_string($source) || !$this->isYouTubeEmbed($source)) {
                continue;
            }

            $processor->set_attribute('src', $this->noCookie($source));
        }

        return $processor->get_updated_html();
    }

    private function isYouTubeEmbed(string $source): bool
    {
        $host = (string) \wp_parse_url($source, \PHP_URL_HOST);
        $path = (string) \wp_parse_url($source, \PHP_URL_PATH);

        return \preg_match('/(^|\.)youtube\.com$/i', $host) === 1 && \str_starts_with($path, '/embed/');
    }

    private function noCookie(string $source): string
    {
        return (string) \preg_replace('#^((?:https?:)?//)[^/]+#i', '${1}' . self::HOST, $source);
    }
}
