<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\UI;

/**
 * The sources the admin imported or dismissed: the notice does not ask for them again.
 */
final readonly class DismissedSources
{
    public const OPTION = 'italy_cookie_choices_migration_dismissed';

    public function add(string $source): void
    {
        \update_option(self::OPTION, \array_values(\array_unique([...$this->all(), $source])), false);
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        $sources = \get_option(self::OPTION);

        return \array_values(\array_filter(\is_array($sources) ? $sources : [], \is_string(...)));
    }
}
