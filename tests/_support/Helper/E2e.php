<?php

declare(strict_types=1);

namespace Helper;

use lucatume\WPBrowser\Module\WPDb;
use lucatume\WPBrowser\Utils\Serializer;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class E2e extends \Codeception\Module
{
    /**
     * Writes an option in one query, also when a request left by the previous test writes it at the same time:
     * the delete and the insert of WPDb::haveOptionInDatabase() can fail with a duplicate key (#327).
     */
    public function haveOptionInDatabaseAtOnce(string $name, mixed $value): void
    {
        $database = $this->getModule(WPDb::class);
        \assert($database instanceof WPDb);
        $table = $database->grabPrefixedTableNameFor('options');
        $statement = $database->_getDbh()->prepare(
            "INSERT INTO {$table} (option_name, option_value, autoload) VALUES (?, ?, 'yes')"
            . ' ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)'
        );
        $statement->execute([$name, Serializer::maybeSerialize($value)]);
    }
}
