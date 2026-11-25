<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Migration\Infrastructure;

use ItalyCookieChoices\Migration\Application\BuildingMigrationSources;
use ItalyCookieChoices\Migration\Infrastructure\Borlabs\BorlabsSource;
use ItalyCookieChoices\Migration\Infrastructure\Complianz\ComplianzSource;
use ItalyCookieChoices\Migration\Infrastructure\CookieAdmin\CookieAdminSource;
use ItalyCookieChoices\Migration\Infrastructure\CookieNotice\CookieNoticeSource;
use ItalyCookieChoices\Migration\Infrastructure\CookieYes\CookieYesSource;
use ItalyCookieChoices\Migration\Infrastructure\GdprCookieCompliance\GdprCookieComplianceSource;
use ItalyCookieChoices\Migration\Infrastructure\MyAgilePrivacy\MyAgilePrivacySource;
use ItalyCookieChoices\Migration\Infrastructure\RealCookieBanner\RealCookieBannerSource;
use ItalyCookieChoices\Migration\Infrastructure\SureCookie\SureCookieSource;
use ItalyCookieChoices\Migration\Infrastructure\WpConsent\WpConsentSource;
use ItalyStrap\Event\SubscriberInterface;

/**
 * Adds the sources of the plugin, the same way other plugins add theirs.
 */
final readonly class BuiltInSources implements SubscriberInterface
{
    public function __construct(
        private ComplianzSource $complianz,
        private CookieYesSource $cookieYes,
        private CookieNoticeSource $cookieNotice,
        private GdprCookieComplianceSource $gdprCookieCompliance,
        private BorlabsSource $borlabs,
        private RealCookieBannerSource $realCookieBanner,
        private CookieAdminSource $cookieAdmin,
        private WpConsentSource $wpConsent,
        private MyAgilePrivacySource $myAgilePrivacy,
        private SureCookieSource $sureCookie,
    ) {
    }

    public function getSubscribedEvents(): iterable
    {
        yield BuildingMigrationSources::class => 'add';
    }

    public function add(BuildingMigrationSources $event): void
    {
        $event->addSource($this->complianz);
        $event->addSource($this->cookieYes);
        $event->addSource($this->cookieNotice);
        $event->addSource($this->gdprCookieCompliance);
        $event->addSource($this->borlabs);
        $event->addSource($this->realCookieBanner);
        $event->addSource($this->cookieAdmin);
        $event->addSource($this->wpConsent);
        $event->addSource($this->myAgilePrivacy);
        $event->addSource($this->sureCookie);
    }
}
