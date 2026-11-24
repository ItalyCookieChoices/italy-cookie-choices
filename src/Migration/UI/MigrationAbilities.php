<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\UI;

use ItalyCookieChoices\Migration\Application\Migrate;
use ItalyCookieChoices\Migration\Application\MigrationSources;
use ItalyCookieChoices\Migration\Domain\MigrationSource;
use ItalyCookieChoices\Settings\UI\Abilities;
use ItalyCookieChoices\WordPress\Hook;
use ItalyStrap\Event\SubscriberInterface;

/**
 * The abilities of the migration from another consent plugin:
 *
 * * italy-cookie-choices/list-migration-sources lists the plugins to import, with what the import changes;
 * * italy-cookie-choices/import-settings imports the settings of one of them.
 */
final readonly class MigrationAbilities implements SubscriberInterface
{
    private const SOURCE = 'source';

    public function __construct(
        private Abilities $abilities,
        private MigrationSources $sources,
        private Migrate $migrate,
        private ImportSummary $summary,
        private DismissedSources $dismissed,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield Hook::ABILITIES_API_INIT => 'register';
    }

    public function register(): void
    {
        $id = ['type' => 'string', 'pattern' => '^[a-z0-9_-]+$'];
        $result = [
            'summary' => ['type' => 'array', 'items' => ['type' => 'string']],
            'notes' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'What to check by hand after the import.',
            ],
        ];

        $this->abilities->register('list-migration-sources', [
            'label' => \__('List the consent plugins to import', 'italy-cookie-choices'),
            'description' => \implode(' ', [
                \__('Lists the other consent plugins whose settings are in the database.', 'italy-cookie-choices'),
                \__('Each one has what its import changes: a preview, nothing is saved.', 'italy-cookie-choices'),
            ]),
            'output_schema' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => ['id' => $id, 'name' => ['type' => 'string'], ...$result],
                ],
            ],
            'execute_callback' => $this->sources(...),
        ], ['readonly' => true]);

        $this->abilities->register('import-settings', [
            'label' => \__('Import the settings of a consent plugin', 'italy-cookie-choices'),
            'description' => \implode(' ', [
                \__(
                    'Imports the settings of another consent plugin, by its id from list-migration-sources.',
                    'italy-cookie-choices'
                ),
                \__(
                    'The texts replace the saved ones, the vendors, the cookies and the scripts are added.',
                    'italy-cookie-choices'
                ),
            ]),
            'input_schema' => [
                'type' => 'object',
                'properties' => [self::SOURCE => $id],
                'required' => [self::SOURCE],
                'additionalProperties' => false,
            ],
            'output_schema' => ['type' => 'object', 'properties' => $result],
            'execute_callback' => $this->import(...),
        ], ['readonly' => false, 'destructive' => true, 'idempotent' => true]);
    }

    /**
     * @return list<array{id: string, name: string, summary: list<string>, notes: list<string>}>
     */
    private function sources(): array
    {
        return \array_map(
            fn(MigrationSource $source): array => [
                'id' => $source->id(),
                'name' => $source->name(),
                ...$this->summary->toArray($this->migrate->preview($source->id())),
            ],
            $this->sources->found()
        );
    }

    /**
     * @param array{source: string} $input
     * @return array{summary: list<string>, notes: list<string>}|\WP_Error
     */
    private function import(array $input): array|\WP_Error
    {
        try {
            $imported = $this->migrate->import($input[self::SOURCE]);
        } catch (\InvalidArgumentException $exception) {
            return $this->abilities->invalidInput(MigrationEndpoint::NOT_FOUND, $exception);
        }

        $this->dismissed->add($input[self::SOURCE]);

        return $this->summary->toArray($imported);
    }
}
