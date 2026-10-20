<?php

/**
 * WPML like stub, it records the register_string() calls.
 * Loaded only by the tests that need it, the function cannot be undefined.
 */

declare(strict_types=1);

if (!\function_exists('icl_register_string')) {
    function icl_register_string(string $context, string $name, string $value): void
    {
        $GLOBALS['icc_registered_strings'][] = [$context, $name, $value];
    }
}
