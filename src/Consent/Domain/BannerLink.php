<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Consent\Domain;

/**
 * A link of the banner to a page of the site: the cookie policy, and the legal notice the sites of Germany
 * and Austria show. On its page the banner does not open by itself, so the visitor can read it.
 */
enum BannerLink: string
{
    case Policy = 'policy';
    case LegalNotice = 'legal-notice';

    /**
     * The setting with the page, its id or slug.
     */
    public function pageSetting(): string
    {
        // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
        return match ($this) {
            self::Policy => 'slug',
            self::LegalNotice => 'legal_notice_page',
        };
    }

    /**
     * The setting with the address of the link.
     */
    public function urlSetting(): string
    {
        // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
        return match ($this) {
            self::Policy => 'url',
            self::LegalNotice => 'legal_notice_url',
        };
    }

    /**
     * The setting with the text of the link, the address when it is empty.
     */
    public function textSetting(): string
    {
        // phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts -- An enum case.
        return match ($this) {
            self::Policy => 'anchor_text',
            self::LegalNotice => 'legal_notice_text',
        };
    }
}
