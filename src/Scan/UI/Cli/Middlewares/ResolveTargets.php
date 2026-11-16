<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI\Cli\Middlewares;

use ItalyCookieChoices\Console\IO\IOInterface;
use ItalyCookieChoices\Console\Pipeline\Message;
use ItalyCookieChoices\Scan\Application\ScanTargets;
use ItalyCookieChoices\Scan\Domain\Selection;
use ItalyCookieChoices\Scan\UI\Cli\ScanExitCode;
use ItalyStrap\Pipeline\HandlerInterface;
use ItalyStrap\Pipeline\MiddlewareInterface;

/**
 * The pages of the selection: --home, --post-type, --category and --post, the lists separated by commas.
 */
final readonly class ResolveTargets implements MiddlewareInterface
{
    public const IDS = 'scan_ids';

    public function __construct(
        private ScanTargets $targets,
    ) {
    }

    public function process(object $message, HandlerInterface $handler): mixed
    {
        \assert($message instanceof Message);
        $io = $message->io();
        $targets = $this->targets->of(new Selection(
            $io->flag('home'),
            $this->list($io, 'post-type'),
            \array_map(\intval(...), $this->list($io, 'category')),
            \array_map(\intval(...), $this->list($io, 'post')),
        ));

        if ($targets->targets() === []) {
            return ScanExitCode::NothingToScan;
        }

        if ($targets->isTruncated()) {
            $io->line(\sprintf(
                /* translators: %d: the number of pages. */
                \__('The selection has more pages than the limit, the first %d are scanned.', 'italy-cookie-choices'),
                \count($targets->targets())
            ));
        }

        $message->set(self::IDS, \array_column($targets->targets(), 'id'));

        return $handler->handle($message);
    }

    /**
     * @return list<string>
     */
    private function list(IOInterface $io, string $option): array
    {
        return \array_values(\array_filter(
            \array_map(\trim(...), \explode(',', $io->option($option) ?? '')),
            static fn(string $value): bool => $value !== ''
        ));
    }
}
