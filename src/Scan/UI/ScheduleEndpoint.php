<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Scan\Application\ScanSchedule;
use ItalyCookieChoices\Scan\Domain\Frequency;
use ItalyCookieChoices\Scan\Domain\Schedule;
use ItalyCookieChoices\Scan\Domain\Selection;
use ItalyCookieChoices\Settings\UI\ImportEndpoint;
use ItalyCookieChoices\Settings\UI\SettingsPage;
use ItalyStrap\Event\SubscriberInterface;

/**
 * POST /wp-json/italy-cookie-choices/v1/scan/schedule saves the schedule of the scan and its cron event,
 * for the administrators.
 */
final readonly class ScheduleEndpoint implements SubscriberInterface
{
    public const ROUTE = '/scan/schedule';

    public function __construct(
        private ScanSchedule $schedule,
        private ScanCron $cron,
        private ScheduleData $data,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield 'rest_api_init' => 'register';
    }

    public function register(): void
    {
        $ids = ['type' => 'array', 'items' => ['type' => 'integer', 'minimum' => 1], 'default' => []];

        \register_rest_route(ImportEndpoint::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => $this->save(...),
            'permission_callback' => static fn(): bool => \current_user_can(SettingsPage::CAPABILITY),
            'args' => [
                'frequency' => [
                    'type' => 'string',
                    'enum' => \array_column(Frequency::cases(), 'value'),
                    'required' => true,
                ],
                'home' => ['type' => 'boolean', 'default' => false],
                'postTypes' => ['type' => 'array', 'items' => ['type' => 'string'], 'default' => []],
                'categories' => $ids,
                'posts' => $ids,
                'email' => ['type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    public function save(\WP_REST_Request $request): \WP_REST_Response
    {
        $frequency = $request->get_param('frequency');
        $schedule = new Schedule(
            Frequency::tryFrom(\is_string($frequency) ? $frequency : '') ?? Frequency::Off,
            new Selection(
                (bool) $request->get_param('home'),
                $this->strings($request->get_param('postTypes')),
                $this->integers($request->get_param('categories')),
                $this->integers($request->get_param('posts')),
            ),
            (bool) $request->get_param('email')
        );

        $this->schedule->save($schedule);
        $this->cron->reschedule($schedule->frequency);

        return new \WP_REST_Response($this->data->data());
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $values): array
    {
        return \array_values(\array_filter(\is_array($values) ? $values : [], \is_string(...)));
    }

    /**
     * @return list<int>
     */
    private function integers(mixed $values): array
    {
        return \array_values(\array_filter(\is_array($values) ? $values : [], \is_int(...)));
    }
}
