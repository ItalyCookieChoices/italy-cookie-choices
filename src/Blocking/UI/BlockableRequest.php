<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Blocking\UI;

use ItalyCookieChoices\Settings\Options;

/**
 * Whether the current request prints a page where the consent script can run the blocked content again.
 *
 * Feeds, the REST API and the admin have no consent script, so their content is never blocked.
 */
final readonly class BlockableRequest
{
    public function __construct(
        private Options $options,
    ) {
    }

    public function isBlockable(): bool
    {
        if (!$this->options->isActive() || \is_admin() || \is_feed()) {
            return false;
        }

        return !\wp_is_serving_rest_request() && !\wp_is_json_request();
    }
}
