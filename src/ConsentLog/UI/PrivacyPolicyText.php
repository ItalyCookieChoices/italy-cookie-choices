<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\ConsentLog\Application\LogSettings;
use ItalyCookieChoices\Scan\Application\AssistantUsage;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The suggested text for the privacy policy, in Settings > Privacy > Policy Guide.
 */
final readonly class PrivacyPolicyText implements SubscriberInterface
{
    public function __construct(
        private LogSettings $settings,
        private AssistantUsage $assistant,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::ADMIN_INIT => 'add';
    }

    public function add(): void
    {
        \wp_add_privacy_policy_content('Cookie Choices', $this->text());
    }

    public function text(): string
    {
        $paragraphs = [
            \__(
                'This site asks your consent before it uses cookies that are not necessary. '
                . 'The cookies are grouped in categories: necessary, preferences, statistics and marketing. '
                . 'The content of third parties, like videos and maps, is loaded only after you accept its category.',
                'italy-cookie-choices'
            ),
            \__(
                'Your choice is stored for six months in a cookie named icc_consent, '
                . 'with the accepted categories, the version of the cookie policy and the date. '
                . 'You can change or withdraw your consent at any time from the cookie preferences.',
                'italy-cookie-choices'
            ),
        ];

        if ($this->settings->isEnabled()) {
            $paragraphs[] = \sprintf(
                /* translators: %d: how many months the consent log keeps a record. */
                \__(
                    'To demonstrate your consent, the site keeps a log of your choices for %d months: '
                    . 'a random id created by your browser, the accepted categories, the version of the cookie policy '
                    . 'and the date. The log does not contain your IP address or any other data that identifies you.',
                    'italy-cookie-choices'
                ),
                $this->settings->retention()->months()
            );
        }

        if ($this->assistant->wasUsed()) {
            $paragraphs[] = \__(
                'To describe its cookies and third parties, the site owner used an AI service with the AI Client '
                . 'of WordPress. Only the names of the cookies and of the third-party sites were sent to it, '
                . 'never the data of the visitors or of their consents.',
                'italy-cookie-choices'
            );
        }

        return \implode('', \array_map(
            static fn(string $paragraph): string => '<p>' . \esc_html($paragraph) . '</p>',
            $paragraphs
        ));
    }
}
