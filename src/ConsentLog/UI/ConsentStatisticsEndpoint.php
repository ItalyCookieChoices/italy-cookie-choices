<?php

declare(strict_types=1);

namespace ItalyCookieChoices\ConsentLog\UI;

use ItalyCookieChoices\Consent\UI\ConsentTexts;
use ItalyCookieChoices\ConsentLog\Application\StatisticsFactory;
use ItalyStrap\Event\SubscriberInterface;

/**
 * GET /wp-json/italy-cookie-choices/v1/statistics, read by the Consent log tab of the settings page.
 *
 * Only for the administrators, from the records already stored: nothing new is collected.
 */
final readonly class ConsentStatisticsEndpoint implements SubscriberInterface
{
    public const ROUTE = '/statistics';

    private const CAPABILITY = 'manage_options';

    public function __construct(
        private StatisticsFactory $statistics,
        private ConsentTexts $texts,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        \register_rest_route(ConsentLogEndpoint::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => $this->read(...),
            'permission_callback' => static fn(): bool => \current_user_can(self::CAPABILITY),
        ]);
    }

    private function read(): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'categories' => $this->optionalCategories(),
            'months' => $this->statistics->create()->months(),
        ]);
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private function optionalCategories(): array
    {
        $categories = [];
        foreach ($this->texts->all()['categories'] as $category) {
            if ($category['required']) {
                continue;
            }

            $categories[] = ['id' => $category['id'], 'label' => $category['label']];
        }

        return $categories;
    }
}
